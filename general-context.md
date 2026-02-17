# Cahier des Charges
## Plateforme de Support Client Intelligente

### 1. Contexte

Les plateformes modernes de support client reposent majoritairement sur des chatbots basés sur des modèles de langage génératifs (LLMs).

Bien que performants en génération de texte, ces systèmes présentent plusieurs limites :
- Hallucinations
- Absence de raisonnement décisionnel explicite
- Difficulté à estimer l'incertitude
- Manque d'interprétabilité
- Risque élevé dans des contextes sensibles

Dans un environnement professionnel exigeant (SaaS, assistance technique, services numériques), la fiabilité et la contrôlabilité sont essentielles.

Ce projet vise donc à concevoir une plateforme décision-centrique, où les LLMs sont utilisés uniquement comme interfaces linguistiques, tandis que la logique décisionnelle repose sur un moteur explicite et contrôlable.

### 2. Problématique

Comment concevoir une plateforme de support client :
- Fiable et interprétable ?
- Capable d'estimer explicitement le risque et la confiance ?
- Permettant une supervision humaine fluide ?
- Intégrant une compréhension visuelle avancée des interfaces utilisateur ?

Les approches purement génératives ne permettent pas un contrôle suffisant. Il est donc nécessaire de proposer une architecture hybride décisionnelle et modulaire.

### 3. Objectifs du projet

#### 3.1 Objectifs techniques
- Concevoir un backend IA robuste, modulaire et scalable
- Implémenter un moteur de décision basé sur la confiance et le risque
- Intégrer un système Human-in-the-loop
- Ajouter une couche Visual AI

#### 3.2 Objectifs scientifiques
- Étudier la prise de décision sous incertitude
- Comparer modèles génératifs vs non génératifs

### 4. Contraintes du projet

#### Contraintes techniques
- Architecture backend prioritaire (backend-heavy)
- Séparation stricte compréhension / génération
- Aucun apprentissage risqué en production

#### Contraintes réglementaires
- Protection des données utilisateurs
- Capture écran opt-in uniquement

#### Contraintes temporelles
- Projet limité à la durée du PFE
- Développement progressif et itératif

### 5. Cible (Users)

La plateforme cible :

1. **Utilisateurs finaux**
    - Clients
    - Employés d'entreprise

2. **Agents humains**
    - Agents support technique
    - Service client
    - Administrateurs

3. **Administrateurs système**
    - Supervision des métriques
    - Configuration des règles décisionnelles
    - Monitoring IA

### 6. Besoins Fonctionnels

La plateforme devra permettre :

#### 6.1 Gestion multi-canaux
- Chat en ligne
- Emails
- Tickets
- Transcriptions d'appels

#### 6.2 Compréhension intelligente
- Classification d'intention
- Détection du niveau d'urgence
- Analyse du risque
- Scoring de confiance

#### 6.3 Moteur décisionnel adaptatif
- Haute confiance + faible risque → Résolution automatique
- Confiance moyenne → Clarification guidée
- Faible confiance ou risque élevé → Escalade humaine

#### 6.4 Human-in-the-loop
En cas d'escalade :
- Résumé structuré du problème
- Historique conversationnel
- Actions déjà tentées
- Scores confiance / risque

#### 6.5 Visual AI
- Capture d'écran opt-in
- Analyse temporelle des états UI
- Détection des écarts attendu / observé
- Guidage utilisateur adaptatif

#### 6.6 Dashboard administrateur
- Suivi des performances
- Taux d'escalade
- Temps moyen de résolution
- Monitoring des modèles

### 7. Besoins Non Fonctionnels

#### 7.1 Performance
- Réponse en temps réel (< 2 secondes)
- Gestion asynchrone des tâches lourdes

#### 7.2 Scalabilité
- Architecture microservices

#### 7.3 Sécurité
- Chiffrement des données
- Authentification sécurisée
- Gestion des accès par rôle

#### 7.4 Interprétabilité
- Décisions traçables
- Justification des actions IA

#### 7.5 Fiabilité
- Gestion des erreurs
- Logs et monitoring

### 8. Environnement Matériel et Logiciel

#### 8.1 Backend
- Framework : FastAPI
- Base de données : PostgreSQL
- Cache : Redis
- Traitement asynchrone : Celery
- Conteneurisation : Docker

#### 8.2 Couche IA
- Modèles ML non génératifs (classification)
- Moteur hybride règles + ML
- LLMs utilisés uniquement pour reformulation

#### 8.3 Frontend
- Framework : React
- Interface agent humain
- Interface client
- Interface administrateur
- Timeline conversationnelle
- Interface Visual AI

### 9. Planning du Projet

| Sprint | Date début | Date fin | Goal |
|--------|-----------|----------|------|
| Initialisation | 02/02/2026 | 15/02/2026 | Phase d'initialisation du projet : analyse détaillée des besoins, étude bibliographique, définition de l'architecture globale, rédaction du cahier des charges, mise en place de l'environnement technique (Docker, base de données, backend). |
| Sprint 1 | 16/02/2026 | 08/03/2026 | Mise en place de l'architecture backend : configuration FastAPI, PostgreSQL, Redis, gestion des utilisateurs, authentification, gestion multi-canaux (chat, email, tickets). |
| Sprint 2 | 09/03/2026 | 29/03/2026 | Implémentation du moteur décisionnel adaptatif : classification d'intention, scoring de confiance et de risque, logique hybride règles + ML, intégration Human-in-the-loop et gestion des escalades. |
| Sprint 3 | 30/03/2026 | 19/04/2026 | Développement de la couche IA avancée et du module Visual AI : encodage d'embeddings visuels, analyse temporelle des états UI, détection d'écarts attendu/observé. |
| Sprint 4 | 20/04/2026 | 10/05/2026 | Développement frontend, intégration complète backend + IA + Visual AI, tests fonctionnels et optimisation des performances. |
| Finalisation | 11/05/2026 | 23/05/2026 | Finalisation du rapport, documentation technique complète, préparation de la démonstration et de la soutenance. |

### 10. Livrables

- Cahier des charges validé
- Code source documenté
- Documentation technique
- Rapport final de PFE
- Démonstration fonctionnelle
- Présentation soutenance

## Conclusion

Ce projet propose une nouvelle génération de plateformes de support client combinant :
- IA décisionnelle explicite
- Supervision humaine intégrée
- Visual AI
- Architecture robuste et scalable

Il dépasse les limites des chatbots purement génératifs en mettant la décision contrôlée et l'interprétabilité au centre du système.