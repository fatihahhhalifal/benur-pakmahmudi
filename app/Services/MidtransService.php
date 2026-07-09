<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    protected string $serverKey;
    protected bool $isProduction;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key');
        $this->isProduction = (bool) config('services.midtrans.is_production');
    }

    protected function snapBaseUrl(): string
    {
        return $this->isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    protected function statusBaseUrl(string $orderId): string
    {
        $host = $this->isProduction
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';

        return "{$host}/v2/{$orderId}/status";
    }

    /**
     * Bikin Snap Token buat 1 transaksi (dipakai buat munculin popup pembayaran Midtrans).
     *
     * @param  string  $orderId  ID unik transaksi. WAJIB unik per percobaan bayar (Midtrans akan
     *                           menolak/reuse token lama kalau order_id sama persis dipakai ulang).
     * @param  int     $amount   Nominal yang harus dibayar (dalam Rupiah, tanpa desimal).
     * @param  array   $customer ['first_name' => ..., 'email' => ..., 'phone' => ...]
     * @return array|null        ['token' => ..., 'redirect_url' => ...] atau null kalau gagal.
     */
    public function createSnapToken(string $orderId, int $amount, array $customer): ?array
    {
        $response = Http::withBasicAuth($this->serverKey, '')
            ->acceptJson()
            ->post($this->snapBaseUrl(), [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'customer_details' => [
                    'first_name' => $customer['first_name'] ?? 'Customer',
                    'email' => $customer['email'] ?? 'customer@example.com',
                    'phone' => $customer['phone'] ?? '',
                ],
            ]);

        if ($response->failed()) {
            Log::error('Midtrans createSnapToken gagal', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Cek status transaksi langsung ke server Midtrans (server-to-server, aman dipakai di localhost
     * karena laptop kita yang manggil keluar ke Midtrans, bukan menunggu dipanggil balik / webhook).
     *
     * @return array|null Response lengkap dari Midtrans, termasuk field 'transaction_status'.
     *                     Nilai yang berarti "sudah lunas": 'settlement' atau 'capture'.
     */
    public function getStatus(string $orderId): ?array
    {
        $response = Http::withBasicAuth($this->serverKey, '')
            ->acceptJson()
            ->get($this->statusBaseUrl($orderId));

        if ($response->failed()) {
            Log::error('Midtrans getStatus gagal', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Helper: apakah status transaksi dari Midtrans berarti sudah lunas.
     */
    public function isLunas(array $statusResponse): bool
    {
        $status = $statusResponse['transaction_status'] ?? null;

        return in_array($status, ['settlement', 'capture'], true);
    }
}
