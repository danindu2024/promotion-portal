<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MainRegistry extends Model
{
    use HasFactory;

    protected $table = 'main_registry';

    protected $fillable = [
        // common fields
        'category',
        'full_name',
        'address',
        'province',
        'district',
        'ds_division',
        'national_id_number',

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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_deleted', false);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by province.
     */
    public function scopeByProvince(Builder $query, string $province): Builder
    {
        return $query->where('province', $province);
    }

    /**
     * Scope to filter by district.
     */
    public function scopeByDistrict(Builder $query, string $district): Builder
    {
        return $query->where('district', $district);
    }

    /**
     * Scope to filter by ds division.
     */
    public function scopeByDSDivision(Builder $query, string $ds_division): Builder
    {
        return $query->where('ds_division', $ds_division);
    }

    /**
     * Scope to filter by field of work.
     */
    public function scopeByFieldOfWork(Builder $query, string $field_of_work): Builder
    {
        return $query->where('field_of_work', $field_of_work);
    }

    /**
    * Relationships
    */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'user_id');
    }
}
