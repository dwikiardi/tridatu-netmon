<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'reply',
        'role',
        'update_status',
        'metode_penanganan',
        'tanggal_kunjungan',
        'jam_kunjungan',
        'teknisi_id',
        'teknisi_ids',
    ];

    protected $casts = [
        'teknisi_ids' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
