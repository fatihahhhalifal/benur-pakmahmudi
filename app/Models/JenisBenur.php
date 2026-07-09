<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisBenur extends Model
{
    use HasFactory;

    // Menghubungkan ke tabel yang kita buat di migration tadi
    protected $table = 'm_jenis_benur';

    // Kolom yang boleh diisi secara massal
    protected $fillable = ['nama', 'kode', 'deskripsi'];
}