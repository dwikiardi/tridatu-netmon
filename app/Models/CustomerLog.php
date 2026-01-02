<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_cid',
        'action',
        'field_changed',
        'old_value',
        'new_value',
        'changed_by',
        'user_id',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_cid', 'cid');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
