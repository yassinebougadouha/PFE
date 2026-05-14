<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Services\GmailService;
use App\Services\GlpiService;
use App\Services\TeamsService;
use App\Services\AiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Google\Service\Gmail as GmailApi;

class FetchSupportEmails extends Command
{
    protected $signature   = 'mail:fetch-support';
    protected $description = 'Lit la boîte support Gmail et crée des tickets depuis les emails non lus';

    public function handle(): void
    {
        try {
            $gmail  = app(GmailService::class);
            $client = $gmail->getClient();
            $service = new GmailApi($client);

            // ── 1. Récupérer les emails non lus dans INBOX ─────────────────────
            $messages = $service->users_messages->listUsersMessages('me', [
                'q'          => 'is:unread in:inbox',
                'maxResults' => 20,
            ]);

            $msgList = $messages->getMessages() ?? [];

            if (empty($msgList)) {
                $this->info('[FetchSupportEmails] Aucun nouvel email.');
                return;
            }

            $created = 0;

            foreach ($msgList as $msgRef) {
                try {
                    $msg     = $service->users_messages->get('me', $msgRef->getId(), ['format' => 'full']);
                    $headers = $this->parseHeaders($msg->getPayload()->getHeaders());

                    $senderEmail = $this->extractEmail($headers['From'] ?? '');
                    $senderName  = $this->extractName($headers['From'] ?? '') ?: $senderEmail;
                    $subject     = $headers['Subject'] ?? '(Sans objet)';
                    $body        = $this->extractBody($msg->getPayload());

                    if (!$senderEmail) {
                        Log::warning("[FetchSupportEmails] Email sans expéditeur valide, ignoré.");
                        $this->markRead($service, $msgRef->getId());
                        continue;
                    }

                    // Ignorer les emails envoyés depuis notre propre adresse (boucle)
                    $ownEmail = \App\Models\Setting::get('gmail_from_email') ?: config('mail.from.address');
                    if (strtolower($senderEmail) === strtolower($ownEmail)) {
                        $this->markRead($service, $msgRef->getId());
                        continue;
                    }

                    // ── 2. Trouver ou créer le compte client ───────────────────
                    $user = User::where('email', $senderEmail)->first();

                    // Ignorer les emails envoyés par des admins ou super_admins
                    if ($user && in_array($user->role, ['admin', 'super_admin'])) {
                        Log::warning("[FetchSupportEmails] Email ignoré — expéditeur admin: {$senderEmail}");
                        $this->markRead($service, $msgRef->getId());
                        continue;
                    }

                    if (!$user) {
                        // Nouveau client → compte auto-créé, type = 'user' (non classifié)
                        $plainPassword = Str::random(12);
                        $user = User::create([
                            'name'                 => $senderName,
                            'email'                => $senderEmail,
                            'password'             => Hash::make($plainPassword),
                            'role'                 => 'client',
                            'client_type'          => 'user',   // ← 🟠 Nouveau non classifié
                            'is_active'            => true,
                            'must_change_password' => true,
                            'email_verified_at'    => now(),
                        ]);

                        AuditLog::log('CREATE', 'Users',
                            "Compte auto-créé depuis email: {$senderName} ({$senderEmail})");

                        // Envoyer les identifiants par email
                        try {
                            $html = view('emails.client-auto-created', [
                                'name'     => $senderName,
                                'email'    => $senderEmail,
                                'password' => $plainPassword,
                            ])->render();

                            $gmail->send(
                                $senderEmail,
                                '🎫 Votre compte L2T Support a été créé',
                                $html
                            );
                        } catch (\Exception $e) {
                            Log::error("[FetchSupportEmails] Email bienvenue failed: " . $e->getMessage());
                        }

                        $this->info("[FetchSupportEmails] Nouveau client créé: {$senderEmail}");
                    }

                    // ── 3. Classification IA ───────────────────────────────────
                    $aiPriority = 3;
                    $aiUrgency  = 3;
                    $aiImpact   = 3;
                    $aiCategory = 'autre';

                    try {
                        $classification = app(AiService::class)->classify(
                            Str::limit($subject, 255),
                            $body ?: $subject
                        );
                        if ($classification) {
                            $aiPriority = (int) ($classification['priority'] ?? 3);
                            $aiUrgency  = (int) ($classification['urgency']  ?? 3);
                            $aiImpact   = (int) ($classification['impact']   ?? 3);
                            $aiCategory = $classification['category']         ?? 'autre';
                        }
                    } catch (\Exception $e) {
                        Log::warning("[FetchSupportEmails] AI classification failed: " . $e->getMessage());
                    }

                    // ── 4. Créer le ticket ─────────────────────────────────────
                    $ticket = Ticket::create([
                        'user_id'     => $user->id,
                        'title'       => Str::limit($subject, 255),
                        'description' => $body ?: $subject,
                        'urgency'     => $aiUrgency,
                        'impact'      => $aiImpact,
                        'priority'    => $aiPriority,
                        'category'    => $aiCategory,
                        'sync_status' => 'pending',
                        'source'      => 'email',
                    ]);

                    // ── 5. SLA par défaut (adapté à la priorité IA)
                    $slaHours = [1 => 72, 2 => 48, 3 => 24, 4 => 8, 5 => 4];
                    $ticket->update(['sla_due_at' => now()->addHours($slaHours[$aiPriority] ?? 24)]);

                    // Auto-assignation si activée
                    if (\App\Models\Setting::get('auto_assignment') === '1') {
                        $method  = \App\Models\Setting::get('auto_assignment_method', 'Round-robin');
                        $adminId = $this->autoAssign($ticket, $method);
                        if ($adminId) {
                            $ticket->update(['assigned_to' => $adminId]);
                        }
                    }

                    AuditLog::log('CREATE', 'Tickets',
                        "Ticket #{$ticket->id} créé depuis email de {$senderEmail}: {$subject}");

                    // ── 6. Notifications in-app ────────────────────────────────
                    $notifData = [
                        'type'      => 'new_ticket',
                        'icon'      => 'email',
                        'color'     => 'info',
                        'title'     => "📧 Ticket #{$ticket->id} via email : " . Str::limit($subject, 60),
                        'body'      => Str::limit($body, 80),
                        'url'       => route('admin.tickets.show', $ticket->id),
                        'ticket_id' => $ticket->id,
                    ];
                    Notification::sendToAdmins($notifData);
                    // Super admin ne reçoit pas les notifications de nouveaux tickets email (géré par les admins)

                    // ── 7. Notification Teams ──────────────────────────────────
                    try {
                        app(TeamsService::class)->notify($ticket);
                    } catch (\Exception $e) {
                        Log::warning("[FetchSupportEmails] Teams notify failed: " . $e->getMessage());
                    }

                    // ── 8. Notification Gmail aux admins ───────────────────────
                    if (\App\Models\Setting::get('notify_new_ticket', '1') === '1') {
                        try {
                            $admins = User::where('role', 'admin')
                                ->where('is_active', true)
                                ->whereNotNull('email')
                                ->get();

                            foreach ($admins as $admin) {
                                $html = view('emails.new-ticket', [
                                    'ticket' => $ticket,
                                    'admin'  => $admin,
                                ])->render();
                                $gmail->send(
                                    $admin->email,
                                    "🎫 Nouveau ticket #{$ticket->id} (email) : " . Str::limit($subject, 60),
                                    $html
                                );
                            }
                        } catch (\Exception $e) {
                            Log::warning("[FetchSupportEmails] Admin email notify failed: " . $e->getMessage());
                        }
                    }

                    // ── 9. Confirmation au client ──────────────────────────────
                    try {
                        $html = view('emails.ticket-confirmation', [
                            'ticket' => $ticket,
                            'user'   => $user,
                        ])->render();
                        $gmail->send(
                            $senderEmail,
                            "✅ Votre demande #{$ticket->id} a bien été reçue — L2T Support",
                            $html
                        );
                    } catch (\Exception $e) {
                        Log::warning("[FetchSupportEmails] Confirmation email failed: " . $e->getMessage());
                    }

                    // ── 10. Sync GLPI ───────────────────────────────────────────
                    try {
                        $glpi   = app(GlpiService::class);
                        $result = $glpi->createTicket([
                            'name'    => $ticket->title,
                            'content' => $ticket->description,
                            'urgency' => $ticket->urgency,
                            'impact'  => $ticket->impact,
                            'priority'=> $ticket->priority,
                        ]);

                        if (!empty($result['id'])) {
                            $ticket->update([
                                'glpi_ticket_id' => $result['id'],
                                'sync_status'    => 'synced',
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::warning("[FetchSupportEmails] GLPI sync failed: " . $e->getMessage());
                    }

                    // ── 11. Marquer comme lu dans Gmail ─────────────────────────
                    $this->markRead($service, $msgRef->getId());

                    $created++;
                    $this->info("[FetchSupportEmails] Ticket #{$ticket->id} créé pour {$senderEmail}");

                } catch (\Exception $e) {
                    Log::error("[FetchSupportEmails] Erreur traitement email: " . $e->getMessage());
                    // On continue avec le prochain email même en cas d'erreur
                }
            }

            $this->info("[FetchSupportEmails] Terminé — {$created} ticket(s) créé(s).");

        } catch (\Exception $e) {
            Log::error("[FetchSupportEmails] Fatal: " . $e->getMessage());
            $this->error("[FetchSupportEmails] Erreur: " . $e->getMessage());
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function parseHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $h) {
            $result[$h->getName()] = $h->getValue();
        }
        return $result;
    }

    private function extractEmail(string $from): string
    {
        if (preg_match('/<([^>]+)>/', $from, $m)) {
            return strtolower(trim($m[1]));
        }
        return strtolower(trim($from));
    }

    private function extractName(string $from): string
    {
        if (preg_match('/^(.+?)\s*</', $from, $m)) {
            return trim($m[1], ' "\'');
        }
        return '';
    }

    private function extractBody($payload): string
    {
        // Chercher text/plain d'abord, puis text/html
        $body = $this->getPartBody($payload, 'text/plain')
              ?: $this->getPartBody($payload, 'text/html')
              ?: '';

        // Nettoyer HTML basique
        if (str_contains($body, '<')) {
            $body = strip_tags($body);
        }

        // Supprimer les lignes de citation (>) communes dans les réponses email
        $lines = explode("\n", $body);
        $lines = array_filter($lines, fn($l) => !str_starts_with(trim($l), '>'));
        $body  = implode("\n", $lines);

        return trim(Str::limit($body, 2000));
    }

    private function getPartBody($payload, string $mimeType): string
    {
        if ($payload->getMimeType() === $mimeType) {
            $data = $payload->getBody()->getData();
            if ($data) {
                return base64_decode(strtr($data, '-_', '+/'));
            }
        }

        foreach ($payload->getParts() ?? [] as $part) {
            $found = $this->getPartBody($part, $mimeType);
            if ($found) return $found;
        }

        return '';
    }

    private function markRead(GmailApi $service, string $msgId): void
    {
        try {
            $mods = new \Google\Service\Gmail\ModifyMessageRequest();
            $mods->setRemoveLabelIds(['UNREAD']);
            $service->users_messages->modify('me', $msgId, $mods);
        } catch (\Exception $e) {
            Log::warning("[FetchSupportEmails] markRead failed: " . $e->getMessage());
        }
    }

    private function autoAssign(Ticket $ticket, string $method): ?int
    {
        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->get();

        if ($admins->isEmpty()) return null;

        return match ($method) {
            'Round-robin' => $this->roundRobin($admins),
            'Par catégorie' => $this->byCategory($ticket, $admins),
            'Par charge' => $this->byWorkload($admins),
            default => $this->roundRobin($admins),
        };
    }

    private function roundRobin($admins): int
    {
        $lastId = \App\Models\Setting::get('rr_last_admin_id');
        $ids    = $admins->pluck('id')->toArray();
        $idx    = array_search($lastId, $ids);
        $next   = $ids[($idx !== false ? $idx + 1 : 0) % count($ids)];
        \App\Models\Setting::set('rr_last_admin_id', $next);
        return $next;
    }

    private function byCategory(Ticket $ticket, $admins): int
    {
        // Assigner à l'admin avec le plus de tickets résolus dans cette catégorie
        $best = $admins->sortByDesc(function ($admin) use ($ticket) {
            return Ticket::where('assigned_to', $admin->id)
                ->where('category', $ticket->category)
                ->whereIn('sync_status', ['resolved', 'closed', 'synced'])
                ->count();
        })->first();

        return $best ? $best->id : $admins->first()->id;
    }

    private function byWorkload($admins): int
    {
        // Assigner à l'admin avec le moins de tickets ouverts
        $least = $admins->sortBy(function ($admin) {
            return Ticket::where('assigned_to', $admin->id)
                ->whereNotIn('sync_status', ['resolved', 'closed', 'synced'])
                ->count();
        })->first();

        return $least ? $least->id : $admins->first()->id;
    }
}