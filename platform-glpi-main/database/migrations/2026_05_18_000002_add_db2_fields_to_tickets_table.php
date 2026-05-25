<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('channel_source', 50)->nullable()->after('source');
            $table->uuid('conversation_id')->nullable()->after('channel_source');
            $table->uuid('source_email_id')->nullable()->after('conversation_id');
            $table->uuid('source_voice_call_id')->nullable()->after('source_email_id');
            $table->boolean('escalation_flag')->default(false)->after('source_voice_call_id');
            $table->boolean('is_deleted')->default(false)->after('escalation_flag');
            $table->string('glpi_sync_status', 50)->nullable()->after('sync_status');
            $table->text('glpi_sync_error')->nullable()->after('glpi_sync_status');
            $table->text('resolution_note')->nullable()->after('solution');
            $table->softDeletes(); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'channel_source',
                'conversation_id',
                'source_email_id',
                'source_voice_call_id',
                'escalation_flag',
                'is_deleted',
                'glpi_sync_status',
                'glpi_sync_error',
                'resolution_note',
                'deleted_at',
            ]);
        });
    }
};
