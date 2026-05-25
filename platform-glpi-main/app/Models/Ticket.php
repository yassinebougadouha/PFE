<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'title', 'description', 'status',
        'urgency', 'impact', 'priority',
        'category', 'solution', 'attachments',
        'glpi_ticket_id', 'sync_status', 'last_error',
        'solved_by', 'source',
        'assigned_to',
        'resolved_at',
        // Champs db2
        'channel_source',
        'conversation_id',
        'source_email_id',
        'source_voice_call_id',
        'escalation_flag',
        'is_deleted',
        'glpi_sync_status',
        'glpi_sync_error',
        'resolution_note',
    ];

    protected $casts = [
        'resolved_at'     => 'datetime',
        'escalation_flag' => 'boolean',
        'is_deleted'      => 'boolean',
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

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    public function canEdit(): bool
    {
        return $this->sync_status === 'pending' && $this->user_id === auth()->id();
    }

    public function canDelete(): bool
    {
        return $this->sync_status === 'pending' && $this->user_id === auth()->id();
    }
}
