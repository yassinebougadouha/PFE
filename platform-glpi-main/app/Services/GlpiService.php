<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Ticket;

class GlpiService
{
    protected string $baseUrl;
    protected string $appToken;
    protected string $userToken;
    protected ?string $sessionToken = null;
    protected int $timeout = 5; // secondes max par requête GLPI

    public function __construct()
    {
        // Lire depuis DB en priorité, fallback vers .env
        $url      = \App\Models\Setting::get('glpi_url')       ?: config('services.glpi.url');
        $appToken = \App\Models\Setting::get('glpi_app_token') ?: config('services.glpi.app_token');
        $userToken = null;
        $raw = \App\Models\Setting::get('glpi_user_token');
        if ($raw) {
            try { $userToken = decrypt($raw); } catch (\Exception $e) { $userToken = null; }
        }
        $userToken = $userToken ?: config('services.glpi.user_token');

        $this->baseUrl   = rtrim((string) $url, '/');
        $this->appToken  = (string) $appToken;
        $this->userToken = (string) $userToken;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // HELPERS INTERNES
    // ══════════════════════════════════════════════════════════════════════════

    // Helper HTTP avec timeout automatique
    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout($this->timeout)->connectTimeout(3)->withHeaders($this->headers());
    }

    protected function headers(): array
    {
        $headers = [
            'App-Token'    => $this->appToken,
            'Content-Type' => 'application/json',
        ];
        if ($this->sessionToken) {
            $headers['Session-Token'] = $this->sessionToken;
        }
        return $headers;
    }

    protected function ensureSession(): void
    {
        if (!$this->sessionToken) {
            $this->initSession();
        }
    }

    protected function logSync(
        string $action,
        string $status,
        ?int $ticketId = null,
        mixed $payload = null,
        mixed $response = null,
        ?string $error = null
    ): void {
        try {
            \DB::table('glpi_sync_logs')->insert([
                'ticket_id'  => $ticketId,
                'action'     => $action,
                'status'     => $status,
                'payload'    => $payload ? json_encode($payload) : null,
                'response'   => $response ? json_encode($response) : null,
                'error'      => $error,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('GlpiService logSync failed: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 1. SESSION
    // ══════════════════════════════════════════════════════════════════════════

   public function initSession(): string
{
    $response = Http::timeout($this->timeout)->connectTimeout(3)->withHeaders([
        'App-Token'     => $this->appToken,
        'Authorization' => 'user_token ' . $this->userToken,
        'Content-Type'  => 'application/json',
    ])->get($this->baseUrl . '/apirest.php/initSession');

    if (!$response->successful()) {
        throw new \Exception('GLPI initSession failed: ' . $response->status() . ' ' . $response->body());
    }

    $this->sessionToken = $response->json('session_token');
    return $this->sessionToken;
}

    public function killSession(): void
    {
        if (!$this->sessionToken) return;

        try {
            Http::timeout(3)->connectTimeout(2)->withHeaders($this->headers())
                ->get($this->baseUrl . '/apirest.php/killSession');
        } catch (\Exception $e) {}

        $this->sessionToken = null;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 2. CRUD GÉNÉRIQUE
    // ══════════════════════════════════════════════════════════════════════════

    public function getItem(string $itemtype, int $id, array $params = []): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . "/apirest.php/{$itemtype}/{$id}", $params);

        if (!$response->successful()) {
            throw new \Exception("GLPI getItem {$itemtype}/{$id} failed: " . $response->body());
        }
        return $response->json();
    }

    public function getAllItems(string $itemtype, array $params = []): array
    {
        $this->ensureSession();
        $params = array_merge(['range' => '0-999', 'order' => 'DESC'], $params);

        $response = $this->http()
            ->get($this->baseUrl . "/apirest.php/{$itemtype}/", $params);

        if (!$response->successful()) {
            throw new \Exception("GLPI getAllItems {$itemtype} failed: " . $response->body());
        }
        return $response->json() ?? [];
    }

    public function addItem(string $itemtype, array $input): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->post($this->baseUrl . "/apirest.php/{$itemtype}/", ['input' => $input]);

        if (!$response->successful()) {
            throw new \Exception("GLPI addItem {$itemtype} failed: " . $response->body());
        }
        return $response->json();
    }

    public function updateItem(string $itemtype, int $id, array $input): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->put($this->baseUrl . "/apirest.php/{$itemtype}/{$id}", ['input' => $input]);

        if (!$response->successful()) {
            throw new \Exception("GLPI updateItem {$itemtype}/{$id} failed: " . $response->body());
        }
        return $response->json();
    }

    public function deleteItem(string $itemtype, int $id, bool $forcePurge = true): bool
    {
        $this->ensureSession();
        $response = $this->http()
            ->delete($this->baseUrl . "/apirest.php/{$itemtype}/{$id}", [
                'force_purge' => $forcePurge,
            ]);
        return $response->successful();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 3. TICKETS
    // ══════════════════════════════════════════════════════════════════════════

    public function createTicket(array $data): array
    {
        $input = [
            'name'     => $data['title'],
            'content'  => $data['description'],
            'urgency'  => $data['urgency']  ?? 3,
            'impact'   => $data['impact']   ?? 3,
            'priority' => $data['priority'] ?? 3,
            'status'   => 1,
        ];

        if (!empty($data['glpi_category_id'])) {
            $input['itilcategories_id'] = $data['glpi_category_id'];
        }

        if (!empty($data['glpi_user_id'])) {
            $input['_users_id_requester'] = $data['glpi_user_id'];
        }

        $result = $this->addItem('Ticket', $input);
        $this->logSync('create', 'success', null, $input, $result);
        return $result;
    }

    public function getTicket(int $glpiId): array
    {
        return $this->getItem('Ticket', $glpiId);
    }

    public function updateTicket(int $glpiId, array $data): array
    {
        $result = $this->updateItem('Ticket', $glpiId, $data);
        $this->logSync('update', 'success', null, $data, $result);
        return $result;
    }

    public function deleteTicket(int $glpiId): bool
    {
        $ok = $this->deleteItem('Ticket', $glpiId);
        $this->logSync('delete', $ok ? 'success' : 'failed', null, ['glpi_id' => $glpiId]);
        return $ok;
    }

    public function assignTicket(int $glpiId, int $glpiUserId): array
    {
        $result = $this->updateItem('Ticket', $glpiId, [
            '_users_id_assign' => $glpiUserId,
        ]);
        $this->logSync('assign', 'success', null, [
            'glpi_id'   => $glpiId,
            'assign_to' => $glpiUserId,
        ], $result);
        return $result;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 4. FOLLOWUPS & SOLUTIONS
    // ══════════════════════════════════════════════════════════════════════════

    public function getFollowups(int $glpiTicketId): array
    {
        return $this->getSubItems('Ticket', $glpiTicketId, 'ITILFollowup');
    }

    public function addFollowup(int $glpiTicketId, string $content, bool $isPrivate = false): array
    {
        return $this->addItem('ITILFollowup', [
            'items_id'   => $glpiTicketId,
            'itemtype'   => 'Ticket',
            'content'    => $content,
            'is_private' => $isPrivate ? 1 : 0,
        ]);
    }

    public function addSolution(int $glpiTicketId, string $content): array
    {
        return $this->addItem('ITILSolution', [
            'items_id' => $glpiTicketId,
            'itemtype' => 'Ticket',
            'content'  => $content,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 5. SUB-ITEMS
    // ══════════════════════════════════════════════════════════════════════════

    public function getSubItems(string $itemtype, int $id, string $subItemtype, array $params = []): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . "/apirest.php/{$itemtype}/{$id}/{$subItemtype}", $params);

        if (!$response->successful()) {
            throw new \Exception("GLPI getSubItems failed: " . $response->body());
        }
        return $response->json() ?? [];
    }

    public function getTicketLogs(int $glpiTicketId): array
    {
        return $this->getSubItems('Ticket', $glpiTicketId, 'Log');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 6. USERS GLPI
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Chercher user GLPI par email — 3 stratégies:
     *    1. login (name) = email exact
     *    2. API searchItems par email field (field 34)
     *    3. Scan complet avec comparaison email dans _useremails
     */
    public function findUserByEmail(string $email): ?array
    {
        try {
            $this->ensureSession();

            // Stratégie 1 : login = email (cas le plus courant dans GLPI)
            $allUsers = $this->getAllItems('User', [
                'range'           => '0-999',
                'forcedisplay[0]' => 1,   // id
                'forcedisplay[1]' => 2,   // name (login)
                'forcedisplay[2]' => 34,  // email
                'forcedisplay[3]' => 9,   // realname
                'forcedisplay[4]' => 8,   // firstname
            ]);

            $emailLower = strtolower(trim($email));

            foreach ($allUsers as $user) {
                // login = email
                $login = strtolower(trim($user['name'] ?? $user[2] ?? ''));
                if ($login === $emailLower) {
                    return $user;
                }

                // email field direct
                $glpiEmail = strtolower(trim($user['email'] ?? $user[34] ?? ''));
                if ($glpiEmail && $glpiEmail === $emailLower) {
                    return $user;
                }
            }

            // Stratégie 2 : searchItems avec criteria sur email (field 34)
            try {
                $searchResult = $this->searchItems('User', [
                    ['field' => '34', 'searchtype' => 'equals', 'value' => $email, 'link' => 'AND'],
                ], [
                    'range'           => '0-10',
                    'forcedisplay[0]' => 1,
                    'forcedisplay[1]' => 2,
                    'forcedisplay[2]' => 34,
                ]);

                $data = $searchResult['data'] ?? [];
                if (!empty($data)) {
                    $first = reset($data);
                    // field 1 = id, field 2 = name/login, field 34 = email
                    return [
                        'id'    => $first['1']  ?? $first[1]  ?? null,  // ← fix: field 1 = id
                        'name'  => $first['2']  ?? $first[2]  ?? '',
                        'email' => $first['34'] ?? $first[34] ?? $email,
                    ];
                }
            } catch (\Exception $e) {
                Log::info('GLPI findUserByEmail search strategy failed: ' . $e->getMessage());
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('GLPI findUserByEmail failed: ' . $e->getMessage());
            return null;
        }
    }

    public function createUser(array $data): array
    {
        $result = $this->addItem('User', [
            'name'          => $data['email'],
            'realname'      => $data['name'] ?? '',
            'is_active'     => 1,
            '_useremails'   => [$data['email']],
        ]);
        $this->logSync('create_user', 'success', null, $data, $result);
        return $result;
    }

    public function deactivateUser(int $glpiUserId): array
    {
        return $this->updateItem('User', $glpiUserId, ['is_active' => 0]);
    }

    public function activateUser(int $glpiUserId): array
    {
        return $this->updateItem('User', $glpiUserId, ['is_active' => 1]);
    }

    /**
     * syncUser — cherche par email EN PRIORITÉ
     * Retourne le glpi_user_id ou null
     */
    public function syncUser(\App\Models\User $user): ?int
    {
        try {
            $this->ensureSession();

            // Si déjà synchronisé — vérifier que le compte GLPI existe toujours
            if ($user->glpi_user_id) {
                try {
                    $glpiUser = $this->getItem('User', $user->glpi_user_id);
                    if (!empty($glpiUser['id'])) {
                        return (int) $user->glpi_user_id;
                    }
                } catch (\Exception $e) {
                    Log::info("GLPI user {$user->glpi_user_id} not found, re-syncing {$user->email}");
                }
            }

            // Chercher dans GLPI par email
            $glpiUser = $this->findUserByEmail($user->email);

            if ($glpiUser) {
                $glpiId = $glpiUser['id'] ?? $glpiUser[2] ?? $glpiUser[1] ?? null;
                if ($glpiId) {
                    $user->updateQuietly(['glpi_user_id' => (int) $glpiId]);
                    Log::info("GLPI syncUser: linked {$user->email} → GLPI ID {$glpiId}");
                    return (int) $glpiId;
                }
            }

            // Créer dans GLPI si n'existe pas
            $result = $this->createUser([
                'name'  => $user->name,
                'email' => $user->email,
            ]);

            $glpiId = $result['id'] ?? null;
            if ($glpiId) {
                $user->updateQuietly(['glpi_user_id' => (int) $glpiId]);
                Log::info("GLPI syncUser: created {$user->email} → GLPI ID {$glpiId}");
                return (int) $glpiId;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('GLPI syncUser failed for ' . $user->email . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync silencieux au login (sans exception)
     * Appelé dans AuthenticatedSessionController après login
     */
    public function silentSyncOnLogin(\App\Models\User $user): void
    {
        try {
            // Déjà synchronisé → rien à faire
            if ($user->glpi_user_id) return;

            $glpiUser = $this->findUserByEmail($user->email);

            if ($glpiUser) {
                $glpiId = $glpiUser['id'] ?? $glpiUser[2] ?? $glpiUser[1] ?? null;
                if ($glpiId) {
                    $user->updateQuietly(['glpi_user_id' => (int) $glpiId]);
                    Log::info("GLPI silentSync login: {$user->email} → ID {$glpiId}");
                }
            }

        } catch (\Exception $e) {
            Log::warning('GLPI silentSyncOnLogin failed (non-blocking): ' . $e->getMessage());
        } finally {
            try { $this->killSession(); } catch (\Exception $e) {}
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 7. CATÉGORIES
    // ══════════════════════════════════════════════════════════════════════════

    public function getCategories(): array
    {
        return $this->getAllItems('ITILCategory', [
            'range'           => '0-999',
            'forcedisplay[0]' => 1,
            'forcedisplay[1]' => 2,
            'forcedisplay[2]' => 3,
        ]);
    }

    public function syncCategoriesToLocal(): int
    {
        $this->ensureSession();
        $categories = $this->getCategories();
        $count = 0;

        foreach ($categories as $cat) {
            $glpiId = $cat['id'] ?? null;
            if (!$glpiId) continue;

            \DB::table('glpi_categories')->updateOrInsert(
                ['glpi_id' => $glpiId],
                [
                    'name'         => $cat['name'] ?? $cat[2] ?? 'Sans nom',
                    'completename' => $cat['completename'] ?? $cat[3] ?? null,
                    'parent_id'    => $cat['itilcategories_id'] ?? null,
                    'updated_at'   => now(),
                    'created_at'   => now(),
                ]
            );
            $count++;
        }

        Log::info("GLPI: {$count} catégories synchronisées.");
        return $count;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 8. SEARCH
    // ══════════════════════════════════════════════════════════════════════════

    public function searchItems(string $itemtype, array $criteria = [], array $params = []): array
    {
        $this->ensureSession();
        $query = array_merge(['range' => '0-999'], $params);

        foreach ($criteria as $i => $criterion) {
            $query["criteria[{$i}][field]"]      = $criterion['field'];
            $query["criteria[{$i}][searchtype]"] = $criterion['searchtype'] ?? 'contains';
            $query["criteria[{$i}][value]"]      = $criterion['value'];
            if (isset($criterion['link'])) {
                $query["criteria[{$i}][link]"] = $criterion['link'];
            }
        }

        $response = $this->http()
            ->get($this->baseUrl . "/apirest.php/search/{$itemtype}", $query);

        if (!$response->successful()) {
            throw new \Exception("GLPI searchItems {$itemtype} failed: " . $response->body());
        }
        return $response->json() ?? [];
    }

    public function getResolvedTicketsForAI(): array
    {
        return $this->searchItems('Ticket', [
            ['field' => '12', 'searchtype' => 'equals', 'value' => 5],
        ], [
            'range'           => '0-9999',
            'forcedisplay[0]' => 1,
            'forcedisplay[1]' => 21,
            'forcedisplay[2]' => 2,
            'forcedisplay[3]' => 3,
            'forcedisplay[4]' => 12,
            'forcedisplay[5]' => 10,
            'forcedisplay[6]' => 11,
            'forcedisplay[7]' => 3,
            'forcedisplay[8]' => 15,
            'forcedisplay[9]' => 7,
        ]);
    }

    public function searchTicketsByStatus(int $glpiStatus): array
    {
        return $this->searchItems('Ticket', [
            ['field' => '12', 'searchtype' => 'equals', 'value' => $glpiStatus],
        ]);
    }

    public function getAllTicketsForDashboard(): array
    {
        return $this->searchItems('Ticket', [], [
            'range'           => '0-9999',
            'forcedisplay[0]' => 1,
            'forcedisplay[1]' => 21,
            'forcedisplay[2]' => 12,
            'forcedisplay[3]' => 10,
            'forcedisplay[4]' => 3,
            'forcedisplay[5]' => 15,
            'forcedisplay[6]' => 7,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 9. DOCUMENTS
    // ══════════════════════════════════════════════════════════════════════════

    public function uploadDocument(string $filePath, string $fileName): array
    {
        $this->ensureSession();

        $response = Http::timeout($this->timeout)->connectTimeout(3)->withHeaders([
            'App-Token'     => $this->appToken,
            'Session-Token' => $this->sessionToken,
        ])->attach('filename[0]', file_get_contents($filePath), $fileName)
          ->post($this->baseUrl . '/apirest.php/Document/', [
              'uploadManifest' => json_encode([
                  'input' => [
                      'name'      => $fileName,
                      '_filename' => [$fileName],
                  ],
              ]),
          ]);

        if (!$response->successful()) {
            throw new \Exception('GLPI uploadDocument failed: ' . $response->body());
        }
        return $response->json();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 10. MAPPING STATUTS
    // ══════════════════════════════════════════════════════════════════════════

    public static function mapGlpiStatus(int $glpiStatus): string
    {
        return match ($glpiStatus) {
            1       => 'pending',
            2, 3, 4 => 'in_progress',
            5       => 'resolved',
            6       => 'closed',
            default => 'pending',
        };
    }

    public static function mapLocalStatus(string $localStatus): int
    {
        return match ($localStatus) {
            'pending'     => 1,
            'in_progress' => 2,
            'resolved'    => 5,
            'closed'      => 6,
            default       => 1,
        };
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 11. SLA
    // ══════════════════════════════════════════════════════════════════════════

    public static function calculateSlaDue(int $priority, \Carbon\Carbon $createdAt): \Carbon\Carbon
    {
        $slaHours = match ($priority) {
            5 => (int) \App\Models\Setting::get('sla_priority_5', 4),
            4 => (int) \App\Models\Setting::get('sla_priority_4', 8),
            3 => (int) \App\Models\Setting::get('sla_priority_3', 24),
            2 => (int) \App\Models\Setting::get('sla_priority_2', 48),
            1 => (int) \App\Models\Setting::get('sla_priority_1', 96),
            default => 24,
        };

        return $createdAt->copy()->addHours($slaHours);
    }

    public static function isSlaBreached(Ticket $ticket): bool
    {
        if (!$ticket->sla_due_at) return false;
        if (in_array($ticket->sync_status, ['resolved', 'closed'])) return false;
        return now()->gt($ticket->sla_due_at);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 12. TEMPS DE RÉSOLUTION
    // ══════════════════════════════════════════════════════════════════════════

    public function syncTicketResolutionTime(Ticket $ticket): ?int
    {
        if (!$ticket->glpi_ticket_id) return null;

        try {
            $this->ensureSession();
            $logs = $this->getTicketLogs($ticket->glpi_ticket_id);

            $resolvedLog = collect($logs)->first(function ($log) {
                return isset($log['new_value']) && $log['new_value'] == 5
                    && isset($log['field']) && str_contains(strtolower($log['field']), 'status');
            });

            if ($resolvedLog && $ticket->created_at) {
                $resolvedAt     = \Carbon\Carbon::parse($resolvedLog['date_mod'] ?? $resolvedLog['date_creation']);
                $resolutionMins = (int) $ticket->created_at->diffInMinutes($resolvedAt);

                $ticket->update([
                    'glpi_resolution_time' => $resolutionMins,
                    'glpi_logs'            => json_encode($logs),
                ]);

                return $resolutionMins;
            }

        } catch (\Exception $e) {
            Log::error('GLPI syncTicketResolutionTime failed: ' . $e->getMessage());
        }

        return null;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 13. MÉTHODES SUPPLÉMENTAIRES
    // ══════════════════════════════════════════════════════════════════════════

    public function getFullSession(): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . '/apirest.php/getFullSession');
        if (!$response->successful()) throw new \Exception('GLPI getFullSession failed: ' . $response->body());
        return $response->json() ?? [];
    }

    public function getGlpiConfig(): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . '/apirest.php/getGlpiConfig');
        if (!$response->successful()) throw new \Exception('GLPI getGlpiConfig failed: ' . $response->body());
        return $response->json('cfg_glpi', []);
    }

    public function requestPasswordReset(string $email): bool
    {
        $response = Http::timeout($this->timeout)->connectTimeout(3)->withHeaders([
            'App-Token'    => $this->appToken,
            'Content-Type' => 'application/json',
        ])->put($this->baseUrl . '/apirest.php/lostPassword', ['email' => $email]);
        return $response->successful();
    }

    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $response = Http::timeout($this->timeout)->connectTimeout(3)->withHeaders([
            'App-Token'    => $this->appToken,
            'Content-Type' => 'application/json',
        ])->put($this->baseUrl . '/apirest.php/lostPassword', [
            'email'                 => $email,
            'password_forget_token' => $token,
            'password'              => $newPassword,
        ]);
        return $response->successful();
    }

    public function getMyProfiles(): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . '/apirest.php/getMyProfiles');
        if (!$response->successful()) throw new \Exception('GLPI getMyProfiles failed: ' . $response->body());
        return $response->json('myprofiles', []);
    }

    public function getActiveProfile(): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . '/apirest.php/getActiveProfile');
        if (!$response->successful()) throw new \Exception('GLPI getActiveProfile failed: ' . $response->body());
        return $response->json() ?? [];
    }

    public function changeActiveProfile(int $profileId): bool
    {
        $this->ensureSession();
        $response = $this->http()
            ->post($this->baseUrl . '/apirest.php/changeActiveProfile', ['profiles_id' => $profileId]);
        return $response->successful();
    }

    public function getMyEntities(bool $recursive = false): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . '/apirest.php/getMyEntities', ['is_recursive' => $recursive]);
        if (!$response->successful()) throw new \Exception('GLPI getMyEntities failed: ' . $response->body());
        return $response->json('myentities', []);
    }

    public function getActiveEntities(): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . '/apirest.php/getActiveEntities');
        if (!$response->successful()) throw new \Exception('GLPI getActiveEntities failed: ' . $response->body());
        return $response->json() ?? [];
    }

    public function changeActiveEntities(int|string $entityId = 'all', bool $recursive = false): bool
    {
        $this->ensureSession();
        $response = $this->http()
            ->post($this->baseUrl . '/apirest.php/changeActiveEntities', [
                'entities_id'  => $entityId,
                'is_recursive' => $recursive,
            ]);
        return $response->successful();
    }

    public function getMultipleItems(array $items, array $params = []): array
    {
        $this->ensureSession();
        $query = $params;
        foreach ($items as $i => $item) {
            $query["items[{$i}][itemtype]"] = $item['itemtype'];
            $query["items[{$i}][items_id]"] = $item['id'];
        }
        $response = $this->http()
            ->get($this->baseUrl . '/apirest.php/getMultipleItems', $query);
        if (!$response->successful()) throw new \Exception('GLPI getMultipleItems failed: ' . $response->body());
        return $response->json() ?? [];
    }

    public function listSearchOptions(string $itemtype): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . "/apirest.php/listSearchOptions/{$itemtype}");
        if (!$response->successful()) throw new \Exception('GLPI listSearchOptions failed: ' . $response->body());
        return $response->json() ?? [];
    }

    public function getMassiveActions(string $itemtype, ?int $id = null): array
    {
        $this->ensureSession();
        $url = $this->baseUrl . "/apirest.php/getMassiveActions/{$itemtype}";
        if ($id !== null) $url .= "/{$id}";
        $response = $this->http()->get($url);
        if (!$response->successful()) throw new \Exception('GLPI getMassiveActions failed: ' . $response->body());
        return $response->json() ?? [];
    }

    public function getMassiveActionParameters(string $itemtype, string $actionKey): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . "/apirest.php/getMassiveActionParameters/{$itemtype}/{$actionKey}");
        if (!$response->successful()) throw new \Exception('GLPI getMassiveActionParameters failed: ' . $response->body());
        return $response->json() ?? [];
    }

    public function applyMassiveAction(string $itemtype, string $actionKey, array $ids, array $input = []): array
    {
        $this->ensureSession();
        $response = $this->http()
            ->post($this->baseUrl . "/apirest.php/applyMassiveAction/{$itemtype}/{$actionKey}", [
                'ids'   => $ids,
                'input' => $input,
            ]);
        if (!$response->successful()) throw new \Exception('GLPI applyMassiveAction failed: ' . $response->body());
        return $response->json() ?? [];
    }

    public function downloadDocument(int $documentId): string
    {
        $this->ensureSession();
        $response = Http::timeout($this->timeout)->connectTimeout(3)->withHeaders(array_merge($this->headers(), [
            'Accept' => 'application/octet-stream',
        ]))->get($this->baseUrl . "/apirest.php/Document/{$documentId}");
        if (!$response->successful()) throw new \Exception('GLPI downloadDocument failed: ' . $response->body());
        return $response->body();
    }

    public function getUserPicture(int $userId): ?string
    {
        $this->ensureSession();
        $response = $this->http()
            ->get($this->baseUrl . "/apirest.php/User/{$userId}/Picture");
        if ($response->status() === 204) return null;
        if (!$response->successful()) return null;
        return $response->body();
    }

    /**
     * Import users depuis GLPI — client_type assigné à 'client' par défaut
     */
    public function importUsersFromGlpi(string $defaultRole = 'client'): array
    {
        $this->ensureSession();

        $glpiUsers = $this->getAllItems('User', [
            'range'           => '0-999',
            'forcedisplay[0]' => 1,
            'forcedisplay[1]' => 2,
            'forcedisplay[2]' => 34,
            'forcedisplay[3]' => 9,
            'forcedisplay[4]' => 8,
            'forcedisplay[5]' => 43,
        ]);

        $results = [];
        $systemLogins = ['post-only', 'tech', 'normal', 'glpi-system', 'Super-Admin', 'glpi'];

        foreach ($glpiUsers as $gu) {
            $login = $gu['name'] ?? ($gu[2] ?? '');
            if (in_array($login, $systemLogins)) continue;

            $email = null;
            if (!empty($gu['name']) && filter_var($gu['name'], FILTER_VALIDATE_EMAIL)) {
                $email = $gu['name'];
            }
            if (!$email && !empty($gu[34])) $email = $gu[34];
            if (!$email && !empty($gu['email'])) $email = $gu['email'];

            if (!$email) {
                $results[] = ['login' => $login, 'status' => 'skip', 'reason' => 'Pas d\'email'];
                continue;
            }

            $glpiId = $gu['id'] ?? $gu[1] ?? null;

            $existing = \App\Models\User::where('email', $email)->first();
            if ($existing) {
                if (!$existing->glpi_user_id && $glpiId) {
                    $existing->updateQuietly(['glpi_user_id' => $glpiId]);
                }
                $results[] = [
                    'login'   => $login,
                    'email'   => $email,
                    'status'  => 'exists',
                    'role'    => $existing->role,
                    'glpi_id' => $glpiId,
                ];
                continue;
            }

            $firstname = $gu[8] ?? $gu['firstname'] ?? '';
            $lastname  = $gu[9] ?? $gu['realname']  ?? '';
            $name = trim($firstname . ' ' . $lastname) ?: $login;

            try {
                $user = \App\Models\User::create([
                    'name'         => $name,
                    'email'        => $email,
                    'password'     => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                    'role'         => $defaultRole,
                    'is_active'    => true,
                    'glpi_user_id' => $glpiId,
                    // Tous les users importés depuis GLPI sont classifiés comme 'client'
                    'client_type'  => $defaultRole === 'client' ? 'client' : null,
                ]);

                $results[] = [
                    'login'   => $login,
                    'email'   => $email,
                    'status'  => 'created',
                    'role'    => $defaultRole,
                    'glpi_id' => $glpiId,
                    'user_id' => $user->id,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'login'  => $login,
                    'email'  => $email,
                    'status' => 'error',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Fetch all tickets from GLPI and transform to view-compatible format.
     * Returns array of stdClass objects matching the local Ticket model shape.
     */
    public function getTransformedTickets(array $params = []): array
    {
        $glpiTickets = $this->getAllItems('Ticket', $params);

        // Batch-fetch all ticket-user assignments (type=2 = assignee)
        $ticketAssignees = [];
        try {
            $assignments = $this->getAllItems('Ticket_User', ['range' => '0-9999']);
            foreach ($assignments as $tu) {
                if (isset($tu['tickets_id'], $tu['users_id'], $tu['type']) && (int)$tu['type'] === 2) {
                    $ticketAssignees[(int)$tu['tickets_id']] = (int)$tu['users_id'];
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch Ticket_User assignments: ' . $e->getMessage());
        }

        $transformed = [];

        // Pre-load categories from local DB — glpi_id => name
        $catMap = \DB::table('glpi_categories')->pluck('name', 'glpi_id')->toArray();

        foreach ($glpiTickets as $t) {
            $status = match ((int)($t['status'] ?? 1)) {
                1       => 'pending',
                2, 3, 4 => 'in_progress',
                5       => 'resolved',
                6       => 'closed',
                default => 'pending',
            };

            $priority = (int)($t['priority'] ?? 3);
            $tid = (int)($t['id'] ?? 0);

            $obj = new \stdClass();
            $obj->id = $tid;
            $obj->glpi_id = $tid;
            $obj->title = $t['name'] ?? '';
            $obj->description = $t['content'] ?? '';
            $obj->sync_status = $status;
            $obj->status = $status; // alias — views may use either
            $obj->priority = $priority;
            $catGlpiId = (int)($t['itilcategories_id'] ?? 0);
            $obj->category = $catGlpiId ? ($catMap[$catGlpiId] ?? '') : '';
            $obj->solution = $t['solution'] ?? null;
            $obj->ai_analysis = null;
            $obj->user_id = $t['users_id_recipient'] ?? null;
            $obj->created_at = \Carbon\Carbon::parse($t['date_creation'] ?? $t['date'] ?? now());
            $obj->updated_at = \Carbon\Carbon::parse($t['date_mod'] ?? $obj->created_at);
            $obj->assigned_to = $ticketAssignees[$tid] ?? ($t['users_id_lastupdater'] ?? null);

            // Build a minimal user object for the view
            $userObj = new \stdClass();
            $userObj->id = $obj->user_id;
            $userObj->name = 'User #' . ($obj->user_id ?? '?');
            $userObj->email = '';
            $userObj->avatar = null;
            $userObj->client_type = 'client';
            $obj->user = $userObj;

            $transformed[] = $obj;
        }

        return $transformed;
    }

    /**
     * Map GLPI numeric status to local status string.
     */
    public static function mapGlpiStatusToLocal(int $glpiStatus): string
    {
        return match ($glpiStatus) {
            1       => 'pending',
            2, 3, 4 => 'in_progress',
            5       => 'resolved',
            6       => 'closed',
            default => 'pending',
        };
    }

    // ══════════════════════════════════════════════════════════════════════════
// 14. AUTHENTIFICATION
// ══════════════════════════════════════════════════════════════════════════

/**
 * Authentifier via GLPI - vérifier que le user existe et est actif
 * Retourne les données user GLPI ou null
 */
public function authenticate(string $email, string $password): ?array
{
    try {
        // Ouvrir session avec user_token admin
        $sessionToken = $this->initSession();

        // Chercher user GLPI par email
        $glpiUser = $this->findUserByEmail($email);

        // Fermer session
        $this->killSession();

        if (!$glpiUser) {
            return null;
        }

        // Retourner les données user
        return [
            'id'    => $glpiUser['id'] ?? $glpiUser[2] ?? null,
            'name'  => $glpiUser['name'] ?? $glpiUser[2] ?? $email,
            'email' => $email,
            'is_active' => $glpiUser['is_active'] ?? 1,
        ];

    } catch (\Exception $e) {
        \Log::error('GLPI authenticate failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Vérifier si un user existe dans GLPI par email
 * Utilisé pour l'authentification
 */
public function verifyUserExists(string $email): ?array
{
    try {
        $this->ensureSession();

        // Méthode 1: Chercher par login = email (le plus courant)
        $allUsers = $this->getAllItems('User', [
            'range'           => '0-999',
            'forcedisplay[0]' => '1',   // id
            'forcedisplay[1]' => '2',   // name (login)
            'forcedisplay[2]' => '34',  // email
            'forcedisplay[3]' => '9',   // realname
            'forcedisplay[4]' => '8',  // firstname
            'forcedisplay[5]' => '68', // is_active
        ]);

        $emailLower = strtolower(trim($email));

        foreach ($allUsers as $user) {
            // Vérifier login = email
            $login = strtolower(trim($user['name'] ?? $user[2] ?? ''));
            if ($login === $emailLower) {
                return [
                    'id'        => $user['id'] ?? $user[1] ?? null,
                    'name'      => $user['name'] ?? $user[2] ?? $email,
                    'email'     => $email,
                    'is_active' => $user['is_active'] ?? $user[68] ?? 1,
                ];
            }

            // Vérifier champ email direct
            $glpiEmail = strtolower(trim($user['email'] ?? $user[34] ?? ''));
            if ($glpiEmail && $glpiEmail === $emailLower) {
                return [
                    'id'        => $user['id'] ?? $user[1] ?? null,
                    'name'      => $user['name'] ?? $user[2] ?? $email,
                    'email'     => $email,
                    'is_active' => $user['is_active'] ?? $user[68] ?? 1,
                ];
            }
        }

        // Méthode 2: Search API sur champ email (field 34)
        try {
            $searchResult = $this->searchItems('User', [
                ['field' => '34', 'searchtype' => 'equals', 'value' => $email, 'link' => 'AND'],
            ], [
                'range'           => '0-10',
                'forcedisplay[0]' => '1',
                'forcedisplay[1]' => '2',
                'forcedisplay[2]' => '34',
            ]);

            $data = $searchResult['data'] ?? [];
            if (!empty($data)) {
                $first = $data[0];
                return [
                    'id'    => $first['2'] ?? $first[2] ?? null,
                    'name'  => $first['2'] ?? $first[2] ?? $email,
                    'email' => $email,
                    'is_active' => 1,
                ];
            }
        } catch (\Exception $e) {
            \Log::info('GLPI verifyUserExists search failed: ' . $e->getMessage());
        }

        return null;

    } catch (\Exception $e) {
        \Log::error('GLPI verifyUserExists failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * تعيين Profile لـ User في GLPI
 * profileName: 'Technician', 'Admin', 'Self-Service', 'Super-Admin'
 */
public function assignProfileToUser(int $glpiUserId, string $profileName): bool
{
    try {
        $this->ensureSession();

        // 1. جيب قائمة الـ Profiles باش نلقاو الـ ID
        $profiles = $this->http()
            ->get($this->baseUrl . '/apirest.php/Profile/', [
                'range' => '0-50',
            ]);

        $profileId = null;
        foreach ($profiles->json() ?? [] as $p) {
            if (strtolower($p['name'] ?? '') === strtolower($profileName)) {
                $profileId = $p['id'];
                break;
            }
        }

        if (!$profileId) {
            \Log::warning("GLPI Profile '{$profileName}' not found");
            return false;
        }

        // 2. تعيين الـ Profile للـ User
        $response = $this->http()
            ->post($this->baseUrl . '/apirest.php/Profile_User/', [
                'input' => [
                    'users_id'    => $glpiUserId,
                    'profiles_id' => $profileId,
                    'is_default'  => 1,
                    'entities_id' => 0,  // Root entity
                    'is_recursive'=> 1,
                ],
            ]);

        return $response->successful();

    } catch (\Exception $e) {
        \Log::error('assignProfileToUser failed: ' . $e->getMessage());
        return false;
    }
}

}