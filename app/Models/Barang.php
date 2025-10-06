<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';

    protected $fillable = [
        'kode',
        'nama',
        'harga',
        'stok', // Bisa dijadikan total stok atau dihapus
        'satuan',
        'kategori_id',
        'jenis_barang_id',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'stok' => 'integer',
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function jenisBarang()
    {
        return $this->belongsTo(JenisBarang::class, 'jenis_barang_id');
    }

    public function riwayat()
    {
        return $this->hasMany(RiwayatBarang::class, 'barang_id');
    }

    public function riwayatTujuan()
    {
        return $this->hasMany(RiwayatBarang::class, 'barang_tujuan_id');
    }

    /**
     * Relasi ke stok gudang
     */
    public function stokGudang()
    {
        return $this->hasMany(StokGudang::class);
    }

    /**
     * Get stok di gudang tertentu
     */
    public function stokDiGudang($gudangId)
    {
        return $this->stokGudang()->where('gudang_id', $gudangId)->first();
    }

    /**
     * Get atau buat stok gudang
     */
    public function getOrCreateStokGudang($gudangId)
    {
        return StokGudang::firstOrCreate(
            [
                'barang_id' => $this->id,
                'gudang_id' => $gudangId
            ],
            ['stok' => 0]
        );
    }

    /**
     * Total stok di semua gudang
     */
    public function getTotalStokAttribute()
    {
        return $this->stokGudang()->sum('stok');
    }

    /**
     * Stok di gudang utama (kategori barang)
     */
    public function getStokGudangUtamaAttribute()
    {
        if (!$this->kategori || !$this->kategori->gudang_id) {
            return 0;
        }
        $stok = $this->stokDiGudang($this->kategori->gudang_id);
        return $stok ? $stok->stok : 0;
    }

    public function scopeStokRendah($query, $minimum = 10)
    {
        return $query->where('stok', '<=', $minimum);
    }

    public function scopeStokHabis($query)
    {
        return $query->where('stok', 0);
    }

    public function getHargaFormattedAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }

    public function getStatusStokAttribute()
    {
        $totalStok = $this->total_stok;
        if ($totalStok == 0) {
            return 'habis';
        } elseif ($totalStok <= 10) {
            return 'rendah';
        }
        return 'tersedia';
    }
}