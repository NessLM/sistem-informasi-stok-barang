<?php
// app/Models/Riwayat.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Riwayat extends Model
{
    use HasFactory;

    protected $table = 'riwayat';
    
    protected $fillable = [
        'tanggal',
        'waktu',
        'nama_barang',
        'jumlah',
        'bagian',
        'bukti', // Tetap di fillable
        'alur_barang'
    ];
    
    protected $casts = [
        'tanggal' => 'date',
    ];
}