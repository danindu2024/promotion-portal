<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\Current;

class StagingData extends Model
{
    use HasFactory;

    protected $table = 'staging_data';

    protected $fillable = [
        'batch_id',
        'data_payload',
        'validation_status', // Pending, Rejected, Approved
        'submission_type',   // NEW, UPDATE
        'target_record_id',
        'rejection_reason',
        'uploaded_by',
        'reviewed_by',
    ];

    protected $casts = [
        'data_payload' => 'array',
        'uploaded_at' => 'datetime',
    ];

    // Status Constants
    const STATUS_PENDING = 'Pending';
    const STATUS_REJECTED = 'Rejected';
    const STATUS_APPROVED = 'Approved';

    // Relationships

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }

    // only for update
    public function targetRecord()
    {
        return $this->belongsTo(MainRegistry::class, 'target_record_id');
    }

    // Helper Methods

    public function reject(string $reason)
    {
        $this->update([
            'validation_status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => Current::id()
        ]);
    }

    public function approve()
    {
        $this->update([
            'validation_status' => self::STATUS_APPROVED,
            'reviewed_by' => Current::id()
        ]);
    }

    /**
     * Scope a query to only include records that the given validator user is authorized to see.
     * Validators are restricted to records uploaded by users in their own district.
     */
    public function scopeForValidator($query, $user)
    {
        if ($user && strtolower(trim($user->access_level)) === 'validator' && !empty($user->district) && $user->district !== 'All') {
            return $query->whereHas('uploader', function ($q) use ($user) {
                $q->where('district', $user->district);
            });
        }
        return $query;
    }
}
