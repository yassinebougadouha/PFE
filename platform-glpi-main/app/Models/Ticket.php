<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description','status',
        'urgency', 'impact', 'priority',
        'category', 'solution', 'attachments',
        'glpi_ticket_id', 'sync_status', 'last_error',
        'solved_by', 'source',
        'assigned_to',   // ✅ admin assigné au ticket
        'resolved_at', // ✅ pour auto-clôture 5 jours
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function solver()
    {
        return $this->belongsTo(User::class, 'solved_by');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function events()
    {
        return $this->hasMany(TicketEvent::class);
    }

    // ✅ Commentaires du ticket
    public function comments()
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    // ✅ Client peut modifier seulement si pending
    public function canEdit(): bool
    {
        return $this->sync_status === 'pending' && $this->user_id === auth()->id();
    }

    // ✅ Client peut supprimer seulement si pending
    public function canDelete(): bool
    {
        return $this->sync_status === 'pending' && $this->user_id === auth()->id();
    }
}