<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('tickets', function (Blueprint $table) {
        $table->id();

        $table->foreignId('user_id')
              ->constrained()
              ->cascadeOnDelete();

        $table->string('title');
        $table->text('description');

        $table->unsignedTinyInteger('urgency')->default(3);
        $table->unsignedTinyInteger('impact')->default(3);
        $table->unsignedTinyInteger('priority')->default(3);

        $table->unsignedBigInteger('glpi_ticket_id')->nullable();

        $table->string('sync_status')->default('pending'); 
        // pending | synced | failed

        $table->text('last_error')->nullable();

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
