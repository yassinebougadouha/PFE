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
    Schema::create('ticket_events', function (Blueprint $table) {
        $table->id();

        $table->foreignId('ticket_id')
              ->constrained('tickets')
              ->cascadeOnDelete();

        $table->string('action'); 
        // created, assigned, followup_added, closed ...

        $table->json('payload')->nullable();
        $table->json('glpi_response')->nullable();

        $table->string('sync_status')->default('pending');
        $table->text('error_message')->nullable();

        $table->timestamps();
    });
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_events');
    }
};
