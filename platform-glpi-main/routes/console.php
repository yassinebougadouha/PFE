<?php

use Illuminate\Support\Facades\Schedule;

// ─── Lecture boîte support (l2t.glpi2026@gmail.com) → création tickets ────────
// Toutes les 2 minutes — détecte rapidement les nouveaux emails clients
Schedule::command('mail:fetch-support')->everyTwoMinutes();

// ─── Auto-fermeture tickets résolus depuis 5 jours ─────────────────────────────
Schedule::command('tickets:auto-close')->daily();

// ─── Vérification breaches SLA ────────────────────────────────────────────────
Schedule::command('glpi:check-sla')->hourly();

// ─── Sync utilisateurs GLPI ────────────────────────────────────────────────────
Schedule::command('glpi:sync-users')->dailyAt('02:00');

// ─── Sync catégories GLPI ──────────────────────────────────────────────────────
Schedule::command('glpi:sync-categories')->dailyAt('02:30');