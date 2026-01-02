<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'user_id',
        'filters',
    ];

    // Don't cast filters to array in model, let controller handle it
    // This prevents double JSON encoding
    protected $casts = [
        // 'filters' => 'array', // Removed to prevent double encoding
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
