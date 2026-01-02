<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalonCustomer extends Model
{
    use HasFactory;

    protected $table = 'calon_customers';

    protected $fillable = [
        'nama',
        'telepon',
        'alamat',
        'koordinat',
        'sales_id',
        'status',
        'converted_to_cid',
        'tipe_survey',
    ];

    public function sales()
    {
        return $this->belongsTo(\App\Models\User::class, 'sales_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'calon_customer_id');
    }
}
