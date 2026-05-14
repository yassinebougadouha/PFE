<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ══════════════════════════════════════════════════════════════════════
// Migration 1 — Ajouter teams_webhook_url à la table users
// Nom du fichier: 2026_04_08_000001_add_teams_webhook_to_users.php
// ══════════════════════════════════════════════════════════════════════
return new class extends Migration
{
    public function up(): void
    {
        // Ajouter webhook personnel à chaque admin
        if (!Schema::hasColumn('users', 'teams_webhook_url')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('teams_webhook_url')->nullable()->after('teams_email');
            });
        }

        // Table de mapping catégorie → admin
        if (!Schema::hasTable('category_admin_mappings')) {
            Schema::create('category_admin_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('category');            // ex: "facturation", "technique", "commercial"
                $table->unsignedBigInteger('admin_id');
                $table->string('teams_channel')->nullable(); // webhook URL optionnel spécifique à la catégorie
                $table->timestamps();
                $table->unique('category');
                $table->foreign('admin_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('teams_webhook_url');
        });
        Schema::dropIfExists('category_admin_mappings');
    }
};