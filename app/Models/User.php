<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['nama', 'username', 'password', 'role', 'bagian'];
    protected $hidden = ['password', 'remember_token'];

    // Laravel 10/11: cast 'hashed' bikin setiap set string otomatis di-hash
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
    public function role()
{
    return $this->belongsTo(Role::class);
}

}
