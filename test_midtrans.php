<?php

$c = new App\Http\Controllers\Customer\MidtransDpController();
$r = Illuminate\Http\Request::create('/foo', 'POST');
$pesanan = Illuminate\Support\Facades\DB::table('pesanan')->first();
if ($pesanan) {
    Illuminate\Support\Facades\Auth::loginUsingId($pesanan->user_id);
    $result = $c->buatToken($r, $pesanan->id);
    echo json_encode($result->getData());
} else {
    echo "No pesanan found";
}
