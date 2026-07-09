<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>SURAT_JALAN_{{ $pesanan->nomor_invoice }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            color: #000;
            background: #fff;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }

        .wrapper {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #000;
            padding: 30px;
        }

        /* Kop Surat (Header) */
        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .kop-surat h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .kop-surat p {
            margin: 2px 0;
            font-size: 12px;
            font-weight: bold;
        }

        /* Judul Dokumen */
        .document-title {
            text-align: center;
            margin-bottom: 25px;
        }

        .document-title h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 900;
            text-decoration: underline;
            text-transform: uppercase;
        }

        /* Tabel Informasi Pengiriman (Tanpa Border) */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 4px;
            vertical-align: top;
            font-weight: bold;
            font-size: 12px;
        }

        /* Tabel Item Barang (Dengan Border) */
        .item-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .item-table th {
            background: #f2f2f2;
            border: 1px solid #000;
            padding: 10px 8px;
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            text-transform: uppercase;
        }

        .item-table td {
            border: 1px solid #000;
            padding: 10px 8px;
            font-size: 12px;
            font-weight: bold;
        }

        /* Tanda Tangan */
        .footer-sign {
            display: table;
            width: 100%;
            margin-top: 50px;
            text-align: center;
        }

        .sign-col {
            display: table-cell;
            width: 33.33%;
            vertical-align: bottom;
        }

        .sign-space {
            margin-top: 70px;
            font-weight: bold;
            text-decoration: underline;
        }

        .no-print {
            max-width: 800px;
            margin: 0 auto 15px auto;
            text-align: right;
        }

        .btn-print {
            background: #000;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-weight: bold;
            cursor: pointer;
            font-family: monospace;
            font-size: 14px;
            border-radius: 4px;
        }

        @media print {
            body {
                padding: 0;
            }

            .wrapper {
                border: none;
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body>

    @php
        // Menarik data profil tambak langsung dari database
        $profilTambak = \Illuminate\Support\Facades\DB::table('profil_tambak')->first();
        $namaTambak = $profilTambak ? $profilTambak->nama_tambak : 'CV. AQUAFARM INDONESIA';
        $alamatTambak = $profilTambak ? $profilTambak->alamat : 'Jalan Raya Tambak No. 1, Tuban, Jawa Timur';
        $kontakTambak = $profilTambak ? $profilTambak->nomor_whatsapp : '081234567890';

        // Format Tanggal
        $tanggalMuat = $pesanan->updated_at
            ? \Carbon\Carbon::parse($pesanan->updated_at)->translatedFormat('d F Y')
            : \Carbon\Carbon::now()->translatedFormat('d F Y');
    @endphp

    <div class="no-print">
        <button onclick="window.print()" class="btn-print">[ CETAK SURAT JALAN ]</button>
    </div>

    <div class="wrapper">
        <div class="kop-surat">
            <h1>{{ $namaTambak }}</h1>
            <p>{{ $alamatTambak }}</p>
            <p>Kontak Hubungi: {{ $kontakTambak }}</p>
        </div>

        <div class="document-title">
            <h2>Surat Jalan / Pengantar Barang</h2>
        </div>

        <table class="info-table">
            <tr>
                <td style="width: 18%;">Nomor Referensi</td>
                <td style="width: 2%;">:</td>
                <td style="width: 40%;">{{ $pesanan->nomor_invoice }}</td>
                <td style="width: 15%;">Tanggal Muat</td>
                <td style="width: 2%;">:</td>
                <td style="width: 23%;">{{ $tanggalMuat }}</td>
            </tr>
            <tr>
                <td>Tujuan Pengiriman</td>
                <td>:</td>
                <td colspan="4">{{ $pesanan->nama_customer }}</td>
            </tr>
            <tr>
                <td>Armada / Nopol</td>
                <td>:</td>
                <td colspan="4">...........................................</td>
            </tr>
        </table>

        <p style="margin-bottom: 15px; font-weight: bold;">Bersama ini kami kirimkan muatan dengan rincian spesifikasi
            sebagai berikut:</p>

        <table class="item-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 45%;">Jenis Komoditas Barang</th>
                    <th style="width: 25%;">Asal Kolam Muat</th>
                    <th style="width: 25%;">Kuantitas Volume Aktual</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center;">1</td>
                    <td>BENUR {{ strtoupper($pesanan->nama_jenis) }} (PL-{{ $pesanan->label_ukuran }})</td>
                    <td style="text-align: center;">{{ $pesanan->nama_kolam }}</td>
                    <td style="text-align: center;">{{ $pesanan->total_kantong_riil_muat }} Kantong</td>
                </tr>
            </tbody>
        </table>

        <p style="font-size: 11px; font-style: italic; margin-top: 15px;">*Catatan Keamanan: Benur dikemas menggunakan
            tabung oksigen bertekanan tinggi di dalam wadah karung styrofoam hulu. Mohon tidak membongkar ikatan plastik
            di jalan demi menjaga keselamatan tingkat kelangsungan hidup benur udang.</p>

        <div class="footer-sign">
            <div class="sign-col">
                <p>Penerima Barang,</p>
                <div class="sign-space">( ........................... )</div>
            </div>
            <div class="sign-col">
                <p>Sopir Kendaraan,</p>
                <div class="sign-space">( ........................... )</div>
            </div>
            <div class="sign-col">
                <p>Admin Penanggung Jawab,</p>
                <div class="sign-space">( {{ Auth::user()->name }} )</div>
            </div>
        </div>
    </div>

</body>

</html>
