<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GlpiService;

/**
 * GlpiApiController
 * ─────────────────
 * Expose toutes les APIs GLPI REST via des routes Laravel sécurisées.
 * Accessible uniquement aux super_admin et admin.
 * Chaque méthode = un endpoint GLPI.
 */
class GlpiApiController extends Controller
{
    protected GlpiService $glpi;

    public function __construct(GlpiService $glpi)
    {
        $this->glpi = $glpi;
    }

    // ─── Helper: réponse JSON + killSession ───────────────────────────────────
    private function respond(callable $callback): \Illuminate\Http\JsonResponse
    {
        try {
            $data = $callback();
            $this->glpi->killSession();
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 1. SESSION & CONFIG
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET /glpi/session/info
     * → GET /apirest.php/getFullSession
     */
    public function sessionInfo(): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getFullSession());
    }

    /**
     * GET /glpi/config
     * → GET /apirest.php/getGlpiConfig
     */
    public function glpiConfig(): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getGlpiConfig());
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 2. PROFILS & ENTITÉS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET /glpi/profiles
     * → GET /apirest.php/getMyProfiles
     */
    public function profiles(): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getMyProfiles());
    }

    /**
     * GET /glpi/profiles/active
     * → GET /apirest.php/getActiveProfile
     */
    public function activeProfile(): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getActiveProfile());
    }

    /**
     * POST /glpi/profiles/change
     * → POST /apirest.php/changeActiveProfile
     */
    public function changeProfile(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['profile_id' => 'required|integer']);
        return $this->respond(fn() => $this->glpi->changeActiveProfile($request->profile_id));
    }

    /**
     * GET /glpi/entities
     * → GET /apirest.php/getMyEntities
     */
    public function entities(): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getMyEntities());
    }

    /**
     * GET /glpi/entities/active
     * → GET /apirest.php/getActiveEntities
     */
    public function activeEntities(): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getActiveEntities());
    }

    /**
     * POST /glpi/entities/change
     * → POST /apirest.php/changeActiveEntities
     */
    public function changeEntity(Request $request): \Illuminate\Http\JsonResponse
    {
        $entityId  = $request->input('entity_id', 'all');
        $recursive = $request->boolean('recursive', false);
        return $this->respond(fn() => $this->glpi->changeActiveEntities($entityId, $recursive));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 3. CRUD GÉNÉRIQUE
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET /glpi/items/multiple?items[0][itemtype]=Ticket&items[0][id]=1
     * → GET /apirest.php/getMultipleItems
     */
    public function getMultipleItems(Request $request): \Illuminate\Http\JsonResponse
    {
        $items = $request->input('items', []);
        return $this->respond(fn() => $this->glpi->getMultipleItems($items));
    }

    /**
     * GET /glpi/items/{itemtype}?range=0-50
     * → GET /apirest.php/{itemtype}/
     */
    public function getAllItems(Request $request, string $itemtype): \Illuminate\Http\JsonResponse
    {
        $params = $request->only(['range', 'order', 'sort', 'is_deleted']);
        return $this->respond(fn() => $this->glpi->getAllItems($itemtype, $params));
    }

    /**
     * GET /glpi/items/{itemtype}/{id}
     * → GET /apirest.php/{itemtype}/{id}
     */
    public function getItem(string $itemtype, int $id): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getItem($itemtype, $id));
    }

    /**
     * GET /glpi/items/{itemtype}/{id}/sub/{subItemtype}
     * → GET /apirest.php/{itemtype}/{id}/{sub_itemtype}
     */
    public function getSubItems(string $itemtype, int $id, string $subItemtype): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getSubItems($itemtype, $id, $subItemtype));
    }

    /**
     * POST /glpi/items/{itemtype}
     * → POST /apirest.php/{itemtype}/
     */
    public function addItem(Request $request, string $itemtype): \Illuminate\Http\JsonResponse
    {
        $input = $request->input('input', []);
        return $this->respond(fn() => $this->glpi->addItem($itemtype, $input));
    }

    /**
     * PUT /glpi/items/{itemtype}/{id}
     * → PUT /apirest.php/{itemtype}/{id}
     */
    public function updateItem(Request $request, string $itemtype, int $id): \Illuminate\Http\JsonResponse
    {
        $input = $request->input('input', []);
        return $this->respond(fn() => $this->glpi->updateItem($itemtype, $id, $input));
    }

    /**
     * DELETE /glpi/items/{itemtype}/{id}
     * → DELETE /apirest.php/{itemtype}/{id}
     */
    public function deleteItem(string $itemtype, int $id): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->deleteItem($itemtype, $id));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 4. RECHERCHE
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET /glpi/search/{itemtype}/options
     * → GET /apirest.php/listSearchOptions/{itemtype}
     */
    public function searchOptions(string $itemtype): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->listSearchOptions($itemtype));
    }

    /**
     * GET /glpi/search/tickets?status=5&priority=3
     * → GET /apirest.php/search/Ticket  (spécialisé pour l'IA)
     * Retourne tous les tickets filtrés par statut/priorité
     */
    public function searchTickets(Request $request): \Illuminate\Http\JsonResponse
    {
        $criteria = [];
        $i = 0;

        if ($request->has('status')) {
            $criteria[] = ['field' => '12', 'searchtype' => 'equals', 'value' => $request->status];
        }
        if ($request->has('priority')) {
            $criteria[] = ['field' => '3', 'searchtype' => 'equals', 'value' => $request->priority, 'link' => 'AND'];
        }
        if ($request->has('category_id')) {
            $criteria[] = ['field' => '7', 'searchtype' => 'equals', 'value' => $request->category_id, 'link' => 'AND'];
        }
        if ($request->boolean('resolved_only')) {
            $criteria = [['field' => '12', 'searchtype' => 'equals', 'value' => 5]];
        }

        // Si aucun filtre → tous les tickets (pour dashboard IA)
        return $this->respond(fn() => $this->glpi->searchItems('Ticket', $criteria, [
            'range'           => $request->input('range', '0-999'),
            'forcedisplay[0]' => 1,    // id
            'forcedisplay[1]' => 21,   // name
            'forcedisplay[2]' => 2,    // content
            'forcedisplay[3]' => 12,   // status
            'forcedisplay[4]' => 10,   // urgency
            'forcedisplay[5]' => 11,   // impact
            'forcedisplay[6]' => 3,    // priority
            'forcedisplay[7]' => 15,   // date_mod (résolution)
            'forcedisplay[8]' => 7,    // itilcategories_id
        ]));
    }

    /**
     * GET /glpi/search/{itemtype}?criteria[0][field]=...
     * → GET /apirest.php/search/{itemtype}
     */
    public function searchItems(Request $request, string $itemtype): \Illuminate\Http\JsonResponse
    {
        $criteria = $request->input('criteria', []);
        $params   = $request->only(['range', 'order', 'sort']);
        return $this->respond(fn() => $this->glpi->searchItems($itemtype, $criteria, $params));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 5. MASSIVE ACTIONS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET /glpi/massive-actions/{itemtype}
     * → GET /apirest.php/getMassiveActions/{itemtype}
     */
    public function massiveActions(string $itemtype): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getMassiveActions($itemtype));
    }

    /**
     * GET /glpi/massive-actions/{itemtype}/{id}
     * → GET /apirest.php/getMassiveActions/{itemtype}/{id}
     */
    public function massiveActionsForItem(string $itemtype, int $id): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getMassiveActions($itemtype, $id));
    }

    /**
     * GET /glpi/massive-actions/{itemtype}/params/{actionKey}
     * → GET /apirest.php/getMassiveActionParameters/{itemtype}/{action_key}
     */
    public function massiveActionParams(string $itemtype, string $actionKey): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getMassiveActionParameters($itemtype, $actionKey));
    }

    /**
     * POST /glpi/massive-actions/{itemtype}/{actionKey}/apply
     * → POST /apirest.php/applyMassiveAction/{itemtype}/{action_key}
     */
    public function applyMassiveAction(Request $request, string $itemtype, string $actionKey): \Illuminate\Http\JsonResponse
    {
        $ids   = $request->input('ids', []);
        $input = $request->input('input', []);
        return $this->respond(fn() => $this->glpi->applyMassiveAction($itemtype, $actionKey, $ids, $input));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 6. DOCUMENTS & MÉDIAS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * POST /glpi/documents/upload
     * → POST /apirest.php/Document/
     */
    public function uploadDocument(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);
        $file     = $request->file('file');
        $path     = $file->store('glpi_uploads', 'public');
        $fullPath = storage_path('app/public/' . $path);

        return $this->respond(fn() => $this->glpi->uploadDocument($fullPath, $file->getClientOriginalName()));
    }

    /**
     * GET /glpi/documents/{id}/download
     * → GET /apirest.php/Document/{id}
     */
    public function downloadDocument(int $id): mixed
    {
        try {
            $content = $this->glpi->downloadDocument($id);
            $this->glpi->killSession();
            return response($content, 200)->header('Content-Type', 'application/octet-stream');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /glpi/users/{id}/picture
     * → GET /apirest.php/User/{id}/Picture
     */
    public function userPicture(int $id): mixed
    {
        try {
            $content = $this->glpi->getUserPicture($id);
            $this->glpi->killSession();
            if (!$content) {
                return response()->json(['success' => false, 'error' => 'No picture'], 404);
            }
            return response($content, 200)->header('Content-Type', 'image/jpeg');
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 7. MOT DE PASSE
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * POST /glpi/password/reset-request
     * → PUT /apirest.php/lostPassword  (étape 1)
     */
    public function requestPasswordReset(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        return $this->respond(fn() => $this->glpi->requestPasswordReset($request->email));
    }

    /**
     * POST /glpi/password/reset
     * → PUT /apirest.php/lostPassword  (étape 2)
     */
    public function resetPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => 'required|string|min:8',
        ]);
        return $this->respond(
            fn() => $this->glpi->resetPassword($request->email, $request->token, $request->password)
        );
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 8. TICKETS SHORTCUTS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET /glpi/tickets/stats
     * Stats par statut pour le dashboard IA
     */
    public function ticketStats(): \Illuminate\Http\JsonResponse
    {
        return $this->respond(function () {
            return [
                'new'         => count($this->glpi->searchTicketsByStatus(1)['data'] ?? []),
                'in_progress' => count($this->glpi->searchTicketsByStatus(2)['data'] ?? []),
                'resolved'    => count($this->glpi->searchTicketsByStatus(5)['data'] ?? []),
                'closed'      => count($this->glpi->searchTicketsByStatus(6)['data'] ?? []),
            ];
        });
    }

    /**
     * GET /glpi/tickets?range=0-50
     * → GET /apirest.php/Ticket/
     */
    public function listTickets(Request $request): \Illuminate\Http\JsonResponse
    {
        $range = $request->input('range', '0-50');
        return $this->respond(fn() => $this->glpi->getAllItems('Ticket', ['range' => $range]));
    }

    /**
     * GET /glpi/tickets/{id}/detail
     * Détail complet: ticket + followups + solutions + logs GLPI
     */
    public function ticketDetail(int $id): \Illuminate\Http\JsonResponse
    {
        return $this->respond(function () use ($id) {
            $ticket     = $this->glpi->getTicket($id);
            $followups  = $this->glpi->getFollowups($id);
            $logs       = $this->glpi->getTicketLogs($id);

            return [
                'ticket'    => $ticket,
                'followups' => $followups,
                'logs'      => $logs,
            ];
        });
    }

    /**
     * POST /glpi/tickets/notes
     * Ajouter une note (followup) à plusieurs tickets d'un coup
     * Body: { ids: [1,2,3], content: "..." }
     */
    public function addNoteToTickets(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'ids'     => 'required|array|min:1',
            'ids.*'   => 'integer',
            'content' => 'required|string|min:3',
        ]);

        return $this->respond(function () use ($request) {
            $results = [];
            foreach ($request->ids as $glpiId) {
                try {
                    $results[$glpiId] = $this->glpi->addFollowup($glpiId, $request->content);
                } catch (\Exception $e) {
                    $results[$glpiId] = ['error' => $e->getMessage()];
                }
            }
            return $results;
        });
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 9. USERS GLPI
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET /glpi/users
     * → GET /apirest.php/User/
     */
    public function listUsers(Request $request): \Illuminate\Http\JsonResponse
    {
        $range = $request->input('range', '0-100');
        return $this->respond(fn() => $this->glpi->getAllItems('User', ['range' => $range]));
    }

    /**
     * GET /glpi/users/{id}
     * → GET /apirest.php/User/{id}
     */
    public function getUser(int $id): \Illuminate\Http\JsonResponse
    {
        return $this->respond(fn() => $this->glpi->getItem('User', $id));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // 10. CATÉGORIES
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET /glpi/categories
     * → GET /apirest.php/ITILCategory/
     * Retourne d'abord le cache local, sinon fetch depuis GLPI
     */
    public function categories(): \Illuminate\Http\JsonResponse
    {
        // D'abord essayer le cache local
        $local = \DB::table('glpi_categories')->orderBy('completename')->get();

        if ($local->isNotEmpty()) {
            return response()->json(['success' => true, 'data' => $local, 'source' => 'cache']);
        }

        // Sinon fetch depuis GLPI et mettre en cache
        return $this->respond(function () {
            $count = $this->glpi->syncCategoriesToLocal();
            $data  = \DB::table('glpi_categories')->orderBy('completename')->get();
            return ['categories' => $data, 'synced' => $count];
        });
    }
}