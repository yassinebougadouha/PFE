<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        if ($ticket->sync_status === 'escalated' || $ticket->assigned_to) {
            return 'escalated';
        }

        $confidence = $this->confidence($ticket);
        $risk = $this->risk($ticket);

        return $this->decisionFromScores($confidence, $risk);
    }

    // Simule un score de confiance IA basé sur les données réelles du ticket
    private function confidence(Ticket $ticket): int
    {
        $base = 50;
        $priority = (int) ($ticket->priority ?: 3);
        // Catégorie connue = plus de confiance
        $catBonus = match($ticket->category) {
            'incident_technique', 'integration_api' => 25,
            'facturation', 'plateforme'              => 15,
            default                                  => 5,
        };
        // Ticket résolu = confiance était haute
        $resolvedBonus = in_array($ticket->sync_status, ['resolved', 'closed']) ? 20 : 0;
        // Priorité haute = risque → moins de confiance IA
        $priorityPenalty = $priority >= 4 ? -10 : 0;

        return min(99, max(30, $base + $catBonus + $resolvedBonus + $priorityPenalty));
    }

    private function risk(Ticket $ticket): int
    {
        $priority = (int) ($ticket->priority ?: 3);
        $base = 20;
        $priorityAdd  = ($priority - 1) * 15;
        $slaLimit     = $this->slaLimit($priority);
        $hoursOpen    = $ticket->created_at->diffInHours(now());
        $slaBreached  = $slaLimit > 0 && $hoursOpen > $slaLimit ? 30 : 0;
        $categoryAdd  = match($ticket->category) {
            'facturation' => 20,
            'incident_technique' => 10,
            default => 0,
        };
        return min(99, $base + $priorityAdd + $slaBreached + $categoryAdd);
    }

    private function intentCategory(?string $category, ?string $text = null): string
    {
        $category = $category ?: 'autre';
        $haystack = Str::lower(($category ?? '') . ' ' . ($text ?? ''));

        if (str_contains($haystack, 'factur') || str_contains($haystack, 'paiement')) {
            return 'billing';
        }
        if (str_contains($haystack, 'api') || str_contains($haystack, 'integration')) {
            return 'integration';
        }
        if (str_contains($haystack, 'incident') || str_contains($haystack, 'bug') || str_contains($haystack, 'erreur')) {
            return 'technical_issue';
        }
        if (str_contains($haystack, 'login') || str_contains($haystack, 'compte') || str_contains($haystack, 'access')) {
            return 'access';
        }

        return $category === 'autre' ? 'general_support' : $category;
    }

    private function priorityFromRisk(int $risk): int
    {
        return match (true) {
            $risk >= 85 => 5,
            $risk >= 65 => 4,
            $risk >= 40 => 3,
            $risk >= 20 => 2,
            default => 1,
        };
    }

    private function decisionFromScores(int $confidence, int $risk): string
    {
        if ($confidence >= 80 && $risk < 30) {
            return 'auto_resolved';
        }
        if ($confidence < 60 || $risk > 60) {
            return 'escalated';
        }
        if ($confidence < 80) {
            return 'clarify';
        }
        return 'routed';
    }

    private function confidenceLevel(int $confidence): string
    {
        return match (true) {
            $confidence >= 80 => 'high',
            $confidence >= 60 => 'medium',
            default => 'low',
        };
    }

    private function riskLevel(int $risk): string
    {
        return match (true) {
            $risk >= 80 => 'critical',
            $risk >= 60 => 'high',
            $risk >= 35 => 'medium',
            default => 'low',
        };
    }

    private function matchedRule(string $outcome): string
    {
        return [
            'auto_resolved' => 'confidence >= 80 AND risk < 30',
            'escalated' => 'confidence < 60 OR risk > 60',
            'clarify' => '60 <= confidence < 80',
            'routed' => 'route by priority/category',
        ][$outcome] ?? 'default route';
    }

    private function decisionPayload(array $data): array
    {
        $confidence = (int) ($data['confidence'] ?? 65);
        $risk = (int) ($data['risk'] ?? 35);
        $outcome = $data['outcome'] ?? $this->decisionFromScores($confidence, $risk);
        $priority = (int) ($data['priority'] ?? $this->priorityFromRisk($risk));
        $intent = $data['intent_category'] ?? $this->intentCategory($data['category'] ?? null, $data['text'] ?? null);
        $rule = $this->matchedRule($outcome);

        return [
            'id' => $data['id'] ?? ('local-' . now()->timestamp),
            'ticket_id' => $data['ticket_id'] ?? null,
            'outcome' => $outcome,
            'decision_outcome' => $outcome,
            'intent_category' => $intent,
            'confidence' => $confidence / 100,
            'confidence_score' => $confidence / 100,
            'confidence_level' => $this->confidenceLevel($confidence),
            'risk_score' => $risk / 100,
            'risk_level' => $this->riskLevel($risk),
            'suggested_priority' => $priority,
            'matched_rules' => ['rules' => [$rule]],
            'reasoning' => $data['reasoning'] ?? "Analyse locale: confiance {$confidence}%, risque {$risk}/100. Regle appliquee: {$rule}.",
            'response_suggestions' => $data['response_suggestions'] ?? [
                'Verifier les informations du ticket et confirmer le contexte avec le client.',
                'Traiter selon la priorite suggeree et documenter la prochaine action.',
                'Escalader si un blocage technique ou SLA est confirme.',
            ],
            'escalation_summary' => $outcome === 'escalated'
                ? ($data['escalation_summary'] ?? 'Risque eleve ou confiance insuffisante: intervention humaine recommandee.')
                : null,
            'created_at' => $data['created_at'] ?? now()->toIso8601String(),
        ];
    }

    private function buildEvents(Ticket $ticket): array
    {
        $events = [];
        $priority = (int) ($ticket->priority ?: 3);
        $slaLimit = $this->slaLimit($priority);
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
        $priority  = (int) ($ticket->priority ?: 3);
        $slaLimit  = $this->slaLimit($priority);
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
            'priority'        => $priority,
            'priority_label'  => $pLabels[$priority] ?? 'Moyenne',
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

        $statsQuery = Ticket::where('created_at', '>=', now()->subDays($days));
        if ($source !== 'all') {
            $statsQuery->where('source', $source);
        }

        $allTickets = $statsQuery->get();
        $outcomes = $allTickets->map(fn($t) => $this->formatTicket($t)['outcome'])->countBy();
        $autoResolved = (int) ($outcomes['auto_resolved'] ?? 0);
        $escalated = (int) ($outcomes['escalated'] ?? 0);
        $clarify = (int) ($outcomes['clarify'] ?? 0);
        $routed = (int) ($outcomes['routed'] ?? 0);
        $total = $allTickets->count();

        $bySource = $allTickets->countBy('source');
        $byCategory = $allTickets->countBy('category');

        $admins = User::where('role', 'admin')->where('is_active', true)->get()->map(function($a) use ($days, $source) {
            $assignedTicketIds = Ticket::where('assigned_to', $a->id)
                ->where('created_at', '>=', now()->subDays($days));
            if ($source !== 'all') {
                $assignedTicketIds->where('source', $source);
            }
            $ids = $assignedTicketIds->pluck('id');
            $answered = \App\Models\TicketComment::where('user_id', $a->id)
                ->whereIn('ticket_id', $ids)
                ->distinct('ticket_id')
                ->count('ticket_id');
            $t = $ids->count();
            return ['name' => $a->name, 'score' => $t > 0 ? round(($answered / $t) * 100) : 0];
        })->sortByDesc('score')->values();

        return response()->json([
            'tickets' => $tickets,
            'stats'   => [
                'total'          => $total,
                'auto_resolved'  => $autoResolved,
                'escalated'      => $escalated,
                'clarify'        => $clarify,
                'routed'         => $routed,
                'resolution_rate'=> $total > 0 ? round(($autoResolved / $total) * 100) : 0,
                'avg_confidence' => round($allTickets->avg(fn($t) => $this->confidence($t)) ?? 0),
                'by_source'      => $bySource,
                'by_category'    => $byCategory,
                'admins'         => $admins,
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

    public function stats()
    {
        $tickets = Ticket::with(['user', 'assignee'])->get();
        $total = $tickets->count();

        $decisions = $tickets->map(function (Ticket $ticket) {
            $confidence = $this->confidence($ticket);
            $risk = $this->risk($ticket);
            return $this->decisionPayload([
                'ticket_id' => 'TK-' . $ticket->id,
                'category' => $ticket->category,
                'confidence' => $confidence,
                'risk' => $risk,
                'outcome' => $this->outcome($ticket),
                'priority' => $ticket->priority ?? 3,
                'created_at' => $ticket->created_at?->toIso8601String(),
            ]);
        });

        $byOutcome = $decisions->countBy('outcome');
        $byCategory = $decisions->countBy('intent_category');
        $escalated = (int) ($byOutcome['escalated'] ?? 0);

        $avgConfidence = $decisions->avg('confidence') ?? 0;
        $avgRisk = $decisions->avg('risk_score') ?? 0;

        return response()->json([
            'total_decisions' => $total,
            'total' => $total,
            'auto_resolved' => (int) ($byOutcome['auto_resolved'] ?? 0),
            'escalated' => $escalated,
            'clarify' => (int) ($byOutcome['clarify'] ?? 0),
            'routed' => (int) ($byOutcome['routed'] ?? 0),
            'escalation_rate' => $total > 0 ? round($escalated / $total, 2) : 0,
            'avg_confidence' => $avgConfidence,
            'avg_risk' => $avgRisk,
            'decisions_by_category' => $byCategory,
            'decisions_by_outcome' => $byOutcome,
        ]);
    }

    public function decisions(?string $ticketId = null)
    {
        $query = Ticket::latest()->limit(100);
        if ($ticketId) {
            $id = (int) preg_replace('/\D+/', '', $ticketId);
            $query->where('id', $id);
        }

        $decisions = $query->get()->map(function (Ticket $ticket) {
            return $this->decisionPayload([
                'id' => 'ticket-' . $ticket->id,
                'ticket_id' => 'TK-' . $ticket->id,
                'category' => $ticket->category,
                'text' => trim(($ticket->title ?? '') . ' ' . ($ticket->description ?? '')),
                'confidence' => $this->confidence($ticket),
                'risk' => $this->risk($ticket),
                'outcome' => $this->outcome($ticket),
                'priority' => $ticket->priority ?? 3,
                'created_at' => $ticket->created_at?->toIso8601String(),
            ]);
        })->values();

        return response()->json(['decisions' => $decisions]);
    }

    public function analyze(Request $request)
    {
        $validated = $request->validate([
            'ticket_id' => ['required', 'string'],
            'auto_assign' => ['sometimes', 'boolean'],
            'auto_update_priority' => ['sometimes', 'boolean'],
        ]);

        $ticketId = (int) preg_replace('/\D+/', '', $validated['ticket_id']);
        $ticket = Ticket::with(['user', 'assignee'])->find($ticketId);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket introuvable.'], 404);
        }

        $risk = $this->risk($ticket);
        $priority = $this->priorityFromRisk($risk);

        if (!empty($validated['auto_update_priority']) && $ticket->priority !== $priority) {
            $ticket->priority = $priority;
            $ticket->save();
        }

        return response()->json($this->decisionPayload([
            'id' => 'ticket-' . $ticket->id,
            'ticket_id' => 'TK-' . $ticket->id,
            'category' => $ticket->category,
            'text' => trim(($ticket->title ?? '') . ' ' . ($ticket->description ?? '')),
            'confidence' => $this->confidence($ticket),
            'risk' => $risk,
            'outcome' => $this->outcome($ticket),
            'priority' => $priority,
            'reasoning' => 'Analyse basee sur la categorie, la priorite, le statut, le SLA et les donnees du ticket.',
        ]));
    }

    public function analyzeText(Request $request)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'min:3'],
            'subject' => ['nullable', 'string'],
        ]);

        $text = Str::lower(($validated['subject'] ?? '') . ' ' . $validated['text']);
        $risk = 30;
        $confidence = 62;

        foreach (['urgent', 'bloque', 'bloqué', 'down', 'indisponible', 'erreur', 'critique'] as $word) {
            if (str_contains($text, $word)) {
                $risk += 12;
                $confidence += 4;
            }
        }
        foreach (['facture', 'paiement', 'api', 'login', 'connexion'] as $word) {
            if (str_contains($text, $word)) {
                $confidence += 6;
            }
        }

        $risk = min(95, $risk);
        $confidence = min(92, $confidence);

        return response()->json($this->decisionPayload([
            'ticket_id' => null,
            'text' => $text,
            'confidence' => $confidence,
            'risk' => $risk,
            'reasoning' => 'Apercu genere depuis le texte libre saisi.',
        ]));
    }

    public function outcomesDocs()
    {
        return response()->json([
            'outcomes' => [
                ['outcome' => 'auto_resolved', 'description' => 'Resolution automatique quand le risque est faible et la confiance haute.', 'operator_guidance' => 'Verifier la suggestion puis cloturer si elle est valide.'],
                ['outcome' => 'clarify', 'description' => 'Informations insuffisantes ou confiance moyenne.', 'operator_guidance' => 'Demander des details au client avant action.'],
                ['outcome' => 'escalated', 'description' => 'Risque eleve ou confiance basse.', 'operator_guidance' => 'Assigner a un agent humain rapidement.'],
                ['outcome' => 'routed', 'description' => 'Routage par categorie/priorite.', 'operator_guidance' => 'Envoyer vers la bonne competence.'],
            ],
            'matrix' => [
                ['category' => 'technical_issue', 'confidence_level' => 'high', 'risk_level' => 'low', 'outcome' => 'auto_resolved', 'matched_rule' => 'confidence >= 80 AND risk < 30'],
                ['category' => 'general_support', 'confidence_level' => 'medium', 'risk_level' => 'medium', 'outcome' => 'clarify', 'matched_rule' => '60 <= confidence < 80'],
                ['category' => 'technical_issue', 'confidence_level' => 'low', 'risk_level' => 'high', 'outcome' => 'escalated', 'matched_rule' => 'confidence < 60 OR risk > 60'],
                ['category' => 'integration', 'confidence_level' => 'high', 'risk_level' => 'medium', 'outcome' => 'routed', 'matched_rule' => 'route by category'],
            ],
        ]);
    }

    // ── GET /super-admin/decision-engine ─────────────────────────────────────
    public function index()
    {
        return view('super-admin.super-admin-decision-engine');
    }
}
