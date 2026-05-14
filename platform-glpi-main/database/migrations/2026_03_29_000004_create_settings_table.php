<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // Valeurs par défaut
        $defaults = [
            'app_name'                  => 'L2T',
            'support_email'             => 'support@l2t.com',
            'description'               => 'Plateforme de gestion des tickets L2T',
            'locale'                    => 'fr',
            'timezone'                  => 'Africa/Tunis',
            'primary_color'             => '#667eea',
            'secondary_color'           => '#764ba2',
            'theme_mode'                => 'light',
            'sidebar_size'              => 'normal',
            'ticket_label'              => 'Ticket',
            'auto_assignment'           => '0',
            'auto_assignment_method'    => 'Round-robin',
            'allow_client_close'        => '0',
            'min_password_length'       => '8',
            'session_timeout'           => '120',
            'max_login_attempts'        => '5',
            'password_complexity'       => '1',
            'allow_registration'        => '1',
            'require_email_verification'=> '0',
            'two_factor_auth'           => '0',
            'notify_new_ticket'         => '1',
            'notify_status_change'      => '1',
            'notify_new_comment'        => '1',
            'notify_assigned'           => '1',
            'notify_overdue'            => '1',
            'notify_resolved'           => '1',
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};