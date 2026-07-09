<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterHarga extends Model
{
    // Fix: Sesuai screenshot Anda, tabelnya adalah master_harga
    protected $table = 'master_harga'; 
    
    protected $fillable = ['jenis_id', 'ukuran_id', 'grade', 'harga_jual'];

    public function jenis() { return $this->belongsTo(JenisBenur::class, 'jenis_id'); }
    public function ukuran() { return $this->belongsTo(UkuranBenur::class, 'ukuran_id'); }
}