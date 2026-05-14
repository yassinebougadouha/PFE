<?php
// ================================================================
// FICHIER: database/migrations/2026_02_21_000001_add_profile_fields_to_users_table.php
// COMMANDE: sail artisan migrate
// ================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('phone_mobile', 20)->nullable()->after('phone');
            $table->string('timezone', 50)->nullable()->default('Africa/Tunis')->after('phone_mobile');
            $table->string('locale', 5)->nullable()->default('fr')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_mobile', 'timezone', 'locale']);
        });
    }
};
