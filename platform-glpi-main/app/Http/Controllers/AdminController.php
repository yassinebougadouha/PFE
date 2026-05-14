<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Services\GmailService;
use App\Services\TeamsService;
use App\Services\GlpiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function userSettings()
    {
        return view('admin.settings');
    }

    public function dashboard()
 {
     $adminId     = auth()->id();
     $adminGlpiId = auth()->user()->glpi_user_id;

     // Fetch all GLPI tickets, filter to this admin's assigned tickets
     $allTickets = [];
     if ($adminGlpiId) {
         try {
             $glpi = app(GlpiService::class);
             $glpi->initSession();
             $allTickets = $glpi->getTransformedTickets(['range' => '0-9999', 'order' => 'DESC']);
             $glpi->killSession();
         } catch (\Exception $e) {
             \Log::error('GLPI dashboard fetch failed: ' . $e->getMessage());
         }
     }
     $myTickets = collect($allTickets)->filter(fn($t) => (int)$t->assigned_to === (int)$adminGlpiId);

     // Stats from assigned tickets
     $totalTickets      = $myTickets->count();
     $openTickets       = $myTickets->filter(fn($t) => $t->sync_status === 'pending')->count();
     $inProgressTickets = $myTickets->filter(fn($t) => $t->sync_status === 'in_progress')->count();
     $closedTickets     = $myTickets->filter(fn($t) => in_array($t->sync_status, ['resolved', 'closed']))->count();

     $tz = config('app.timezone', 'Africa/Tunis');
     $startOfDay = now()->timezone($tz)->startOfDay()->utc();
     $endOfDay   = now()->timezone($tz)->endOfDay()->utc();
     $ticketsAujourdhui = $myTickets->filter(fn($t) =>
         \Carbon\Carbon::parse($t->created_at)->between($startOfDay, $endOfDay)
     )->count();
     $ticketsNonResolus = $myTickets->filter(fn($t) => !in_array($t->sync_status, ['resolved', 'closed']))->count();

     $reclamationsExternes = User::where('role', 'client')
         ->where(fn($q) => $q->where('client_type','user')->orWhereNull('client_type'))
         ->count();
     $clientsActifs = User::where('role', 'client')->where('is_active', true)
                               ->where('client_type', 'client')->count();
     $countClient = User::where('role','client')->where('client_type','client')->count();
     $countNew    = User::where('role','client')->where(fn($q)=>$q->where('client_type','user')->orWhereNull('client_type'))->count();
     $totalAdmins = User::where('role', 'admin')->count();
     $totalClients = User::where('role', 'client')->count();
     $myResolvedTickets = $closedTickets;

     // Recent tickets assigned to me
     $recentTickets = $myTickets->sortByDesc('created_at')->take(10)->values();

     $recentAdmins  = User::where('role', 'admin')->latest()->take(5)->get();
     $recentClients = User::where('role', 'client')
                          ->withCount('tickets')
                          ->latest()
                          ->take(5)
                          ->get();

     $ticketsByMonth = [];
     for ($i = 5; $i >= 0; $i--) {
         $month = now()->timezone('Africa/Tunis')->subMonths($i);
         $count = $myTickets->filter(fn($t) =>
             \Carbon\Carbon::parse($t->created_at)->year === (int)$month->year
             && \Carbon\Carbon::parse($t->created_at)->month === (int)$month->month
         )->count();
         $ticketsByMonth[] = [
             'month' => $month->locale('fr')->isoFormat('MMM YYYY'),
             'count' => $count,
         ];
     }

     // Urgent tickets assigned to me
     $urgentTickets = $myTickets
         ->filter(fn($t) => in_array($t->sync_status, ['pending', 'in_progress']) && $t->priority >= 4)
         ->sortBy('created_at')
         ->take(20)
         ->map(function ($t) {
             $slaMap = [
                 5 => (int) \App\Models\Setting::get('sla_très haute', '4'),
                 4 => (int) \App\Models\Setting::get('sla_haute',      '8'),
                 3 => (int) \App\Models\Setting::get('sla_moyenne',    '24'),
                 2 => (int) \App\Models\Setting::get('sla_basse',      '48'),
                 1 => (int) \App\Models\Setting::get('sla_basse',      '48'),
             ];
             $slaLimit  = $slaMap[(int)$t->priority] ?? 8;
             $hoursOpen = (int) round(\Carbon\Carbon::parse($t->created_at)->floatDiffInHours(now()));
             $slaUsed   = $slaLimit > 0 ? round(($hoursOpen / $slaLimit) * 100, 1) : 100;
             $hoursLeft = $slaLimit - $hoursOpen;
             $t->sla_limit      = $slaLimit;
             $t->sla_used_pct   = min($slaUsed, 100);
             $t->sla_hours_open = $hoursOpen;
             $t->sla_hours_left = $hoursLeft;
             $t->sla_breached   = $hoursLeft < 0;
             $t->sla_risk       = !($t->sla_breached) && $slaUsed >= 80;
             $t->sla_ratio      = $slaLimit > 0 ? $hoursOpen / $slaLimit : 999;
             return $t;
         })
         ->sortByDesc('sla_ratio')
         ->take(6)
         ->values();

     $adminPerformance = User::where('role', 'admin')
         ->where('is_active', true)
         ->get()
        ->map(function ($admin) {
            $id = $admin->id;
            $base = fn($q) => $q->where(fn($s) => $s->where('assigned_to', $id)->orWhere('solved_by', $id));
            $admin->resolved_tickets   = Ticket::where($base)->whereIn('sync_status', ['resolved', 'closed', 'synced'])->count();
            $admin->pending_tickets    = Ticket::where($base)->where('sync_status', 'pending')->count();
            $admin->inprogress_tickets = Ticket::where($base)->where('sync_status', 'in_progress')->count();
            $admin->total_tickets      = $admin->resolved_tickets + $admin->pending_tickets + $admin->inprogress_tickets;
            return $admin;
        });

    return view('admin.dashboard', compact(
        'reclamationsExternes', 'clientsActifs', 'ticketsAujourdhui', 'ticketsNonResolus',
        'countClient', 'countNew', 'urgentTickets',
        'totalTickets', 'openTickets', 'inProgressTickets', 'closedTickets',
        'myResolvedTickets', 'totalAdmins', 'totalClients',
        'recentTickets', 'recentAdmins', 'recentClients',
        'ticketsByMonth', 'adminPerformance'
    ));
}

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
                $slaLimit  = $slaMap[$t->priority] ?? 8;
                $hoursOpen = (int) round($t->created_at->floatDiffInHours(now()));
                $slaUsed   = $slaLimit > 0 ? round(($hoursOpen / $slaLimit) * 100, 1) : 100;
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

        return view('admin.urgent-tickets', compact('tickets'));
    }

    public function tickets()
    {
        $glpi = app(GlpiService::class);
        try {
            $glpi->initSession();
            $allTickets = $glpi->getTransformedTickets(['range' => '0-9999', 'order' => 'DESC']);
            $glpi->killSession();
        } catch (\Exception $e) {
            \Log::error('GLPI tickets fetch failed: ' . $e->getMessage());
            $allTickets = [];
        }

        // Filter to only tickets assigned to the current admin (by GLPI user ID)
        $adminUser   = auth()->user();
        $adminGlpiId = $adminUser ? (int)$adminUser->glpi_user_id : 0;
        $collection  = collect($allTickets)->filter(fn($t) => (int)$t->assigned_to === $adminGlpiId);

        if (!$adminGlpiId) {
            \Log::warning("Admin user {$adminUser->id} has no glpi_user_id — showing 0 tickets");
            $collection = collect([]);
        }

        // Apply additional filters

        if ($status = request('status')) {
            $collection = $collection->where('sync_status', $status);
        }

        if ($priority = request('priority')) {
            $collection = $collection->where('priority', '>=', (int) $priority);
        }

        if ($clientId = request('client_id')) {
            $collection = $collection->where('user_id', (int) $clientId);
        }

        if ($search = request('search')) {
            $q = strtolower($search);
            $collection = $collection->filter(fn($t) =>
                str_contains(strtolower($t->title), $q)
                || str_contains(strtolower($t->description), $q)
                || str_contains(strtolower($t->user->name ?? ''), $q)
                || str_contains(strtolower($t->user->email ?? ''), $q)
            );
        }

        $hasAnyFilter = request()->hasAny(['search','status','date_from','date_to','client_type','priority']);
        $showAll      = request('all') === '1';

        if ($dateFrom = request('date_from')) {
            $from = \Carbon\Carbon::parse($dateFrom)->startOfDay();
            $collection = $collection->filter(fn($t) => \Carbon\Carbon::parse($t->created_at)->gte($from));
        }

        if ($dateTo = request('date_to')) {
            $to = \Carbon\Carbon::parse($dateTo)->endOfDay();
            $collection = $collection->filter(fn($t) => \Carbon\Carbon::parse($t->created_at)->lte($to));
        }

        // Counts (from the assigned-only collection before further filters)
        $assignedOnly  = $collection;
        $totalAll      = $assignedOnly->count();
        $totalPending  = $assignedOnly->filter(fn($t) => $t->sync_status === 'pending')->count();
        $totalProgress = $assignedOnly->filter(fn($t) => $t->sync_status === 'in_progress')->count();
        $totalResolved = $assignedOnly->filter(fn($t) => in_array($t->sync_status, ['resolved', 'closed']))->count();
        $totalNonClass = 0;

        // Paginate
        $page    = (int) request('page', 1);
        $perPage = 20;
        $slice   = $collection->slice(($page - 1) * $perPage, $perPage)->values();
        $tickets = new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $collection->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('admin.tickets', compact(
            'tickets', 'totalAll', 'totalPending', 'totalProgress', 'totalResolved', 'totalNonClass'
        ));
    }

    public function showTicket($id)
    {
        $glpi = app(GlpiService::class);
        try {
            $glpi->initSession();
            $glpiTicket = $glpi->getItem('Ticket', (int) $id);
            $glpiFollowups = $glpi->getFollowups((int) $id);
            $glpiMultiData = ['ticket' => $glpiTicket, 'user' => [], 'category' => []];
            $glpi->killSession();
        } catch (\Exception $e) {
            \Log::error('GLPI fetch ticket failed: ' . $e->getMessage());
            $glpiTicket = [];
            $glpiFollowups = [];
            $glpiMultiData = [];
        }

        // Build a compatible ticket object for the view
        $status = GlpiService::mapGlpiStatusToLocal((int)($glpiTicket['status'] ?? 1));
        $ticket = (object) [
            'id'          => (int) $id,
            'glpi_id'     => (int) $id,
            'title'       => $glpiTicket['name'] ?? '',
            'description' => $glpiTicket['content'] ?? '',
            'sync_status' => $status,
            'priority'    => (int)($glpiTicket['priority'] ?? 3),
            'category'    => '',
            'solution'    => $glpiTicket['solution'] ?? null,
            'created_at'  => \Carbon\Carbon::parse($glpiTicket['date_creation'] ?? $glpiTicket['date'] ?? now()),
            'updated_at'  => \Carbon\Carbon::parse($glpiTicket['date_mod'] ?? now()),
            'user_id'     => $glpiTicket['users_id_recipient'] ?? null,
            'assigned_to' => $glpiTicket['users_id_assign'] ?? null,
            'user'        => (object) ['name' => 'Client', 'email' => ''],
        ];

        $admins = User::where('role', 'admin')->where('is_active', true)->get(['id', 'name']);

        return view('admin.ticket-show', compact('ticket', 'glpiFollowups', 'admins', 'glpiMultiData'));
    }

    public function updateStatus(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validated = $request->validate([
            'sync_status' => 'required|in:pending,in_progress,resolved,closed,synced,failed,local',
            'solution'    => 'required|string|min:5',
        ]);

        $updateData = [
            'sync_status' => $validated['sync_status'],
            'solution'    => $validated['solution'],
        ];

        if (in_array($validated['sync_status'], ['resolved', 'closed', 'synced'])) {
            $updateData['solved_by']   = auth()->id();
            $updateData['resolved_at'] = now();
        }

        $ticket->update($updateData);

        \App\Models\TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id'   => auth()->id(),
            'content'   => $validated['solution'],
        ]);

        \App\Models\AuditLog::log(
            'UPDATE',
            'Tickets',
            "Réponse admin sur ticket #{$ticket->id}: {$ticket->title} → {$validated['sync_status']}"
        );

        // ── Sync GLPI ────────────────────────────────────────────────────────
        if ($ticket->glpi_ticket_id) {
            try {
                $glpi       = app(GlpiService::class);
                $glpiStatus = GlpiService::mapLocalStatus($validated['sync_status']);

                $glpi->updateTicket($ticket->glpi_ticket_id, ['status' => $glpiStatus]);
                $glpi->addFollowup($ticket->glpi_ticket_id, $validated['solution']);

                if (in_array($validated['sync_status'], ['resolved', 'closed'])) {
                    $glpi->addSolution($ticket->glpi_ticket_id, $validated['solution']);
                }

                $glpi->killSession();
            } catch (\Exception $e) {
                \Log::error('GLPI updateStatus sync error: ' . $e->getMessage());
            }
        }

        // ── Notification in-app client ───────────────────────────────────────
        $statusLabels = [
            'pending'     => 'En attente',
            'in_progress' => 'En cours de traitement',
            'resolved'    => 'Résolu ✅',
            'closed'      => 'Clôturé',
        ];
        $statusColors = [
            'pending'     => 'warning',
            'in_progress' => 'info',
            'resolved'    => 'success',
            'closed'      => 'primary',
        ];
        $statusIcons = [
            'pending'     => 'hourglass_empty',
            'in_progress' => 'autorenew',
            'resolved'    => 'check_circle',
            'closed'      => 'lock',
        ];
        $newStatus = $validated['sync_status'];

        \App\Models\Notification::sendOnce(
            $ticket->user_id,
            'ticket_answered_' . $newStatus,
            $ticket->id,
            [
                'icon'  => $statusIcons[$newStatus] ?? 'notifications',
                'color' => $statusColors[$newStatus] ?? 'primary',
                'title' => "Ticket #{$ticket->id} : " . ($statusLabels[$newStatus] ?? $newStatus),
                'body'  => \Illuminate\Support\Str::limit($validated['solution'], 80),
                'url'   => route('tickets.show', $ticket->id),
            ]
        );

        // ── Notification in-app super admin ──────────────────────────────────
        \App\Models\Notification::sendToSuperAdmins([
            'type'      => 'ticket_resolved',
            'icon'      => 'task_alt',
            'color'     => 'success',
            'title'     => "Ticket #{$ticket->id} traité par " . auth()->user()->name,
            'body'      => $ticket->title,
            'url'       => route('super-admin.decision-engine') . '?ticket=' . $ticket->id,
            'ticket_id' => $ticket->id,
        ]);

        // ── Email client (selon settings) ─────────────────────────────────────
        $isResolved         = in_array($newStatus, ['resolved', 'closed']);
        $notifyStatusChange = \App\Models\Setting::get('notify_status_change', '1') === '1';
        $notifyResolved     = \App\Models\Setting::get('notify_resolved',      '1') === '1';

        if (($isResolved && $notifyResolved) || (!$isResolved && $notifyStatusChange)) {
            $this->notifyClient($ticket);
        }

        $this->notifyTeamsResolved($ticket);

        // ── SMS client (selon toggles settings) ──────────────────────────────
        $ticket->load('user');
        $clientPhone = $ticket->user->phone ?? null;

        if ($clientPhone) {
            try {
                $sms = app(\App\Services\SmsService::class);

                if ($sms->isConfigured()) {

                    // Toggle: Ticket résolu / clôturé
                    if ($isResolved && \App\Models\Setting::get('sms_notify_resolved', '0') === '1') {
                        $sms->sendTicketReply($clientPhone, $ticket->id, $newStatus);
                    }

                    // Toggle: Changement de statut (non résolu)
                    if (!$isResolved && \App\Models\Setting::get('sms_notify_status_change', '0') === '1') {
                        $sms->sendTicketReply($clientPhone, $ticket->id, $newStatus);
                    }

                    // Toggle: Réponse de l'admin → envoie le vrai contenu de la réponse
                    $smsReplyEnabled = \App\Models\Setting::get('sms_notify_reply', '0');
                    \Log::info('SMS_REPLY_DEBUG', [
                        'enabled'    => $smsReplyEnabled,
                        'phone'      => $clientPhone,
                        'solution'   => mb_substr($validated['solution'] ?? '', 0, 50),
                        'configured' => $sms->isConfigured(),
                    ]);
                    if ($smsReplyEnabled === '1') {
                        $replyText = strip_tags($validated['solution'] ?? '');
                        if (!empty($replyText)) {
                            $adminName = auth()->user()->name ?? 'Support';
                            $smsMsg = mb_substr("{$adminName} (L2T) : {$replyText}", 0, 160);
                            $sent = $sms->send($clientPhone, $smsMsg);
                            \Log::info('SMS_REPLY_SENT', ['result' => $sent, 'msg' => $smsMsg]);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('SMS updateStatus failed: ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('admin.tickets')
            ->with('success', "Réponse envoyée au client ✅ — Ticket #{$ticket->id} mis à jour.");
    }

    protected function notifyClient(Ticket $ticket)
    {
        try {
            $gmail = app(GmailService::class);
            $ticket->load('user');
            $html = view('emails.ticket-reply', compact('ticket'))->render();
            $gmail->send(
                $ticket->user->email,
                "✅ Réponse à votre ticket #{$ticket->id} — L2T Support",
                $html
            );
        } catch (\Exception $e) {
            \Log::error('Erreur email client: ' . $e->getMessage());
        }
    }

    public function clients()
    {
        $query = User::where('role', 'client')->withCount('tickets');

        if (request('client_type')) {
            $ct = request('client_type');
            if ($ct === 'user') {
                $query->where(fn($q) => $q->where('client_type','user')->orWhereNull('client_type'));
            } elseif ($ct === 'client') {
                $query->where('client_type', 'client');
            }
        }

        if (request('search')) {
            $s = request('search');
            $query->where(fn($q) => $q->where('name','ilike',"%{$s}%")->orWhere('email','ilike',"%{$s}%"));
        }

        $clients      = $query->latest()->paginate(20);
        $activeFilter = request('client_type', 'all');

        $countAll    = \App\Models\User::where('role', 'client')->count();
        $countClient = \App\Models\User::where('role', 'client')->where('client_type', 'client')->count();
        $countNew    = \App\Models\User::where('role', 'client')->where(fn($q) => $q->where('client_type','user')->orWhereNull('client_type'))->count();

        return view('admin.clients', compact('clients', 'activeFilter', 'countAll', 'countClient', 'countNew'));
    }

    public function updateClientType(Request $request, $id)
    {
        $request->validate([
            'client_type' => 'required|in:client,user',
        ]);

        $client  = User::where('role', 'client')->findOrFail($id);
        $oldType = $client->client_type;
        $client->update(['client_type' => $request->client_type]);

        \App\Models\AuditLog::log(
            'UPDATE USER', 'Users',
            "Type client {$client->name}: {$oldType} → {$request->client_type}"
        );

        return response()->json([
            'success' => true,
            'message' => "Type mis à jour : {$request->client_type}",
        ]);
    }

    public function showClient($id)
    {
        $client  = \App\Models\User::where('role', 'client')->withCount('tickets')->findOrFail($id);
        $tickets = \App\Models\Ticket::where('user_id', $id)->latest()->paginate(15);
        return view('admin.client-detail', compact('client', 'tickets'));
    }

    public function clientTickets($id)
    {
        $client = \App\Models\User::where('role', 'client')->findOrFail($id);

        $tickets = \App\Models\Ticket::where('user_id', $id)
            ->latest()
            ->take(10)
            ->get(['id', 'title', 'sync_status', 'priority', 'created_at']);

        return response()->json([
            'tickets' => $tickets->map(fn($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'sync_status' => $t->sync_status,
                'priority'    => $t->priority,
                'created_at'  => $t->created_at->format('d/m/Y'),
            ]),
            'total'     => \App\Models\Ticket::where('user_id', $id)->count(),
            'client_id' => $id,
        ]);
    }

    public function assignTicket(Request $request, $id)
    {
        $ticket  = Ticket::findOrFail($id);
        $adminId = $request->input('admin_id');

        $admin = User::where('role', 'admin')
                     ->where('is_active', true)
                     ->find($adminId);

        if (!$admin) {
            return response()->json(['success' => false, 'error' => 'Admin introuvable'], 422);
        }

        if (!\Schema::hasColumn('tickets', 'assigned_to')) {
            return response()->json(['success' => false, 'error' => 'Migration manquante: colonne assigned_to'], 500);
        }

        $ticket->update(['assigned_to' => $admin->id]);

        \App\Models\AuditLog::log(
            'UPDATE',
            'Tickets',
            "Ticket #{$ticket->id} assigné à {$admin->name}"
        );

        if (\App\Models\Setting::get('notify_assigned', '1') === '1') {
            try {
                $gmail = app(\App\Services\GmailService::class);
                $ticket->load('user');
                $html = view('emails.ticket-assigned', compact('ticket', 'admin'))->render();
                $gmail->send($admin->email, "🎫 Ticket #{$ticket->id} vous a été assigné — L2T Support", $html);
            } catch (\Exception $e) {
                \Log::error('Erreur email assignation: ' . $e->getMessage());
            }
        }

        if ($ticket->glpi_ticket_id) {
            try {
                $glpi = app(GlpiService::class);
                $glpi->updateTicket($ticket->glpi_ticket_id, [
                    '_actors' => [
                        'assign' => [['itemtype' => 'User', 'items_id' => $admin->glpi_user_id ?? 0, 'use_notification' => 1]]
                    ]
                ]);
                $glpi->killSession();
            } catch (\Exception $e) {
                \Log::warning('GLPI assign sync failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'admin'   => $admin->name,
            'message' => "Ticket assigné à {$admin->name}",
        ]);
    }

    protected function notifyTeamsResolved(Ticket $ticket): void
    {
        try {
            $teams = app(TeamsService::class);
            if ($teams->isConfigured()) {
                $teams->notifyTicketResolved($ticket, auth()->user()->name);
            }
        } catch (\Exception $e) {
            \Log::warning('Teams notify resolved failed: ' . $e->getMessage());
        }
    }

    public function listChatOperators(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $roleFilter = strtoupper((string) $request->query('role', 'ALL'));

        $query = User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->orderBy('name');

        if (in_array($roleFilter, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $query->where('role', strtolower($roleFilter));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->get()->map(function (User $u) {
            $isActive = (bool) $u->is_active;
            return [
                'id' => (string) $u->id,
                'full_name' => $u->name,
                'email' => $u->email,
                'role' => strtoupper((string) $u->role),
                'can_reply_conversations' => $isActive,
                'can_reply_whatsapp' => $isActive,
                'is_active' => $isActive,
            ];
        })->values();

        return response()->json([
            'users' => $users,
            'total' => $users->count(),
        ]);
    }

    public function updateChatOperatorAccess(Request $request, User $user)
    {
        if (!in_array($user->role, ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'User role not allowed.'], 422);
        }

        if ((int) $user->id === (int) Auth::id()) {
            return response()->json(['message' => 'Cannot update your own access here.'], 422);
        }

        $validated = $request->validate([
            'can_reply_conversations' => 'sometimes|boolean',
            'can_reply_whatsapp' => 'sometimes|boolean',
        ]);

        $currentConv = (bool) $user->is_active;
        $currentWa = (bool) $user->is_active;
        $nextConv = array_key_exists('can_reply_conversations', $validated)
            ? (bool) $validated['can_reply_conversations']
            : $currentConv;
        $nextWa = array_key_exists('can_reply_whatsapp', $validated)
            ? (bool) $validated['can_reply_whatsapp']
            : $currentWa;

        // Laravel user table has no channel-specific flags; use active state as unified access.
        $user->is_active = $nextConv && $nextWa;
        $user->save();

        $isActive = (bool) $user->is_active;
        return response()->json([
            'id' => (string) $user->id,
            'full_name' => $user->name,
            'email' => $user->email,
            'role' => strtoupper((string) $user->role),
            'can_reply_conversations' => $isActive,
            'can_reply_whatsapp' => $isActive,
            'is_active' => $isActive,
        ]);
    }

    public function chatAccess(Request $request)
    {
        $search     = $request->get('search', '');
        $selectedId = $request->get('user');

        $query = \App\Models\User::where('role', 'client');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users        = $query->orderBy('name')->get();
        $selectedUser = $selectedId ? \App\Models\User::find($selectedId) : null;

        $messages = collect();
        if ($selectedUser) {
            $conversation = \App\Models\Conversation::where('user_id', $selectedUser->id)
                ->latest()
                ->first();

            if ($conversation) {
                $messages = \App\Models\Message::where('conversation_id', $conversation->id)
                    ->orderBy('created_at')
                    ->get();
            }
        }

        return view('admin.chat-access', compact('users', 'selectedUser', 'messages', 'search'));
    }

    public function chatShow(Request $request, $clientId)
    {
        return redirect()->route('admin.chat-access', [
            'user'   => $clientId,
            'search' => $request->get('search', ''),
        ]);
    }

    public function chat()
    {
        return view('admin.chat');
    }

    public function whatsapp()
    {
        return view('admin.whatsapp');
    }

    public function escalations()
    {
        return view('admin.escalations');
    }

    public function urgentTickets()
    {
        $adminGlpiId = auth()->user()->glpi_user_id;
        if (!$adminGlpiId) {
            return view('admin.urgent-tickets', ['tickets' => collect()]);
        }

        try {
            $glpi = app(GlpiService::class);
            $glpi->initSession();
            $allTickets = $glpi->getTransformedTickets(['range' => '0-9999', 'order' => 'DESC']);
            $glpi->killSession();
        } catch (\Exception $e) {
            \Log::error('GLPI urgent fetch failed: ' . $e->getMessage());
            return view('admin.urgent-tickets', ['tickets' => collect()]);
        }

        $myTickets = collect($allTickets)->filter(fn($t) => (int)$t->assigned_to === (int)$adminGlpiId);

        $slaMap = [
            5 => (int) \App\Models\Setting::get('sla_très haute', '4'),
            4 => (int) \App\Models\Setting::get('sla_haute',      '8'),
            3 => (int) \App\Models\Setting::get('sla_moyenne',    '24'),
            2 => (int) \App\Models\Setting::get('sla_basse',      '48'),
            1 => (int) \App\Models\Setting::get('sla_basse',      '48'),
        ];

        $tickets = $myTickets
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

        return view('admin.urgent-tickets', compact('tickets'));
    }

    public function rag()
    {
        return view('admin.rag');
    }
}