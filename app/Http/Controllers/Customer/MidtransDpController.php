<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class MidtransDpController extends Controller
{
    private function setupMidtrans(): void
    {
        \Midtrans\Config::$serverKey    = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized  = true;
        \Midtrans\Config::$is3ds        = true;
    }

    public function buatToken(Request $request, int $id): JsonResponse
    {
        $this->setupMidtrans();
        $user    = Auth::user();
        $pesanan = DB::table('pesanan')->where('id', $id)->where('user_id', $user->id)->first();

        if (!$pesanan) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }
        if ($pesanan->status !== 'pending') {
            return response()->json(['error' => 'Pesanan ini tidak memerlukan pembayaran DP lagi.'], 400);
        }

        $details = DB::table('detail_pesanan')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->where('detail_pesanan.pesanan_id', $id)
            ->select('detail_pesanan.*', 'master_kolam.nama_kolam', 'jenis_benur.nama as nama_jenis')
            ->get();

        if ($details->isEmpty()) {
            return response()->json(['error' => 'Detail pesanan tidak ditemukan.'], 404);
        }

        $totalKontrak = 0;
        foreach ($details as $d) {
            $totalKontrak += $d->total_kantong_hitung * $d->konversi_per_kantong * $d->harga_per_ekor_kontrak;
        }
        $nominalDp = (int) round($totalKontrak * 0.2);

        if ($nominalDp < 1) {
            return response()->json(['error' => 'Nominal DP tidak valid.'], 400);
        }

        $itemDetails = [];
        foreach ($details as $d) {
            $totalEkor = $d->total_kantong_hitung * $d->konversi_per_kantong;
            $subtotal  = $totalEkor * $d->harga_per_ekor_kontrak;
            $itemDetails[] = [
                'id'       => 'DP-' . $d->id,
                'price'    => (int) round($subtotal * 0.2),
                'quantity' => 1,
                'name'     => \Illuminate\Support\Str::limit('DP 20% - ' . ($d->nama_kolam ?? 'Kolam') . ' (' . number_format($totalEkor, 0, ',', '.') . ' Ekor)', 50),
            ];
        }

        $totalItem = array_sum(array_column($itemDetails, 'price'));
        if ($totalItem !== $nominalDp && !empty($itemDetails)) {
            $itemDetails[count($itemDetails) - 1]['price'] += ($nominalDp - $totalItem);
        }

        $orderId = $pesanan->nomor_invoice . '-DP-' . time();

        $chargePayload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $nominalDp,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email'      => $user->email,
            ],
            'item_details' => $itemDetails,
            'callbacks'    => [
                'finish'  => route('customer.pesanan.detail', $id),
                'error'   => route('customer.pesanan.detail', $id),
                'pending' => route('customer.pesanan.detail', $id),
            ],
        ];

        // DEBUG: dicatat sementara supaya bisa dilampirkan ke tiket support Midtrans.
        // Server key TIDAK ikut tercatat di sini karena tidak pernah dimasukkan ke $chargePayload.
        Log::info('Midtrans Full Charge Request (buatToken DP)', [
            'endpoint' => \Midtrans\Config::$isProduction
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
            'is_production' => \Midtrans\Config::$isProduction,
            'payload' => $chargePayload,
        ]);

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($chargePayload);

            return response()->json([
                'snap_token' => $snapToken,
                'order_id'   => $orderId,
                'nominal_dp' => $nominalDp,
            ]);
        } catch (\Exception $e) {
            Log::error('Midtrans Full Charge Request GAGAL (buatToken DP)', [
                'payload' => $chargePayload,
                'error_message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Gagal membuat token: ' . $e->getMessage()], 500);
        }
    }

    public function cekStatus(Request $request, int $id): JsonResponse
    {
        $this->setupMidtrans();
        $user    = Auth::user();
        $pesanan = DB::table('pesanan')->where('id', $id)->where('user_id', $user->id)->first();

        if (!$pesanan) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        $orderId = $request->input('order_id');
        if (!$orderId) {
            return response()->json(['error' => 'Order ID tidak ada.'], 400);
        }

        try {
            $status            = \Midtrans\Transaction::status($orderId);
            $transactionStatus = $status->transaction_status ?? '';
            $grossAmount       = (int) ($status->gross_amount ?? 0);
            $paymentType       = $status->payment_type ?? '';

            $sukses = in_array($transactionStatus, ['capture', 'settlement']);

            if ($sukses) {
                $diterapkan = $this->terapkanSuksesDp($pesanan, $grossAmount, $paymentType, $orderId, $user->id);
                if ($diterapkan) {
                    return response()->json([
                        'success'  => true,
                        'message'  => 'Pembayaran DP berhasil!',
                        'redirect' => route('customer.pesanan.detail', $id),
                    ]);
                }
                return response()->json([
                    'success'  => true,
                    'message'  => 'Pembayaran DP sudah tercatat.',
                    'redirect' => route('customer.pesanan.detail', $id),
                ]);
            }

            if (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
                return response()->json(['success' => false, 'message' => 'Pembayaran ' . $transactionStatus . '.']);
            }

            return response()->json(['success' => false, 'message' => 'Pembayaran masih diproses.']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal cek status: ' . $e->getMessage()], 500);
        }
    }

    public function buatTokenPelunasan(Request $request, int $id): JsonResponse
    {
        $this->setupMidtrans();
        $user    = Auth::user();
        $pesanan = DB::table('pesanan')->where('id', $id)->where('user_id', $user->id)->first();

        if (!$pesanan) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }
        if ($pesanan->status !== 'menunggu_pelunasan') {
            return response()->json(['error' => 'Pesanan belum siap untuk dilunasi.'], 400);
        }
        if (!$pesanan->total_pembayaran_final || $pesanan->total_pembayaran_final <= 0) {
            return response()->json(['error' => 'Total tagihan akhir belum ditetapkan admin.'], 400);
        }

        $nominalPelunasan = (int) round($pesanan->total_pembayaran_final - $pesanan->nominal_dp_dibayar);

        if ($nominalPelunasan < 1) {
            return response()->json(['error' => 'Nominal pelunasan tidak valid.'], 400);
        }

        $details = DB::table('detail_pesanan')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->join('master_kolam', 'siklus_kolam.kolam_id', '=', 'master_kolam.id')
            ->leftJoin('jenis_benur', 'siklus_kolam.jenis_id', '=', 'jenis_benur.id')
            ->where('detail_pesanan.pesanan_id', $id)
            ->select('detail_pesanan.*', 'master_kolam.nama_kolam', 'jenis_benur.nama as nama_jenis')
            ->get();

        $itemDetails   = [];
        $totalKontrak  = 0;
        foreach ($details as $d) {
            $konversiAktual  = $d->konversi_per_kantong_aktual ?? $d->konversi_per_kantong;
            $totalEkorAktual = ($d->total_kantong_riil_muat ?? $d->total_kantong_hitung) * $konversiAktual;
            $subtotalKolam   = $totalEkorAktual * ($d->harga_per_ekor_aktual ?? $d->harga_per_ekor_kontrak);
            $totalKontrak   += $subtotalKolam;

            $itemDetails[] = [
                '_subtotal' => $subtotalKolam, 
                'nama_kolam' => $d->nama_kolam ?? 'Kolam',
                'total_ekor' => $totalEkorAktual,
            ];
        }

        $finalItems = [];
        foreach ($itemDetails as $i => $item) {
            $proporsi = $totalKontrak > 0 ? $item['_subtotal'] / $totalKontrak : 1 / count($itemDetails);
            $finalItems[] = [
                'id'       => 'LUNAS-' . ($i + 1),
                'price'    => (int) round($nominalPelunasan * $proporsi),
                'quantity' => 1,
                'name'     => \Illuminate\Support\Str::limit('Pelunasan - ' . $item['nama_kolam'] . ' (' . number_format($item['total_ekor'], 0, ',', '.') . ' Ekor)', 50),
            ];
        }

        $totalItem = array_sum(array_column($finalItems, 'price'));
        if ($totalItem !== $nominalPelunasan && !empty($finalItems)) {
            $finalItems[count($finalItems) - 1]['price'] += ($nominalPelunasan - $totalItem);
        }

        $orderId = $pesanan->nomor_invoice . '-LUNAS-' . time();

        $chargePayload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $nominalPelunasan,
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email'      => $user->email,
            ],
            'item_details' => $finalItems,
            'callbacks'    => [
                'finish'  => route('customer.pesanan.detail', $id),
                'error'   => route('customer.pesanan.detail', $id),
                'pending' => route('customer.pesanan.detail', $id),
            ],
        ];

        // DEBUG: dicatat sementara supaya bisa dilampirkan ke tiket support Midtrans.
        Log::info('Midtrans Full Charge Request (buatToken Pelunasan)', [
            'endpoint' => \Midtrans\Config::$isProduction
                ? 'https://app.midtrans.com/snap/v1/transactions'
                : 'https://app.sandbox.midtrans.com/snap/v1/transactions',
            'is_production' => \Midtrans\Config::$isProduction,
            'payload' => $chargePayload,
        ]);

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($chargePayload);

            return response()->json([
                'snap_token'       => $snapToken,
                'order_id'         => $orderId,
                'nominal_pelunasan' => $nominalPelunasan,
            ]);
        } catch (\Exception $e) {
            Log::error('Midtrans Full Charge Request GAGAL (buatToken Pelunasan)', [
                'payload' => $chargePayload,
                'error_message' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Gagal membuat token pelunasan: ' . $e->getMessage()], 500);
        }
    }

    public function cekStatusPelunasan(Request $request, int $id): JsonResponse
    {
        $this->setupMidtrans();
        $user    = Auth::user();
        $pesanan = DB::table('pesanan')->where('id', $id)->where('user_id', $user->id)->first();

        if (!$pesanan) {
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        $orderId = $request->input('order_id');
        if (!$orderId) {
            return response()->json(['error' => 'Order ID tidak ada.'], 400);
        }

        try {
            $status            = \Midtrans\Transaction::status($orderId);
            $transactionStatus = $status->transaction_status ?? '';
            $grossAmount       = (int) ($status->gross_amount ?? 0);
            $paymentType       = $status->payment_type ?? '';

            $sukses = in_array($transactionStatus, ['capture', 'settlement']);

            if ($sukses) {
                $diterapkan = $this->terapkanSuksesPelunasan($pesanan, $grossAmount, $paymentType, $orderId, $user->id);
                if ($diterapkan) {
                    return response()->json([
                        'success'  => true,
                        'message'  => 'Pelunasan berhasil! Pesanan selesai.',
                        'redirect' => route('customer.pesanan.detail', $id),
                    ]);
                }
                return response()->json([
                    'success'  => true,
                    'message'  => 'Pelunasan sudah tercatat.',
                    'redirect' => route('customer.pesanan.detail', $id),
                ]);
            }

            if (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
                return response()->json(['success' => false, 'message' => 'Pembayaran ' . $transactionStatus . '.']);
            }

            return response()->json(['success' => false, 'message' => 'Pembayaran masih diproses.']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal cek status pelunasan: ' . $e->getMessage()], 500);
        }
    }

    public function notificationHandler(Request $request): JsonResponse
    {
        $this->setupMidtrans();

        $orderId      = $request->input('order_id');
        $statusCode   = $request->input('status_code');
        $grossAmount  = $request->input('gross_amount');
        $signatureKey = $request->input('signature_key');
        $transactionStatus = $request->input('transaction_status');
        $paymentType  = $request->input('payment_type', '');

        if (!$orderId || !$statusCode || !$grossAmount || !$signatureKey) {
            Log::warning('Midtrans notifikasi payload tidak lengkap', $request->all());
            return response()->json(['error' => 'Payload tidak lengkap.'], 400);
        }

        $serverKey          = config('services.midtrans.server_key');
        $expectedSignature  = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if (!hash_equals($expectedSignature, (string) $signatureKey)) {
            Log::warning('Midtrans notifikasi signature tidak valid', ['order_id' => $orderId]);
            return response()->json(['error' => 'Signature tidak valid.'], 403);
        }

        if (str_contains($orderId, '-DP-')) {
            $noInvoice = strstr($orderId, '-DP-', true);
            $jenis     = 'dp';
        } elseif (str_contains($orderId, '-LUNAS-')) {
            $noInvoice = strstr($orderId, '-LUNAS-', true);
            $jenis     = 'pelunasan';
        } else {
            Log::warning('Midtrans notifikasi order_id polanya tidak dikenali', ['order_id' => $orderId]);
            return response()->json(['error' => 'Order ID tidak dikenali.'], 400);
        }

        $pesanan = DB::table('pesanan')->where('nomor_invoice', $noInvoice)->first();
        if (!$pesanan) {
            Log::warning('Midtrans notifikasi: pesanan tidak ditemukan', ['no_invoice' => $noInvoice]);
            return response()->json(['error' => 'Pesanan tidak ditemukan.'], 404);
        }

        $sukses = in_array($transactionStatus, ['capture', 'settlement']);

        if ($sukses) {
            if ($jenis === 'dp') {
                $this->terapkanSuksesDp($pesanan, (int) $grossAmount, $paymentType, $orderId, null);
            } else {
                $this->terapkanSuksesPelunasan($pesanan, (int) $grossAmount, $paymentType, $orderId, null);
            }
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Menerapkan pembayaran DP yang sukses ke pesanan.
     *
     * Menangani 1 kasus khusus: pesanan yang SEMPAT auto-batal karena kedaluwarsa
     * (1 jam tanpa bayar), tapi ternyata pembayarannya baru sukses/terkonfirmasi
     * SETELAH auto-batal itu terjadi (race condition antara timeout & notifikasi
     * Midtrans yang telat sampai). Dalam kasus ini pesanan DIHIDUPKAN KEMBALI ke
     * 'proses', karena uang customer sudah benar-benar masuk.
     *
     * Pesanan yang dibatalkan MANUAL oleh admin (keterangan_batal diawali
     * "Dibatalkan Admin:") TIDAK ikut dihidupkan otomatis di sini — itu keputusan
     * final admin, bukan soal timeout sistem.
     *
     * @return bool true kalau baru saja diterapkan/dihidupkan, false kalau sudah pernah diproses sebelumnya (idempoten)
     */
    private function terapkanSuksesDp(object $pesanan, int $grossAmount, string $paymentType, string $orderId, ?int $userId): bool
    {
        $autoBatalKedaluwarsa = $pesanan->status === 'batal'
            && str_starts_with((string) ($pesanan->keterangan_batal ?? ''), 'Sistem Otomatis:');

        if ($pesanan->status !== 'pending' && !$autoBatalKedaluwarsa) {
            return false;
        }

        DB::table('pesanan')->where('id', $pesanan->id)->update([
            'status'             => 'proses',
            'nominal_dp_dibayar' => $grossAmount,
            'is_harga_dikunci'   => true,
            'waktu_kunci_dp'     => now(),
            'catatan_dp'         => 'Midtrans (' . $paymentType . ') | ' . $orderId,
            'keterangan_batal'   => null,
            'updated_at'         => now(),
        ]);

        $catatanTambahan = $autoBatalKedaluwarsa
            ? ' | Catatan: pesanan sempat auto-batal karena kedaluwarsa, dihidupkan kembali karena pembayaran ternyata berhasil.'
            : '';

        DB::table('log_kalkulasi_pesanan')->insert([
            'pesanan_id'   => $pesanan->id,
            'user_id'      => $userId, 
            'aksi'         => 'verifikasi_dp',
            'data_sesudah' => json_encode([
                'nominal_dp'            => $grossAmount,
                'status'                => 'proses',
                'metode'                => 'Midtrans - ' . $paymentType,
                'order_id'              => $orderId,
                'dipulihkan_dari_batal' => $autoBatalKedaluwarsa,
            ]),
            'catatan'    => 'DP Rp ' . number_format($grossAmount, 0, ',', '.') . ' dibayar via Midtrans'
                            . ($userId ? ' (dikonfirmasi customer)' : ' (dikonfirmasi otomatis via notifikasi Midtrans)')
                            . $catatanTambahan,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($autoBatalKedaluwarsa) {
            Log::info('Pesanan dipulihkan dari auto-batal karena pembayaran DP telat terkonfirmasi', [
                'pesanan_id' => $pesanan->id,
                'order_id'   => $orderId,
            ]);
        }

        return true;
    }

    private function terapkanSuksesPelunasan(object $pesanan, int $grossAmount, string $paymentType, string $orderId, ?int $userId): bool
    {
        if ($pesanan->status !== 'menunggu_pelunasan') {
            return false;
        }

        DB::table('pesanan')->where('id', $pesanan->id)->update([
            'status'                => 'selesai',
            'waktu_pelunasan_final' => now(),
            'catatan_pelunasan'     => 'Midtrans (' . $paymentType . ') | ' . $orderId,
            'updated_at'            => now(),
        ]);

        DB::table('log_kalkulasi_pesanan')->insert([
            'pesanan_id'   => $pesanan->id,
            'user_id'      => $userId,
            'aksi'         => 'validasi_pelunasan',
            'data_sesudah' => json_encode([
                'nominal_pelunasan' => $grossAmount,
                'status'            => 'selesai',
                'metode'            => 'Midtrans - ' . $paymentType,
                'order_id'          => $orderId,
            ]),
            'catatan'    => 'Pelunasan Rp ' . number_format($grossAmount, 0, ',', '.') . ' dibayar via Midtrans'
                            . ($userId ? ' (dikonfirmasi customer)' : ' (dikonfirmasi otomatis via notifikasi Midtrans)'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }
}