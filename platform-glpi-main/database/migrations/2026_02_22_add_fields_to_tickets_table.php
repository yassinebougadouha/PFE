<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('category')->nullable()->after('title');
            $table->text('solution')->nullable()->after('description');
            $table->text('attachments')->nullable()->after('solution');
        });
    }
    public function down(): void {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['category', 'solution', 'attachments']);
        });
    }
};