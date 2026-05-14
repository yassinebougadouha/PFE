<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Ticket;

class AiService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey  = config('services.ai.key', '');
        $this->model   = config('services.ai.model', 'llama-3.3-70b-versatile');
        $this->baseUrl = config('services.ai.base_url', 'https://api.groq.com/openai/v1');
        $this->timeout = (int) config('services.ai.timeout', 15);
    }

    // ─── Appel LLM central ────────────────────────────────────────────────────
    private function chat(string $systemPrompt, string $userMessage, int $maxTokens = 500): ?string
    {
        if (empty($this->apiKey)) {
            Log::warning('AI: No API key configured');
            return null;
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type'  => 'application/json',
                ])
                ->post($this->baseUrl . '/chat/completions', [
                    'model'       => $this->model,
                    'max_tokens'  => $maxTokens,
                    'temperature' => 0.3,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userMessage],
                    ],
                ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content');
            }

            Log::warning('AI API error: ' . $response->status() . ' — ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::warning('AI unavailable: ' . $e->getMessage());
            return null;
        }
    }

    // ─── Vérifier disponibilité ───────────────────────────────────────────────
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    // ─── 1. Classification ticket (client — formulaire) ───────────────────────
    public function classify(string $title, string $description = ''): ?array
    {
        $system = <<<PROMPT
Tu es un assistant IA pour L2T (Landolsi Telecom Technology), société tunisienne de SMS marketing.
Tu analyses les tickets support IT et retournes UNIQUEMENT un JSON valide, sans explication.

Catégories disponibles:
- incident_technique: pannes, erreurs serveur, service down, timeout, SMS non délivrés
- integration_api: problèmes API, token, authentification, endpoints, documentation
- facturation: factures, crédits SMS, recharge, devis, remboursement
- plateforme: connexion plateforme, campagnes, Didon SMS, Cloud Messaging
- paiement_mobile: micropaiement, transactions, débit mobile
- autre: questions générales, partenariat, démonstration

Priorités: 1=très basse, 2=basse, 3=moyenne, 4=haute, 5=critique

Format JSON attendu:
{"category":"...","category_label":"...","priority":3,"priority_label":"...","urgency":3,"impact":3,"confidence":85,"solutions":["solution 1","solution 2"]}
PROMPT;

        $user = "Titre: {$title}\nDescription: {$description}";

        $raw = $this->chat($system, $user, 400);
        if (!$raw) return null;

        // Extraire le JSON de la réponse
        preg_match('/\{.*\}/s', $raw, $matches);
        if (empty($matches[0])) return null;

        try {
            $result = json_decode($matches[0], true);
            if (json_last_error() !== JSON_ERROR_NONE) return null;
            return $result;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ─── 2. Analyse ticket pour admin (résumé + réponse suggérée) ─────────────
    public function analyzeForAdmin(string $title, string $description, string $category, array $comments = [], array $pastResponses = []): ?array
    {
        $commentsText = '';
        foreach ($comments as $c) {
            $commentsText .= "- {$c}\n";
        }

        $styleText = '';
        if (!empty($pastResponses)) {
            $styleText = "\nExemples de réponses précédentes de cet admin (adapter le ton):\n";
            foreach (array_slice($pastResponses, 0, 2) as $r) {
                $styleText .= "---\n" . substr($r, 0, 150) . "\n";
            }
        }

        $system = <<<PROMPT
Tu es un assistant IA pour les admins support de L2T (société SMS Tunisie).
Tu analyses les tickets ET tous les commentaires du client pour répondre au problème LE PLUS RÉCENT.
Si le client a ajouté des commentaires, ta réponse doit tenir compte de ces informations supplémentaires.
Réponds UNIQUEMENT en JSON valide sans explication.

Format JSON attendu:
{
  "summary": "résumé du problème actuel en 1-2 phrases (inclure les infos des commentaires)",
  "response": "réponse complète professionnelle qui adresse TOUS les éléments du ticket ET des commentaires",
  "urgency_label": "Dans les délais | Réponse urgente requise",
  "is_urgent": false,
  "tags": ["TAG1","TAG2"]
}

Tags possibles: URGENT, API, FACTURATION, TECHNIQUE, PLATEFORME, PAIEMENT
PROMPT;

        $user = "Ticket:\nTitre: {$title}\nDescription initiale: {$description}\nCatégorie: {$category}";
        if ($commentsText) $user .= "\n\n=== NOUVEAUX MESSAGES DU CLIENT (à traiter en priorité) ===\n{$commentsText}=== FIN MESSAGES ===";
        if ($styleText)    $user .= $styleText;

        $raw = $this->chat($system, $user, 600);
        if (!$raw) return null;

        preg_match('/\{.*\}/s', $raw, $matches);
        if (empty($matches[0])) return null;

        try {
            return json_decode($matches[0], true);
        } catch (\Exception $e) {
            return null;
        }
    }

    // ─── 3. Reformulation description client ──────────────────────────────────
    public function reformulate(string $title, string $description): ?string
    {
        $system = "Tu améliores la description d'un ticket support IT pour L2T (société SMS Tunisie). "
                . "Garde le sens exact, améliore uniquement la clarté et la structure. "
                . "Réponds avec la description améliorée uniquement, sans introduction.";

        $user = "Titre: {$title}\nDescription originale: {$description}";

        return $this->chat($system, $user, 300);
    }

    // ─── 4. Tickets similaires — GLPI d'abord, fallback PostgreSQL ─────────────
    public function findSimilar(string $query): array
    {
        if (strlen($query) < 4) return [];

        // ── 1. GLPI searchItems (prioritaire)
        try {
            $glpi = app(\App\Services\GlpiService::class);

            $result = $glpi->searchItems('Ticket', [
                ['field' => '1',  'searchtype' => 'contains', 'value' => $query, 'link' => 'AND'],
                ['field' => '12', 'searchtype' => 'equals',   'value' => 5,      'link' => 'AND'],
            ], [
                'range'           => '0-4',
                'forcedisplay[0]' => 2,
                'forcedisplay[1]' => 1,
                'forcedisplay[2]' => 21,
                'forcedisplay[3]' => 24,
                'order'           => 'DESC',
            ]);

            $glpi->killSession();

            $tickets = [];
            $seen    = [];
            foreach ($result['data'] ?? [] as $item) {
                $title = trim($item['1'] ?? '');
                if (!$title) continue;

                $key = preg_replace('/\s+/', ' ', strtolower($title));
                if (isset($seen[$key])) continue;
                $seen[$key] = true;

                $desc = trim(strip_tags(html_entity_decode($item['21'] ?? '')));
                $sol  = trim(strip_tags(html_entity_decode($item['24'] ?? '')));

                if (strlen($sol) < 10) continue;

                $tickets[] = [
                    'id'          => $item['2'] ?? null,
                    'title'       => $title,
                    'description' => strlen($desc) > 5 ? \Illuminate\Support\Str::limit($desc, 150) : null,
                    'solution'    => \Illuminate\Support\Str::limit($sol, 200),
                    'source'      => 'glpi',
                ];
            }

            if (!empty($tickets)) return $tickets;

        } catch (\Exception $e) {
            Log::info('findSimilar GLPI fallback: ' . $e->getMessage());
        }

        // ── 2. Fallback base locale — recherche multi-mots intelligente
        $stopwords = ['dans', 'pour', 'avec', 'plus', 'cette', 'notre', 'depuis', 'lors', 'vers', 'tout', "l'api", "l'envoi"];
        $words = array_filter(
            array_unique(explode(' ', strtolower(preg_replace('/[^\w\s]/u', ' ', $query)))),
            fn($w) => strlen($w) > 3 && !in_array($w, $stopwords)
        );
        $words = array_slice(array_values($words), 0, 5);

        if (empty($words)) return [];

        $driver = \DB::connection()->getDriverName();

        $results = Ticket::whereIn('sync_status', ['resolved', 'closed'])
            ->whereNotNull('solution')
            ->where('solution', '!=', '')
            ->where(function ($q) use ($words, $driver) {
                foreach ($words as $word) {
                    if ($driver === 'pgsql') {
                        $q->orWhere('title',       'ilike', "%{$word}%")
                          ->orWhere('description', 'ilike', "%{$word}%");
                    } else {
                        $q->orWhereRaw('LOWER(title) LIKE ?',       ['%' . $word . '%'])
                          ->orWhereRaw('LOWER(description) LIKE ?', ['%' . $word . '%']);
                    }
                }
            })
            ->latest('resolved_at')
            ->limit(8)
            ->get(['id', 'title', 'description', 'solution', 'category']);

        if ($results->isEmpty()) return [];

        $queryLower = strtolower($query);

        $scored = $results->map(function ($t) use ($words, $queryLower) {
            $titleLower = strtolower($t->title);
            $descLower  = strtolower($t->description ?? '');
            $score      = 0;

            if (str_contains($titleLower, $queryLower)) $score += 10;

            foreach ($words as $w) {
                if (str_contains($titleLower, $w)) $score += 3;
                if (str_contains($descLower,  $w)) $score += 1;
            }

            return ['ticket' => $t, 'score' => $score];
        })
        ->filter(fn($item) => $item['score'] > 0)
        ->sortByDesc('score')
        ->take(3)
        ->values();

        $seen   = [];
        $output = [];
        foreach ($scored as $item) {
            $t   = $item['ticket'];
            $key = preg_replace('/\s+/', ' ', strtolower($t->title));
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $output[] = [
                'id'          => $t->id,
                'title'       => $t->title,
                'description' => $t->description
                    ? \Illuminate\Support\Str::limit(strip_tags($t->description), 150)
                    : null,
                'solution'    => \Illuminate\Support\Str::limit(strip_tags($t->solution), 200),
                'source'      => 'local',
            ];
        }

        return $output;
    }

    // ─── 5. Prédiction SLA ────────────────────────────────────────────────────
    public function predictSla(int $priority, string $category): array
    {
        $slaHours    = [5 => 4, 4 => 8, 3 => 24, 2 => 48, 1 => 72];
        $slaLimit    = $slaHours[$priority] ?? 24;
        $openTickets = Ticket::whereIn('sync_status', ['pending', 'in_progress'])->count();
        $hour        = (int) now()->format('H');

        $risk = 0;
        $risk += (5 - $priority) * 5;
        if ($hour >= 17) $risk += 25;
        elseif ($hour >= 15) $risk += 15;
        if ($openTickets > 10) $risk += 30;
        elseif ($openTickets > 5) $risk += 15;

        $complexity = ['incident_technique' => 5, 'integration_api' => 10, 'facturation' => 0, 'plateforme' => 5, 'paiement_mobile' => 8, 'autre' => 0];
        $risk += $complexity[$category] ?? 0;
        $risk = min(100, max(0, $risk));

        return [
            'risk_score'      => $risk,
            'breach_likely'   => $risk >= 50,
            'sla_limit_hours' => $slaLimit,
            'sla_used'        => 0,
            'recommendation'  => $risk > 70 ? 'Escalader immédiatement' : ($risk > 40 ? 'Surveiller' : 'Normal'),
        ];
    }

    // ─── 6. Rapport hebdo super admin ─────────────────────────────────────────
    public function generateWeeklyReport(array $stats): ?string
    {
        $system = "Tu es un assistant analytique pour L2T. "
                . "Tu génères un rapport hebdomadaire concis sur les tickets support. "
                . "Réponds en français, format structuré, 150 mots maximum.";

        $user = "Stats de la semaine:\n" . json_encode($stats, JSON_UNESCAPED_UNICODE);

        return $this->chat($system, $user, 400);
    }
}