<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notifications';

    protected $fillable = [
        'user_id', 'type', 'icon', 'color',
        'title', 'body', 'url', 'ticket_id', 'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // ── Envoyer une notification à un user ──────────────────────────────────
    public static function send(int $userId, array $data): self
    {
        return self::create([
            'user_id'   => $userId,
            'type'      => $data['type'],
            'icon'      => $data['icon']  ?? 'notifications',
            'color'     => $data['color'] ?? 'primary',
            'title'     => $data['title'],
            'body'      => $data['body']  ?? null,
            'url'       => $data['url']   ?? null,
            'ticket_id' => $data['ticket_id'] ?? null,
            'is_read'   => false,
        ]);
    }

    // ── Envoyer à tous les admins actifs ─────────────────────────────────────
    public static function sendToAdmins(array $data): void
    {
        // is_active peut être null (anciens comptes) → on inclut null ET true
        $admins = User::where('role', 'admin')
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->pluck('id');

        foreach ($admins as $adminId) {
            self::send($adminId, $data);
        }
    }

    // ── Envoyer au super admin ───────────────────────────────────────────────
    public static function sendToSuperAdmins(array $data): void
    {
        $superAdmins = User::where('role', 'super_admin')
            ->where(function ($q) {
                $q->where('is_active', true)->orWhereNull('is_active');
            })
            ->pluck('id');

        foreach ($superAdmins as $id) {
            self::send($id, $data);
        }
    }

    // ── Éviter les doublons (même ticket + même type + non lu) ───────────────
    public static function sendOnce(int $userId, string $type, int $ticketId, array $data): void
    {
        $exists = self::where('user_id', $userId)
            ->where('type', $type)
            ->where('ticket_id', $ticketId)
            ->where('is_read', false)
            ->exists();

        if (!$exists) {
            self::send($userId, array_merge($data, ['type' => $type, 'ticket_id' => $ticketId]));
        }
    }
}