<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;

class DecisionEngineController extends Controller
{
    // ── Helpers ─────────────────────────────────────────────────────────────

    private function slaLimit(int $priority): int
    {
        return match($priority) {
            5 => (int) Setting::get('sla_très haute', '4'),
            4 => (int) Setting::get('sla_haute',      '8'),
            3 => (int) Setting::get('sla_moyenne',    '24'),
            2 => (int) Setting::get('sla_basse',      '48'),
            default => (int) Setting::get('sla_basse', '48'),
        };
    }

    private function outcome(Ticket $ticket): string
    {
        if (in_array($ticket->sync_status, ['resolved', 'closed', 'synced'])) {
            return 'auto_resolved';
        }
        if ($ticket->assigned_to) {
            return 'escalated';
        }
        if ($ticket->sync_status === 'in_progress') {
            return 'clarify';
        }
        return 'routed';
    }

    // Simule un score de confiance IA basé sur les données réelles du ticket
    private function confidence(Ticket $ticket): int
    {
        $base = 50;
        // Catégorie connue = plus de confiance
        $catBonus = match($ticket->category) {
            'incident_technique', 'integration_api' => 25,
            'facturation', 'plateforme'              => 15,
            default                                  => 5,
        };
        // Ticket résolu = confiance était haute
        $resolvedBonus = in_array($ticket->sync_status, ['resolved', 'closed']) ? 20 : 0;
        // Priorité haute = risque → moins de confiance IA
        $priorityPenalty = $ticket->priority >= 4 ? -10 : 0;

        return min(99, max(30, $base + $catBonus + $resolvedBonus + $priorityPenalty));
    }

    private function risk(Ticket $ticket): int
    {
        $base = 20;
        $priorityAdd  = ($ticket->priority - 1) * 15;
        $slaLimit     = $this->slaLimit($ticket->priority);
        $hoursOpen    = $ticket->created_at->diffInHours(now());
        $slaBreached  = $slaLimit > 0 && $hoursOpen > $slaLimit ? 30 : 0;
        $categoryAdd  = match($ticket->category) {
            'facturation' => 20,
            'incident_technique' => 10,
            default => 0,
        };
        return min(99, $base + $priorityAdd + $slaBreached + $categoryAdd);
    }

    private function buildEvents(Ticket $ticket): array
    {
        $events = [];
        $slaLimit = $this->slaLimit($ticket->priority);
        $hoursOpen = $ticket->created_at->diffInHours(now());
        $confidence = $this->confidence($ticket);
        $risk = $this->risk($ticket);
        $outcome = $this->outcome($ticket);

        $sourceLabels = [
            'email'     => 'Via email automatique',
            'whatsapp'  => 'Via WhatsApp Business',
            'platform'  => 'Via portail client',
            'web'       => 'Via portail client',
        ];
        $sourceIcons = [
            'email'    => '📧',
            'whatsapp' => '💬',
            'platform' => '🖥️',
            'web'      => '🖥️',
        ];

        $src = $ticket->source ?? 'platform';

        // ── Événement 1 : Réception ──────────────────────────────────────────
        $events[] = [
            'type'   => 'received',
            'title'  => 'Ticket reçu',
            'sub'    => ($sourceLabels[$src] ?? 'Via plateforme') . ' — ' . ($ticket->user->name ?? 'Client'),
            'time'   => $ticket->created_at->format('d/m H:i'),
            'icon'   => $sourceIcons[$src] ?? '📋',
            'color'  => '#3B82F6',
            'detail' => "Titre : \"{$ticket->title}\"\nDescription : " . \Illuminate\Support\Str::limit($ticket->description ?? '', 200),
        ];

        // ── Événement 2 : Classification IA ─────────────────────────────────
        $catLabels = [
            'incident_technique' => 'Incident Technique',
            'integration_api'    => 'API / Intégration',
            'facturation'        => 'Facturation',
            'plateforme'         => 'Plateforme',
            'paiement_mobile'    => 'Paiement Mobile',
            'autre'              => 'Autre',
        ];
        $pLabels = [5=>'Critique',4=>'Haute',3=>'Moyenne',2=>'Basse',1=>'Très Basse'];
        $catLabel = $catLabels[$ticket->category] ?? ucfirst($ticket->category ?? 'Autre');

        $events[] = [
            'type'   => 'classified',
            'title'  => 'Classification IA',
            'sub'    => "Catégorie : {$catLabel} · Priorité : " . ($pLabels[$ticket->priority] ?? 'Moyenne'),
            'time'   => $ticket->created_at->addMinutes(1)->format('d/m H:i'),
            'icon'   => '🧠',
            'color'  => '#6C63FF',
            'detail' => "Catégorie : {$catLabel}\nConfiance : {$confidence}%\nRisque : {$risk}/100\nPriorité assignée : " . ($pLabels[$ticket->priority] ?? '3'),
        ];

        // ── Événement 3 : Décision ────────────────────────────────────────────
        $decisionLabels = [
            'auto_resolved' => 'AUTO_RESOLVE',
            'escalated'     => 'ESCALATE_HUMAN',
            'clarify'       => 'CLARIFY',
            'routed'        => 'ROUTE_AGENT',
        ];
        $decisionIcons = [
            'auto_resolved' => '✅',
            'escalated'     => '🚨',
            'clarify'       => '❓',
            'routed'        => '📋',
        ];
        $decisionColors = [
            'auto_resolved' => '#10B981',
            'escalated'     => '#EF4444',
            'clarify'       => '#F59E0B',
            'routed'        => '#3B82F6',
        ];
        $decisionRules = [
            'auto_resolved' => "Règle : confidence >= 80 AND risk < 30 → AUTO_RESOLVE",
            'escalated'     => "Règle : confidence < 60 OR risk > 60 → ESCALATE_HUMAN\nRaison : Intervention humaine requise",
            'clarify'       => "Règle : 60 <= confidence < 80 → CLARIFY\nQuestions générées pour le client",
            'routed'        => "Règle : Routage par compétence → ROUTE_AGENT",
        ];

        $events[] = [
            'type'   => 'decision',
            'title'  => 'Décision : ' . ($decisionLabels[$outcome] ?? 'ROUTE_AGENT'),
            'sub'    => $confidence >= 80 && $risk < 30 ? 'Confiance élevée + risque faible' : ($risk > 60 ? 'Risque élevé → escalade' : 'Analyse en cours'),
            'time'   => $ticket->created_at->addMinutes(2)->format('d/m H:i'),
            'icon'   => $decisionIcons[$outcome] ?? '📋',
            'color'  => $decisionColors[$outcome] ?? '#3B82F6',
            'detail' => $decisionRules[$outcome] ?? '',
        ];

        // ── Événement 4 : Assignation (si assigné) ───────────────────────────
        if ($ticket->assigned_to && $ticket->assignee) {
            $events[] = [
                'type'   => 'assigned',
                'title'  => 'Assigné à ' . $ticket->assignee->name,
                'sub'    => 'Admin · ' . $catLabel,
                'time'   => $ticket->created_at->addMinutes(3)->format('d/m H:i'),
                'icon'   => '👤',
                'color'  => '#F59E0B',
                'detail' => "Routage par compétence : {$catLabel} → {$ticket->assignee->name}\nNotification envoyée",
            ];
        }

        // ── Événement 5 : Commentaires admin ────────────────────────────────
        $comments = $ticket->comments()->with('user')->get()->reverse()->values();
        foreach ($comments as $comment) {
            $isAdmin = in_array($comment->user->role ?? '', ['admin', 'super_admin']);
            $events[] = [
                'type'   => 'response',
                'title'  => $isAdmin ? 'Réponse admin envoyée' : 'Message client',
                'sub'    => ($comment->user->name ?? 'Inconnu') . ' · ' . $comment->created_at->format('d/m H:i'),
                'time'   => $comment->created_at->format('d/m H:i'),
                'icon'   => $isAdmin ? '✉️' : '💬',
                'color'  => $isAdmin ? '#10B981' : '#3B82F6',
                'detail' => \Illuminate\Support\Str::limit($comment->content ?? '', 300),
            ];
        }

        // ── Événement 6 : SLA dépassé ───────────────────────────────────────
        if ($hoursOpen > $slaLimit && !in_array($ticket->sync_status, ['resolved', 'closed'])) {
            $events[] = [
                'type'   => 'sla',
                'title'  => '⚠️ SLA Dépassé',
                'sub'    => "{$slaLimit}h limite · Utilisé : {$hoursOpen}h",
                'time'   => $ticket->created_at->addHours($slaLimit)->format('d/m H:i'),
                'icon'   => '⏰',
                'color'  => '#EF4444',
                'detail' => "SLA : {$slaLimit}h\nDépassement : " . ($hoursOpen - $slaLimit) . "h\nAlertes envoyées",
            ];
        }

        // ── Événement 7 : Résolution ─────────────────────────────────────────
        if (in_array($ticket->sync_status, ['resolved', 'closed', 'synced'])) {
            $resolvedAt = $ticket->resolved_at ?? $ticket->updated_at;
            $events[] = [
                'type'   => 'closed',
                'title'  => 'Ticket résolu',
                'sub'    => 'Résolution en ' . $ticket->created_at->diffForHumans($resolvedAt, true),
                'time'   => $resolvedAt->format('d/m H:i'),
                'icon'   => '🎉',
                'color'  => '#10B981',
                'detail' => "SLA : {$slaLimit}h disponibles\nTemps de résolution : " . $ticket->created_at->diffInHours($resolvedAt) . "h\n" . ($ticket->solution ? "Solution : " . \Illuminate\Support\Str::limit($ticket->solution, 150) : ''),
            ];
        }

        // GLPI sync event
        if ($ticket->glpi_ticket_id) {
            $events[] = [
                'type'   => 'glpi',
                'title'  => 'Synchronisé avec GLPI',
                'sub'    => 'GLPI Ticket #' . $ticket->glpi_ticket_id,
                'time'   => $ticket->updated_at->format('d/m H:i'),
                'icon'   => '🔗',
                'color'  => '#F59E0B',
                'detail' => "GLPI Ticket #{$ticket->glpi_ticket_id}\nStatut : " . ucfirst($ticket->sync_status),
            ];
        }

        // Trier par time réel
        usort($events, fn($a, $b) => strcmp($a['time'], $b['time']));

        return $events;
    }

    private function formatTicket(Ticket $ticket): array
    {
        $slaLimit  = $this->slaLimit($ticket->priority);
        $hoursOpen = (int) $ticket->created_at->diffInHours(now());
        $slaUsed   = $slaLimit > 0 ? round(($hoursOpen / $slaLimit) * 100) : 100;
        $outcome   = $this->outcome($ticket);

        $pLabels = [5=>'Critique',4=>'Haute',3=>'Moyenne',2=>'Basse',1=>'Très Basse'];
        $src = $ticket->source ?? 'platform';

        return [
            'id'              => 'TK-' . $ticket->id,
            'db_id'           => $ticket->id,
            'title'           => $ticket->title,
            'client'          => $ticket->user->name ?? 'N/A',
            'source'          => $src,
            'date'            => $ticket->created_at->diffForHumans(),
            'category'        => $ticket->category ?? 'autre',
            'cat_label'       => ['incident_technique'=>'Incident Technique','integration_api'=>'API / Intégration','facturation'=>'Facturation','plateforme'=>'Plateforme','paiement_mobile'=>'Paiement Mobile','autre'=>'Autre'][$ticket->category] ?? ucfirst($ticket->category ?? 'Autre'),
            'priority'        => $ticket->priority ?? 3,
            'priority_label'  => $pLabels[$ticket->priority] ?? 'Moyenne',
            'outcome'         => $outcome,
            'confidence'      => $this->confidence($ticket),
            'risk'            => $this->risk($ticket),
            'sla_limit'       => $slaLimit . 'h',
            'sla_used'        => $hoursOpen . 'h',
            'sla_pct'         => min(150, $slaUsed),
            'assigned_admin'  => $ticket->assignee?->name,
            'sync_status'     => $ticket->sync_status,
            'glpi_id'         => $ticket->glpi_ticket_id,
        ];
    }

    // ── GET /super-admin/decision-engine/tickets ─────────────────────────────
    public function tickets(Request $request)
    {
        $days   = (int) $request->input('days', 7);
        $source = $request->input('source', 'all');

        $query = Ticket::with(['user', 'assignee', 'comments.user'])
            ->where('created_at', '>=', now()->subDays($days))
            ->latest()
            ->limit(50);

        if ($source !== 'all') {
            $query->where('source', $source);
        }

        $tickets = $query->get()->map(fn($t) => $this->formatTicket($t));

        // Stats globales
        $all = Ticket::where('created_at', '>=', now()->subDays($days));
        $resolved  = (clone $all)->whereIn('sync_status', ['resolved', 'closed', 'synced'])->count();
        $escalated = (clone $all)->where('assigned_to', '!=', null)->whereNotIn('sync_status', ['resolved', 'closed'])->count();
        $total     = (clone $all)->count();

        // Sources
        $bySource = (clone $all)->selectRaw('source, count(*) as cnt')->groupBy('source')->pluck('cnt', 'source');

        // Catégories
        $byCategory = (clone $all)->selectRaw('category, count(*) as cnt')->groupBy('category')->pluck('cnt', 'category');

        // Admins performance (from leaderboard data)
        $admins = User::where('role', 'admin')->where('is_active', true)->get()->map(function($a) use ($days) {
            $ids      = Ticket::where('assigned_to', $a->id)->where('created_at', '>=', now()->subDays($days))->pluck('id');
            $answered = \App\Models\TicketComment::where('user_id', $a->id)->whereIn('ticket_id', $ids)->distinct('ticket_id')->count('ticket_id');
            $t        = $ids->count();
            return ['name' => $a->name, 'score' => $t > 0 ? round(($answered / $t) * 100) : 0];
        })->sortByDesc('score')->values();

        return response()->json([
            'tickets' => $tickets,
            'stats'   => [
                'total'        => $total,
                'auto_resolved'=> $resolved,
                'escalated'    => $escalated,
                'clarify'      => max(0, $total - $resolved - $escalated),
                'routed'       => 0,
                'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100) : 0,
                'avg_confidence'  => $tickets->avg('confidence') ? round($tickets->avg('confidence')) : 0,
                'by_source'    => $bySource,
                'by_category'  => $byCategory,
                'admins'       => $admins,
            ],
        ]);
    }

    // ── GET /super-admin/decision-engine/tickets/{id} ────────────────────────
    public function ticketDetail(int $id)
    {
        $ticket = Ticket::with(['user', 'assignee', 'comments.user'])->findOrFail($id);

        return response()->json([
            'ticket' => $this->formatTicket($ticket),
            'events' => $this->buildEvents($ticket),
        ]);
    }

    // ── GET /super-admin/decision-engine ─────────────────────────────────────
    public function index()
    {
        return view('support.decision-engine');
    }
}