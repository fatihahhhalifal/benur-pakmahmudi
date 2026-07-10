<?php

$userId = 1; // Assuming user 1 is a customer
$pesananId = Illuminate\Support\Facades\DB::table('pesanan')->insertGetId([
    'user_id' => $userId,
    'nomor_invoice' => 'INV-TEST-'.time(),
    'status' => 'pending', 
    'nominal_dp_dibayar' => 0, 
    'is_harga_dikunci' => false,
    'created_at' => now(),
    'updated_at' => now()
]);

Illuminate\Support\Facades\DB::table('detail_pesanan')->insert([
    'pesanan_id' => $pesananId,
    'siklus_id' => 1, // assume 1 exists
    'jumlah_sak_dipesan' => 10,
    'kantong_eceran_dipesan' => 0,
    'total_kantong_hitung' => 450,
    'konversi_per_kantong' => 1700, 
    'harga_per_ekor_kontrak' => 50,
    'harga_per_ekor_aktual' => 50, 
    'subtotal_kotor' => 450 * 1700 * 50,
    'created_at' => now(),
    'updated_at' => now()
]);

Illuminate\Support\Facades\Auth::loginUsingId($userId);
$c = new App\Http\Controllers\Customer\MidtransDpController();
$r = Illuminate\Http\Request::create('/foo', 'POST');
$result = $c->buatToken($r, $pesananId);
echo json_encode($result->getData());
