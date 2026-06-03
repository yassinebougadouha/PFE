<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    protected $fillable = [
        'name', 'first_name', 'last_name', 'full_name',
        'birthday', 'gender',
        'email', 'password', 'role', 'is_active', 'last_login_at',
        'role_python',
        'phone', 'phone_mobile', 'phone_number', 'whatsapp',
        'teams_email', 'teams_webhook_url',
        'avatar', 'profile_picture_url',
        'timezone', 'locale',
        'profile_completed', 'notifications_read',
        'glpi_user_id',
        'client_type',
        'phone_verified',
        'must_change_password',
        'hashed_password',
        // Champs db2
        'status',
        'is_vip',
        'is_deleted',
        'can_reply_conversations',
        'can_reply_whatsapp',
    ];

    public function getClientTypeInfo(): array
    {
        return match($this->client_type) {
            'client' => ['label' => 'Client',   'icon' => '🟣', 'css' => 'ctype-client', 'desc' => 'Client'],
            'user'   => ['label' => 'Nouveau',  'icon' => '🟠', 'css' => 'ctype-new',    'desc' => 'Non classifié'],
            default  => ['label' => '—',        'icon' => '⚪', 'css' => 'ctype-none',   'desc' => ''],
        };
    }

    protected $hidden = ['password', 'hashed_password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'password'                => 'hashed',
            'profile_completed'       => 'boolean',
            'is_vip'                  => 'boolean',
            'is_deleted'              => 'boolean',
            'can_reply_conversations' => 'boolean',
            'can_reply_whatsapp'      => 'boolean',
        ];
    }

    public function isProfileComplete(): bool
    {
        if ($this->role !== 'admin') return true;
        return $this->profile_completed;
    }

    public function getNotificationEmail(): string
    {
        return $this->teams_email ?? $this->email;
    }

    public function isSyncedWithGlpi(): bool
    {
        return !is_null($this->glpi_user_id);
    }

    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ], false));

        try {
            $gmail = app(\App\Services\GmailService::class);
            $html  = view('emails.reset-password', [
                'name'     => $this->name,
                'resetUrl' => $resetUrl,
            ])->render();

            $gmail->send(
                $this->email,
                '🔐 Réinitialisation de votre mot de passe — L2T Support',
                $html
            );
        } catch (\Exception $e) {
            \Log::error('sendPasswordResetNotification failed: ' . $e->getMessage());
        }
    }
}
