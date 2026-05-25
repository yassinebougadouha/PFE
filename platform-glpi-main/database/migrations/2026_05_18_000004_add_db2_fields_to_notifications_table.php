<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->timestamp('read_at')->nullable()->after('is_read');
            $table->string('resource_type')->nullable()->after('read_at');
            $table->string('resource_id')->nullable()->after('resource_type');
            $table->string('action_url')->nullable()->after('resource_id');
            $table->jsonb('meta')->nullable()->after('action_url');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn([
                'read_at',
                'resource_type',
                'resource_id',
                'action_url',
                'meta',
            ]);
        });
    }
};