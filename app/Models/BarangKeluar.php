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
        'bagian_id',   
        'nama_penerima',
        'jumlah',
        'tanggal',
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

    /**
     * Relasi ke Bagian
     */
    public function bagian()
    {
        return $this->belongsTo(Bagian::class, 'bagian_id');
    }
}
