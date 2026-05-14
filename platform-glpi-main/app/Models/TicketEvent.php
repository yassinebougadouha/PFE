<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketEvent extends Model
{
    protected $fillable = [
        'ticket_id',
        'action',
        'payload',
        'glpi_response',
        'sync_status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'glpi_response' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}

