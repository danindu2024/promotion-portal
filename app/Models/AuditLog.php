<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    /**
     * Disable updated_at as it's not present in the migration.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'event_type',
        'action',
        'target_module',
        'ip_address',
        'details',
        'metadata',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'json',
        'created_at' => 'datetime',
    ];

    /**
     * Relationship with user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
