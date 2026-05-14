<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Ajouter phone_verified aux users ───────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone_verified')) {
                $table->boolean('phone_verified')
                      ->default(false)
                      ->after('phone_mobile')
                      ->comment('True si le numéro a été vérifié par OTP SMS');
            }
        });

        // ── Ajouter type à otp_codes (email | sms) ─────────────────────────────
        Schema::table('otp_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('otp_codes', 'type')) {
                $table->string('type', 10)
                      ->default('email')
                      ->after('email')
                      ->comment('email | sms');
            }
            if (!Schema::hasColumn('otp_codes', 'phone')) {
                $table->string('phone', 20)
                      ->nullable()
                      ->after('type')
                      ->comment('Numéro pour OTP SMS');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumnIfExists('phone_verified');
        });
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumnIfExists('type');
            $table->dropColumnIfExists('phone');
        });
    }
};