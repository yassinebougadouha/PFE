<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp', 20)->nullable()->after('phone_mobile');
            $table->string('teams_email')->nullable()->after('whatsapp');
            $table->string('avatar')->nullable()->after('teams_email');
            $table->boolean('profile_completed')->default(false)->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'teams_email', 'avatar', 'profile_completed']);
        });
    }
};