<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SamplingStok extends Model
{
    protected $table = 'sampling_stoks'; 

    protected $fillable = [
        'stok_id',
        'serokan_ke',
        'target_serokan',
        'estimasi_sr',
        'sampel_grade_a',
        'sampel_grade_b',
        'sampel_grade_c',
        'catatan',
        'created_at',
    ];

    public $timestamps = false; 
    public function stok()
    {
        return $this->belongsTo(StokBenur::class, 'stok_id');
    }
}