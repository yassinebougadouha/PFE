<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Ticket;
use App\Services\GlpiService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query   = $request->input('q', '');
        $results = [];
 
        if (strlen($query) < 1) {
            return response()->json(['query' => $query, 'results' => [], 'count' => 0]);
        }
 
        $user = auth()->user();
 
        // ── 1. Tickets locaux ─────────────────────────────────────────────────
        $ticketQuery = Ticket::query();
        if ($user->role === 'client') {
            $ticketQuery->where('user_id', $user->id);
        }
 
        // Strip # prefix — "#170" → "170", "#" alone → show all
        $stripped     = ltrim(trim($query), '#');
        $numericQuery = $stripped;
        $isNumeric    = is_numeric($stripped) && $stripped !== '';
        $isHashOnly   = trim($query) === '#';

        $tickets = $ticketQuery
            ->where(function ($q) use ($query, $numericQuery, $isNumeric, $isHashOnly) {
                if ($isHashOnly) {
                    // "#" alone → return all tickets (no filter)
                    $q->whereNotNull('id');
                } elseif ($isNumeric) {
                    // "#170" or "170" → search by ID
                    $q->where('id', (int) $numericQuery)
                      ->orWhere('title', 'ilike', "%{$numericQuery}%");
                } else {
                    // Normal text search
                    $q->where('title', 'ilike', "%{$query}%")
                      ->orWhere('description', 'ilike', "%{$query}%");
                }
            })
            ->with('user')
            ->limit(8)
            ->get();
 
        foreach ($tickets as $ticket) {
            try {
                if ($user->role === 'super_admin') {
                    $ticketUrl = route('super-admin.decision-engine') . '?ticket=' . $ticket->id;
                } elseif ($user->role === 'admin') {
                    $ticketUrl = route('admin.tickets.show', $ticket->id);
                } else {
                    $ticketUrl = route('tickets.show', $ticket->id);
                }
            } catch (\Exception $e) {
                // Fallback si la route n'existe pas
                if ($user->role === 'super_admin') {
                    $ticketUrl = route('super-admin.tickets') . '?search=' . $ticket->id;
                } elseif ($user->role === 'admin') {
                    $ticketUrl = route('admin.tickets') . '?search=' . $ticket->id;
                } else {
                    $ticketUrl = route('tickets.index');
                }
            }
 
            $results[] = [
                'type'     => 'ticket',
                'icon'     => 'confirmation_number',
                'color'    => '#667eea',
                'title'    => $ticket->title,
                'subtitle' => 'Ticket #' . $ticket->id . ' — ' . ($ticket->user->name ?? ''),
                'url'      => $ticketUrl,
                'source'   => 'local',
            ];
        }
 
        // ── 2. Users locaux (admin + super_admin) ─────────────────────────────
        if (in_array($user->role, ['super_admin', 'admin'])) {

            // Admin ne voit que les clients (pas les autres admins ni super_admins)
            // Super admin ne voit que les clients et admins (pas les autres super_admins)
            $userQuery = User::where(function ($q) use ($query) {
                    $q->where('name', 'ilike', "%{$query}%")
                      ->orWhere('email', 'ilike', "%{$query}%");
                });

            if ($user->role === 'admin') {
                $userQuery->where('role', 'client');
            } elseif ($user->role === 'super_admin') {
                $userQuery->whereIn('role', ['client', 'admin']);
            }

            $users = $userQuery->limit(5)->get();

            foreach ($users as $u) {
                if ($u->role === 'client') {
                    $uUrl   = $user->role === 'super_admin'
                        ? route('super-admin.clients.show', $u->id)
                        : route('admin.clients.show', $u->id);
                    $uIcon  = 'person';
                    $uColor = '#764ba2';
                    $uSub   = $u->email . ' — Client · ' . $u->tickets()->count() . ' ticket(s)';
                } elseif ($u->role === 'admin') {
                    $uUrl   = route('super-admin.admins.show', $u->id);
                    $uIcon  = 'shield_person';
                    $uColor = '#1e40af';
                    $uSub   = $u->email . ' — Admin';
                } else {
                    $uUrl   = route('super-admin.dashboard');
                    $uIcon  = 'manage_accounts';
                    $uColor = '#059669';
                    $uSub   = $u->email . ' — Super Admin';
                }

                $results[] = [
                    'type'     => 'user',
                    'icon'     => $uIcon,
                    'color'    => $uColor,
                    'title'    => $u->name,
                    'subtitle' => $uSub,
                    'url'      => $uUrl,
                    'source'   => 'local',
                ];
            }
        }

        // ── 3. ✅ API: GET /search/:itemtype — searchItems GLPI (générique)
        // Super admin uniquement — cherche dans Ticket + User + Computer + Software
        if ($user->role === 'super_admin' && strlen($query) >= 3) {
            try {
                $glpi = app(GlpiService::class);
                $glpi->initSession();

                $criteria = [
                    ['field' => '1', 'searchtype' => 'contains', 'value' => $query, 'link' => 'AND'],
                ];

                // ✅ API: GET /search/Ticket (générique searchItems)
                $glpiTickets = $glpi->searchItems('Ticket', $criteria, ['range' => '0-4']);
                foreach ($glpiTickets['data'] ?? [] as $gt) {
                    $alreadyLocal = collect($results)->contains(fn($r) =>
                        $r['type'] === 'ticket' && isset($gt['2']) && str_contains($r['title'], $gt['2'] ?? '')
                    );
                    if (!$alreadyLocal) {
                        $results[] = [
                            'type'     => 'ticket',
                            'icon'     => 'confirmation_number',
                            'color'    => '#1565c0',
                            'title'    => $gt['1'] ?? $gt['name'] ?? 'Ticket GLPI',
                            'subtitle' => 'GLPI Ticket #' . ($gt['2'] ?? ''),
                            'url'      => '#',
                            'source'   => 'glpi',
                        ];
                    }
                }

                // ✅ API: GET /search/User (générique searchItems)
                $glpiUsers = $glpi->searchItems('User', $criteria, ['range' => '0-4']);
                foreach ($glpiUsers['data'] ?? [] as $gu) {
                    $results[] = [
                        'type'     => 'user',
                        'icon'     => 'manage_accounts',
                        'color'    => '#0f6e56',
                        'title'    => $gu['1'] ?? $gu['name'] ?? 'User GLPI',
                        'subtitle' => 'GLPI User — ' . ($gu['34'] ?? $gu['email'] ?? ''),
                        'url'      => '#',
                        'source'   => 'glpi',
                    ];
                }

                // ✅ API: GET /search/Computer (générique searchItems)
                $glpiComputers = $glpi->searchItems('Computer', $criteria, ['range' => '0-3']);
                foreach ($glpiComputers['data'] ?? [] as $gc) {
                    $results[] = [
                        'type'     => 'computer',
                        'icon'     => 'computer',
                        'color'    => '#854f0b',
                        'title'    => $gc['1'] ?? $gc['name'] ?? 'Computer GLPI',
                        'subtitle' => 'GLPI Computer — ' . ($gc['5'] ?? $gc['serial'] ?? ''),
                        'url'      => '#',
                        'source'   => 'glpi',
                    ];
                }

                $glpi->killSession();

            } catch (\Exception $e) {
                \Log::warning('GLPI search failed: ' . $e->getMessage());
                // Mū critical — local results yibanu normalement
            }
        }

        return response()->json([
            'query'   => $query,
            'results' => array_slice($results, 0, 15),
            'count'   => count($results),
        ]);
    }
}