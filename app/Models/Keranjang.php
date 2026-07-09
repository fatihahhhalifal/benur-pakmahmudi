<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keranjang extends Model
{
    use HasFactory;

    // Nama tabel di database
    protected $table = 'keranjang';

    protected $fillable = [
        'user_id',
        'etalase_id',
        'jumlah',
    ];

    // Relasi ke tabel EtalaseProduk
    public function produk()
    {
        return $this->belongsTo(EtalaseProduk::class, 'etalase_id');
    }

    // Relasi ke tabel User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}