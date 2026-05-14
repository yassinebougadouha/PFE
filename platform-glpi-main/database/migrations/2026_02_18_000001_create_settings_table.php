<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Valeurs par défaut
        DB::table('settings')->insert([
            ['key' => 'app_name',                   'value' => 'L2T',                                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'support_email',               'value' => 'support@l2t.com',                        'created_at' => now(), 'updated_at' => now()],
            ['key' => 'description',                 'value' => 'Plateforme de gestion des tickets L2T',  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'locale',                      'value' => 'fr',                                     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'timezone',                    'value' => 'Africa/Tunis',                           'created_at' => now(), 'updated_at' => now()],
            ['key' => 'primary_color',               'value' => '#667eea',                                'created_at' => now(), 'updated_at' => now()],
            ['key' => 'secondary_color',             'value' => '#764ba2',                                'created_at' => now(), 'updated_at' => now()],
            ['key' => 'theme_mode',                  'value' => 'light',                                  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sidebar_size',                'value' => 'normal',                                 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ticket_label',                'value' => 'Ticket',                                 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_assignment',             'value' => '0',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_assignment_method',      'value' => 'Round-robin',                            'created_at' => now(), 'updated_at' => now()],
            ['key' => 'allow_client_close',          'value' => '0',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sla_très haute',              'value' => '4h',                                     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sla_haute',                   'value' => '8h',                                     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sla_moyenne',                 'value' => '24h',                                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sla_basse',                   'value' => '48h',                                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'min_password_length',         'value' => '8',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'session_timeout',             'value' => '120',                                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'max_login_attempts',          'value' => '5',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'password_complexity',         'value' => '0',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'allow_registration',          'value' => '1',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'require_email_verification',  'value' => '0',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'two_factor_auth',             'value' => '0',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_host',                   'value' => 'smtp.gmail.com',                         'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_port',                   'value' => '587',                                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_encryption',             'value' => 'tls',                                    'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_from_name',              'value' => 'L2T Support',                            'created_at' => now(), 'updated_at' => now()],
            ['key' => 'smtp_username',               'value' => 'support@l2t.com',                        'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_new_ticket',           'value' => '1',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_status_change',        'value' => '1',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_new_comment',          'value' => '1',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_assigned',             'value' => '1',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_overdue',              'value' => '0',                                      'created_at' => now(), 'updated_at' => now()],
            ['key' => 'notify_resolved',             'value' => '1',                                      'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};