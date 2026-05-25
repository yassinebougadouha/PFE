<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'user_role',
        'action', 'module', 'description',
        'ip_address', 'user_agent',
        'old_values', 'new_values', 'status',
        // Champs db2
        'resource_type', 'resource_id',
        'meta', 'trace_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta'       => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        string $action,
        string $module,
        string $description,
        string $status = 'success',
        array $oldValues = [],
        array $newValues = [],
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?array $meta = null,
        ?string $traceId = null,
    ): void {
        $user = auth()->user();

        static::create([
            'user_id'       => $user?->id,
            'user_name'     => $user?->name ?? 'Système',
            'user_role'     => $user?->role ?? 'system',
            'action'        => strtoupper($action),
            'module'        => $module,
            'description'   => $description,
            'ip_address'    => request()->ip(),
            'user_agent'    => substr(request()->userAgent() ?? '', 0, 200),
            'old_values'    => $oldValues ?: null,
            'new_values'    => $newValues ?: null,
            'status'        => $status,
            'resource_type' => $resourceType,
            'resource_id'   => $resourceId,
            'meta'          => $meta,
            'trace_id'      => $traceId,
        ]);
    }

    public function getActionColorAttribute(): string
    {
        return match($this->action) {
            'LOGIN'           => 'info',
            'LOGOUT'          => 'secondary',
            'CREATE'          => 'success',
            'CREATE TICKET'   => 'success',
            'UPDATE'          => 'primary',
            'UPDATE TICKET'   => 'primary',
            'UPDATE SETTINGS' => 'warning',
            'DELETE'          => 'danger',
            'DELETE USER'     => 'danger',
            'DELETE TICKET'   => 'danger',
            'ASSIGN TICKET'   => 'info',
            'CHANGE ROLE'     => 'warning',
            'TOGGLE USER'     => 'warning',
            'CACHE CLEAR'     => 'dark',
            'OPTIMIZE'        => 'dark',
            'MAINTENANCE'     => 'danger',
            'LOGIN FAILED'    => 'danger',
            default           => 'secondary',
        };
    }

    public function getActionIconAttribute(): string
    {
        return match($this->action) {
            'LOGIN'           => 'login',
            'LOGOUT'          => 'logout',
            'CREATE',
            'CREATE TICKET'   => 'add_circle',
            'UPDATE',
            'UPDATE TICKET',
            'UPDATE SETTINGS' => 'edit',
            'DELETE',
            'DELETE USER',
            'DELETE TICKET'   => 'delete',
            'ASSIGN TICKET'   => 'assignment_ind',
            'CHANGE ROLE'     => 'manage_accounts',
            'TOGGLE USER'     => 'toggle_on',
            'CACHE CLEAR'     => 'refresh',
            'OPTIMIZE'        => 'bolt',
            'MAINTENANCE'     => 'construction',
            'LOGIN FAILED'    => 'gpp_bad',
            default           => 'info',
        };
    }
}