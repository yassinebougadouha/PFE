<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('resource_type')->nullable()->after('action');
            $table->string('resource_id')->nullable()->after('resource_type');
            $table->jsonb('meta')->nullable()->after('description');
            $table->string('trace_id')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn([
                'resource_type',
                'resource_id',
                'meta',
                'trace_id',
            ]);
        });
    }
};