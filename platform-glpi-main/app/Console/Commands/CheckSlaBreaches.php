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
            $ticket->update(['sla_breached' => true]);
 
            // Notifier l'admin
            try {
                $admin = \App\Models\User::where('role', 'admin')
                    ->where('is_active', true)
                    ->first();
 
                if ($admin) {
                    $gmail = app(\App\Services\GmailService::class);
                    $gmail->send(
                        $admin->email,
                        "⚠️ SLA dépassé — Ticket #{$ticket->id}: {$ticket->title}",
                        "<p>Le ticket <strong>#{$ticket->id}</strong> a dépassé son SLA.</p>
                         <p>Priorité: {$ticket->priority} | Deadline était: {$ticket->sla_due_at}</p>"
                    );
                }
            } catch (\Exception $e) {
                \Log::error('SLA notification failed: ' . $e->getMessage());
            }
 
            $this->warn("  ⚠️  Ticket #{$ticket->id} — {$ticket->title}");
        }
 
        return Command::SUCCESS;
    }
}