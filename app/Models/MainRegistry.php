<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class MainRegistry extends Model
{
    use HasFactory;

    protected $table = 'main_registry';

    protected $fillable = [
        'category',
        'full_name',
        'address',
        'district',
        'ds_division',
        'gn_division',
        'contact_number',
        'whatsapp_number',
        'email',
        // Self-Employed
        'age',
        'field_of_work',
        'employees_count',
        // Trade
        'contact_person',
        'members_count',
        // Audit
        'is_deleted',
        'deleted_at',
        'deleted_by',
        'deletion_reason',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'approved_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to only include active (non-deleted) records.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_deleted', false);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory(Builder $query, string $category): void
    {
        $query->where('category', $category);
    }

    /**
     * Scope to filter by district.
     */
    public function scopeByDistrict(Builder $query, string $district): void
    {
        $query->where('district', $district);
    }

    // Relationships

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'user_id');
    }
}
