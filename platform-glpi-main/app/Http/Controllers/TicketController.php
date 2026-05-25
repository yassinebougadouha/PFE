<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Services\GmailService;
use App\Services\GlpiService;
use App\Services\SmsService;
use App\Services\SupportBackendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // LISTE TICKETS CLIENT
    // ─────────────────────────────────────────────────────────────
    public function index()
    {
        // الـ local DB هو المصدر الأساسي — سريع وموثوق
        $tickets = \App\Models\Ticket::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('client.tickets.index', compact('tickets'));
    }

    // ─────────────────────────────────────────────────────────────
    // AFFICHER TICKET
    // ─────────────────────────────────────────────────────────────
    public function show($id)
    {
        try {
            $glpi = app(GlpiService::class);
            $glpi->initSession();
            $glpiTicket = $glpi->getItem('Ticket', (int) $id);
            $glpiFollowups = $glpi->getFollowups((int) $id);
            $glpi->killSession();
        } catch (\Exception $e) {
            \Log::warning('Could not fetch ticket from GLPI: ' . $e->getMessage());
            abort(404, 'Ticket not found');
        }

        $status = GlpiService::mapGlpiStatusToLocal((int)($glpiTicket['status'] ?? 1));
        $ticket = (object) [
            'id'          => (int) $id,
            'title'       => $glpiTicket['name'] ?? '',
            'description' => $glpiTicket['content'] ?? '',
            'sync_status' => $status,
            'priority'    => (int)($glpiTicket['priority'] ?? 3),
            'solution'    => $glpiTicket['solution'] ?? null,
            'created_at'  => \Carbon\Carbon::parse($glpiTicket['date_creation'] ?? $glpiTicket['date'] ?? now()),
            'updated_at'  => \Carbon\Carbon::parse($glpiTicket['date_mod'] ?? now()),
            'user_id'     => $glpiTicket['users_id_recipient'] ?? null,
        ];

        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->get();

        return view('client.tickets.show', compact('ticket', 'admins'));
    }

    // ─────────────────────────────────────────────────────────────
    // FORM CREATE
    // ─────────────────────────────────────────────────────────────
    public function create()
    {
        return view('client.tickets.create');
    }

    // ─────────────────────────────────────────────────────────────
    // STORE
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'content'       => 'required|string',
            'urgency'       => 'required|integer|min:1|max:5',
            'impact'        => 'required|integer|min:1|max:5',
            'priority'      => 'required|integer|min:1|max:5',
            'category'      => 'required|string|max:255',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'nullable|file|max:5120',
        ]);

        $attachmentPaths = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $attachmentPaths[] = $file->store('tickets/attachments', 'public');
                }
            }
        }

        $glpiId = null;
        $backendTicket = null;
        $backendError = null;

        // ─── GLPI CREATE (optionnel — si GLPI indisponible, on continue quand même)
        try {
            $glpi = app(GlpiService::class);

            $result = $glpi->createTicket([
                'title'       => $validated['title'],
                'description' => $validated['content'],
                'urgency'     => (int) $validated['urgency'],
                'impact'      => (int) $validated['impact'],
                'priority'    => (int) $validated['priority'],
            ]);

            $glpiId = $result['id'] ?? null;

            if ($glpiId) {

                // Upload docs
                foreach ($attachmentPaths as $path) {
                    try {
                        $fullPath = storage_path('app/public/' . $path);

                        $glpi->uploadDocument(
                            $fullPath,
                            basename($path)
                        );
                    } catch (\Exception $e) {
                        Log::warning('GLPI upload failed: ' . $e->getMessage());
                    }
                }

                // Backend sync
                try {
                    $backendTicket = app(SupportBackendService::class)
                        ->ingestGlpiTicket([
                            'glpi_ticket_id' => $glpiId,
                            'subject'        => $validated['title'],
                            'description'    => $validated['content'],
                            'priority'       => $this->mapBackendPriority(
                                $validated['priority']
                            ),
                            'channel_source' => 'TICKET',
                            'creator_email'  => auth()->user()->email,
                            'creator_name'   => auth()->user()->name,
                        ]);
                } catch (\Exception $e) {
                    $backendError = $e->getMessage();

                    Log::error(
                        'Backend ingest failed: ' . $e->getMessage()
                    );
                }
            }
        } catch (\Exception $e) {
            // GLPI indisponible — on log et on continue sans bloquer
            Log::error('GLPI create failed (non-blocking): ' . $e->getMessage());
            $glpiId = null;
        } finally {
            if (isset($glpi)) {
                try { $glpi->killSession(); } catch (\Exception $e) {}
            }
        }

        // ─────────────────────────────────────────────
        // LOCAL DB
        // ─────────────────────────────────────────────
        DB::beginTransaction();

        try {

            $mirroredStatus = $this->mapBackendStatusToLocal(
                $backendTicket['status'] ?? null
            );

            $mirroredPriority = $this->mapBackendPriorityToLocal(
                $backendTicket['priority'] ?? null,
                (int) $validated['priority']
            );

            $ticket = Ticket::create([
                'user_id'        => auth()->id(),
                'title'          => $validated['title'],
                'description'    => $validated['content'],
                'urgency'        => (int) $validated['urgency'],
                'impact'         => (int) $validated['impact'],
                'priority'       => $mirroredPriority,
                'category'       => $validated['category'],
                'attachments'    => !empty($attachmentPaths)
                    ? json_encode($attachmentPaths)
                    : null,
                'glpi_ticket_id' => $glpiId,
                'status'         => $backendTicket
                    ? $mirroredStatus
                    : 'pending',
                'last_error'     => $backendError,
                'source'         => 'glpi',
            ]);

            // SLA
            $slaHours = [
                5 => (int) Setting::get('sla_très haute', '4'),
                4 => (int) Setting::get('sla_haute', '8'),
                3 => (int) Setting::get('sla_moyenne', '24'),
                2 => (int) Setting::get('sla_basse', '48'),
                1 => (int) Setting::get('sla_basse', '48'),
            ][$ticket->priority] ?? 24;

            $ticket->update([
                'sla_due_at' => now()->addHours($slaHours),
            ]);

            // Auto assign
            if (Setting::get('auto_assignment') === '1') {

                $method = Setting::get(
                    'auto_assignment_method',
                    'Round-robin'
                );

                $adminId = $this->autoAssign(
                    $ticket,
                    $method
                );

                if ($adminId) {
                    $ticket->update([
                        'assigned_to' => $adminId,
                    ]);
                }
            }

            AuditLog::log(
                'CREATE',
                'Tickets',
                "Nouveau ticket: {$ticket->title} (#{$ticket->id})"
            );

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error(
                'Ticket local create failed: ' . $e->getMessage()
            );

            return redirect()
                ->back()
                ->withInput()
                ->with(
                    'error',
                    'Erreur lors de la création locale du ticket.'
                );
        }

        // ─────────────────────────────────────────────
        // NOTIFICATIONS & EMAILS — en arrière-plan (non-bloquant)
        // ─────────────────────────────────────────────
        try {
            // In-app notification seulement (DB locale — rapide)
            $priorityIndex = max(0, min(4, ((int)$ticket->priority) - 1));
            $notifColor = ['warning','info','primary','danger','danger'][$priorityIndex];
            Notification::sendToAdmins([
                'type'      => 'new_ticket',
                'icon'      => 'confirmation_number',
                'color'     => $notifColor,
                'title'     => "Nouveau ticket #{$ticket->id}",
                'body'      => Str::limit($ticket->description, 80),
                'url'       => route('admin.tickets.show', ['ticket' => $ticket->id]),
                'ticket_id' => $ticket->id,
            ]);
        } catch (\Exception $e) {
            Log::warning('In-app notification failed: ' . $e->getMessage());
        }

        // Email, Teams, SMS, GLPI — désactivés pour éviter timeout
        // À activer via Queue worker en production

        // AJAX
        if ($request->expectsJson()) {

            return response()->json([
                'success'   => true,
                'ticket_id' => $ticket->id,
                'message'   => 'Ticket créé avec succès.',
                'url'       => route(
                    'tickets.show',
                    ['ticket' => $ticket->id]
                ),
            ]);
        }

        return redirect()
            ->route('tickets.index')
            ->with(
                'success',
                'Ticket soumis avec succès ✅'
            );
    }

    // ─────────────────────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────────────────────
    public function edit($id)
    {
        $ticket = Ticket::where('user_id', auth()->id())
            ->findOrFail($id);

        if (!$ticket->canEdit()) {

            return redirect()
                ->route('tickets.index')
                ->with(
                    'error',
                    'Ce ticket ne peut plus être modifié.'
                );
        }

        return view(
            'client.tickets.edit',
            compact('ticket')
        );
    }

    // ─────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $ticket = Ticket::where('user_id', auth()->id())
            ->findOrFail($id);

        if (!$ticket->canEdit()) {

            return redirect()
                ->route('tickets.index')
                ->with(
                    'error',
                    'Ce ticket ne peut plus être modifié.'
                );
        }

        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $ticket->update([
            'title'       => $validated['title'],
            'description' => $validated['content'],
        ]);

        // GLPI UPDATE
        if ($ticket->glpi_ticket_id) {

            try {

                $glpi = app(GlpiService::class);

                $glpi->updateTicket(
                    $ticket->glpi_ticket_id,
                    [
                        'input' => [
                            'name'    => $validated['title'],
                            'content' => $validated['content'],
                        ]
                    ]
                );

            } catch (\Exception $e) {

                Log::error(
                    'GLPI update failed: '
                    . $e->getMessage()
                );

            } finally {

                if (isset($glpi)) {
                    try {
                        $glpi->killSession();
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        AuditLog::log(
            'UPDATE',
            'Tickets',
            "Modification ticket #{$ticket->id}"
        );

        return redirect()
            ->route('tickets.index')
            ->with(
                'success',
                'Ticket modifié avec succès.'
            );
    }

    // ─────────────────────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $ticket = Ticket::where('user_id', auth()->id())
            ->findOrFail($id);

        if (!$ticket->canDelete()) {

            return redirect()
                ->route('tickets.index')
                ->with(
                    'error',
                    'Ce ticket ne peut plus être supprimé.'
                );
        }

        if ($ticket->glpi_ticket_id) {

            try {

                $glpi = app(GlpiService::class);

                $glpi->deleteTicket(
                    $ticket->glpi_ticket_id
                );

            } catch (\Exception $e) {

                Log::error(
                    'GLPI delete failed: '
                    . $e->getMessage()
                );

            } finally {

                if (isset($glpi)) {
                    try {
                        $glpi->killSession();
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        AuditLog::log(
            'DELETE',
            'Tickets',
            "Suppression ticket #{$ticket->id}"
        );

        $ticket->delete();

        return redirect()
            ->route('tickets.index')
            ->with(
                'success',
                'Ticket supprimé.'
            );
    }

    // ─────────────────────────────────────────────────────────────
    // ADD COMMENT
    // FIX: was saving comment locally but never pushing it to GLPI
    //      as a followup — added GlpiService::addFollowup() call.
    // ─────────────────────────────────────────────────────────────
    public function addComment(Request $request, $id)
    {
        $ticket = Ticket::where('user_id', auth()->id())
            ->with('user')
            ->findOrFail($id);

        $validated = $request->validate([
            'content'        => 'required|string|max:2000',
            'attachments'    => 'nullable|array|max:5',
            'attachments.*'  => 'nullable|file|max:5120',
        ]);

        $attachmentPaths = [];

        if ($request->hasFile('attachments')) {

            foreach ($request->file('attachments') as $file) {

                if ($file && $file->isValid()) {

                    $attachmentPaths[] = $file->store(
                        'tickets/comments',
                        'public'
                    );
                }
            }
        }

        $comment = TicketComment::create([
            'ticket_id'       => $ticket->id,
            'user_id'         => auth()->id(),
            'content'         => $validated['content'],
            'attachment_path' => !empty($attachmentPaths)
                ? json_encode($attachmentPaths)
                : null,
        ]);

        // ── GLPI FOLLOWUP ───────────────────────────────────────
        if ($ticket->glpi_ticket_id) {

            try {

                $glpi = app(GlpiService::class);

                $glpi->addFollowup(
                    $ticket->glpi_ticket_id,
                    $validated['content']
                );

            } catch (\Exception $e) {

                Log::warning(
                    'GLPI followup failed: ' . $e->getMessage()
                );

            } finally {

                if (isset($glpi)) {
                    try {
                        $glpi->killSession();
                    } catch (\Exception $e) {
                    }
                }
            }
        }
        // ────────────────────────────────────────────────────────

        AuditLog::log(
            'COMMENT',
            'Tickets',
            "Commentaire sur ticket #{$ticket->id}"
        );

        return redirect()
            ->route(
                'tickets.show',
                ['ticket' => $ticket->id]
            )
            ->with(
                'success',
                'Commentaire ajouté.'
            );
    }

    // ─────────────────────────────────────────────────────────────
    // CLOSE
    // FIX: was updating status locally but never pushing "closed"
    //      (status 6) back to GLPI — added GlpiService::updateTicket().
    // ─────────────────────────────────────────────────────────────
    public function close($id)
    {
        if (Setting::get('allow_client_close') !== '1') {

            return redirect()
                ->route(
                    'tickets.show',
                    ['ticket' => $id]
                )
                ->with(
                    'error',
                    'Fermeture désactivée.'
                );
        }

        $ticket = Ticket::where('user_id', auth()->id())
            ->findOrFail($id);

        if ($ticket->status === 'closed') {

            return redirect()
                ->route(
                    'tickets.show',
                    ['ticket' => $id]
                )
                ->with(
                    'error',
                    'Ticket déjà clôturé.'
                );
        }

        $ticket->update([
            'status' => 'closed'
        ]);

        // ── GLPI STATUS SYNC ─────────────────────────────────────
        // GLPI status 6 = Closed (per status mapping table)
        if ($ticket->glpi_ticket_id) {

            try {

                $glpi = app(GlpiService::class);

                $glpi->updateTicket(
                    $ticket->glpi_ticket_id,
                    [
                        'input' => [
                            'status' => 6,
                        ]
                    ]
                );

            } catch (\Exception $e) {

                Log::error(
                    'GLPI close sync failed: ' . $e->getMessage()
                );

            } finally {

                if (isset($glpi)) {
                    try {
                        $glpi->killSession();
                    } catch (\Exception $e) {
                    }
                }
            }
        }
        // ────────────────────────────────────────────────────────

        AuditLog::log(
            'CLOSE',
            'Tickets',
            "Ticket clôturé #{$ticket->id}"
        );

        return redirect()
            ->route('tickets.index')
            ->with(
                'success',
                'Ticket clôturé.'
            );
    }

    // ─────────────────────────────────────────────────────────────
    // NOTIFY ADMINS
    // ─────────────────────────────────────────────────────────────
    protected function notifyAdmins(
        Ticket $ticket,
        array $attachmentPaths = []
    ) {
        $admins = User::where('role', 'admin')
            ->where(function ($q) {
                $q->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->whereNotNull('email')
            ->get();

        $gmail = app(GmailService::class);

        foreach ($admins as $admin) {

            try {

                $html = view(
                    'emails.new-ticket',
                    compact(
                        'ticket',
                        'attachmentPaths',
                        'admin'
                    )
                )->render();

                $gmail->send(
                    $admin->email,
                    "🎫 Nouveau ticket #{$ticket->id}",
                    $html
                );

            } catch (\Exception $e) {

                Log::error(
                    'Admin email failed: '
                    . $e->getMessage()
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // AUTO ASSIGN
    // ─────────────────────────────────────────────────────────────
    protected function autoAssign(
        Ticket $ticket,
        string $method = 'Round-robin'
    ): ?int {

        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->get();

        if ($admins->isEmpty()) {
            return null;
        }

        if ($method === 'Round-robin') {

            $selected = $admins->sortBy(function ($admin) {

                return Ticket::where(
                    'assigned_to',
                    $admin->id
                )
                ->whereIn('status', [
                    'pending',
                    'in_progress'
                ])
                ->count();
            })->first();

            return $selected?->id;
        }

        if ($method === 'Random') {
            return $admins->random()->id;
        }

        return $admins->first()->id;
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────
    private function mapBackendStatusToLocal(
        ?string $status
    ): string {

        return match (strtoupper((string) $status)) {

            'OPEN'                => 'pending',
            'IN_PROGRESS'         => 'in_progress',
            'WAITING_ON_CUSTOMER' => 'pending',
            'ESCALATED'           => 'escalated',
            'RESOLVED'            => 'resolved',
            'CLOSED'              => 'closed',

            default               => 'pending',
        };
    }

    private function mapBackendPriorityToLocal(
        ?string $priority,
        int $fallback
    ): int {

        return match (strtoupper((string) $priority)) {

            'LOW'      => 2,
            'MEDIUM'   => 3,
            'HIGH'     => 4,
            'CRITICAL' => 5,

            default    => $fallback,
        };
    }

    private function mapBackendPriority(
        int $priority
    ): string {

        return match ((int) $priority) {

            1 => 'LOW',
            2 => 'LOW',
            3 => 'MEDIUM',
            4 => 'HIGH',
            5 => 'CRITICAL',

            default => 'MEDIUM',
        };
    }
}