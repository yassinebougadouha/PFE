<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('type');           // new_ticket | ticket_answered | ticket_status | new_comment | ticket_assigned
            $table->string('icon')->default('notifications');
            $table->string('color')->default('primary');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};