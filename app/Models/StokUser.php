<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StokUser extends Model
{
    use HasFactory;

    protected $table = 'stokuser';

    protected $fillable = [
        'user_id',
        'barang_id',
        'jumlah',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }
}
