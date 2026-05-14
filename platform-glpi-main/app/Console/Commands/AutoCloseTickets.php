<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Services\GmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseTickets extends Command
{
    protected $signature   = 'tickets:auto-close';
    protected $description = 'Clôture automatiquement les tickets résolus depuis plus de 5 jours';

    public function handle(): void
    {
        // Tickets résolus depuis plus de 5 jours et pas encore clôturés
        $tickets = Ticket::where('sync_status', 'resolved')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', now()->subDays(5))
            ->with('user')
            ->get();

        if ($tickets->isEmpty()) {
            $this->info('Aucun ticket à clôturer.');
            return;
        }

        $gmail = app(GmailService::class);

        foreach ($tickets as $ticket) {
            // Clôturer le ticket
            $ticket->update(['sync_status' => 'closed']);

            $this->info("Ticket #{$ticket->id} clôturé automatiquement.");
            \App\Models\AuditLog::log(
                'AUTO CLOSE',
                'Tickets',
                "Clôture automatique ticket #{$ticket->id}: {$ticket->title} (résolu depuis 5 jours)"
            );

            // Envoyer email au client
            try {
                $html = view('emails.ticket-auto-closed', compact('ticket'))->render();
                $gmail->send(
                    $ticket->user->email,
                    "🔒 Ticket #{$ticket->id} clôturé automatiquement — L2T Support",
                    $html
                );
            } catch (\Exception $e) {
                Log::error("Erreur email auto-close ticket #{$ticket->id}: " . $e->getMessage());
            }
        }

        $this->info("✅ {$tickets->count()} ticket(s) clôturé(s) automatiquement.");
    }
}
