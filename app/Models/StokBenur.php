<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StokBenur extends Model
{
    use HasFactory;

    protected $table = 'stok_benur';
    
    protected $fillable = [
        'jenis_id', 
        'ukuran_id', 
        'grade_id', 
        'nama_kolam',
        'foto',
        'jml_karung', 
        'kantong_per_karung', 
        'ekor_per_kantong',
        'jumlah_ekor', 
        'tanggal_tabur', 
        'harga_beli', 
        'catatan',
        'status' 
    ];

    // --- Relasi Dasar ---
    public function jenis() { return $this->belongsTo(JenisBenur::class, 'jenis_id'); }
    public function ukuran() { return $this->belongsTo(UkuranBenur::class, 'ukuran_id'); }
    public function grade() { return $this->belongsTo(GradeBenur::class, 'grade_id'); }
    public function samplings() { return $this->hasMany(SamplingStok::class, 'stok_id'); }
    public function biaya() { return $this->hasMany(BiayaOperasional::class, 'stok_id'); }
    public function penjualan() { return $this->hasMany(Penjualan::class, 'stok_id'); }
    
    // --- Relasi Baru: Alokasi Pesanan ---
    public function alokasiPesanan() { return $this->hasMany(AlokasiPesanan::class, 'stok_id'); }

    // --- Scopes ---
    public function scopeAktif($query) { return $query->where('status', 'aktif'); }

    // --- Accessors ---

    /**
     * MENGAMBIL GRADE TERKINI (HASIL SAMPLING TERAKHIR)
     */
    public function getGradeTerkiniAttribute()
    {
        $last = $this->samplings()->latest()->first();
        if (!$last) return $this->grade->nama_grade ?? null;

        $grades = [
            'Grade A' => $last->sampel_grade_a,
            'Grade B' => $last->sampel_grade_b,
            'Grade C' => $last->sampel_grade_c,
        ];

        return array_search(max($grades), $grades);
    }

    /**
     * POPULASI SEKARANG (BERDASARKAN SR SAMPLING TERAKHIR)
     * Menghitung nilai seperti "825 ekor dari SR 82.5%"
     */
    public function getPopulasiSekarangAttribute()
    {
        $last = $this->samplings()->latest()->first();
        
        // Jika ada sampling, gunakan estimasi_sr. Jika tidak ada, anggap 100% utuh.
        $sr = $last ? $last->estimasi_sr : 100;
        
        return floor(($this->jumlah_ekor * $sr) / 100);
    }

    /**
     * STOK TERSEDIA (SIAP DIALOKASIKAN)
     * Rumus: Populasi Sekarang (SR) - Total yang sudah dialokasikan ke pesanan riil
     */
    public function getStokTersediaAttribute()
    {
        // Hitung total ekor yang sudah secara resmi dialokasikan dari kolam ini
        $totalSudahDialokasikan = $this->alokasiPesanan()->sum('jumlah_ekor');
        
        $sisa = $this->populasi_sekarang - $totalSudahDialokasikan;
        
        return $sisa > 0 ? floor($sisa) : 0;
    }

    /**
     * TOTAL MODAL (HPP)
     */
    public function getNetCostAttribute()
    {
        $modalBibit = $this->harga_beli;
        $biayaSpesifik = $this->biaya->sum('nominal');
        $biayaUmumProRata = 0;

        if ($this->status === 'aktif') {
            $jumlahKolamAktif = self::aktif()->count();
            $totalBiayaUmum = \App\Models\BiayaOperasional::whereNull('stok_id')->sum('nominal');
            $biayaUmumProRata = $jumlahKolamAktif > 0 ? ($totalBiayaUmum / $jumlahKolamAktif) : 0;
        }

        return $modalBibit + $biayaSpesifik + $biayaUmumProRata;
    }

    public function getHppPerEkorAttribute()
    {
        // Menghitung HPP berdasarkan populasi riil saat ini, bukan tebar awal
        $populasi = $this->populasi_sekarang;
        if ($populasi <= 0) return 0;
        return $this->net_cost / $populasi;
    }
}