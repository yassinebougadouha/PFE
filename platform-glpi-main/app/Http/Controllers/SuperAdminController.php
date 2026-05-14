<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ticket;
use App\Models\Setting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use App\Services\GmailService;
use App\Services\GlpiService;
use Illuminate\Support\Facades\Http;

class SuperAdminController extends Controller
{
    // ==================== DASHBOARD ====================
    public function dashboard()
    {
        // Fetch ALL GLPI tickets
        $allTickets = [];
        try {
            $glpi = app(GlpiService::class);
            $glpi->initSession();
            $allTickets = $glpi->getTransformedTickets(['range' => '0-9999', 'order' => 'DESC']);
            $glpi->killSession();
        } catch (\Exception $e) {
            \Log::error('GLPI SA dashboard fetch failed: ' . $e->getMessage());
        }
        $collection = collect($allTickets);

        // CARD 1 & 2 : from local users (GLPI-independent)
        $reclamationsExternes = \App\Models\User::where('role', 'client')
            ->where('client_type', 'user')->count();
        $clientsActifs = \App\Models\User::where('role', 'client')
            ->where('is_active', true)->where('client_type', 'client')->count();

        // CARD 3 : Tickets soumis aujourd'hui
        $tz = config('app.timezone', 'Africa/Tunis');
        $startOfDay = now()->timezone($tz)->startOfDay()->utc();
        $endOfDay   = now()->timezone($tz)->endOfDay()->utc();
        $ticketsAujourdhui = $collection->filter(fn($t) =>
            \Carbon\Carbon::parse($t->created_at)->between($startOfDay, $endOfDay)
        )->count();

        // CARD 4 : Tickets non résolus
        $ticketsNonResolus = $collection->filter(fn($t) => !in_array($t->sync_status, ['resolved', 'closed']))->count();

        // Stats from GLPI
        $totalTickets      = $collection->count();
        $openTickets       = $collection->filter(fn($t) => $t->sync_status === 'pending')->count();
        $inProgressTickets = $collection->filter(fn($t) => $t->sync_status === 'in_progress')->count();
        $closedTickets     = $collection->filter(fn($t) => in_array($t->sync_status, ['resolved', 'closed']))->count();

        // Tickets urgents (priorité >= 3 en GLPI = haute/critique)
        $slaMap = [
            5 => (int) \App\Models\Setting::get('sla_très haute', '4'),
            4 => (int) \App\Models\Setting::get('sla_haute',      '8'),
            3 => (int) \App\Models\Setting::get('sla_moyenne',    '24'),
            2 => (int) \App\Models\Setting::get('sla_basse',      '48'),
            1 => (int) \App\Models\Setting::get('sla_basse',      '48'),
        ];
        $urgentTickets = $collection
            ->filter(fn($t) => in_array($t->sync_status, ['pending', 'in_progress']) && (int)$t->priority >= 4)
            ->sortBy('created_at')
            ->take(20)
            ->map(function ($t) use ($slaMap) {
                $created   = \Carbon\Carbon::parse($t->created_at);
                $hoursOpen = (int) round($created->floatDiffInHours(now()));
                $slaLimit  = $slaMap[(int)$t->priority] ?? 8;
                $slaUsed   = $slaLimit > 0 ? round(($hoursOpen / $slaLimit) * 100, 1) : 100;
                $hoursLeft = $slaLimit - $hoursOpen;
                $t->sla_limit      = $slaLimit;
                $t->sla_used_pct   = min($slaUsed, 100);
                $t->sla_hours_open = $hoursOpen;
                $t->sla_hours_left = $hoursLeft;
                $t->sla_breached   = $hoursLeft < 0;
                $t->sla_risk       = !($hoursLeft < 0) && $slaUsed >= 80;
                $t->sla_ratio      = $slaLimit > 0 ? $hoursOpen / $slaLimit : 999;
                return $t;
            })
            ->sortByDesc('sla_ratio')
            ->take(8)
            ->values();

        // Local user data
        $totalUsers   = \App\Models\User::count();
        $totalAdmins  = \App\Models\User::where('role', 'admin')->count();
        $totalClients = \App\Models\User::where('role', 'client')->count();

        $recentUsers   = \App\Models\User::latest()->take(5)->get();
        $recentAdmins  = \App\Models\User::where('role', 'admin')->latest()->take(5)->get();
        $recentClients = \App\Models\User::where('role', 'client')
                             ->withCount('tickets')
                             ->latest()
                             ->take(5)
                             ->get();

        $recentTickets = $collection->sortByDesc('created_at')->take(8)->values();

        $ticketsByMonth = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->timezone('Africa/Tunis')->subMonths($i);
            $count = $collection->filter(fn($t) =>
                \Carbon\Carbon::parse($t->created_at)->year === (int)$month->year
                && \Carbon\Carbon::parse($t->created_at)->month === (int)$month->month
            )->count();
            $ticketsByMonth[] = [
                'month' => $month->locale('fr')->isoFormat('MMM YYYY'),
                'count' => $count,
            ];
        }

        // Admin performance (still from GLPI, grouped by assigned_to)
        $adminUsers     = \App\Models\User::where('role', 'admin')->where('is_active', true)->get();
        $adminPerformance = $adminUsers->map(function ($admin) use ($collection) {
            $glpiId = (int)$admin->glpi_user_id;
            $myTix = $collection->filter(fn($t) => (int)$t->assigned_to === $glpiId);
            $resolved    = $myTix->filter(fn($t) => in_array($t->sync_status, ['resolved', 'closed']))->count();
            $pending     = $myTix->filter(fn($t) => $t->sync_status === 'pending')->count();
            $inprogress  = $myTix->filter(fn($t) => $t->sync_status === 'in_progress')->count();
            $admin->resolved_tickets   = $resolved;
            $admin->pending_tickets    = $pending;
            $admin->inprogress_tickets = $inprogress;
            $admin->total_tickets      = $resolved + $pending + $inprogress;
            return $admin;
        });

        return view('super-admin.dashboard', compact(
            // Cards
            'reclamationsExternes', 'clientsActifs', 'ticketsAujourdhui', 'ticketsNonResolus',
            'urgentTickets',
            // Données existantes
            'totalUsers', 'totalAdmins', 'totalClients', 'totalTickets',
            'openTickets', 'inProgressTickets', 'closedTickets',
            'recentUsers', 'recentAdmins', 'recentClients',
            'recentTickets', 'ticketsByMonth', 'adminPerformance'
        ));
    }

    // ==================== GLPI STATS (AJAX) ====================
    public function glpiStats()
    {
        try {
            $glpi = app(GlpiService::class);
            $glpi->initSession();
            $config = $glpi->getGlpiConfig();

            $stats = [
                'new'      => $glpi->searchTicketsByStatus(1)['totalcount'] ?? 0,
                'assigned' => $glpi->searchTicketsByStatus(2)['totalcount'] ?? 0,
                'planned'  => $glpi->searchTicketsByStatus(3)['totalcount'] ?? 0,
                'waiting'  => $glpi->searchTicketsByStatus(4)['totalcount'] ?? 0,
                'solved'   => $glpi->searchTicketsByStatus(5)['totalcount'] ?? 0,
                'closed'   => $glpi->searchTicketsByStatus(6)['totalcount'] ?? 0,
                'version'  => $config['glpi_version'] ?? 'N/A',
            ];

            $glpiUsers = $glpi->getAllItems('User', ['range' => '0-999']);
            $stats['total_users'] = count($glpiUsers);

            $glpiCats = $glpi->getAllItems('ITILCategory', ['range' => '0-999']);
            $stats['categories'] = count($glpiCats);

            $glpi->killSession();

            return response()->json(['success' => true, 'data' => $stats]);

        } catch (\Exception $e) {
            \Log::warning('GLPI stats failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ==================== ADMINS ====================
    public function admins()
    {
        $admins = User::where('role', 'admin')->oldest()->get();
        return view('super-admin.admins', compact('admins'));
    }

    public function showAdmin($id)
    {
        $admin = User::where('role', 'admin')->findOrFail($id);

        $assignedTickets = \App\Models\Ticket::with('user')
            ->where('assigned_to', $admin->id)
            ->latest()
            ->paginate(15);

        $stats = [
            'total'    => \App\Models\Ticket::where('assigned_to', $admin->id)->count(),
            'pending'  => \App\Models\Ticket::where('assigned_to', $admin->id)->where('sync_status', 'pending')->count(),
            'inprog'   => \App\Models\Ticket::where('assigned_to', $admin->id)->where('sync_status', 'in_progress')->count(),
            'resolved' => \App\Models\Ticket::where('assigned_to', $admin->id)->whereIn('sync_status', ['resolved', 'closed', 'synced'])->count(),
        ];

        $logs = \App\Models\AuditLog::where('user_id', $admin->id)
            ->latest()
            ->take(10)
            ->get();

        return view('super-admin.admin-detail', compact('admin', 'assignedTickets', 'stats', 'logs'));
    }

    public function createAdmin()
    {
        return view('super-admin.create-admin');
    }

    public function storeAdmin(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $plainPassword = $request->password;

        $admin = User::create([
            'name'                => $request->name,
            'email'               => $request->email,
            'password'            => Hash::make($plainPassword),
            'role'                => 'admin',
            'is_active'           => true,
            'must_change_password'=> true,
            'profile_completed'   => false,
        ]);

        AuditLog::log('CREATE', 'Users', "Création admin: {$request->name} ({$request->email})");

        \App\Models\Notification::sendToSuperAdmins([
            'type'  => 'new_admin',
            'icon'  => 'admin_panel_settings',
            'color' => 'primary',
            'title' => "Nouvel admin créé : {$admin->name}",
            'body'  => $admin->email,
            'url'   => route('super-admin.admins.show', $admin->id),
        ]);

        try {
            $gmail = app(GmailService::class);
            $html  = view('emails.admin-created', [
                'name'     => $admin->name,
                'email'    => $admin->email,
                'password' => $plainPassword,
            ])->render();

            $gmail->send(
                $admin->email,
                '🎉 Votre compte Administrateur L2T Support a été créé',
                $html
            );
        } catch (\Exception $e) {
            \Log::error('Erreur email création admin: ' . $e->getMessage());
        }

        return redirect()->route('super-admin.admins')
                         ->with('success', "Admin créé avec succès ! Email envoyé à {$admin->email} avec les identifiants ✅");
    }

    public function toggleAdmin($id)
    {
        $admin            = User::where('role', 'admin')->findOrFail($id);
        $admin->is_active = !$admin->is_active;
        $admin->save();

        $status = $admin->is_active ? 'activé' : 'désactivé';
        AuditLog::log('TOGGLE USER', 'Users', "Admin {$admin->name} → {$status}");

        \App\Models\Notification::sendToSuperAdmins([
            'type'  => 'admin_status_changed',
            'icon'  => $admin->is_active ? 'check_circle' : 'block',
            'color' => $admin->is_active ? 'success' : 'danger',
            'title' => "Admin {$admin->name} → {$status}",
            'body'  => $admin->email,
            'url'   => route('super-admin.admins.show', $admin->id),
        ]);

        try {
            $gmail = app(GmailService::class);
            $html  = view('emails.admin-status-changed', [
                'name'     => $admin->name,
                'isActive' => $admin->is_active,
            ])->render();

            $gmail->send(
                $admin->email,
                $admin->is_active
                    ? '✅ Votre compte L2T Support a été activé'
                    : '🚫 Votre compte L2T Support a été désactivé',
                $html
            );
        } catch (\Exception $e) {
            \Log::error('Erreur email toggle admin: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Admin {$status} avec succès ! Email de notification envoyé ✅");
    }

    public function deleteAdmin($id)
    {
        $admin = User::where('role', 'admin')->findOrFail($id);

        try {
            $gmail = app(GmailService::class);
            $html  = view('emails.admin-deleted', [
                'name'  => $admin->name,
                'email' => $admin->email,
            ])->render();

            $gmail->send(
                $admin->email,
                '🗑️ Votre compte L2T Support a été supprimé',
                $html
            );
        } catch (\Exception $e) {
            \Log::error('Erreur email suppression admin: ' . $e->getMessage());
        }

        AuditLog::log('DELETE USER', 'Users', "Suppression admin: {$admin->name} ({$admin->email})", 'warning');
        $admin->delete();

        return redirect()->back()->with('success', 'Admin supprimé avec succès ! Email de notification envoyé ✅');
    }

    // ==================== CLIENTS ====================
    public function clients()
    {
        $query = User::where('role', 'client')->withCount('tickets');

        // Filter by client_type: 'client' ou 'user'
        if (request('client_type')) {
            $query->where('client_type', request('client_type'));
        }

        // Filter by active status
        if (request('active') === '1') {
            $query->where('is_active', true);
        } elseif (request('active') === '0') {
            $query->where('is_active', false);
        }

        // Search
        if (request('search')) {
            $q = request('search');
            $query->where(function($sub) use ($q) {
                $sub->where('name',  'ilike', "%{$q}%")
                    ->orWhere('email', 'ilike', "%{$q}%");
            });
        }

        $clients       = $query->oldest()->paginate(20);
        $totalClients  = User::where('role', 'client')->count();
        $activeClients = User::where('role', 'client')->where('is_active', true)->count();

        // Stats by client_type (2 types seulement)
        $countClient = User::where('role', 'client')->where('client_type', 'client')->count();
        $countUser   = User::where('role', 'client')->where('client_type', 'user')->count();
        $countNull   = User::where('role', 'client')->whereNull('client_type')->count();

        return view('super-admin.clients', compact(
            'clients', 'totalClients', 'activeClients',
            'countClient', 'countUser', 'countNull'
        ));
    }

    public function showClient($id)
    {
        $client  = User::where('role', 'client')->withCount('tickets')->findOrFail($id);
        $tickets = \App\Models\Ticket::where('user_id', $id)
            ->latest()
            ->paginate(15);
        return view('super-admin.client-detail', compact('client', 'tickets'));
    }

    // AJAX: update client_type — seulement 'client' ou 'user'
    public function updateClientType(\Illuminate\Http\Request $request, $id)
    {
        $client = User::where('role', 'client')->findOrFail($id);
        $request->validate(['client_type' => 'required|in:client,user']);
        $oldType = $client->client_type;
        $client->update(['client_type' => $request->client_type]);

        AuditLog::log('UPDATE USER', 'Users',
            "Type client {$client->name}: {$oldType} → {$request->client_type}");

        return response()->json([
            'success'     => true,
            'ok'          => true,
            'client_type' => $request->client_type,
            'message'     => "Type mis à jour : {$request->client_type}",
        ]);
    }

    public function toggleClient($id)
    {
        $client            = User::where('role', 'client')->findOrFail($id);
        $client->is_active = !$client->is_active;
        $client->save();

        $status = $client->is_active ? 'activé' : 'désactivé';
        AuditLog::log('TOGGLE USER', 'Users', "Client {$client->name} → {$status}");

        \App\Models\Notification::sendToSuperAdmins([
            'type'  => 'client_status_changed',
            'icon'  => $client->is_active ? 'how_to_reg' : 'person_off',
            'color' => $client->is_active ? 'success' : 'warning',
            'title' => "Client {$client->name} → {$status}",
            'body'  => $client->email,
            'url'   => route('super-admin.clients.show', $client->id),
        ]);

        return redirect()->back()->with('success', "Client {$status} avec succès!");
    }

    // AJAX: tickets d'un client pour le drawer
    public function clientTickets($id)
    {
        $client = User::where('role', 'client')->findOrFail($id);

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

    public function deleteClient($id)
    {
        $client = User::where('role', 'client')->findOrFail($id);
        AuditLog::log('DELETE USER', 'Users', "Suppression client: {$client->name} ({$client->email})", 'warning');
        $client->tickets()->delete();
        $client->delete();

        return redirect()->route('super-admin.clients')->with('success', 'Client et ses tickets supprimés avec succès!');
    }

    // ==================== TICKETS ====================
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

        $collection = collect($allTickets);

        $status = request('status');
        if ($status === 'resolved') {
            $collection = $collection->filter(fn($t) => in_array($t->sync_status, ['resolved', 'closed']));
        } elseif ($status) {
            $collection = $collection->where('sync_status', $status);
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

        $hasAnyFilter = request()->hasAny(['search','status','date_from','date_to','priority','client_type']);
        $showAll      = request('all') === '1';

        if ($dateFrom = request('date_from')) {
            $from = \Carbon\Carbon::parse($dateFrom)->startOfDay();
            $collection = $collection->filter(fn($t) => \Carbon\Carbon::parse($t->created_at)->gte($from));
        } elseif (!$showAll && !$hasAnyFilter) {
            $today = now()->startOfDay();
            $collection = $collection->filter(fn($t) => \Carbon\Carbon::parse($t->created_at)->gte($today));
        }

        if ($dateTo = request('date_to')) {
            $to = \Carbon\Carbon::parse($dateTo)->endOfDay();
            $collection = $collection->filter(fn($t) => \Carbon\Carbon::parse($t->created_at)->lte($to));
        }

        if ($p = request('priority')) {
            $collection = $collection->where('priority', (int) $p);
        }

        // Totals
        $totalTickets      = count($allTickets);
        $openTickets       = count(array_filter($allTickets, fn($t) => $t->sync_status === 'pending'));
        $inProgressTickets = count(array_filter($allTickets, fn($t) => $t->sync_status === 'in_progress'));
        $closedTickets     = count(array_filter($allTickets, fn($t) => in_array($t->sync_status, ['resolved', 'closed'])));
        $totalNonClass     = 0;

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

        return view('super-admin.tickets', compact(
            'tickets', 'totalTickets', 'openTickets', 'inProgressTickets', 'closedTickets', 'totalNonClass'
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

    public function updateTicketStatus(Request $request, $id)
    {
        $ticket    = Ticket::findOrFail($id);
        $oldStatus = $ticket->sync_status;
        $ticket->sync_status = $request->status;
        $ticket->save();

        AuditLog::log('UPDATE TICKET', 'Tickets', "Ticket #{$ticket->id} statut: {$oldStatus} → {$request->status}");

        return redirect()->back()->with('success', 'Statut mis à jour!');
    }

    public function deleteTicket($id)
    {
        $ticket = Ticket::findOrFail($id);
        AuditLog::log('DELETE TICKET', 'Tickets', "Suppression ticket #{$ticket->id}: {$ticket->title}", 'warning');
        $ticket->delete();

        return redirect()->route('super-admin.tickets')
                         ->with('success', 'Ticket supprimé définitivement!');
    }

    // ==================== SETTINGS ====================
    public function settings()
    {
        $settings = Setting::getAllAsArray();
        return view('super-admin.settings', compact('settings'));
    }

    public function settingsUpdate(Request $request)
    {
        $section = $request->input('_section');

        switch ($section) {

            case 'general':
                $request->validate([
                    'app_name'      => 'required|string|max:100',
                    'support_email' => 'required|email',
                ]);
                Setting::set('app_name',      $request->app_name);
                Setting::set('support_email', $request->support_email);
                Setting::set('description',   $request->description ?? '');
                Setting::set('locale',        $request->locale ?? 'fr');
                Setting::set('timezone',      $request->timezone ?? 'Africa/Tunis');
                AuditLog::log('UPDATE SETTINGS', 'Settings', "Paramètres généraux mis à jour");
                break;

            case 'logo':
                if ($request->hasFile('logo')) {
                    $request->validate(['logo' => 'required|image|mimes:png,jpg,jpeg|max:2048']);
                    $path = $request->file('logo')->storeAs('logos', 'logo-l2t.png', 'public');
                    Setting::set('logo_path', 'storage/' . $path);
                    $request->file('logo')->move(public_path('assets/img'), 'logo-l2t.png');
                    AuditLog::log('UPDATE SETTINGS', 'Settings', "Logo application mis à jour");
                }
                break;

            case 'branding':
                Setting::set('primary_color',   $request->primary_color   ?? '#667eea');
                Setting::set('secondary_color', $request->secondary_color ?? '#764ba2');
                Setting::set('theme_mode',      $request->theme_mode      ?? 'light');
                Setting::set('sidebar_size',    $request->sidebar_size    ?? 'normal');
                Setting::set('ticket_label',    $request->ticket_label    ?? 'Ticket');
                AuditLog::log('UPDATE SETTINGS', 'Settings', "Branding/UI mis à jour");
                break;

            case 'tickets':
                Setting::set('auto_assignment',        $request->has('auto_assignment') ? '1' : '0');
                Setting::set('auto_assignment_method', $request->auto_assignment_method ?? 'Round-robin');
                Setting::set('allow_client_close',     $request->has('allow_client_close') ? '1' : '0');
                Setting::set('sla_très haute',         ($request->input('sla_très haute', '4')) . 'h');
                Setting::set('sla_haute',              ($request->input('sla_haute', '8')) . 'h');
                Setting::set('sla_moyenne',            ($request->input('sla_moyenne', '24')) . 'h');
                Setting::set('sla_basse',              ($request->input('sla_basse', '48')) . 'h');
                AuditLog::log('UPDATE SETTINGS', 'Settings', "Configuration tickets mise à jour");
                break;

            case 'security':
                $request->validate([
                    'min_password_length' => 'required|integer|min:6|max:32',
                    'session_timeout'     => 'required|integer|min:5',
                    'max_login_attempts'  => 'required|integer|min:3',
                ]);
                Setting::set('min_password_length',        $request->min_password_length);
                Setting::set('session_timeout',            $request->session_timeout);
                Setting::set('max_login_attempts',         $request->max_login_attempts);
                Setting::set('password_complexity',        $request->has('password_complexity') ? '1' : '0');
                Setting::set('allow_registration',         $request->has('allow_registration') ? '1' : '0');
                Setting::set('require_email_verification', $request->has('require_email_verification') ? '1' : '0');
                Setting::set('two_factor_auth',            $request->has('two_factor_auth') ? '1' : '0');
                AuditLog::log('UPDATE SETTINGS', 'Settings', "Paramètres sécurité mis à jour");
                break;

            case 'notifications':
                Setting::set('mail_mode',            $request->mail_mode           ?? 'gmail');
                Setting::set('gmail_from_email',     $request->gmail_from_email    ?? '');
                Setting::set('smtp_from_name',       $request->smtp_from_name      ?? 'L2T Support');
                Setting::set('smtp_username',        $request->gmail_from_email    ?? '');
                Setting::set('smtp_from_email',      $request->gmail_from_email    ?? $request->smtp_from_email_field ?? '');
                Setting::set('smtp_host',            $request->smtp_host           ?? 'smtp.gmail.com');
                Setting::set('smtp_port',            $request->smtp_port           ?? '587');
                Setting::set('smtp_encryption',      $request->smtp_encryption     ?? 'tls');
                Setting::set('smtp_username',        $request->smtp_username       ?? $request->gmail_from_email ?? '');
                if ($request->filled('smtp_password')) {
                    Setting::set('smtp_password', encrypt($request->smtp_password));
                }
                if ($request->filled('gmail_client_id')) {
                    Setting::set('gmail_client_id', $request->gmail_client_id);
                }
                if ($request->filled('gmail_client_secret')) {
                    Setting::set('gmail_client_secret', encrypt($request->gmail_client_secret));
                }
                if ($request->filled('gmail_refresh_token')) {
                    Setting::set('gmail_refresh_token', encrypt($request->gmail_refresh_token));
                }
                Setting::set('notify_new_ticket',    $request->has('notify_new_ticket')    ? '1' : '0');
                Setting::set('notify_status_change', $request->has('notify_status_change') ? '1' : '0');
                Setting::set('notify_new_comment',   $request->has('notify_new_comment')   ? '1' : '0');
                Setting::set('notify_assigned',      $request->has('notify_assigned')      ? '1' : '0');
                Setting::set('notify_overdue',       $request->has('notify_overdue')       ? '1' : '0');
                Setting::set('notify_resolved',      $request->has('notify_resolved')      ? '1' : '0');
                AuditLog::log('UPDATE SETTINGS', 'Settings', "Configuration SMTP/notifications mise à jour");
                break;

            case 'sms':
                Setting::set('sms_api_url',        $request->sms_api_url       ?? '');
                Setting::set('sms_api_key',        $request->sms_api_key       ?? '');
                Setting::set('sms_sender',         $request->sms_sender        ?? 'L2T');
                Setting::set('sms_api_type',       $request->sms_api_type      ?? 'get');
                Setting::set('sms_max_chars',      (string)(max(50, min(160, (int)($request->sms_max_chars ?? 150)))));
                Setting::set('sms_fct',            $request->sms_fct           ?? 'sms');
                Setting::set('sms_param_fct',      $request->sms_param_fct     ?? 'fct');
                Setting::set('sms_param_key',      $request->sms_param_key     ?? 'key');
                Setting::set('sms_param_sender',   $request->sms_param_sender  ?? 'sender');
                Setting::set('sms_param_mobile',   $request->sms_param_mobile  ?? 'mobile');
                Setting::set('sms_param_msg',      $request->sms_param_msg     ?? 'sms');
                Setting::set('sms_notify_new_ticket',    $request->has('sms_notify_new_ticket')    ? '1' : '0');
                Setting::set('sms_notify_status_change', $request->has('sms_notify_status_change') ? '1' : '0');
                Setting::set('sms_notify_reply',         $request->has('sms_notify_reply')         ? '1' : '0');
                Setting::set('sms_notify_resolved',      $request->has('sms_notify_resolved')      ? '1' : '0');
                AuditLog::log('UPDATE SETTINGS', 'Settings', 'Configuration SMS L2T mise à jour');
                return redirect()->route('super-admin.settings', ['tab' => 'sms'])->with('success', 'Configuration SMS enregistrée ✓');

            case 'cache':
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('view:clear');
                Artisan::call('route:clear');
                AuditLog::log('CACHE CLEAR', 'System', 'Cache application vidé');
                return redirect()->route('super-admin.settings', ['tab' => 'system'])->with('success', 'Cache vidé avec succès ✓');

            case 'optimize':
                Artisan::call('optimize');
                AuditLog::log('OPTIMIZE', 'System', 'Application optimisée');
                return redirect()->route('super-admin.settings', ['tab' => 'system'])->with('success', 'Application optimisée ✓');

            case 'glpi':
                Setting::set('glpi_url', $request->glpi_url ?? '');
                Setting::set('glpi_app_token', $request->glpi_app_token ?? '');
                if ($request->filled('glpi_user_token')) {
                    Setting::set('glpi_user_token', encrypt($request->glpi_user_token));
                }
                if ($request->filled('glpi_url')) {
                    \Illuminate\Support\Facades\Config::set('services.glpi.url', $request->glpi_url);
                }
                if ($request->filled('glpi_app_token')) {
                    \Illuminate\Support\Facades\Config::set('services.glpi.app_token', $request->glpi_app_token);
                }
                AuditLog::log('UPDATE', 'Settings', 'Mise à jour config GLPI');
                return redirect()->route('super-admin.settings', ['tab' => 'glpi'])->with('success', 'Configuration GLPI enregistrée ✓');

            case 'teams_routing':
                Setting::set('teams_routing_method', $request->teams_routing_method ?? 'general');

                if ($request->filled('teams_webhook_url')) {
                    Setting::set('teams_webhook_url', $request->teams_webhook_url);
                }

                if ($request->has('category_admin')) {
                    foreach ($request->category_admin as $category => $adminId) {
                        if ($adminId) {
                            \Illuminate\Support\Facades\DB::table('category_admin_mappings')
                                ->updateOrInsert(
                                    ['category' => $category],
                                    [
                                        'admin_id'   => $adminId,
                                        'updated_at' => now(),
                                        'created_at' => now(),
                                    ]
                                );
                        } else {
                            \Illuminate\Support\Facades\DB::table('category_admin_mappings')
                                ->where('category', $category)->delete();
                        }
                    }
                }

                AuditLog::log('UPDATE SETTINGS', 'Settings', 'Routing Teams mis à jour');
                break;

            case 'maintenance':
                if (app()->isDownForMaintenance()) {
                    Artisan::call('up');
                    AuditLog::log('MAINTENANCE', 'System', 'Mode maintenance désactivé');
                    return redirect()->route('super-admin.settings', ['tab' => 'system'])->with('success', 'Mode maintenance désactivé ✓');
                } else {
                    Artisan::call('down', ['--message' => 'Maintenance en cours...', '--retry' => '60']);
                    AuditLog::log('MAINTENANCE', 'System', 'Mode maintenance activé', 'warning');
                    return redirect()->route('super-admin.settings', ['tab' => 'system'])->with('success', 'Mode maintenance activé ✓');
                }
        }

        return redirect()->route('super-admin.settings', ['tab' => $section])->with('success', 'Paramètres enregistrés avec succès ✓');
    }

    // ==================== LOGS ====================
    public function logs()
    {
        $query = AuditLog::latest();

        if (request('search')) {
            $q = request('search');
            $query->where(function ($sub) use ($q) {
                $sub->where('user_name',    'ilike', "%{$q}%")
                    ->orWhere('description', 'ilike', "%{$q}%")
                    ->orWhere('action',      'ilike', "%{$q}%");
            });
        }
        if (request('date_from')) $query->whereDate('created_at', '>=', request('date_from'));
        if (request('date_to'))   $query->whereDate('created_at', '<=', request('date_to'));
        if (request('action'))    $query->where('action', request('action'));
        if (request('module'))    $query->where('module', request('module'));
        if (request('status'))    $query->where('status', request('status'));

        $logs          = $query->paginate(25);
        $totalLogs     = AuditLog::count();
        $failedLogs    = AuditLog::where('status', 'failed')->count();
        $todayLogs     = AuditLog::whereDate('created_at', today())->count();
        $statsByAction = AuditLog::selectRaw('action, count(*) as total')
                                  ->groupBy('action')
                                  ->pluck('total', 'action')
                                  ->toArray();
        $actions       = AuditLog::distinct()->pluck('action')->sort()->values();

        return view('super-admin.logs', compact(
            'logs', 'totalLogs', 'failedLogs', 'todayLogs', 'statsByAction', 'actions'
        ));
    }

    public function exportLogs()
    {
        $format = request('format', 'csv');
        $query  = AuditLog::latest();

        if (request('search')) {
            $q = request('search');
            $query->where(function ($sub) use ($q) {
                $sub->where('user_name',    'ilike', "%{$q}%")
                    ->orWhere('description', 'ilike', "%{$q}%");
            });
        }
        if (request('date_from')) $query->whereDate('created_at', '>=', request('date_from'));
        if (request('date_to'))   $query->whereDate('created_at', '<=', request('date_to'));
        if (request('action'))    $query->where('action', request('action'));
        if (request('module'))    $query->where('module', request('module'));
        if (request('status'))    $query->where('status', request('status'));

        $logs = $query->get();
        AuditLog::log('EXPORT', 'System', "Export logs {$format} — {$logs->count()} entrées");

        if ($format === 'csv') {
            $filename = 'audit_logs_' . now()->format('Y-m-d_H-i') . '.csv';
            $headers  = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];
            $callback = function () use ($logs) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($file, ['#', 'Date', 'Heure', 'Utilisateur', 'Rôle', 'Action', 'Module', 'Description', 'IP', 'Statut']);
                foreach ($logs as $log) {
                    fputcsv($file, [
                        $log->id,
                        $log->created_at->format('d/m/Y'),
                        $log->created_at->format('H:i:s'),
                        $log->user_name  ?? 'Système',
                        $log->user_role  ?? '-',
                        $log->action,
                        $log->module,
                        $log->description,
                        $log->ip_address ?? '-',
                        $log->status,
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }

        return view('super-admin.logs-pdf', compact('logs'));
    }

    public function clearLogs()
    {
        $count = AuditLog::count();
        AuditLog::log('DELETE', 'System', "Suppression de tous les logs ({$count} entrées)", 'warning');
        AuditLog::truncate();

        return redirect()->route('super-admin.logs')->with('success', 'Tous les logs ont été supprimés ✓');
    }

    public function syncCategories()
    {
        try {
            $glpi = app(\App\Services\GlpiService::class);
            $cats = $glpi->getCategories();
            $glpi->killSession();

            $synced = 0;
            foreach ($cats as $cat) {
                \DB::table('itil_categories')->updateOrInsert(
                    ['glpi_id' => $cat['id']],
                    ['name' => $cat['name'], 'updated_at' => now(), 'created_at' => now()]
                );
                $synced++;
            }

            return response()->json(['success' => true, 'synced' => $synced]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // ==================== SYNC USERS GLPI ====================
    public function syncUsers()
    {
        try {
            $glpi  = app(\App\Services\GlpiService::class);
            $users = \App\Models\User::whereIn('role', ['admin', 'client'])->get();
            $ok    = 0;
            $fail  = 0;
            $results = [];

            foreach ($users as $user) {
                $glpiId = $glpi->syncUser($user);
                if ($glpiId) {
                    $ok++;
                    $results[] = ['email' => $user->email, 'status' => 'ok', 'glpi_id' => $glpiId];
                } else {
                    $fail++;
                    $results[] = ['email' => $user->email, 'status' => 'fail'];
                }
            }

            $glpi->killSession();

            \App\Models\AuditLog::log('SYNC', 'Users', "Sync GLPI: {$ok} ok, {$fail} echecs");

            return response()->json([
                'success' => true,
                'synced'  => $ok,
                'failed'  => $fail,
                'results' => $results,
                'message' => "{$ok} utilisateur(s) synchronisé(s) avec GLPI, {$fail} échec(s).",
            ]);

        } catch (\Exception $e) {
            \Log::error('syncUsers failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ==================== IMPORT USERS FROM GLPI ====================
    public function importUsersFromGlpi(\Illuminate\Http\Request $request)
    {
        try {
            $role = $request->input('role', 'client');
            if (!in_array($role, ['client', 'admin'])) $role = 'client';

            $glpi    = app(\App\Services\GlpiService::class);
            $results = $glpi->importUsersFromGlpi($role);
            $glpi->killSession();

            $created = count(array_filter($results, fn($r) => $r['status'] === 'created'));
            $exists  = count(array_filter($results, fn($r) => $r['status'] === 'exists'));
            $skipped = count(array_filter($results, fn($r) => in_array($r['status'], ['skip','error'])));

            \App\Models\AuditLog::log('IMPORT', 'Users',
                "Import GLPI: {$created} crees, {$exists} existants, {$skipped} ignores"
            );

            return response()->json([
                'success' => true,
                'created' => $created,
                'exists'  => $exists,
                'skipped' => $skipped,
                'results' => $results,
                'message' => "{$created} utilisateur(s) importé(s), {$exists} déjà existant(s), {$skipped} ignoré(s).",
            ]);

        } catch (\Exception $e) {
            \Log::error('importUsersFromGlpi failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function testSms(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'phone'   => 'required|string|min:8|max:20',
            'message' => 'nullable|string|max:160',
        ]);
        $sms     = app(\App\Services\SmsService::class);
        $phone   = $sms->normalizePhone($request->phone);
        $message = $request->input('message') ?? $request->json('message') ?? '';
        $result  = $sms->testConnection($phone, $message);
        return response()->json($result);
    }

    public function listChatOperators(\Illuminate\Http\Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $roleFilter = strtoupper((string) $request->query('role', 'ALL'));

        $query = \App\Models\User::query()
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

        $users = $query->get()->map(function (\App\Models\User $u) {
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

    public function updateChatOperatorAccess(\Illuminate\Http\Request $request, \App\Models\User $user)
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

    public function chatAccess(\Illuminate\Http\Request $request)
    {
        $type           = $request->input('type', 'all');
        $search         = $request->input('search', '');
        $selectedUserId = $request->input('user');

        $usersQuery = \App\Models\User::whereIn('role', ['admin', 'client']);
        if (in_array($type, ['admin', 'client'])) {
            $usersQuery->where('role', $type);
        }
        if ($search) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $users = $usersQuery->orderBy('name')->get();

        $selectedUser = $selectedUserId ? \App\Models\User::find($selectedUserId) : null;
        $messages     = collect();

        if ($selectedUser && class_exists(\App\Models\ChatMessage::class)) {
            $messages = \App\Models\ChatMessage::where('user_id', $selectedUser->id)
                ->orderBy('created_at')->get();
        }

        return view('super-admin.chat-access', compact(
            'users', 'selectedUser', 'messages', 'type', 'search'
        ));
    }

    public function chatShow(\Illuminate\Http\Request $request, $userId)
    {
        $type   = $request->input('type', 'all');
        $search = $request->input('search', '');

        $usersQuery = \App\Models\User::whereIn('role', ['admin', 'client']);
        if (in_array($type, ['admin', 'client'])) {
            $usersQuery->where('role', $type);
        }
        if ($search) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name',  'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        $users = $usersQuery->orderBy('name')->get();

        $selectedUser = \App\Models\User::findOrFail($userId);
        $messages     = collect();

        if (class_exists(\App\Models\ChatMessage::class)) {
            $messages = \App\Models\ChatMessage::where('user_id', $selectedUser->id)
                ->orderBy('created_at')->get();
        }

        return view('super-admin.chat-access', compact(
            'users', 'selectedUser', 'messages', 'type', 'search'
        ));
    }

    public function grantChatAccess(\Illuminate\Http\Request $r)  { return response()->json(['ok' => true]); }
    public function revokeChatAccess(\Illuminate\Http\Request $r) { return response()->json(['ok' => true]); }
    public function listChatAccess(\Illuminate\Http\Request $r)   { return response()->json(['users' => []]); }

    public function whatsapp()
    {
        return view('admin.whatsapp');
    }

    public function escalations()
    {
        return view('admin.escalations');
    }

    public function conversations()
    {
        $channel = request('channel', '');
        $convs   = [];
        $chatCount = 0;
        $wappCount = 0;
        try {
            $resp = $this->apiClient()->timeout(15)->get(
                $this->apiUrl('conversations'),
                ['skip' => 0, 'limit' => 200]
            );
            $body = $resp->json();
            $all = is_array($body) ? ($body['conversations'] ?? $body) : [];

            foreach ($all as $c) {
                $ch = strtolower($c['channel'] ?? $c['channel_source'] ?? '');
                if (str_contains($ch, 'whatsapp')) $wappCount++;
                else $chatCount++;
            }

            if ($channel === 'whatsapp') {
                $convs = array_filter($all, fn($c) =>
                    str_contains(strtolower($c['channel'] ?? $c['channel_source'] ?? ''), 'whatsapp')
                );
            } elseif ($channel === 'chat') {
                $convs = array_filter($all, fn($c) =>
                    !str_contains(strtolower($c['channel'] ?? $c['channel_source'] ?? ''), 'whatsapp')
                );
            } else {
                $convs = $all;
            }
            $convs = array_slice(array_values($convs), 0, 50);
        } catch (\Exception $e) {
            \Log::error('Conversations fetch failed: ' . $e->getMessage());
        }
        return view('super-admin.conversations', compact('convs', 'channel', 'chatCount', 'wappCount'));
    }

    public function conversationDetail($id)
    {
        $messages = [];
        $conv = null;
        $convs = [];
        try {
            $conv = $this->apiClient()->timeout(15)->get(
                $this->apiUrl("conversations/{$id}")
            )->json();

            $msgBody = $this->apiClient()->timeout(15)->get(
                $this->apiUrl("conversations/{$id}/messages"),
                ['skip' => 0, 'limit' => 100]
            )->json();
            $messages = is_array($msgBody) ? (isset($msgBody[0]) ? $msgBody : ($msgBody['messages'] ?? [])) : [];

            $listBody = $this->apiClient()->timeout(15)->get(
                $this->apiUrl('conversations'),
                ['skip' => 0, 'limit' => 50]
            )->json();
            $convs = is_array($listBody) ? ($listBody['conversations'] ?? $listBody) : [];
            $convs = array_slice(array_values($convs), 0, 50);
        } catch (\Exception $e) {
            \Log::error('Conversation detail fetch failed: ' . $e->getMessage());
        }
        return view('super-admin.conversation-detail', compact('conv', 'messages', 'id', 'convs'));
    }

    public function rag()
    {
        return view('super-admin.rag');
    }

    /**
     * RAG API Proxies
     */
    public function ragStats()
    {
        return $this->jsonProxy('GET', '/rag/stats');
    }

    public function ragArticles(Request $request)
    {
        return $this->jsonProxy('GET', '/rag/articles', $request->all());
    }

    public function uploadRagPdf(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'File is required'], 422);
        }

        $file = $request->file('file');
        $response = $this->apiClient()
            ->attach('file', file_get_contents($file->path()), $file->getClientOriginalName())
            ->post($this->apiUrl('/rag/upload'));

        return response($response->body(), $response->status())
            ->header('Content-Type', 'application/json');
    }

    public function deleteRagArticle(string $id)
    {
        return $this->jsonProxy('DELETE', "/rag/articles/{$id}");
    }

    /**
     * Escalation API Proxies
     */
    public function getEscalations(Request $request)
    {
        // Fetch escalated tickets from the backend
        return $this->jsonProxy('GET', '/tickets', array_merge($request->all(), ['escalated_only' => true]));
    }

    public function getEscalationDetail(string $id)
    {
        return $this->jsonProxy('GET', "/tickets/{$id}");
    }

    public function resolveEscalation(Request $request, string $id)
    {
        return $this->jsonProxy('POST', "/tickets/{$id}/resolve", $request->all());
    }

    private function jsonProxy(string $method, string $path, array $query = [])
    {
        $response = $this->apiClient()->send($method, $this->apiUrl($path), [
            'query' => $query,
            'json'  => $method !== 'GET' ? $query : null
        ]);
        return response($response->body(), $response->status())
            ->header('Content-Type', $response->header('Content-Type', 'application/json'));
    }

    private function apiClient()
    {
        $headers = ['Accept' => 'application/json'];
        $token = config('services.support_api.bearer_token');
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return Http::timeout((int) config('services.support_api.timeout', 30))
            ->withHeaders($headers);
    }

    private function apiUrl(string $path): string
    {
        return rtrim((string) config('services.support_api.base_url'), '/')
            . '/'
            . trim((string) config('services.support_api.prefix', '/api/v1'), '/')
            . '/'
            . ltrim($path, '/');
    }
}
