<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TeamsService
{
    // ── Vérifier si le webhook est configuré ─────────────────────────────────
    public function isConfigured(): bool
    {
        $url = \App\Models\Setting::get('teams_webhook_url')
             ?? config('services.teams.webhook_url');
        return !empty($url);
    }

    // ── Notifier ticket résolu ────────────────────────────────────────────────
    public function notifyTicketResolved(Ticket $ticket, string $resolvedBy = ''): void
    {
        $webhookUrl = \App\Models\Setting::get('teams_webhook_url')
                   ?? config('services.teams.webhook_url');
        if (!$webhookUrl) return;

        $ticketUrl = config('app.url') . '/admin/tickets/' . $ticket->id;

        $payload = [
            'ticket_id'   => $ticket->id,
            'title'       => '✅ Ticket résolu — ' . ($ticket->title ?? 'Sans titre'),
            'status'      => 'resolved',
            'priority'    => $this->formatPriority($ticket->priority),
            'requester'   => optional($ticket->user)->name ?? 'Inconnu',
            'category'    => $ticket->category ?? 'Non classé',
            'description' => 'Résolu par : ' . ($resolvedBy ?: 'Admin') .
                             ($ticket->solution ? "\n💬 " . \Illuminate\Support\Str::limit($ticket->solution, 200) : ''),
            'mention'     => $resolvedBy ? "@{$resolvedBy}" : '',
            'date'        => now()->timezone('Africa/Tunis')->format('d/m/Y H:i'),
            'url'         => $ticketUrl,
        ];

        $this->send($webhookUrl, $payload, 'channel général (résolution)');
    }

    // ── Notifier nouveau ticket (appelé depuis FetchSupportEmails) ────────────
    public function notifyNewTicket(Ticket $ticket): void
    {
        $this->notify($ticket, 'new');
    }

    // ── Notifier nouveau commentaire ──────────────────────────────────────────
    public function notifyNewComment(Ticket $ticket, $comment = null): void
    {
        // Utilise le même canal que notify() mais avec un payload commentaire
        $webhookUrl = \App\Models\Setting::get('teams_webhook_url')
                   ?? config('services.teams.webhook_url');
        if (!$webhookUrl) return;

        $admin = $ticket->assigned_to ? User::find($ticket->assigned_to) : null;
        $mentionText = $this->buildMentionText($admin);
        $ticketUrl   = config('app.url') . '/admin/tickets/' . $ticket->id;

        $payload = [
            'ticket_id'   => $ticket->id,
            'title'       => '💬 Nouveau commentaire — ' . ($ticket->title ?? 'Sans titre'),
            'status'      => $ticket->sync_status ?? 'pending',
            'priority'    => $this->formatPriority($ticket->priority),
            'requester'   => optional($ticket->user)->name ?? 'Inconnu',
            'category'    => $ticket->category ?? 'Non classé',
            'description' => $comment ? \Illuminate\Support\Str::limit($comment->content ?? '', 200) : '',
            'mention'     => $mentionText,
            'date'        => now()->timezone('Africa/Tunis')->format('d/m/Y H:i'),
            'url'         => $ticketUrl,
        ];

        $this->send($webhookUrl, $payload, 'channel général (commentaire)');
    }

    // ── Méthode principale ────────────────────────────────────────────────────
    public function notify(Ticket $ticket, string $event = 'new'): void
    {
        $routingMethod = \App\Models\Setting::get('teams_routing_method', 'general');

        if ($routingMethod === 'category') {
            $admin = $this->findAdminByCategory($ticket->category ?? '');
            $this->notifyGeneralWithMention($ticket, $admin);
        } else {
            $assignedAdmin = $ticket->assigned_to
                ? User::find($ticket->assigned_to)
                : null;
            $this->notifyGeneralWithMention($ticket, $assignedAdmin);
        }
    }

    // ── Notifier channel général avec @tag admin ──────────────────────────────
    public function notifyGeneralWithMention(Ticket $ticket, ?User $assignedAdmin = null): bool
    {
        $webhookUrl = \App\Models\Setting::get('teams_webhook_url')
                   ?? config('services.teams.webhook_url');

        if (!$webhookUrl) {
            Log::warning('TeamsService: teams_webhook_url non configuré.');
            return false;
        }

        $payload = $this->buildPayload($ticket, $assignedAdmin);
        return $this->send($webhookUrl, $payload, 'channel général');
    }

    // ── Build mention text ────────────────────────────────────────────────────
    private function buildMentionText(?User $admin): string
    {
        if ($admin && $admin->teams_email) {
            return "<at>{$admin->name}</at>";
        } elseif ($admin && $admin->name) {
            return "@{$admin->name}";
        }
        return "En attente d'assignation";
    }

    // ── Formater la priorité (int → label) ───────────────────────────────────
    private function formatPriority($priority): string
    {
        $map = [
            5 => '🔴 Très haute',
            4 => '🟠 Haute',
            3 => '🟡 Moyenne',
            2 => '🟢 Basse',
            1 => '🟢 Très basse',
        ];

        // Si c'est un int (1-5)
        if (is_numeric($priority)) {
            return $map[(int)$priority] ?? '🟡 Moyenne';
        }

        // Si c'est déjà un string
        return match(strtolower($priority)) {
            'très haute', 'critique' => '🔴 Très haute',
            'haute'                   => '🟠 Haute',
            'moyenne'                 => '🟡 Moyenne',
            'basse'                   => '🟢 Basse',
            default                   => '🟡 Moyenne',
        };
    }

    // ── Build payload complet ─────────────────────────────────────────────────
    private function buildPayload(Ticket $ticket, ?User $admin): array
    {
        $ticketUrl   = config('app.url') . '/admin/tickets/' . $ticket->id;
        $mentionText = $this->buildMentionText($admin);

        // On intègre le responsable directement dans le body
        // pour qu'il apparaisse dans Teams quelle que soit la template Power Automate
        $body = '';
        if ($admin) {
            $body .= "📌 Responsable: {$admin->name}";
            if ($admin->teams_email) {
                $body .= " ({$admin->teams_email})";
            }
        } else {
            $body .= "📌 Responsable: En attente d'assignation";
        }
        if ($ticket->description) {
            $body .= "
📝 " . \Illuminate\Support\Str::limit($ticket->description, 200);
        }

        return [
            'ticket_id'   => $ticket->id,
            'title'       => $ticket->title ?? 'Sans titre',
            'status'      => $ticket->sync_status ?? 'pending',
            'priority'    => $this->formatPriority($ticket->priority),
            'requester'   => optional($ticket->user)->name ?? 'Inconnu',
            'category'    => $ticket->category ?? 'Non classé',
            'description' => $body,
            'mention'     => $mentionText,
            'responsable' => $admin ? $admin->name : 'Non assigné',
            'responsable_email' => $admin ? ($admin->teams_email ?? '') : '',
            'date'        => now()->timezone('Africa/Tunis')->format('d/m/Y H:i'),
            'url'         => $ticketUrl,
        ];
    }

    // ── HTTP send ─────────────────────────────────────────────────────────────
    private function send(string $url, array $payload, string $target): bool
    {
        try {
            $response = Http::timeout(10)->post($url, $payload);

            if ($response->successful()) {
                Log::info("TeamsService: Notification envoyée → {$target} (ticket #{$payload['ticket_id']})");
                return true;
            }

            Log::warning("TeamsService: Échec → {$target} — HTTP {$response->status()}");
            return false;

        } catch (\Exception $e) {
            Log::error("TeamsService: Erreur → {$target} — " . $e->getMessage());
            return false;
        }
    }

    // ── Trouver admin responsable d'une catégorie ─────────────────────────────
    public function findAdminByCategory(string $category): ?User
    {
        if (!$category) return null;

        // Recherche exacte d'abord
        $mapping = DB::table('category_admin_mappings')
            ->where('category', $category)
            ->first();

        // Fallback: recherche insensible à la casse
        if (!$mapping) {
            $mapping = DB::table('category_admin_mappings')
                ->whereRaw('LOWER(category) = ?', [strtolower($category)])
                ->first();
        }

        return $mapping ? User::find($mapping->admin_id) : null;
    }
}