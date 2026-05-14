<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Tickets : ajouter champs GLPI manquants ──────────────────────────
        Schema::table('tickets', function (Blueprint $table) {

            // GLPI category ID (ITILCategory)
            if (!Schema::hasColumn('tickets', 'glpi_category_id')) {
                $table->unsignedBigInteger('glpi_category_id')->nullable()->after('category');
            }

            // GLPI assigned user ID
            if (!Schema::hasColumn('tickets', 'glpi_assigned_user_id')) {
                $table->unsignedBigInteger('glpi_assigned_user_id')->nullable()->after('glpi_category_id');
            }

            // Temps de résolution réel depuis GLPI (en minutes)
            if (!Schema::hasColumn('tickets', 'glpi_resolution_time')) {
                $table->unsignedInteger('glpi_resolution_time')->nullable()->after('glpi_assigned_user_id');
            }

            // Logs GLPI historique (JSON)
            if (!Schema::hasColumn('tickets', 'glpi_logs')) {
                $table->json('glpi_logs')->nullable()->after('glpi_resolution_time');
            }

            // SLA dépassé ?
            if (!Schema::hasColumn('tickets', 'sla_breached')) {
                $table->boolean('sla_breached')->default(false)->after('glpi_logs');
            }

            // Date limite SLA calculée
            if (!Schema::hasColumn('tickets', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->after('sla_breached');
            }
        });

        // ─── Users : ajouter glpi_user_id pour synchro ────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'glpi_user_id')) {
                $table->unsignedBigInteger('glpi_user_id')->nullable()->after('email');
            }
        });

        // ─── Table GLPI categories cache ───────────────────────────────────────
        if (!Schema::hasTable('glpi_categories')) {
            Schema::create('glpi_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('glpi_id')->unique(); // ID dans GLPI
                $table->string('name');
                $table->string('completename')->nullable(); // chemin complet ex: "Réseau > DNS"
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->timestamps();
            });
        }

        // ─── Table synchro log (pour tracker les syncs GLPI) ──────────────────
        if (!Schema::hasTable('glpi_sync_logs')) {
            Schema::create('glpi_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id')->nullable();
                $table->string('action'); // create, update, delete, sync_status, sync_followup
                $table->string('status'); // success, failed
                $table->text('payload')->nullable();  // ce qu'on a envoyé
                $table->text('response')->nullable(); // ce que GLPI a répondu
                $table->text('error')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumnIfExists([
                'glpi_category_id', 'glpi_assigned_user_id',
                'glpi_resolution_time', 'glpi_logs',
                'sla_breached', 'sla_due_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists('glpi_user_id');
        });

        Schema::dropIfExists('glpi_categories');
        Schema::dropIfExists('glpi_sync_logs');
    }
};