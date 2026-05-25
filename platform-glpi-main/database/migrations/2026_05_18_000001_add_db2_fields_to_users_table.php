<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('name');
            $table->string('phone_number', 20)->nullable()->after('phone_mobile');
            $table->string('profile_picture_url')->nullable()->after('avatar');
            $table->string('status', 20)->default('active')->after('is_active');
            $table->boolean('is_vip')->default(false)->after('status');
            $table->boolean('is_deleted')->default(false)->after('is_vip');
            $table->boolean('can_reply_conversations')->default(true)->after('is_deleted');
            $table->boolean('can_reply_whatsapp')->default(false)->after('can_reply_conversations');
            $table->softDeletes(); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'full_name',
                'phone_number',
                'profile_picture_url',
                'status',
                'is_vip',
                'is_deleted',
                'can_reply_conversations',
                'can_reply_whatsapp',
                'deleted_at',
            ]);
        });
    }
};
