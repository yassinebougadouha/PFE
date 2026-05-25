<?php
namespace App\Console\Commands;
 
use Illuminate\Console\Command;
use App\Models\Ticket;
use App\Services\GlpiService;
 
class CheckSlaBreaches extends Command
{
    protected $signature   = 'glpi:check-sla';
    protected $description = 'Vérifier les tickets qui ont dépassé leur SLA';
 
    public function handle(): int
    {
        $tickets = Ticket::whereNotNull('sla_due_at')
            ->where('sla_breached', false)
            ->whereNotIn('sync_status', ['resolved', 'closed'])
            ->where('sla_due_at', '<', now())
            ->get();
 
        $this->info("⏳ Vérification SLA — {$tickets->count()} tickets dépassés trouvés.");
 
        foreach ($tickets as $ticket) {
            $ticket->update([
                'sla_breached' => true,
                'urgency'      => 5,
                'priority'     => 5,
            ]);

            // Notifications Plateforme
            $baseNotification = [
                'type'      => 'ticket_sla_breached',
                'icon'      => 'alarm_on',
                'color'     => 'danger',
                'title'     => "⚠️ Ticket Urgent (SLA dépassé)",
                'body'      => "Le ticket #{$ticket->id} a dépassé son SLA et est devenu urgent.",
                'ticket_id' => $ticket->id,
            ];

            \App\Models\Notification::sendToAdmins(array_merge($baseNotification, [
                'url' => "/admin/tickets/{$ticket->id}"
            ]));

            \App\Models\Notification::sendToSuperAdmins(array_merge($baseNotification, [
                'url' => "/super-admin/tickets/{$ticket->id}"
            ]));
 
            // Notifier par email (Admins w Super Admins)
            try {
                $notifiableUsers = \App\Models\User::whereIn('role', ['admin', 'super_admin'])
                    ->where('is_active', true)
                    ->get();
 
                if ($notifiableUsers->isNotEmpty()) {
                    $gmail = app(\App\Services\GmailService::class);
                    
                    foreach ($notifiableUsers as $user) {
                        $gmail->send(
                            $user->email,
                            "⚠️ SLA dépassé — Ticket #{$ticket->id}: {$ticket->title}",
                            "<p>Le ticket <strong>#{$ticket->id}</strong> a dépassé son SLA et est devenu URGENT.</p>
                             <p>Priorité: {$ticket->priority} | Deadline était: {$ticket->sla_due_at}</p>"
                        );
                    }
                }
            } catch (\Exception $e) {
                \Log::error('SLA notification failed: ' . $e->getMessage());
            }
 
            $this->warn("  ⚠️  Ticket #{$ticket->id} — {$ticket->title} (Urgent)");
        }
 
        return Command::SUCCESS;
    }
}