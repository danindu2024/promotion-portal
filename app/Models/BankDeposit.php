<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'nic',
        'mobile',
        'address',
        'enrollment_number',
        'amount',
        'deposit_date',
        'branch',
        'receipt_reference_number',
        'slip_path',
        'remarks',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }
}
