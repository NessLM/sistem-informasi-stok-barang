// ==========================================
// File: app/Models/TransaksiBarangMasuk.php
// ==========================================
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiBarangMasuk extends Model
{
    use HasFactory;

    protected $table = 'transaksi_barang_masuk';

    protected $fillable = [
        'kode_barang',
        'jumlah',
        'tanggal',
        'user_id',
        'keterangan',
        'bukti',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'integer',
    ];

    // Relasi ke Barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Method untuk proses barang masuk
    public static function prosesBarangMasuk($data)
    {
        \DB::beginTransaction();
        try {
            // 1. Simpan transaksi
            $transaksi = self::create($data);

            // 2. Update stok PB
            $pbStok = PbStok::where('kode_barang', $data['kode_barang'])->first();
            if ($pbStok) {
                $pbStok->tambahStok($data['jumlah']);
            } else {
                PbStok::create([
                    'kode_barang' => $data['kode_barang'],
                    'stok' => $data['jumlah'],
                ]);
            }

            \DB::commit();
            return $transaksi;
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }
}

// ==========================================
// File: app/Models/TransaksiDistribusi.php
// ==========================================
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiDistribusi extends Model
{
    use HasFactory;

    protected $table = 'transaksi_distribusi';

    protected $fillable = [
        'kode_barang',
        'id_gudang_tujuan',
        'jumlah',
        'tanggal',
        'user_id',
        'keterangan',
        'bukti',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'integer',
    ];

    // Relasi ke Barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'kode_barang', 'kode_barang');
    }

    // Relasi ke Gudang Tujuan
    public function gudangTujuan()
    {
        return $this->belongsTo(Gudang::class, 'id_gudang_tujuan');
    }

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Method untuk proses distribusi
    public static function prosesDistribusi($data)
    {
        \DB::beginTransaction();
        try {
            // 1. Cek stok PB
            $pbStok = PbStok::where('kode_barang', $data['kode_barang'])->firstOrFail();
            if (!$pbStok->cekStokCukup($data['jumlah'])) {
                throw new \Exception('Stok PB tidak mencukupi');
            }

            // 2. Simpan transaksi
            $transaksi = self::create($data);

            // 3. Kurangi stok PB
            $pbStok->kurangiStok($data['jumlah']);

            // 4. Tambah stok PJ
            $barang = Barang::where('kode_barang', $data['kode_barang'])->first();
            $pjStok = PjStok::where('kode_barang', $data['kode_barang'])
                           ->where('id_gudang', $data['id_gudang_tujuan'])
                           ->first();

            if ($pjStok) {
                $pjStok->tambahStok($data['jumlah']);
            } else {
                PjStok::create([
                    'kode_barang' => $data['kode_barang'],
                    'id_gudang' => $data['id_gudang_tujuan'],
                    'id_kategori' => $barang->id_kategori,
                    'stok' => $data['jumlah'],
                ]);
            }

            \DB::commit();
            return $transaksi;
        } catch (\Exception $e) {
            \DB::rollback();
            throw $e;
        }
    }
}