<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupportTroubleshootingWizardController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'goal' => 'nullable|string|max:500',
            'issue_summary' => 'nullable|string|max:1000',
            'observed_screen_caption' => 'nullable|string|max:1000',
            'observed_text' => 'nullable|string|max:1000',
            'user_actions_attempted' => 'nullable|array',
            'user_actions_attempted.*' => 'string|max:500',
            'context_hints' => 'nullable|array',
            'context_hints.*' => 'string|max:500',
            'max_steps' => 'nullable|integer|min:3|max:8',
        ]);

        $goal = trim($validated['goal'] ?? 'Aider l’utilisateur à résoudre le problème');
        $issueSummary = trim($validated['issue_summary'] ?? '');
        $screenCaption = trim($validated['observed_screen_caption'] ?? '');
        $observedText = trim($validated['observed_text'] ?? '');
        $attempts = array_values(array_filter((array) ($validated['user_actions_attempted'] ?? []), 'strlen'));
        $contextHints = array_values(array_filter((array) ($validated['context_hints'] ?? []), 'strlen'));
        $maxSteps = max(3, min(8, (int) ($validated['max_steps'] ?? 5)));

        $descriptionParts = array_filter([
            $goal ? "Objectif du support : $goal." : null,
            $issueSummary ? "Résumé du problème : $issueSummary." : null,
            $screenCaption ? "Contexte de l’écran : $screenCaption." : null,
            $observedText ? "Texte visible ou erreur : $observedText." : null,
        ]);

        $steps = [
            [
                'step_number' => 1,
                'title' => 'Collecter les informations essentielles',
                'why' => 'Valider le contexte et les erreurs visibles permet de cibler rapidement la cause.',
                'instructions' => array_filter([
                    'Lire attentivement le message d’erreur affiché sur l’écran.',
                    $issueSummary ? "Confirmer le problème décrit : $issueSummary." : 'Demander à l’utilisateur de reformuler le problème si nécessaire.',
                    'Vérifier si l’utilisateur a déjà essayé de rafraîchir ou rouvrir la page.',
                ]),
                'expected_signal' => 'Le problème est clairement décrit et reproductible.',
                'if_not_seen' => 'Demander plus de captures d’écran ou informations complémentaires.',
            ],
        ];

        if ($screenCaption || $observedText) {
            $steps[] = [
                'step_number' => 2,
                'title' => 'Vérifier les éléments de l’interface',
                'why' => 'L’état du formulaire ou le message d’erreur indique souvent la source du bug.',
                'instructions' => array_filter([
                    $screenCaption ? "Examiner l’écran décrit : $screenCaption." : 'Vérifier la présence de champs manquants ou désactivés.',
                    $observedText ? "Rechercher l’erreur signalée : $observedText." : 'Vérifier si des éléments UI sont grisés ou verrouillés.',
                    'Tester la saisie et le comportement du bouton de validation.',
                ]),
                'expected_signal' => 'Les champs sont cohérents et le bouton peut être activé.',
                'if_not_seen' => 'Passer à une vérification de la logique client ou du backend.',
            ];
        }

        if (!empty($attempts)) {
            $steps[] = [
                'step_number' => min(3, $maxSteps),
                'title' => 'Analyser les actions déjà tentées',
                'why' => 'Les tentatives antérieures permettent d’écarter les solutions redondantes.',
                'instructions' => array_merge(
                    ['Vérifier l’impact des actions déjà effectuées :'],
                    array_map(fn($line) => "- $line", $attempts)
                ),
                'expected_signal' => 'Les actions sont pertinentes et n’ont pas résolu le problème.',
                'if_not_seen' => 'Proposer une autre approche ciblée ou la prochaine étape.',
            ];
        }

        if ($maxSteps >= 4) {
            $steps[] = [
                'step_number' => min(4, $maxSteps),
                'title' => 'Valider la configuration et l’état de session',
                'why' => 'Certaines erreurs de paiement se produisent si la session est expirée ou le formulaire mal initialisé.',
                'instructions' => [
                    'Demander à l’utilisateur de se reconnecter ou de rouvrir la page.',
                    'Vérifier si le formulaire contient des données incomplètes ou invalides.',
                    'Confirmer que le mode de paiement sélectionné est supporté.',
                ],
                'expected_signal' => 'La page charge correctement et le formulaire est prêt à soumettre.',
                'if_not_seen' => 'Reproduire le flux dans un environnement de test.',
            ];
        }

        if ($maxSteps >= 5) {
            $steps[] = [
                'step_number' => min(5, $maxSteps),
                'title' => 'Tester la solution de contournement',
                'why' => 'Une alternative rapide permet de déterminer si le problème est spécifique au parcours actuel.',
                'instructions' => [
                    'Essayer un autre moyen de paiement si possible.',
                    'Vider le cache du navigateur ou ouvrir la page en navigation privée.',
                    'Tester le même scénario sur un autre appareil ou navigateur.',
                ],
                'expected_signal' => 'Le formulaire fonctionne dans un autre contexte ou explique une limitation.',
                'if_not_seen' => 'Escalader vers l’équipe technique si le problème persiste.',
            ];
        }

        return response()->json([
            'caption' => 'Assistant de dépannage généré pour le support.',
            'diagnosis' => 'Le problème apparaît lié à un comportement de validation ou à un état de formulaire incorrect.',
            'risk_level' => 'medium',
            'estimated_time_minutes' => 10,
            'provider' => 'local-laravel-wizard',
            'model' => 'support-wizard-v1',
            'assistance_hints' => array_slice($steps, 0, $maxSteps),
            'generated_at' => now()->toISOString(),
            'issue_summary' => $issueSummary,
            'context_hints' => $contextHints,
        ], 200);
    }
}
