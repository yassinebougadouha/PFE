<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chat_access_grants')) {
            Schema::create('chat_access_grants', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id');
                $table->unsignedBigInteger('client_id');
                $table->timestamps();

                $table->unique(['admin_id', 'client_id']);
                $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_access_grants');
    }
};