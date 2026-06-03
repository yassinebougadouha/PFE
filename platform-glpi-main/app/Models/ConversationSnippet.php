<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConversationSnippet extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'body',
        'description',
        'shortcut',
        'channel',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
