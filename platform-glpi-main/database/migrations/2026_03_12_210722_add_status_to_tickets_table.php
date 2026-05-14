<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void {
    Schema::table('tickets', function (Blueprint $table) {
        $table->string('status')->default('open')->after('category');
        // open | in_progress | resolved | closed | error
    });
}

public function down(): void {
    Schema::table('tickets', function (Blueprint $table) {
        $table->dropColumn('status');
    });
}
};
