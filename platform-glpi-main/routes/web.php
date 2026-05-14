<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\PasswordChangeController;
use App\Models\Ticket;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\GlpiApiController;
use App\Http\Controllers\DecisionEngineController;
use App\Http\Controllers\SupportApiProxyController;

// ==================== NOTIFICATIONS ====================
Route::middleware('auth')->group(function () {
    Route::post('/notifications/{id}/read', function ($id) {
        \App\Models\Notification::where('id', $id)
            ->where('user_id', auth()->id())
            ->update(['is_read' => true]);
        return response()->json(['ok' => true]);
    })->name('notifications.read');

    Route::post('/notifications/read-all', function () {
        \App\Models\Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);
        return response()->json(['ok' => true]);
    })->name('notifications.read-all');

    Route::post('/auth/sms-otp/send',   [App\Http\Controllers\Auth\OtpController::class, 'sendSmsOtpForExisting'])->name('sms.otp.send');
    Route::post('/auth/sms-otp/verify', [App\Http\Controllers\Auth\OtpController::class, 'verifySmsOtpForExisting'])->name('sms.otp.verify');

    Route::get('/notifications/poll', function () {
        $notifications = \App\Models\Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->latest()
            ->limit(20)
            ->get(['id', 'type', 'icon', 'color', 'title', 'body', 'url', 'ticket_id', 'created_at']);

        return response()->json([
            'count'         => $notifications->count(),
            'notifications' => $notifications->map(fn($n) => [
                'id'    => $n->id,
                'type'  => $n->type,
                'icon'  => $n->icon,
                'color' => $n->color,
                'title' => $n->title,
                'body'  => $n->body ? \Illuminate\Support\Str::limit($n->body, 60) : null,
                'url'   => $n->url,
                'ago'   => $n->created_at->diffForHumans(),
            ]),
        ]);
    })->name('notifications.poll');
});

// ==================== HOME ====================
Route::get('/', function () {
    if (!auth()->check()) return redirect()->route('login');
    return redirect()->route('dashboard');
})->name('home');

Route::middleware('auth')->get('/dashboard', function () {
    $role = auth()->user()->role;
    if ($role === 'super_admin') return redirect()->route('super-admin.dashboard');
    elseif ($role === 'admin') return redirect()->route('admin.dashboard');
    else return redirect()->route('client.dashboard');
})->name('dashboard');

// ==================== SUPPORT OPS PAGES ====================
Route::middleware(['auth', CheckRole::class.':super_admin'])
    ->get('/voice-agents', function () {
        return view('support.voice-agents');
    })->name('voice-agents.runtime');

Route::middleware(['auth', 'force.password.change', CheckRole::class.':super_admin'])
    ->get('/visual-ai', function () {
        return view('support.visual-ai');
    })->name('visual-ai');

Route::middleware(['auth', 'force.password.change', CheckRole::class.':super_admin'])
    ->get('/decision-engine', function () {
        return view('support.decision-engine');
    })->name('decision-engine.runtime');

// ==================== SUPER ADMIN ====================
Route::middleware(['auth', CheckRole::class.':super_admin'])
    ->prefix('super-admin')->name('super-admin.')->group(function () {

    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/admins', [SuperAdminController::class, 'admins'])->name('admins');
    Route::get('/admins/create', [SuperAdminController::class, 'createAdmin'])->name('admins.create');
    Route::post('/admins', [SuperAdminController::class, 'storeAdmin'])->name('admins.store');
    Route::post('/admins/{id}/toggle', [SuperAdminController::class, 'toggleAdmin'])->name('admins.toggle');
    Route::delete('/admins/{id}', [SuperAdminController::class, 'deleteAdmin'])->name('admins.delete');
    Route::get('/clients', [SuperAdminController::class, 'clients'])->name('clients');
    Route::post('/clients/{id}/toggle', [SuperAdminController::class, 'toggleClient'])->name('clients.toggle');
    Route::delete('/clients/{id}', [SuperAdminController::class, 'deleteClient'])->name('clients.delete');
    Route::post('/clients/{id}/type', [SuperAdminController::class, 'updateClientType'])->name('clients.type');
    Route::get('/tickets', [SuperAdminController::class, 'tickets'])->name('tickets');
    Route::get('/tickets/{id}', [SuperAdminController::class, 'showTicket'])->name('tickets.show');
    Route::post('/tickets/{id}/status', [SuperAdminController::class, 'updateTicketStatus'])->name('tickets.status');
    Route::delete('/tickets/{id}', [SuperAdminController::class, 'deleteTicket'])->name('tickets.delete');
    Route::get('/clients/{id}', [SuperAdminController::class, 'showClient'])->name('clients.show');
    Route::get('/admins/{id}', [SuperAdminController::class, 'showAdmin'])->name('admins.show');
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('settings');
    Route::put('/settings', [SuperAdminController::class, 'settingsUpdate'])->name('settings.update');
    Route::get('/logs', [SuperAdminController::class, 'logs'])->name('logs');
    Route::get('/logs/export', [SuperAdminController::class, 'exportLogs'])->name('logs.export');
    Route::delete('/logs/clear', [SuperAdminController::class, 'clearLogs'])->name('logs.clear');
    Route::post('/sms/test', [SuperAdminController::class, 'testSms'])->name('sms.test');

    // GLPI
    Route::get('/glpi/stats', [SuperAdminController::class, 'glpiStats'])->name('glpi.stats');
    Route::post('/glpi/bulk-delete', [SuperAdminController::class, 'bulkDeleteItems'])->name('glpi.bulk-delete');
    Route::get('/glpi/explorer', function () {
        return view('super-admin.glpi-explorer');
    })->name('glpi.explorer');
    Route::post('/glpi/sync-categories', [SuperAdminController::class, 'syncCategories'])->name('glpi.sync-categories');
    Route::post('/glpi/sync-users', [SuperAdminController::class, 'syncUsers'])->name('glpi.sync-users');
    Route::post('/glpi/import-users', [SuperAdminController::class, 'importUsersFromGlpi'])->name('glpi.import-users');

    // IA — Super Admin
    Route::get('/ai/leaderboard',    [AiController::class, 'adminLeaderboard'])->name('ai.leaderboard');
    Route::get('/urgent-tickets',    [AiController::class, 'urgentTicketsList'])->name('urgent-tickets');
    Route::get('/ai/weekly-report',  [AiController::class, 'weeklyReport'])->name('ai.report');
    Route::get('/ai/urgent-tickets', [AiController::class, 'urgentTickets'])->name('ai.urgent-tickets');

    // Chat Access
    Route::get('/chat-access', [SuperAdminController::class, 'chatAccess'])->name('chat-access');
    Route::get('/chat-access/operators', [SuperAdminController::class, 'listChatOperators'])->name('chat-access.operators.index');
    Route::patch('/chat-access/operators/{user}', [SuperAdminController::class, 'updateChatOperatorAccess'])->name('chat-access.operators.update');
    Route::get('/chat-access/{user}', [SuperAdminController::class, 'chatShow'])->name('chat.show');
    Route::post('/chat/access/grant', [SuperAdminController::class, 'grantChatAccess'])->name('chat.access.grant');
    Route::delete('/chat/access/revoke', [SuperAdminController::class, 'revokeChatAccess'])->name('chat.access.revoke');
    Route::get('/chat/access/list', [SuperAdminController::class, 'listChatAccess'])->name('chat.access.list');

    // WhatsApp Supervision
    Route::get('/whatsapp', [SuperAdminController::class, 'whatsapp'])->name('whatsapp');

    // ── WhatsApp QR proxy (super-admin) ──────────────────────────────────
    // The browser cannot resolve Docker-internal hostnames (e.g. whatsapp-bridge:3000).
    // This route fetches the QR PNG server-side and streams it to the browser.
    // Named 'super-admin.whatsapp.qr-proxy' due to the prefix+name on this group.
    Route::get('/whatsapp/qr-proxy', [\App\Http\Controllers\WhatsAppQrProxyController::class, 'show'])
         ->name('whatsapp.qr-proxy');

    // Escalations
    Route::get('/escalations', [SuperAdminController::class, 'escalations'])->name('escalations');

    // Base de Connaissance (RAG)
    Route::get('/rag', [SuperAdminController::class, 'rag'])->name('rag');

    // Decision Engine
    Route::get('/decision-engine', [DecisionEngineController::class, 'index'])->name('decision-engine');
    Route::get('/decision-engine/tickets', [DecisionEngineController::class, 'tickets'])->name('decision-engine.tickets');
    Route::get('/decision-engine/tickets/{id}', [DecisionEngineController::class, 'ticketDetail'])->name('decision-engine.ticket');

    // Voice Calls
    Route::get('/voice-calls', function () {
        return view('super-admin.voice-calls');
    })->name('voice-calls');
    Route::get('/voice-calls/{id}', function (string $id) {
        return view('super-admin.voice-call-detail', ['callId' => $id]);
    })->name('voice-calls.show');

    // AI Draft Snippets
    Route::get('/ai-draft-snippets', function () {
        return view('admin.ai-draft-snippets');
    })->name('ai-draft-snippets');

    Route::get('/voice-agents', function () {
        return redirect()->route('voice-agents.runtime');
    })->name('voice-agents');

    // Conversations (read-only)
    Route::get('/conversations', [SuperAdminController::class, 'conversations'])->name('conversations');
    Route::get('/conversations/{id}', [SuperAdminController::class, 'conversationDetail'])->name('conversations.detail');
});

// ==================== ADMIN ====================
Route::middleware(['auth', 'force.password.change', CheckRole::class.':admin', 'check.profile.complete'])
    ->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/tickets', [AdminController::class, 'tickets'])->name('tickets');
    Route::get('/tickets/{id}', [AdminController::class, 'showTicket'])->name('tickets.show');
    Route::post('/tickets/{id}/status', [AdminController::class, 'updateStatus'])->name('tickets.update-status');
    Route::get('/clients', [AdminController::class, 'clients'])->name('clients');
    Route::get('/clients/{id}', [AdminController::class, 'showClient'])->name('clients.show');
    Route::post('/tickets/{id}/assign', [AdminController::class, 'assignTicket'])->name('tickets.assign');
    Route::get('/urgent-tickets', [AdminController::class, 'urgentTickets'])->name('urgent-tickets');
    Route::get('/user-settings', [AdminController::class, 'userSettings'])->name('user-settings');
    Route::post('/clients/{id}/type', [AdminController::class, 'updateClientType'])->name('clients.type');
    Route::get('/clients/{id}/tickets', [AdminController::class, 'clientTickets'])->name('clients.tickets');

    // IA — Analyse ticket
    Route::post('/ai/analyze', [AiController::class, 'analyzeTicket'])->name('ai.analyze');

    // Admin chat page
    Route::get('/chat', [AdminController::class, 'chat'])->name('chat');

    // WhatsApp interface
    Route::get('/whatsapp', [AdminController::class, 'whatsapp'])->name('whatsapp');

    // Voice Calls
    Route::get('/voice-calls', function () {
        return view('super-admin.voice-calls');
    })->name('voice-calls');
    Route::get('/voice-calls/{id}', function (string $id) {
        return view('super-admin.voice-call-detail', ['callId' => $id]);
    })->name('voice-calls.show');

    // AI Draft Snippets is super_admin only

    // ── WhatsApp QR proxy (admin) ─────────────────────────────────────────
    // The browser cannot resolve Docker-internal hostnames (e.g. whatsapp-bridge:3000).
    // This route fetches the QR PNG server-side and streams it to the browser.
    // Named 'admin.whatsapp.qr-proxy' due to the prefix+name on this group.
    Route::get('/whatsapp/qr-proxy', [\App\Http\Controllers\WhatsAppQrProxyController::class, 'show'])
         ->name('whatsapp.qr-proxy');

    // Escalations
    Route::get('/escalations', [SuperAdminController::class, 'escalations'])->name('escalations');
    Route::get('/api/escalations', [SuperAdminController::class, 'getEscalations'])->name('escalations.api');
    Route::get('/api/escalations/{id}', [SuperAdminController::class, 'getEscalationDetail'])->name('escalations.detail');
    Route::post('/api/escalations/{id}/resolve', [SuperAdminController::class, 'resolveEscalation'])->name('escalations.resolve');

    // RAG is super_admin only
});

// ==================== CLIENT ====================
Route::middleware(['auth', 'force.password.change', CheckRole::class.':client'])
    ->prefix('client')->name('client.')->group(function () {

    Route::get('/', function () {
        $userId            = auth()->id();
        $totalTickets      = Ticket::where('user_id', $userId)->count();
        $openTickets       = Ticket::where('user_id', $userId)->where('sync_status', 'pending')->count();
        $inProgressTickets = Ticket::where('user_id', $userId)->where('sync_status', 'in_progress')->count();
        $closedTickets     = Ticket::where('user_id', $userId)->whereIn('sync_status', ['resolved', 'closed'])->count();
        $recentTickets     = Ticket::where('user_id', $userId)->latest()->take(5)->get();
        return view('client.dashboard', compact(
            'totalTickets', 'openTickets', 'inProgressTickets', 'closedTickets', 'recentTickets'
        ));
    })->name('dashboard');

    Route::get('/settings', function () {
        $uid = auth()->id();
        $clientPrefs = [
            'theme_mode'      => \App\Models\Setting::get("user_{$uid}_theme_mode",      'light'),
            'primary_color'   => \App\Models\Setting::get("user_{$uid}_primary_color",   null),
            'secondary_color' => \App\Models\Setting::get("user_{$uid}_secondary_color", null),
        ];
        return view('client.settings', compact('clientPrefs'));
    })->name('settings');

    Route::post('/settings/ui', function (\Illuminate\Http\Request $request) {
        $uid = auth()->id();
        $validated = $request->validate([
            'theme_mode'      => 'in:light,dark,auto',
            'primary_color'   => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
            'secondary_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
        ]);
        \App\Models\Setting::set("user_{$uid}_theme_mode",      $validated['theme_mode']      ?? 'light');
        \App\Models\Setting::set("user_{$uid}_primary_color",   $validated['primary_color']   ?? '');
        \App\Models\Setting::set("user_{$uid}_secondary_color", $validated['secondary_color'] ?? '');
        return redirect()->route('client.settings')->with('success', 'Préférences UI sauvegardées !');
    })->name('settings.ui');
});

// ==================== PROFILE ====================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/glpi-picture', [ProfileController::class, 'glpiPicture'])->name('profile.glpi-picture');
    Route::post('/profile/verify-teams-email', [ProfileController::class, 'verifyTeamsEmail'])->name('profile.verify-teams');
    Route::get('/search', [App\Http\Controllers\SearchController::class, 'search'])->name('search');
});

Route::middleware('auth')->post('/user/settings/ui', function (\Illuminate\Http\Request $request) {
    $uid = auth()->id();
    $validated = $request->validate([
        'theme_mode'      => 'nullable|in:light,dark,auto',
        'primary_color'   => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
        'secondary_color' => 'nullable|regex:/^#[0-9a-fA-F]{6}$/',
    ]);
    \App\Models\Setting::set("user_{$uid}_theme_mode",      $validated['theme_mode']      ?? 'light');
    \App\Models\Setting::set("user_{$uid}_primary_color",   $validated['primary_color']   ?? '');
    \App\Models\Setting::set("user_{$uid}_secondary_color", $validated['secondary_color'] ?? '');
    return redirect()->back()->with('success', 'Préférences UI sauvegardées !');
})->name('user.settings.ui');

// ==================== GLPI API (BACKEND BRIDGE) ====================
// This group allows the FastAPI backend to communicate with GLPI through Laravel.
// It bypasses the 'auth' middleware but should ideally be secured by a shared secret.
Route::prefix('api/v1/glpi')->group(function () {
    Route::get('/items/{itemtype}',                         [GlpiApiController::class, 'getAllItems']);
    Route::get('/items/{itemtype}/{id}',                         [GlpiApiController::class, 'getItem']);
    Route::post('/items/{itemtype}',                             [GlpiApiController::class, 'addItem']);
    Route::put('/items/{itemtype}/{id}',                         [GlpiApiController::class, 'updateItem']);
    Route::delete('/items/{itemtype}/{id}',                      [GlpiApiController::class, 'deleteItem']);
});

// ==================== SUPPORT OPS API BRIDGE ====================
// Blade pages use the same /api/v1/... endpoint paths as the React frontend.
// Laravel keeps session auth/CSRF locally and forwards API calls to FastAPI.
Route::middleware('auth')->prefix('api/v1')->group(function () {
    Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], '/{path?}', SupportApiProxyController::class)
        ->where('path', '.*');
});

// ==================== TICKETS CLIENT ====================
Route::middleware(['auth', 'force.password.change', CheckRole::class.':client'])->group(function () {
    Route::get('/tickets',        [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets',       [TicketController::class, 'store'])->name('tickets.store');

    // IA Client — avant {id} pour éviter conflit de routing
    Route::post('/tickets/classify',    [AiController::class, 'classify'])->name('tickets.classify');
    Route::post('/tickets/reformulate', [AiController::class, 'reformulate'])->name('tickets.reformulate');
    Route::get('/tickets/similar',      [AiController::class, 'similar'])->name('tickets.similar');

    Route::get('/tickets/{id}',          [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{id}/edit',     [TicketController::class, 'edit'])->name('tickets.edit');
    Route::put('/tickets/{id}',          [TicketController::class, 'update'])->name('tickets.update');
    Route::delete('/tickets/{id}',       [TicketController::class, 'destroy'])->name('tickets.destroy');
    Route::post('/tickets/{id}/comment', [TicketController::class, 'addComment'])->name('tickets.comment');
    Route::post('/tickets/{id}/retry',   [TicketController::class, 'retry'])->name('tickets.retry');
    Route::post('/tickets/{id}/close',   [TicketController::class, 'close'])->name('tickets.close');
});

// ==================== GLPI API ====================
Route::middleware(['auth', CheckRole::class.':super_admin,admin'])
    ->prefix('glpi')->name('glpi.')->group(function () {

    Route::get('/session/info',                                  [GlpiApiController::class, 'sessionInfo'])->name('session.info');
    Route::get('/config',                                        [GlpiApiController::class, 'glpiConfig'])->name('config');
    Route::get('/profiles',                                      [GlpiApiController::class, 'profiles'])->name('profiles');
    Route::get('/profiles/active',                               [GlpiApiController::class, 'activeProfile'])->name('profiles.active');
    Route::post('/profiles/change',                              [GlpiApiController::class, 'changeProfile'])->name('profiles.change');
    Route::get('/entities',                                      [GlpiApiController::class, 'entities'])->name('entities');
    Route::get('/entities/active',                               [GlpiApiController::class, 'activeEntities'])->name('entities.active');
    Route::post('/entities/change',                              [GlpiApiController::class, 'changeEntity'])->name('entities.change');
    Route::get('/items/multiple',                                [GlpiApiController::class, 'getMultipleItems'])->name('items.multiple');
    Route::get('/items/{itemtype}',                              [GlpiApiController::class, 'getAllItems'])->name('items.list');
    Route::get('/items/{itemtype}/{id}',                         [GlpiApiController::class, 'getItem'])->name('items.get');
    Route::get('/items/{itemtype}/{id}/sub/{subItemtype}',       [GlpiApiController::class, 'getSubItems'])->name('items.sub');
    Route::post('/items/{itemtype}',                             [GlpiApiController::class, 'addItem'])->name('items.create');
    Route::put('/items/{itemtype}/{id}',                         [GlpiApiController::class, 'updateItem'])->name('items.update');
    Route::delete('/items/{itemtype}/{id}',                      [GlpiApiController::class, 'deleteItem'])->name('items.delete');
    Route::get('/search/{itemtype}/options',                     [GlpiApiController::class, 'searchOptions'])->name('search.options');
    Route::get('/search/tickets',                                [GlpiApiController::class, 'searchTickets'])->name('search.tickets');
    Route::get('/search/{itemtype}',                             [GlpiApiController::class, 'searchItems'])->name('search.items');
    Route::get('/massive-actions/{itemtype}',                    [GlpiApiController::class, 'massiveActions'])->name('massive.list');
    Route::get('/massive-actions/{itemtype}/{id}',               [GlpiApiController::class, 'massiveActionsForItem'])->name('massive.item');
    Route::get('/massive-actions/{itemtype}/params/{actionKey}', [GlpiApiController::class, 'massiveActionParams'])->name('massive.params');
    Route::post('/massive-actions/{itemtype}/{actionKey}/apply', [GlpiApiController::class, 'applyMassiveAction'])->name('massive.apply');
    Route::post('/documents/upload',                             [GlpiApiController::class, 'uploadDocument'])->name('documents.upload');
    Route::get('/documents/{id}/download',                       [GlpiApiController::class, 'downloadDocument'])->name('documents.download');
    Route::get('/users/{id}/picture',                            [GlpiApiController::class, 'userPicture'])->name('users.picture');
    Route::post('/password/reset-request',                       [GlpiApiController::class, 'requestPasswordReset'])->name('password.reset-request');
    Route::post('/password/reset',                               [GlpiApiController::class, 'resetPassword'])->name('password.reset');
    Route::get('/tickets/stats',                                 [GlpiApiController::class, 'ticketStats'])->name('tickets.stats');
    Route::get('/tickets',                                       [GlpiApiController::class, 'listTickets'])->name('tickets.list');
    Route::get('/tickets/{id}/detail',                           [GlpiApiController::class, 'ticketDetail'])->name('tickets.detail');
    Route::post('/tickets/notes',                                [GlpiApiController::class, 'addNoteToTickets'])->name('tickets.notes');
    Route::get('/users',                                         [GlpiApiController::class, 'listUsers'])->name('users.list');
    Route::get('/users/{id}',                                    [GlpiApiController::class, 'getUser'])->name('users.get');
    Route::get('/categories',                                    [GlpiApiController::class, 'categories'])->name('categories');
});

// ==================== FORCE PASSWORD CHANGE ====================
Route::middleware('auth')->group(function () {
    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');
});

// ── Chat Widget ──────────────────────────────────────────────────────────────
Route::middleware(['auth', 'force.password.change', CheckRole::class.':client'])
    ->group(function () {

    Route::get('/chat', function () {
        return view('client.chat');
    })->name('chat');

    Route::get('/chat/conversations',
        [\App\Http\Controllers\ChatController::class, 'conversations']
    )->name('chat.conversations');

    Route::get('/chat/conversations/{id}/messages',
        [\App\Http\Controllers\ChatController::class, 'messages']
    )->name('chat.messages');

    Route::delete('/chat/conversations/{id}',
        [\App\Http\Controllers\ChatController::class, 'deleteConversation']
    )->name('chat.delete');

    Route::post('/chat/send',
        [\App\Http\Controllers\ChatController::class, 'send']
    )->name('chat.send');
});

require __DIR__ . '/auth.php';
