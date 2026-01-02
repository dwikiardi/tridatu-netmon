<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'cid',
        'calon_customer_id',
        'created_by',
        'created_by_role',
        'jenis',
        'metode_penanganan',
        'priority',
        'tanggal_kunjungan',
        'pic_it_lokasi',
        'no_it_lokasi',
        'pic_teknisi',
        'teknisi_id',
        'packet',
        'note',
        'jam',
        'hari',
        'indikasi',
        'kendala',
        'solusi',
        'hasil',
        'status',
        'pop',
        'sla_remote_minutes',
        'sla_onsite_minutes',
        'sla_total_minutes',
    ];

    protected $casts = [
        'tanggal_kunjungan' => 'date',
        'jam' => 'datetime:H:i',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'cid', 'cid');
    }

    public function calonCustomer()
    {
        return $this->belongsTo(CalonCustomer::class, 'calon_customer_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class, 'ticket_id', 'id');
    }

    public function getSlaRemoteFormattedAttribute(): ?string
    {
        return $this->formatMinutes($this->sla_remote_minutes);
    }

    public function getSlaOnsiteFormattedAttribute(): ?string
    {
        return $this->formatMinutes($this->sla_onsite_minutes);
    }

    public function getSlaTotalFormattedAttribute(): ?string
    {
        return $this->formatMinutes($this->sla_total_minutes);
    }

    protected function formatMinutes(?int $minutes): ?string
    {
        if ($minutes === null) {
            return null;
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        if ($hours === 0) {
            return $mins . 'm';
        }

        if ($mins === 0) {
            return $hours . 'h';
        }

        return $hours . 'h ' . $mins . 'm';
    }
}
