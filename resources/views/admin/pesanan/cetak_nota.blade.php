<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Invoice_{{ $pesanan->nomor_invoice }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Pengaturan Cetak Standar Internasional */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background-color: #ffffff;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
        }

        @page {
            size: A4;
            margin: 10mm 12mm;
        }
    </style>
</head>

<body class="bg-slate-50 font-sans min-h-screen sm:py-6 px-4">

    @php
        // 1. Tarik Data Profil Tambak Aktual
        $profilTambak = \Illuminate\Support\Facades\DB::table('profil_tambak')->first();

        $namaTambak = $profilTambak ? $profilTambak->nama_tambak : 'AQUAFARM INDONESIA';
        $alamatTambak = $profilTambak ? $profilTambak->alamat : 'Jl. Tambak Modern No. 1, Surabaya, Jawa Timur';
        $telpTambak = $profilTambak ? $profilTambak->nomor_whatsapp : '+62 812-3456-7890';
        $emailTambak = $profilTambak
            ? $profilTambak->email
            : 'halo@' . strtolower(str_replace(' ', '', $namaTambak)) . '.com';

        // 2. Tarik Data Siklus Tambahan
        $detailSiklus = \Illuminate\Support\Facades\DB::table('detail_pesanan')
            ->join('siklus_kolam', 'detail_pesanan.siklus_id', '=', 'siklus_kolam.id')
            ->leftJoin('grade_benur', 'siklus_kolam.grade_id', '=', 'grade_benur.id')
            ->where('detail_pesanan.pesanan_id', $pesanan->id)
            ->select(
                'siklus_kolam.jenis_id',
                'siklus_kolam.ukuran_id',
                'siklus_kolam.grade_id',
                'grade_benur.nama_grade',
            )
            ->first();

        // 3. Tarik Harga Kontrak yang Sinkron dengan master_harga
        $hargaReal = 0;
        $namaGrade = 'Premium';

        if ($detailSiklus) {
            $namaGrade = $detailSiklus->nama_grade ?? 'Premium';
            $hargaReal = \Illuminate\Support\Facades\DB::table('master_harga')
                ->where('jenis_id', $detailSiklus->jenis_id)
                ->where('ukuran_id', $detailSiklus->ukuran_id)
                ->where('grade_id', $detailSiklus->grade_id)
                ->value('harga_jual');
        }

        // Fallback Harga
        if (!$hargaReal) {
            $hargaReal = $type == 'dp' ? $pesanan->harga_per_ekor_kontrak ?? 0 : $pesanan->harga_per_ekor_aktual ?? 0;
        }

        // 4. Kalkulasi Nilai Finansial
        $subtotalKotorDP = $pesanan->total_ekor_booking * $hargaReal;

        $subtotalInvo = $type == 'dp' ? $subtotalKotorDP : $pesanan->subtotal_kotor;
        $diskonInvo = $type == 'dp' ? 0 : $pesanan->diskon_pembulatan_manual;
        $dpInvo = $pesanan->nominal_dp_dibayar;

        // Sisa yang harus dibayar / dilunasi
        $sisaInvo = $subtotalInvo - $diskonInvo - $dpInvo;
    @endphp

    {{-- TOMBOL AKSI ATAS --}}
    <div class="max-w-3xl mx-auto mb-4 flex justify-between items-center no-print">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Pratinjau Dokumen Resmi</span>
        <button onclick="window.print()"
            class="px-5 py-2 bg-slate-900 hover:bg-blue-600 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-sm">
            <i class="fa-solid fa-print mr-1"></i> Cetak / Simpan PDF
        </button>
    </div>

    {{-- LEMBAR NOTA INVOICE UTAMA --}}
    <div
        class="max-w-3xl mx-auto bg-white rounded-2xl border border-slate-200 p-6 sm:p-10 shadow-sm print-card relative overflow-hidden">
        <div class="relative z-10">

            {{-- 1. KOP SURAT / HEADER IDENTITAS TAMBAK --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-b border-slate-200 pb-4 mb-5 items-start">
                <div class="sm:col-span-2">
                    <div class="flex items-center gap-2 text-slate-900">
                        <i class="fa-solid fa-shrimp text-xl text-blue-600"></i>
                        <h1 class="text-lg font-black tracking-tight uppercase italic leading-none">
                            {{ $namaTambak }}
                        </h1>
                    </div>
                    <p class="text-[11px] font-medium text-slate-600 mt-2 max-w-xl whitespace-pre-line leading-relaxed">
                        {{ $alamatTambak }}
                    </p>
                    <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase tracking-wide">
                        <i class="fa-brands fa-whatsapp text-[10px] mr-0.5 text-slate-300"></i> WA: {{ $telpTambak }}
                        <span class="mx-1 text-slate-200">|</span>
                        <i class="fa-solid fa-envelope text-[8px] mr-0.5 text-slate-300"></i> Email: {{ $emailTambak }}
                    </p>
                </div>
                <div class="sm:text-right flex flex-col sm:items-end justify-between h-full pt-1">
                    <span
                        class="px-2.5 py-0.5 {{ $type == 'dp' ? 'bg-blue-50 text-blue-700 border-blue-100' : ($pesanan->status == 'selesai' ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-rose-50 text-rose-700 border-rose-100') }} text-[9px] font-black uppercase tracking-widest rounded border mb-2 inline-block">
                        {{ $type == 'dp' ? 'FAKTUR PREORDER DP' : ($pesanan->status == 'selesai' ? 'NOTA PELUNASAN FINAL' : 'INVOICE TAGIHAN FINAL') }}
                    </span>
                    <div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-wider leading-none">No.
                            Invoice</p>
                        <span
                            class="inline-block mt-1 bg-slate-900 text-white font-mono text-xs font-black px-2.5 py-1 rounded-md tracking-wide shadow-sm">
                            {{ $pesanan->nomor_invoice }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- 2. INFO TUJUAN CUSTOMER & TANGGAL --}}
            <div class="grid grid-cols-2 gap-4 mb-5 text-[11px] bg-slate-50 rounded-xl p-3.5 border border-slate-100">
                <div>
                    <p class="font-black text-slate-400 uppercase tracking-widest mb-1">Pelanggan / Pembeli:</p>
                    <h4 class="text-xs font-black text-slate-800 uppercase tracking-tight">{{ $pesanan->nama_customer }}
                    </h4>
                    <p class="text-slate-500 font-medium mt-0.5">Kemitraan AQUAFARM</p>
                    <p class="text-slate-400 font-mono text-[10px] mt-0.5">{{ $pesanan->email_customer ?? '-' }}</p>
                </div>
                <div class="text-right">
                    <p class="font-black text-slate-400 uppercase tracking-widest mb-1">Detail Penerbitan Nota:</p>
                    <p class="text-slate-800 font-bold">
                        Tgl. Nota:
                        {{ $type == 'dp' ? \Carbon\Carbon::parse($pesanan->waktu_kunci_dp ?? $pesanan->created_at)->format('d M Y - H:i') : \Carbon\Carbon::parse($pesanan->waktu_pelunasan_final ?? now())->format('d M Y - H:i') }}
                        WIB
                    </p>
                    <p class="text-slate-500 font-medium">Asal Pengambilan: {{ $pesanan->nama_kolam }}</p>
                    <div class="mt-1">
                        <span
                            class="font-black uppercase text-[9px] px-1.5 py-0.5 rounded border tracking-wider inline-block bg-slate-100 text-slate-600 border-slate-200">
                            {{ str_replace('_', ' ', $pesanan->status) }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- 3. TABEL PRODUK ITEM LIST --}}
            <div class="border border-slate-200 rounded-xl overflow-hidden mb-5 bg-transparent">
                <table class="w-full text-left border-collapse text-[11px]">
                    <thead>
                        <tr
                            class="bg-slate-50/80 font-black text-slate-400 uppercase tracking-wider border-b border-slate-200">
                            <th class="px-4 py-2.5">Deskripsi Komoditas Benur</th>
                            <th class="px-4 py-2.5 text-center">Volume (Kemasan & Riil Ekor)</th>
                            <th class="px-4 py-2.5 text-right">Harga Kontrak Satuan</th>
                            <th class="px-4 py-2.5 text-right">Subtotal Kotor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <span class="font-black text-slate-800 uppercase block mb-0.5">Benur
                                    {{ $pesanan->nama_jenis }}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase block">Size Var:
                                    PL-{{ $pesanan->label_ukuran }}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase block">Grade:
                                    {{ $namaGrade }}</span>
                            </td>
                            <td class="px-4 py-3 text-center align-top">
                                @if ($type == 'dp')
                                    <span class="font-bold text-slate-600 block">{{ $pesanan->jumlah_sak_dipesan }} Sak
                                        + {{ $pesanan->kantong_eceran_dipesan }} Ecer</span>
                                    <span class="font-mono font-black text-slate-800 block text-xs mt-0.5">Est:
                                        {{ number_format($pesanan->total_ekor_booking, 0, ',', '.') }} Ekor</span>
                                @else
                                    <span class="font-bold text-slate-600 block">Riil Muat:
                                        {{ $pesanan->total_kantong_riil_muat }} Kantong</span>
                                    <span class="text-[9px] text-slate-400 italic block">Kerapatan:
                                        {{ number_format($pesanan->konversi_per_kantong, 0, ',', '.') }}
                                        Ekor/Ktg</span>
                                    <span class="font-mono font-black text-slate-800 block text-xs mt-0.5">Aktual:
                                        {{ number_format($pesanan->total_ekor_aktual ?? $pesanan->total_kantong_riil_muat * $pesanan->konversi_per_kantong, 0, ',', '.') }}
                                        Ekor</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-slate-600 align-top">
                                Rp{{ number_format($hargaReal, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono font-bold text-slate-900 align-top">
                                Rp{{ number_format($subtotalInvo, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- 4. RINGKASAN KALKULASI FINANSIAL & OPERASIONAL STEMPEL --}}
            <div class="relative flex flex-row justify-between items-end border-b border-slate-200 pb-4 mb-6">

                {{-- AREA KIRI: STEMPEL VALIDASI --}}
                <div class="w-full flex items-center justify-start pl-6 pb-2 select-none">
                    @if ($type == 'pelunasan')
                        @if ($pesanan->status == 'selesai')
                            <div
                                class="border-[4px] border-emerald-500 text-emerald-500 text-sm font-black uppercase tracking-[0.2em] px-4 py-1.5 rounded-lg transform -rotate-6 font-sans shadow-sm">
                                <i class="fa-solid fa-circle-check mr-1 text-xs"></i> PAID / LUNAS
                            </div>
                        @else
                            <div
                                class="border-[4px] border-rose-500 text-rose-500 text-sm font-black uppercase tracking-[0.2em] px-4 py-1.5 rounded-lg transform -rotate-6 font-sans shadow-sm">
                                <i class="fa-solid fa-clock mr-1 text-xs"></i> UNPAID / TAGIHAN
                            </div>
                        @endif
                    @else
                        <div
                            class="border-[4px] border-amber-500 text-amber-500 text-sm font-black uppercase tracking-[0.15em] px-4 py-1.5 rounded-lg transform -rotate-6 font-sans shadow-sm">
                            <i class="fa-solid fa-receipt text-xs mr-1"></i> DP VERIFIED
                        </div>
                    @endif
                </div>

                {{-- AREA KANAN: DATA FINANSIAL NUMERIK --}}
                <div class="flex flex-col items-end text-[11px] space-y-1.5 w-72 shrink-0">
                    <div class="w-full flex justify-between px-1">
                        <span class="text-slate-400 font-bold uppercase">Subtotal Nilai Kontrak:</span>
                        <span
                            class="font-mono font-bold text-slate-800">Rp{{ number_format($subtotalInvo, 0, ',', '.') }}</span>
                    </div>

                    @if ($type == 'pelunasan')
                        <div class="w-full flex justify-between px-1 text-rose-600 font-semibold">
                            <span class="text-slate-400 font-bold uppercase">Diskon Penggenapan:</span>
                            <span class="font-mono">-Rp{{ number_format($diskonInvo, 0, ',', '.') }}</span>
                        </div>
                    @endif

                    <div class="w-full flex justify-between px-1 text-amber-600 font-semibold">
                        <span class="text-slate-400 font-bold uppercase">Telah Dibayar (DP):</span>
                        <span class="font-mono">Rp{{ number_format($dpInvo, 0, ',', '.') }}</span>
                    </div>

                    @if ($type == 'pelunasan')
                        @if ($pesanan->status == 'selesai')
                            <div
                                class="w-full flex justify-between text-xs font-black text-emerald-700 bg-emerald-50 p-2 rounded-xl border border-emerald-200 mt-1">
                                <span class="uppercase tracking-tight text-[10px] mt-0.5"><i
                                        class="fa-solid fa-circle-check mr-0.5"></i> Pelunasan Disetor:</span>
                                <span class="font-mono text-xs">Rp{{ number_format($sisaInvo, 0, ',', '.') }}</span>
                            </div>
                        @else
                            <div
                                class="w-full flex justify-between text-xs font-black text-rose-700 bg-rose-50 p-2 rounded-xl border border-rose-200 mt-1">
                                <span class="uppercase tracking-tight text-[10px] mt-0.5"><i
                                        class="fa-solid fa-triangle-exclamation mr-0.5"></i> Wajib Dilunasi:</span>
                                <span class="font-mono text-xs">Rp{{ number_format($sisaInvo, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    @else
                        <div
                            class="w-full flex justify-between text-xs font-black text-blue-700 bg-blue-50/70 p-2 rounded-xl border border-blue-100 mt-1">
                            <span class="uppercase tracking-tight text-[10px] mt-0.5">Sisa Tagihan (Est):</span>
                            <span class="font-mono text-sm">Rp{{ number_format($sisaInvo, 0, ',', '.') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- 5. FOOTER NOTA & TANDA TANGAN VALIDASI --}}
            <div
                class="grid grid-cols-2 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest mt-6">
                <div>
                    <p class="mb-12">Customer,</p>
                    <div class="w-28 border-b border-slate-300 mx-auto"></div>
                    <p class="text-slate-600 font-bold mt-1.5 truncate max-w-[180px] mx-auto">
                        {{ $pesanan->nama_customer }}
                    </p>
                </div>
                <div>
                    <p class="mb-12">Admin Penanggung Jawab,</p>
                    <div class="w-24 border-b border-slate-300 mx-auto"></div>
                    <p class="text-slate-600 font-bold mt-1.5">( {{ $namaTambak }} )</p>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
