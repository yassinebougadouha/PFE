<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update role enum to include super_admin
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('client')->change();
            // Super admin permissions
            $table->timestamp('last_login_at')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_login_at', 'is_active']);
        });
    }
};
