<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * client_type values:
     *  - client → client classifié
     *  - user   → nouveau compte auto-créé (non classifié)
     *  - null   → admins / super_admins
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'client_type')) {
                $table->string('client_type', 20)
                      ->nullable()
                      ->default(null)
                      ->after('role')
                      ->comment('client | user');
            }
        });

        // Backfill : clients sans type → user (non classifié)
        DB::table('users')
            ->where('role', 'client')
            ->whereNull('client_type')
            ->update(['client_type' => 'user']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists('client_type');
        });
    }
};