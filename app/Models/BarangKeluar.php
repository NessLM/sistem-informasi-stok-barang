<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangKeluar extends Model
{
    use HasFactory;

    protected $fillable = [
        'barang_id',
        'gudang_id',
        'user_id',
        'nama_penerima',
        'jumlah',
        'tanggal',
        'bagian',
        'keterangan',
        'bukti',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Relasi ke Barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    /**
     * Relasi ke Gudang
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class);
    }

    /**
     * Relasi ke User (yang melakukan transaksi)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}