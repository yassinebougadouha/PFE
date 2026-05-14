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

        $comments = $ticket->comments
            ->sortBy('created_at')
            ->map(fn($c) => '[' . ($c->user->name ?? 'Client') . ' — ' . $c->created_at->format('d/m H:i') . '] ' . $c->content)
            ->toArray();

        $pastResponses = Ticket::where('solved_by', auth()->id())
            ->whereNotNull('solution')
            ->where('category', $ticket->category)
            ->latest()
            ->limit(3)
            ->pluck('solution')
            ->toArray();

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
            ->where(function ($query) {
                $query->where('is_active', true)->orWhereNull('is_active');
            })
            ->get()
            ->map(function ($admin) {

                $assignedIds = Ticket::where('assigned_to', $admin->id)->pluck('id');
                $total = $assignedIds->count();

                $repliedTicketIds = \App\Models\TicketComment::where('user_id', $admin->id)
                    ->distinct('ticket_id')
                    ->pluck('ticket_id');

                $answered = $repliedTicketIds->intersect($assignedIds)->count();

                $resolved = Ticket::whereIn('id', $assignedIds)
                    ->whereIn('sync_status', ['resolved', 'closed', 'synced'])
                    ->count();

                $avgHours = Ticket::whereIn('id', $assignedIds)
                    ->whereIn('sync_status', ['resolved', 'closed', 'synced'])
                    ->whereNotNull('resolved_at')
                    ->get()
                    ->avg(fn($t) => $t->created_at->diffInHours($t->resolved_at));

                $urgentIds = Ticket::whereIn('id', $assignedIds)->where('priority', '>=', 4)->pluck('id');
                $urgentHandled = $repliedTicketIds->intersect($urgentIds)->count();

                $pending = Ticket::whereIn('id', $assignedIds)
                    ->whereIn('sync_status', ['pending', 'in_progress'])->count();

                $score = 0;
                if ($total > 0) {
                    $answerRatio   = ($answered / max($total, 1)) * 40;
                    $speedBonus    = $avgHours !== null
                        ? max(0, 30 - min(30, (int)($avgHours / 2)))
                        : 15;
                    $urgentBonus   = min(20, $urgentHandled * 5);
                    $resolvedBonus = min(10, (int)(($resolved / max($total, 1)) * 10));

                    $score = min(100, (int)($answerRatio + $speedBonus + $urgentBonus + $resolvedBonus));
                }

                return [
                    'id'             => $admin->id,
                    'name'           => $admin->name,
                    'resolved'       => $resolved,
                    'answered'       => $answered,
                    'total'          => $total,
                    'pending'        => $pending,
                    'avg_hours'      => $avgHours ? round($avgHours, 1) : null,
                    'urgent_handled' => $urgentHandled,
                    'score'          => $score,
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
            ->where(function ($query) {
                $query->where('priority', '>=', 4)
                      ->orWhere(function ($sub) {
                          $sub->where('priority', '>=', 3)
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
                $slaLimit = $slaMap[$t->priority] ?? 8;
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
        $adminGlpiId = auth()->user()->glpi_user_id;
        $allTickets  = [];

        if ($adminGlpiId) {
            try {
                $glpi = app(GlpiService::class);
                $glpi->initSession();
                $rawTickets = $glpi->getAllItems('Ticket', ['range' => '0-9999', 'order' => 'DESC']);

                // Build ticket-to-assignee map using the same session
                $ticketAssignees = [];
                try {
                    $tuList = $glpi->getAllItems('Ticket_User', ['range' => '0-9999']);
                    foreach ($tuList as $tu) {
                        if (isset($tu['tickets_id'], $tu['users_id'], $tu['type']) && (int)$tu['type'] === 2) {
                            $ticketAssignees[(int)$tu['tickets_id']] = (int)$tu['users_id'];
                        }
                    }
                } catch (\Exception $e2) {
                    \Log::warning('TU fetch failed: ' . $e2->getMessage());
                }

                $glpi->killSession();

                // Transform
                $statusMap = [1 => 'pending', 2 => 'in_progress', 3 => 'in_progress', 4 => 'in_progress', 5 => 'resolved', 6 => 'closed'];
                foreach ($rawTickets as $t) {
                    $tid = (int)($t['id'] ?? 0);
                    $obj = new \stdClass();
                    $obj->id = $tid;
                    $obj->glpi_id = $tid;
                    $obj->title = $t['name'] ?? '';
                    $obj->description = $t['content'] ?? '';
                    $obj->sync_status = $statusMap[(int)($t['status'] ?? 1)] ?? 'pending';
                    $obj->priority = (int)($t['priority'] ?? 3);
                    $obj->user_id = $t['users_id_recipient'] ?? null;
                    $obj->assigned_to = $ticketAssignees[$tid] ?? (int)($t['users_id_lastupdater'] ?? 0);
                    $obj->created_at = \Carbon\Carbon::parse($t['date_creation'] ?? $t['date'] ?? now());
                    $allTickets[] = $obj;
                }
            } catch (\Exception $e) {
                \Log::error('GLPI urgent tickets fetch failed: ' . $e->getMessage());
            }
        }

        if (empty($allTickets)) {
            $allTickets = [];
        }

        $slaMap = [
            5 => (int) \App\Models\Setting::get('sla_très haute', '4'),
            4 => (int) \App\Models\Setting::get('sla_haute',      '8'),
            3 => (int) \App\Models\Setting::get('sla_moyenne',    '24'),
            2 => (int) \App\Models\Setting::get('sla_basse',      '48'),
            1 => (int) \App\Models\Setting::get('sla_basse',      '48'),
        ];

        $tickets = collect($allTickets)
            ->filter(fn($t) => (int)$t->assigned_to === (int)$adminGlpiId)
            ->filter(fn($t) => in_array($t->sync_status, ['pending', 'in_progress']))
            ->filter(function ($t) {
                $p = (int)$t->priority;
                $created = \Carbon\Carbon::parse($t->created_at);
                return $p >= 4 || ($p >= 3 && $created->lte(now()->subHours(20)));
            })
            ->sortBy('created_at')
            ->map(function ($t) use ($slaMap) {
                $created   = \Carbon\Carbon::parse($t->created_at);
                $hoursOpen = (int) round($created->floatDiffInHours(now()));
                $slaLimit  = $slaMap[(int)$t->priority] ?? 8;
                $slaUsed   = $slaLimit > 0 ? ($hoursOpen / $slaLimit) * 100 : 100;
                $hoursLeft = $slaLimit - $hoursOpen;

                return (object)[
                    'id'           => $t->glpi_id,
                    'title'        => $t->title,
                    'client'       => 'Client #' . ($t->user_id ?? '?'),
                    'priority'     => (int)$t->priority,
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
    }

    // GET /super-admin/ai/weekly-report — rapport IA hebdo
    public function weeklyReport()
    {
        $stats = [
            'total_tickets' => Ticket::whereBetween('created_at', [now()->subDays(7), now()])->count(),
            'resolved'      => Ticket::whereBetween('created_at', [now()->subDays(7), now()])
                                   ->whereIn('sync_status', ['resolved', 'closed'])->count(),
            'urgent'        => Ticket::whereBetween('created_at', [now()->subDays(7), now()])
                                   ->where('priority', '>=', 4)->count(),
            'by_category'   => Ticket::whereBetween('created_at', [now()->subDays(7), now()])
                                   ->selectRaw('category, count(*) as total')
                                   ->groupBy('category')->pluck('total', 'category'),
            'active_admins' => User::where('role', 'admin')->where('is_active', true)->count(),
        ];

        $report = $this->ai->isAvailable()
            ? $this->ai->generateWeeklyReport($stats)
            : null;

        return response()->json([
            'stats'  => $stats,
            'report' => $report ?? "Rapport indisponible — service IA non configuré.",
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════

    private function fallbackSummary(Ticket $ticket): string
    {
        $cat  = [
            'incident_technique' => 'Incident technique',
            'integration_api'    => 'Problème API',
            'facturation'        => 'Facturation',
            'plateforme'         => 'Plateforme',
            'paiement_mobile'    => 'Paiement mobile',
            'autre'              => 'Demande',
        ][$ticket->category] ?? 'Demande';

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

        return $responses[$ticket->category]
            ?? "Bonjour,\n\nNous avons bien reçu votre demande et notre équipe vous répondra dans les meilleurs délais.\n\nCordialement,\nL'équipe Support L2T";
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

        $slaUsed = min(100, (int)(($hoursOpen / $slaLimit) * 100));

        return [
            'is_urgent'  => ($ticket->priority ?? 3) >= 4 || $slaUsed >= 80,
            'hours_open' => $hoursOpen,
            'sla_limit'  => $slaLimit,
            'sla_used'   => $slaUsed,
        ];
    }

    private function adminSuggestion(int $score, int $answered, ?float $avgHours, int $urgentHandled, int $total = 0): string
    {
        if ($total === 0)            return "Aucun ticket assigné pour le moment.";
        if ($score >= 80)            return "Excellente performance — top performer de l'équipe. 🏆";
        if ($score >= 60)            return "Bonne performance — continuer sur cette lancée !";
        if ($answered === 0)         return "A reçu des tickets mais n'a pas encore répondu.";
        if ($avgHours !== null && $avgHours > 48)
                                     return "Délai moyen élevé (" . round($avgHours) . "h) — prioriser les tickets urgents.";
        if ($urgentHandled === 0 && $total > 2)
                                     return "N'a pas traité de tickets urgents — les inclure en priorité.";
        if ($total > 0 && $answered > 0)
                                     return "A traité {$answered}/{$total} tickets — bonne réactivité.";
        return "Performance en cours d'évaluation — données insuffisantes.";
    }
}