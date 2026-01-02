<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $primaryKey = 'cid';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'cid',
        'nama',
        'email',
        'sales',
        'pop',
        'packet',
        'alamat',
        'pic_it',
        'pic_finance',
        'no_it',
        'no_finance',
        'coordinate_maps',
        'status',
        'pembayaran_perbulan',
        'setup_fee',
        'note',
        'tgl_customer_aktif',
        'billing_aktif',
    ];

    protected $appends = ['pembayaran_perbulan_formatted', 'setup_fee_formatted'];

    public function getPembayaranPerbulanFormattedAttribute()
    {
        if ($this->pembayaran_perbulan) {
            return 'Rp. ' . number_format($this->pembayaran_perbulan, 0, ',', '.');
        } else {
            return '-';
        }
    }

    public function getSetupFeeFormattedAttribute()
    {
        if ($this->setup_fee) {
            return 'Rp. ' . number_format($this->setup_fee, 0, ',', '.');
        } else {
            return '-';
        }
    }
}
