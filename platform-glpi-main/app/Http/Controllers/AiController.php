<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Services\AiService;

class AiController extends Controller
{
    protected AiService $ai;

    public function __construct(AiService $ai)
    {
        $this->ai = $ai;
    }

    // ═══════════════════════════════════════════════════════════
    // CLIENT
    // ═══════════════════════════════════════════════════════════

    // POST /tickets/classify — classification real-time pendant saisie
    public function classify(Request $request)
    {
        $title       = $request->input('title', '');
        $description = $request->input('description', '');

        if (strlen($title) < 5 || !$this->ai->isAvailable()) {
            return response()->json(['available' => false]);
        }

        $result = $this->ai->classify($title, $description);

        if (!$result) {
            return response()->json(['available' => false]);
        }

        return response()->json([
            'available'      => true,
            'category'       => $result['category']       ?? 'autre',
            'category_label' => $result['category_label'] ?? 'Autre',
            'priority'       => $result['priority']       ?? 3,
            'priority_label' => $result['priority_label'] ?? 'Moyenne',
            'urgency'        => $result['urgency']        ?? 3,
            'confidence'     => $result['confidence']     ?? 0,
            'solutions'      => $result['solutions']      ?? [],
        ]);
    }

    // POST /tickets/reformulate — LLM améliore la description
    public function reformulate(Request $request)
    {
        $title       = $request->input('title', '');
        $description = $request->input('description', '');

        if (!$this->ai->isAvailable()) {
            return response()->json(['available' => false]);
        }

        $improved = $this->ai->reformulate($title, $description);

        return response()->json([
            'available'    => true,
            'reformulated' => $improved ?? $description,
        ]);
    }

    // GET /tickets/similar?q=... — tickets similaires résolus
    public function similar(Request $request)
    {
        $q = $request->input('q', '');

        if (strlen($q) < 4) {
            return response()->json(['tickets' => []]);
        }

        $tickets = $this->ai->findSimilar($q);

        return response()->json(['tickets' => $tickets]);
    }

    // ═══════════════════════════════════════════════════════════
    // ADMIN
    // ═══════════════════════════════════════════════════════════

    // POST /admin/ai/analyze — résumé + réponse suggérée
    public function analyzeTicket(Request $request)
    {
        $ticketId = $request->input('ticket_id');
        $ticket   = Ticket::with('comments.user')->findOrFail($ticketId);

        // Commentaires — avec auteur + date pour contexte complet
        $comments = $ticket->comments
            ->sortBy('created_at')
            ->map(fn($c) => '[' . ($c->user->name ?? 'Client') . ' — ' . $c->created_at->format('d/m H:i') . '] ' . $c->content)
            ->toArray();

        // Historique réponses de CET admin (style learning)
        $pastResponses = Ticket::where('solved_by', auth()->id())
            ->whereNotNull('solution')
            ->where('category', $ticket->category)
            ->latest()
            ->limit(3)
            ->pluck('solution')
            ->toArray();

        // Fallback si IA indisponible
        if (!$this->ai->isAvailable()) {
            return response()->json([
                'available' => false,
                'summary'   => $this->fallbackSummary($ticket),
                'response'  => $this->fallbackResponse($ticket),
                'urgency'   => $this->assessUrgency($ticket),
                'tags'      => [],
            ]);
        }

        $result = $this->ai->analyzeForAdmin(
            $ticket->title,
            $ticket->description,
            $ticket->category,
            $comments,
            $pastResponses
        );

        if (!$result) {
            return response()->json([
                'available' => false,
                'summary'   => $this->fallbackSummary($ticket),
                'response'  => $this->fallbackResponse($ticket),
                'urgency'   => $this->assessUrgency($ticket),
                'tags'      => [],
            ]);
        }

        return response()->json([
            'available' => true,
            'summary'   => $result['summary']       ?? $this->fallbackSummary($ticket),
            'response'  => $result['response']      ?? $this->fallbackResponse($ticket),
            'urgency'   => array_merge($this->assessUrgency($ticket), [
                'label'     => $result['urgency_label'] ?? 'Dans les délais',
                'is_urgent' => $result['is_urgent']     ?? false,
            ]),
            'tags'      => $result['tags'] ?? [],
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // SUPER ADMIN
    // ═══════════════════════════════════════════════════════════

    // GET /super-admin/ai/leaderboard — performance admins
    public function adminLeaderboard()
    {
        $admins = User::where('role', 'admin')
            ->where(function($q) {
                $q->where('is_active', true)
                  ->orWhere('is_active', 1)
                  ->orWhereNull('is_active');
            })
            ->get()
            ->map(function ($admin) {

                // ── Activité RÉELLE de l'admin ─────────────────────────────────
                // On compte TOUT ce qu'il a fait, même si assigned_to est vide

                // Commentaires/réponses postés par cet admin (sur n'importe quel ticket)
                $commentedTicketIds = \App\Models\TicketComment::where('user_id', $admin->id)
                    ->distinct('ticket_id')
                    ->pluck('ticket_id');
                $answered = $commentedTicketIds->count();

                // Tickets résolus PAR cet admin (solved_by) ou dans son portefeuille
                $resolvedBySelf = Ticket::where('solved_by', $admin->id)
                    ->whereIn('sync_status', ['resolved', 'closed', 'synced'])
                    ->count();

                // Tickets assignés à cet admin
                $assignedIds = Ticket::where('assigned_to', $admin->id)->pluck('id');
                $totalAssigned = $assignedIds->count();

                // Résolus dans son portefeuille
                $resolvedAssigned = Ticket::whereIn('id', $assignedIds)
                    ->whereIn('sync_status', ['resolved', 'closed', 'synced'])
                    ->count();

                // Total resolved = union des deux
                $resolved = max($resolvedBySelf, $resolvedAssigned);

                // Tickets en attente dans son portefeuille
                $pending = Ticket::whereIn('id', $assignedIds)
                    ->whereIn('sync_status', ['pending', 'in_progress'])->count();

                // Total = tickets commentés OU assignés (son activité globale)
                $total = max($answered, $totalAssigned);

                // Temps moyen résolution (tickets résolus par lui)
                $avgHours = Ticket::where('solved_by', $admin->id)
                    ->whereIn('sync_status', ['resolved', 'closed', 'synced'])
                    ->whereNotNull('resolved_at')
                    ->get()
                    ->avg(fn($t) => $t->created_at->diffInHours($t->resolved_at));

                // Tickets urgents traités (commentés ou résolus)
                $urgentHandled = \App\Models\TicketComment::where('user_id', $admin->id)
                    ->whereHas('ticket', fn($q) => $q->where('priority', '>=', 4))
                    ->distinct('ticket_id')
                    ->count();

                // ── Score (0-100) ────────────────────────────────────────────────
                // Score basé sur l'activité réelle, pas seulement les assignations
                $score = 0;

                // 1. Réponses données (35 pts max) — 5 pts par ticket répondu, max 35
                $answerPts = min(35, $answered * 5);

                // 2. Tickets résolus (30 pts max) — 6 pts par résolution, max 30
                $resolvedPts = min(30, $resolved * 6);

                // 3. Rapidité (20 pts max) — si temps moyen < 2h = 20pts, < 8h = 15pts, etc.
                $speedPts = 10; // par défaut si pas de données
                if ($avgHours !== null) {
                    if ($avgHours <= 2)  $speedPts = 20;
                    elseif ($avgHours <= 8)  $speedPts = 15;
                    elseif ($avgHours <= 24) $speedPts = 10;
                    elseif ($avgHours <= 72) $speedPts = 5;
                    else $speedPts = 2;
                }

                // 4. Tickets urgents traités (15 pts max)
                $urgentPts = min(15, $urgentHandled * 5);

                $score = min(100, (int)($answerPts + $resolvedPts + $speedPts + $urgentPts));

                // Temps de service (ancienneté en jours)
                $daysSinceCreation = $admin->created_at
                    ? (int) $admin->created_at->diffInDays(now())
                    : 0;

                return [
                    'id'             => $admin->id,
                    'name'           => $admin->name,
                    'email'          => $admin->email,
                    'resolved'       => $resolved,
                    'answered'       => $answered,
                    'total'          => $total,
                    'pending'        => $pending,
                    'avg_hours'      => $avgHours ? round($avgHours, 1) : null,
                    'urgent_handled' => $urgentHandled,
                    'score'          => $score,
                    'days_active'    => $daysSinceCreation,
                    'suggestion'     => $this->adminSuggestion($score, $answered, $avgHours, $urgentHandled, $total),
                ];
            })
            ->sortByDesc('score')
            ->values();

        return response()->json([
            'admins'         => $admins,
            'total_resolved' => Ticket::whereIn('sync_status', ['resolved', 'closed'])->count(),
            'total_pending'  => Ticket::whereIn('sync_status', ['pending', 'in_progress'])->count(),
            'urgent_pending' => Ticket::whereIn('sync_status', ['pending', 'in_progress'])
                                    ->where('priority', '>=', 4)->count(),
        ]);
    }

    // GET /super-admin/ai/urgent-tickets — tickets urgents non résolus
    public function urgentTickets()
    {
        $tickets = Ticket::with('user')
            ->whereIn('sync_status', ['pending', 'in_progress'])
            ->where(function($q) {
        $q->where('priority', '>=', 4)
      ->orWhere(function($q2) {
          // Priority 3 (Moyenne) ouverts depuis plus de 20h = urgent aussi
          $q2->where('priority', '>=', 3)
             ->where('created_at', '<=', now()->subHours(20));
      });
      })
            ->orderByDesc('priority')
            ->orderBy('created_at')
            ->limit(5)
            ->get()
            ->map(function ($t) {
                $hoursOpen = $t->created_at->diffInHours(now());
                $slaMap = [
                    5 => (int) \App\Models\Setting::get('sla_très haute', '4'),
                    4 => (int) \App\Models\Setting::get('sla_haute',      '8'),
                    3 => (int) \App\Models\Setting::get('sla_moyenne',    '24'),
                    2 => (int) \App\Models\Setting::get('sla_basse',      '48'),
                    1 => (int) \App\Models\Setting::get('sla_basse',      '48'),
                ];
                $slaLimit  = $slaMap[$t->priority] ?? 8;
                return [
                    'id'         => $t->id,
                    'title'      => $t->title,
                    'client'     => $t->user->name ?? 'N/A',
                    'priority'   => $t->priority,
                    'hours_open' => $hoursOpen,
                    'sla_limit'  => $slaLimit,
                    'sla_risk'   => $hoursOpen >= $slaLimit * 0.8,
                    'created_at' => $t->created_at->format('d/m H:i'),
                ];
            });

        return response()->json(['tickets' => $tickets]);
    }

    // GET /super-admin/urgent-tickets — vue blade liste complète
public function urgentTicketsList()
{
    $slaMap = [
        5 => (int) \App\Models\Setting::get('sla_très haute', '4'),
        4 => (int) \App\Models\Setting::get('sla_haute',      '8'),
        3 => (int) \App\Models\Setting::get('sla_moyenne',    '24'),
        2 => (int) \App\Models\Setting::get('sla_basse',      '48'),
        1 => (int) \App\Models\Setting::get('sla_basse',      '48'),
    ];

    $tickets = Ticket::with('user')
        ->whereIn('sync_status', ['pending', 'in_progress'])
        ->where(function($q) {
            $q->where('priority', '>=', 4)
              ->orWhere(function($q2) {
                  $q2->where('priority', '>=', 3)
                     ->where('created_at', '<=', now()->subHours(20));
              });
        })
        ->orderBy('created_at')
        ->get()
        ->map(function ($t) use ($slaMap) {
            $hoursOpen = (int) round($t->created_at->floatDiffInHours(now()));
            $slaLimit  = $slaMap[$t->priority] ?? 8;
            $slaUsed   = $slaLimit > 0 ? ($hoursOpen / $slaLimit) * 100 : 100;
            $hoursLeft = $slaLimit - $hoursOpen;

            return (object)[
                'id'           => $t->id,
                'title'        => $t->title,
                'client'       => $t->user->name ?? 'N/A',
                'priority'     => $t->priority,
                'hours_open'   => $hoursOpen,
                'sla_limit'    => $slaLimit,
                'sla_used'     => round($slaUsed, 1),
                'sla_risk'     => !($hoursLeft < 0) && $slaUsed >= 80,
                'sla_breached' => $hoursLeft < 0,
                'hours_left'   => $hoursLeft,
                'sla_ratio'    => $slaLimit > 0 ? $hoursOpen / $slaLimit : 999,
                'created_at'   => $t->created_at,
                'status'       => $t->sync_status,
            ];
        })
        ->sortByDesc('sla_ratio')
        ->values();

    $role = auth()->user()->role;
    $view = $role === 'admin' ? 'admin.urgent-tickets' : 'super-admin.urgent-tickets';

    return view($view, compact('tickets'));

    return view($view, compact('tickets'));
}

    // GET /super-admin/ai/weekly-report — rapport IA hebdo
    public function weeklyReport()
    {
        $stats = [
            'total_tickets'   => Ticket::whereBetween('created_at', [now()->subDays(7), now()])->count(),
            'resolved'        => Ticket::whereBetween('created_at', [now()->subDays(7), now()])
                                    ->whereIn('sync_status', ['resolved', 'closed'])->count(),
            'urgent'          => Ticket::whereBetween('created_at', [now()->subDays(7), now()])
                                    ->where('priority', '>=', 4)->count(),
            'by_category'     => Ticket::whereBetween('created_at', [now()->subDays(7), now()])
                                    ->selectRaw('category, count(*) as total')
                                    ->groupBy('category')->pluck('total', 'category'),
            'active_admins'   => User::where('role', 'admin')->where('is_active', true)->count(),
        ];

        $report = $this->ai->isAvailable()
            ? $this->ai->generateWeeklyReport($stats)
            : null;

        return response()->json([
            'stats'  => $stats,
            'report' => $report ?? "Rapport indisponible — service IA non configuré.",
        ]);
    }


    private function fallbackSummary(Ticket $ticket): string
    {
        $cat  = ['incident_technique' => 'Incident technique', 'integration_api' => 'Problème API', 'facturation' => 'Facturation', 'plateforme' => 'Plateforme', 'paiement_mobile' => 'Paiement mobile', 'autre' => 'Demande'][$ticket->category] ?? 'Demande';
        $prio = ['', 'très basse', 'basse', 'moyenne', 'haute', 'critique'][$ticket->priority ?? 3] ?? 'moyenne';
        return "{$cat} — priorité {$prio}. \"" . substr($ticket->title, 0, 80) . "\" — " . $ticket->created_at->diffForHumans() . ".";
    }

    private function fallbackResponse(Ticket $ticket): string
    {
        $responses = [
            'incident_technique' => "Bonjour,\n\nNous avons bien reçu votre signalement. Notre équipe technique prend en charge votre incident immédiatement.\n\nPouvez-vous nous préciser depuis quand le problème est apparu et si vous observez un message d'erreur spécifique ?\n\nCordialement,\nL'équipe Support L2T",
            'integration_api'    => "Bonjour,\n\nMerci pour votre message. Pour résoudre ce problème, veuillez vérifier votre token API dans votre espace client et consulter notre documentation.\n\nN'hésitez pas à nous partager le message d'erreur exact.\n\nCordialement,\nL'équipe Support L2T",
            'facturation'        => "Bonjour,\n\nVotre demande a été transmise à notre service comptabilité. Vous recevrez une réponse dans les 24h ouvrées.\n\nCordialement,\nL'équipe Support L2T",
            'plateforme'         => "Bonjour,\n\nMerci de nous avoir signalé ce problème. Essayez de vider le cache de votre navigateur et de vous reconnecter. Si le problème persiste, notre équipe prend en charge votre demande.\n\nCordialement,\nL'équipe Support L2T",
        ];
        return $responses[$ticket->category] ?? "Bonjour,\n\nNous avons bien reçu votre demande et notre équipe vous répondra dans les meilleurs délais.\n\nCordialement,\nL'équipe Support L2T";
    }

    private function assessUrgency(Ticket $ticket): array
    {
        $hoursOpen = $ticket->created_at->diffInHours(now());
        $slaLimit  = [
            5 => (int) \App\Models\Setting::get('sla_très haute', '4'),
            4 => (int) \App\Models\Setting::get('sla_haute',      '8'),
            3 => (int) \App\Models\Setting::get('sla_moyenne',    '24'),
            2 => (int) \App\Models\Setting::get('sla_basse',      '48'),
            1 => (int) \App\Models\Setting::get('sla_basse',      '48'),
        ][$ticket->priority ?? 3] ?? 24;
        $slaUsed   = min(100, (int)(($hoursOpen / $slaLimit) * 100));
        return [
            'is_urgent'  => ($ticket->priority ?? 3) >= 4 || $slaUsed >= 80,
            'hours_open' => $hoursOpen,
            'sla_limit'  => $slaLimit,
            'sla_used'   => $slaUsed,
        ];
    }

    private function adminSuggestion(int $score, int $answered, ?float $avgHours, int $urgentHandled, int $total = 0): string
    {
        if ($total === 0) return "Aucun ticket assigné pour le moment.";
        if ($score >= 80) return "Excellente performance — top performer de l'équipe. 🏆";
        if ($score >= 60) return "Bonne performance — continuer sur cette lancée !";
        if ($answered === 0) return "A reçu des tickets mais n'a pas encore répondu.";
        if ($avgHours !== null && $avgHours > 48) return "Délai moyen élevé (" . round($avgHours) . "h) — prioriser les tickets urgents.";
        if ($urgentHandled === 0 && $total > 2) return "N'a pas traité de tickets urgents — les inclure en priorité.";
        if ($total > 0 && $answered > 0) return "A traité {$answered}/{$total} tickets — bonne réactivité.";
        return "Performance en cours d'évaluation — données insuffisantes.";
    }
}