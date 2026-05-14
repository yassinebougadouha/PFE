<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Admin ki solver el ticket, yitsajjel id mte3ou houna
            $table->unsignedBigInteger('solved_by')->nullable()->after('solution');
            $table->foreign('solved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['solved_by']);
            $table->dropColumn('solved_by');
        });
    }
};
