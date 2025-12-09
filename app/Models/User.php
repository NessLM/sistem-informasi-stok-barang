<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use Notifiable;

    public function sendPasswordResetNotification($token)
{
    $this->notify(new ResetPasswordNotification($token));
}

    protected $fillable = [
        'nama',
        'username',
        'password',
        'role_id',
        'bagian_id', // UBAH dari 'bagian' ke 'bagian_id'
        'gudang_id',
        'email',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi ke role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relasi ke bagian - TAMBAH INI
     */
    public function bagian()
    {
        return $this->belongsTo(Bagian::class, 'bagian_id');
    }

    /**
     * Relasi ke gudang (untuk Penanggung Jawab Gudang)
     */
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'gudang_id');
    }

    /**
     * Relasi ke stok barang milik user
     */
    public function stokBarang()
    {
        return $this->hasMany(StokUser::class);
    }

    /**
     * Distribusi barang yang dikirim oleh user
     */
    public function distribusiDikirim()
    {
        return $this->hasMany(Distribusi::class, 'pengirim_id');
    }

    /**
     * Distribusi barang yang diterima oleh user
     */
    public function distribusiDiterima()
    {
        return $this->hasMany(Distribusi::class, 'penerima_id');
    }

    public function isAdmin(): bool
    {
        return $this->role?->nama === 'Admin';
    }
}
