<x-customer-layout>
    @php
        $calculatedGrandTotal = 0;
        foreach ($items as $item) {
            $hargaReal = \Illuminate\Support\Facades\DB::table('master_harga')
                ->where('jenis_id', function($q) use($item){ $q->select('jenis_id')->from('siklus_kolam')->where('id',$item->siklus_id); })
                ->where('ukuran_id', function($q) use($item){ $q->select('ukuran_id')->from('siklus_kolam')->where('id',$item->siklus_id); })
                ->where('grade_id',  function($q) use($item){ $q->select('grade_id')->from('siklus_kolam')->where('id',$item->siklus_id); })
                ->value('harga_jual') ?? ($item->harga_per_ekor ?? 0);

            $item->subtotal_aktual = ($item->jumlah_sak * 45 + $item->kantong_eceran) * 1700 * $hargaReal;
            $calculatedGrandTotal += $item->subtotal_aktual;

            $item->foto = \Illuminate\Support\Facades\DB::table('riwayat_sampling')
                ->where('siklus_id', $item->siklus_id)
                ->whereNotNull('path_foto')
                ->orderBy('tanggal_sampling', 'desc')
                ->value('path_foto');

            $item->total_kantong = $item->jumlah_sak * 45 + $item->kantong_eceran;
            $item->total_ekor    = $item->total_kantong * 1700;
        }
        $dpWajib       = $calculatedGrandTotal * 0.2;
        $sisaPelunasan = $calculatedGrandTotal * 0.8;
    @endphp

    <div class="font-sans text-slate-800 pb-[160px] md:pb-32"
        x-data="{
            showAlert: {{ $errors->any() ? 'true' : 'false' }},
            alertMsg: '{{ $errors->first() ?: '' }}',
            loadingCheckout: false,
            errorMsg: '',
            bayarSekarang() {
                if (this.loadingCheckout) return;
                this.loadingCheckout = true;
                this.errorMsg = '';

                fetch('{{ route('customer.checkout.process') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        this.loadingCheckout = false;
                        this.errorMsg = data.error;
                        return;
                    }

                    const pesananId = data.pesanan_id;

                    return fetch(`{{ url('/pesanan-saya') }}/${pesananId}/midtrans/token`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                        }
                    })
                    .then(r => r.json())
                    .then(tokenData => {
                        this.loadingCheckout = false;

                        if (tokenData.error) {
                            this.errorMsg = tokenData.error;
                            return;
                        }

                        window.snap.pay(tokenData.snap_token, {
                            onSuccess: (result) => {
                                fetch(`{{ url('/pesanan-saya') }}/${pesananId}/midtrans/cek-status`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                    },
                                    body: JSON.stringify({ order_id: result.order_id })
                                })
                                .then(r => r.json())
                                .then(res => {
                                    window.location.href = res.redirect || `{{ url('/pesanan-saya') }}/${pesananId}`;
                                });
                            },
                            onPending: () => {
                                window.location.href = `{{ url('/pesanan-saya') }}/${pesananId}`;
                            },
                            onError: () => {
                                this.errorMsg = 'Pembayaran gagal. Silakan coba lagi dari halaman pesanan.';
                            },
                            onClose: () => {
                                // Pesanan sudah tercatat, arahkan ke detail biar bisa lanjut bayar kapan saja
                                window.location.href = `{{ url('/pesanan-saya') }}/${pesananId}`;
                            }
                        });
                    });
                })
                .catch(() => {
                    this.loadingCheckout = false;
                    this.errorMsg = 'Gagal menghubungi server. Coba lagi.';
                });
            }
        }">

        {{-- HEADER --}}
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <a href="{{ route('customer.keranjang') }}"
                    class="w-9 h-9 bg-white rounded-xl flex items-center justify-center text-slate-600 shadow-sm border border-slate-200">
                    <i class="fa-solid fa-chevron-left text-sm"></i>
                </a>
                <div>
                    <h1 class="text-base font-black text-slate-900 leading-none mb-0.5">Checkout Pesanan</h1>
                    <p class="text-[10px] text-slate-400 font-medium">Tinjau pesanan, lalu bayar DP</p>
                </div>
            </div>
            <div class="flex items-center gap-1 text-blue-600 bg-blue-50 px-2.5 py-1.5 rounded-xl">
                <i class="fa-solid fa-shield-halved text-xs"></i>
                <span class="text-[9px] font-black">Aman</span>
            </div>
        </div>

        {{-- ALERT ERROR (validasi server, kalau ada) --}}
        <div x-show="showAlert" x-transition class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
            <p class="text-[11px] font-bold text-rose-700 flex-1" x-text="alertMsg"></p>
            <button @click="showAlert=false" class="text-rose-400"><i class="fa-solid fa-xmark"></i></button>
        </div>

        {{-- ALERT ERROR (proses bayar) --}}
        <div x-show="errorMsg" x-transition class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
            <p class="text-[11px] font-bold text-rose-700 flex-1" x-text="errorMsg"></p>
            <button @click="errorMsg=''" class="text-rose-400"><i class="fa-solid fa-xmark"></i></button>
        </div>

        {{-- STEP 1: Identitas --}}
        <div class="bg-white rounded-[18px] p-4 border border-slate-100 shadow-sm mb-3">
            <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                <span class="w-4 h-4 bg-blue-600 text-white rounded-full flex items-center justify-center text-[8px] font-black">1</span>
                Identitas Pemesan
            </p>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 shrink-0">
                    <i class="fa-solid fa-user text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-black text-slate-900">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-slate-500">{{ Auth::user()->email }}</p>
                </div>
                <span class="ml-auto text-[8px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-md font-black">Terkunci</span>
            </div>
        </div>

        {{-- STEP 2: Item Pesanan --}}
        <div class="bg-white rounded-[18px] p-4 border border-slate-100 shadow-sm mb-3">
            <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                <span class="w-4 h-4 bg-blue-600 text-white rounded-full flex items-center justify-center text-[8px] font-black">2</span>
                Item Pesanan
            </p>
            <div class="space-y-3">
                @foreach($items as $item)
                <div class="flex gap-3">
                    <div class="w-14 h-14 bg-slate-100 rounded-xl overflow-hidden shrink-0 border border-slate-100">
                        @if($item->foto)
                            <img src="{{ asset('storage/'.$item->foto) }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-blue-200">
                                <i class="fa-solid fa-shrimp text-xl"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-black text-slate-900 truncate">Benur {{ $item->nama_jenis ?? 'Vaname' }}</p>
                        <p class="text-[9px] text-slate-400 font-medium mb-1.5">PL{{ $item->label_ukuran ?? '10' }}</p>
                        <div class="flex flex-wrap items-center gap-1">
                            <span class="text-[8px] font-black text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded-md border border-blue-100">
                                {{ $item->jumlah_sak }} Sak
                                @if($item->kantong_eceran > 0)
                                    + {{ $item->kantong_eceran }} Ktg
                                @endif
                            </span>
                            <span class="text-slate-300 text-[8px]">→</span>
                            <span class="text-[8px] font-black text-slate-600 bg-slate-50 px-1.5 py-0.5 rounded-md border border-slate-200">
                                {{ number_format($item->total_kantong, 0, ',', '.') }} Ktg
                            </span>
                            <span class="text-slate-300 text-[8px]">→</span>
                            <span class="text-[8px] font-black text-teal-700 bg-teal-50 px-1.5 py-0.5 rounded-md border border-teal-200">
                                {{ number_format($item->total_ekor, 0, ',', '.') }} Ekor
                            </span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-xs font-black text-slate-900">Rp{{ number_format($item->subtotal_aktual, 0, ',', '.') }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- STEP 3: Rincian Pembayaran --}}
        <div class="bg-white rounded-[18px] p-4 border border-slate-100 shadow-sm mb-3">
            <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                <span class="w-4 h-4 bg-blue-600 text-white rounded-full flex items-center justify-center text-[8px] font-black">3</span>
                Rincian Pembayaran
            </p>
            <div class="space-y-2.5 mb-3 pb-3 border-b border-slate-100">
                <div class="flex justify-between">
                    <span class="text-slate-500 text-xs">Nilai Kontrak (100%)</span>
                    <span class="font-black text-slate-900 text-xs">Rp{{ number_format($calculatedGrandTotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-emerald-600 text-xs font-bold">Sisa Pelunasan Nanti (80%)</span>
                    <span class="font-black text-emerald-600 text-xs">Rp{{ number_format($sisaPelunasan, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-sm font-black text-slate-900">Wajib Bayar DP 20%</p>
                    <p class="text-[9px] text-slate-400">Bayar langsung via Midtrans</p>
                </div>
                <p class="text-xl font-black text-rose-600">Rp{{ number_format($dpWajib, 0, ',', '.') }}</p>
            </div>
            <div class="mt-3 bg-slate-50 rounded-xl p-3 flex gap-2 text-[9px] text-slate-500 border border-slate-100">
                <i class="fa-solid fa-circle-info text-blue-400 mt-0.5 shrink-0"></i>
                <p class="leading-relaxed">Pelunasan akhir dihitung berdasarkan <strong>volume fisik riil</strong> di lapangan. Harga/ekor Anda sudah dikunci.</p>
            </div>
        </div>

        {{-- STICKY BOTTOM --}}
        <div class="fixed bottom-[60px] md:bottom-0 left-0 right-0 bg-white border-t border-slate-100 px-4 py-3 z-40 shadow-[0_-6px_20px_rgba(0,0,0,0.06)]">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div>
                    <p class="text-[10px] text-slate-500 font-medium">Wajib Bayar DP 20%</p>
                    <p class="text-xl font-black text-rose-600 leading-tight">Rp{{ number_format($dpWajib, 0, ',', '.') }}</p>
                </div>
                <button @click="bayarSekarang()" :disabled="loadingCheckout"
                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-black text-sm px-6 py-3.5 rounded-2xl shadow-lg shadow-blue-600/25 flex items-center gap-2 active:scale-95 transition-all">
                    <span x-show="!loadingCheckout" class="flex items-center gap-2">Bayar Sekarang <i class="fa-solid fa-arrow-right text-xs"></i></span>
                    <span x-show="loadingCheckout" class="flex items-center gap-2"><i class="fa-solid fa-spinner fa-spin text-xs"></i> Memproses...</span>
                </button>
            </div>
        </div>

    </div>

    <script src="{{ config('midtrans.is_production')
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js' }}"
        data-client-key="{{ config('midtrans.client_key') }}">
    </script>
</x-customer-layout>
