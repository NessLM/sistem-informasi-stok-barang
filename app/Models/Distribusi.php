<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribusi extends Model
{
    use HasFactory;

    protected $table = 'distribusi';

    protected $fillable = [
        'barang_id',
        'user_asal_id',
        'user_tujuan_id',
        'gudang_tujuan',
        'jumlah',
        'tanggal',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah' => 'integer',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id');
    }

    public function userAsal()
    {
        return $this->belongsTo(User::class, 'user_asal_id');
    }

    public function userTujuan()
    {
        return $this->belongsTo(User::class, 'user_tujuan_id');
    }

    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan');
    }
}