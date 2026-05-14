<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    protected $fillable = [
        'name', 'first_name', 'last_name', 'birthday', 'gender',
        'email', 'password', 'role', 'is_active', 'last_login_at',
        'phone', 'phone_mobile', 'whatsapp', 'teams_email', 'teams_webhook_url', 'avatar',
        'timezone', 'locale', 'profile_completed', 'notifications_read',
        // GLPI sync
        'glpi_user_id',
        // Client type: 'client' | 'user'
        'client_type',
        // Vérification SMS
        'phone_verified',
        'must_change_password',
    ];

    /**
     * Retourne le label + style du type client
     *
     * client → client classifié              (violet)
     * user   → nouveau / non classifié       (orange)
     * null   → admins/super_admins           (sans badge)
     */
    public function getClientTypeInfo(): array
    {
        return match($this->client_type) {
            'client' => ['label' => 'Client', 'icon' => '🟣', 'css' => 'ctype-client', 'desc' => 'Client'],
            'user'   => ['label' => 'Nouveau', 'icon' => '🟠', 'css' => 'ctype-new',    'desc' => 'Non classifié'],
            default  => ['label' => '—',       'icon' => '⚪', 'css' => 'ctype-none',   'desc' => ''],
        };
    }

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'password'           => 'hashed',
            'profile_completed'  => 'boolean',
        ];
    }

    // Vérifier si le profil admin est complet
    public function isProfileComplete(): bool
    {
        if ($this->role !== 'admin') return true;
        return $this->profile_completed;
    }

    // Email pour les notifications (Teams ou email login)
    public function getNotificationEmail(): string
    {
        return $this->teams_email ?? $this->email;
    }

    // Vérifier si synchronisé avec GLPI
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