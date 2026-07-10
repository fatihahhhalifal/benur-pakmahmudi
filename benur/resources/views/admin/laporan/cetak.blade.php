<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $judul }} - AQUAFARM</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 15mm 20mm 15mm;
        }

        body {
            font-family: 'Arial', 'Helvetica Neue', Helvetica, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 0;
            font-size: 10.5pt;
            line-height: 1.4;
            background-color: #ffffff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .kop-surat {
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: table;
            width: 100%;
        }

        .kop-logo {
            display: table-cell;
            vertical-align: middle;
            width: 8%;
            font-size: 28pt;
            color: #2563eb;
        }

        .kop-info {
            display: table-cell;
            vertical-align: middle;
            width: 67%;
            padding-left: 10px;
        }

        .kop-info h1 {
            font-size: 16pt;
            font-weight: bold;
            margin: 0 0 3px 0;
            letter-spacing: -0.3px;
            text-transform: uppercase;
        }

        .kop-info p {
            margin: 0;
            font-size: 9pt;
            color: #475569;
        }

        .kop-legalitas {
            display: table-cell;
            vertical-align: middle;
            width: 25%;
            text-align: right;
        }

        .kop-legalitas .badge-nib {
            font-weight: bold;
            color: #1e40af;
            font-size: 8.5pt;
            background-color: #f0f4ff;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            border: 1px solid #bfdbfe;
            text-transform: uppercase;
        }

        .judul-dokumen {
            text-align: center;
            margin-bottom: 20px;
        }

        .judul-dokumen h2 {
            font-size: 13pt;
            font-weight: bold;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .judul-dokumen p {
            margin: 0;
            color: #64748b;
            font-size: 9pt;
        }

        .tabel-ringkasan {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .tabel-ringkasan td {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            width: 33.33%;
        }

        .ringkasan-label {
            font-size: 8pt;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .ringkasan-nilai {
            font-size: 12pt;
            font-weight: bold;
        }

        .tabel-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .tabel-data th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 0.3px;
            padding: 7px 6px;
            border: 1px solid #0f172a;
        }

        .tabel-data td {
            padding: 7px 6px;
            border: 1px solid #e2e8f0;
            font-size: 9pt;
            vertical-align: middle;
        }

        .tabel-data tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .nominal-masuk {
            color: #16a34a;
            font-weight: bold;
            text-align: right;
        }

        .nominal-keluar {
            color: #dc2626;
            font-weight: bold;
            text-align: right;
        }

        .tabel-ttd {
            margin-top: 35px;
            width: 100%;
            border: none;
            page-break-inside: avoid;
        }

        .ttd-waktu {
            margin-bottom: 5px;
            font-size: 9pt;
        }

        .ttd-jabatan {
            font-weight: bold;
            margin-top: 2px;
            font-size: 9.5pt;
        }

        .ttd-jarak {
            height: 55px;
        }

        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
            font-size: 10pt;
            text-transform: uppercase;
        }

        .no-print {
            margin-bottom: 15px;
            text-align: right;
        }

        .no-print button {
            background-color: #1e293b;
            color: #ffffff;
            border: none;
            padding: 6px 14px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            font-size: 9pt;
            transition: background 0.2s;
        }

        .no-print button:hover {
            background-color: #0f172a;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    @php
        $profil =
            \Illuminate\Support\Facades\DB::table('profil_tambak')->first() ??
            (object) [
                'nama_tambak' => 'CV AQUAFARM INDONESIA',
                'alamat' => 'Kawasan Pesisir Pantai Utara, Jawa Timur',
                'email' => 'admin@aquafarm-indonesia.com',
                'npwp_nib' => 'NIB 9120003456128',
            ];
    @endphp

    <div class="no-print">
        <button onclick="window.print();">
            <i class="fa-solid fa-print"></i> Cetak / Unduh PDF Dokumen
        </button>
    </div>

    <div class="kop-surat">
        <div class="kop-logo">
            <i class="fa-solid fa-shrimp"></i>
        </div>
        <div class="kop-info">
            <h1>{{ $profil->nama_tambak }}</h1>
            <p>{{ $profil->alamat }}</p>
            <p>Pos-el: {{ $profil->email }} | Sistem Informasi Penjualan Benur</p>
        </div>
        <div class="kop-legalitas">
            <span class="badge-nib">{{ $profil->npwp_nib ?? 'IZIN USAHA VALID' }}</span>
        </div>
    </div>

    <div class="judul-dokumen">
        <h2>{{ $judul }}</h2>
        @if(request('bulan') && request('bulan') != 'semua')
            <p>Periode Data: {{ \Carbon\Carbon::createFromFormat('Y-m', request('bulan'))->translatedFormat('F Y') }}</p>
        @elseif(request('start') || request('end'))
            <p>Rentang Waktu: {{ request('start') ? \Carbon\Carbon::parse(request('start'))->translatedFormat('d F Y') : 'Awal' }} sampai {{ request('end') ? \Carbon\Carbon::parse(request('end'))->translatedFormat('d F Y') : 'Sekarang' }}</p>
        @endif
        <p>Tanggal Penarikan Laporan: {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB</p>
    </div>

    <table class="tabel-ringkasan">
        <tr>
            @if ($totalPendapatan > 0)
                <td>
                    <div class="ringkasan-label">Total Pendapatan (Kredit)</div>
                    <div class="ringkasan-nilai" style="color: #16a34a;">Rp
                        {{ number_format($totalPendapatan, 0, ',', '.') }}</div>
                </td>
            @endif

            @if ($totalPengeluaran > 0)
                <td>
                    <div class="ringkasan-label">Total Pengeluaran (Debet)</div>
                    <div class="ringkasan-nilai" style="color: #dc2626;">Rp
                        {{ number_format($totalPengeluaran, 0, ',', '.') }}</div>
                </td>
            @endif

            <td>
                <div class="ringkasan-label">Sisa Saldo Kas Bersih</div>
                <div class="ringkasan-nilai" style="color: #0f172a;">Rp
                    {{ number_format($totalPendapatan - $totalPengeluaran, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <table class="tabel-data">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">No.</th>
                <th style="width: 18%; text-align: left;">Tanggal Mutasi</th>
                <th style="width: 14%; text-align: left;">Alokasi Kolam</th>
                <th style="width: 18%; text-align: left;">Pos Akun</th>
                <th style="text-align: left;">Rincian Keterangan Pembukuan</th>
                <th style="width: 16%; text-align: right;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($arusKas as $index => $row)
                <tr>
                    <td style="text-align: center; color: #475569;">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->tanggal)->translatedFormat('d M Y, H:i') }}</td>
                    <td style="font-weight: bold; text-transform: uppercase;">{{ $row->nama_kolam }}</td>
                    <td style="font-size: 8.5pt; color: #334155;">{{ $row->pos_akun }}</td>
                    <td style="text-transform: uppercase; font-size: 8.5pt; color: #1e293b;">{{ $row->rincian }}</td>
                    <td class="{{ $row->jenis_arus === 'MASUK' ? 'nominal-masuk' : 'nominal-keluar' }}">
                        {{ $row->jenis_arus === 'MASUK' ? '(+) ' : '(-) ' }}Rp
                        {{ number_format($row->nominal, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; font-style: italic; padding: 15px;">
                        Tidak ada data transaksi yang terekam pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="tabel-ttd">
        <tr>
            <td style="width: 65%;"></td>
            <td style="width: 35%; text-align: center; vertical-align: top;">
                <p class="ttd-waktu">{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                <p class="ttd-jabatan">Pemilik Tambak Benur,</p>
                <div class="ttd-jarak"></div>
                <p class="ttd-nama">MAHMUDI</p>
                <p style="margin: 0; font-size: 8.5pt; color: #475569;">{{ $profil->nama_tambak }}</p>
            </td>
        </tr>
    </table>
</body>

</html>