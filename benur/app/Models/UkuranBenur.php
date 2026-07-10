<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UkuranBenur extends Model
{
    use HasFactory;

    protected $table = 'm_ukuran_benur';

    protected $fillable = ['ukuran', 'deskripsi'];
}