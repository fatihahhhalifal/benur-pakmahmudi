<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeBenur extends Model
{
    use HasFactory;

    protected $table = 'm_grade_benur';

    protected $fillable = ['nama_grade', 'deskripsi'];
}