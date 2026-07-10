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
            fileName: null,
            rekening: '{{ $profilTambak->nomor_rekening ?? '0123456789012' }}',
            copySuccess: false,
            showAlert: {{ $errors->any() ? 'true' : 'false' }},
            alertMsg: '{{ $errors->first() ?: '' }}',
            copyToClipboard() {
                navigator.clipboard.writeText(this.rekening).then(() => {
                    this.copySuccess = true;
                    setTimeout(() => this.copySuccess = false, 2000);
                });
            },
            validateForm(e) {
                if (!this.fileName) {
                    e.preventDefault();
                    this.alertMsg = 'Bukti transfer DP wajib diunggah.';
                    this.showAlert = true;
                    window.scrollTo({top:0, behavior:'smooth'});
                }
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
                    <p class="text-[10px] text-slate-400 font-medium">Lengkapi & lakukan pembayaran</p>
                </div>
            </div>
            <div class="flex items-center gap-1 text-blue-600 bg-blue-50 px-2.5 py-1.5 rounded-xl">
                <i class="fa-solid fa-shield-halved text-xs"></i>
                <span class="text-[9px] font-black">Aman</span>
            </div>
        </div>

        {{-- ALERT ERROR --}}
        <div x-show="showAlert" x-transition class="mb-4 p-3 bg-rose-50 border border-rose-200 rounded-xl flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
            <p class="text-[11px] font-bold text-rose-700 flex-1" x-text="alertMsg"></p>
            <button @click="showAlert=false" class="text-rose-400"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="checkoutForm" action="{{ route('customer.checkout.process') }}" method="POST" enctype="multipart/form-data" @submit="validateForm">
            @csrf

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
                            <p class="text-xs font-black text-slate-900 truncate">{{ $item->nama_kolam }}</p>
                            <p class="text-[9px] text-slate-400 font-medium mb-1.5">Benur {{ $item->nama_jenis ?? 'Vaname' }} · PL{{ $item->label_ukuran ?? '10' }}</p>
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

            {{-- STEP 3: Rekening --}}
            <div class="bg-white rounded-[18px] p-4 border border-slate-100 shadow-sm mb-3">
                <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                    <span class="w-4 h-4 bg-blue-600 text-white rounded-full flex items-center justify-center text-[8px] font-black">3</span>
                    Transfer ke Rekening Tambak
                </p>
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[14px] p-4 text-white relative overflow-hidden">
                    <i class="fa-solid fa-building-columns absolute -right-2 -bottom-3 text-[60px] text-white/10"></i>
                    <div class="flex justify-between items-start mb-2 relative z-10">
                        <p class="text-[9px] font-bold text-blue-200 uppercase tracking-widest">{{ $profilTambak->nama_bank ?? 'BRI' }}</p>
                        <button type="button" @click="copyToClipboard"
                            class="flex items-center gap-1 bg-white/20 px-2 py-1 rounded-lg text-[9px] font-black"
                            x-text="copySuccess ? '✓ Tersalin!' : 'Salin'">
                        </button>
                    </div>
                    <p class="text-xl font-black tracking-widest mb-1 font-mono relative z-10" x-text="rekening"></p>
                    <p class="text-[9px] font-bold text-blue-200 relative z-10">a.n. {{ $profilTambak->atas_nama ?? 'AQUAFARM OFFICIAL' }}</p>
                </div>
            </div>

            {{-- STEP 4: Rincian Pembayaran --}}
            <div class="bg-white rounded-[18px] p-4 border border-slate-100 shadow-sm mb-3">
                <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                    <span class="w-4 h-4 bg-blue-600 text-white rounded-full flex items-center justify-center text-[8px] font-black">4</span>
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
                        <p class="text-[9px] text-slate-400">Transfer minimal nominal ini</p>
                    </div>
                    <p class="text-xl font-black text-rose-600">Rp{{ number_format($dpWajib, 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- STEP 5: Upload Bukti --}}
            <div class="bg-white rounded-[18px] p-4 border border-slate-100 shadow-sm">
                <p class="text-[9px] font-black text-blue-600 uppercase tracking-widest mb-1 flex items-center gap-1.5">
                    <span class="w-4 h-4 bg-rose-500 text-white rounded-full flex items-center justify-center text-[8px] font-black">5</span>
                    Upload Bukti Transfer <span class="text-rose-500 ml-1">*Wajib</span>
                </p>
                <p class="text-[10px] text-slate-400 font-medium mb-3">Foto struk/resi transfer. Pesanan diproses setelah diverifikasi admin.</p>

                <label class="block cursor-pointer">
                    <input type="file" name="bukti_transfer" accept="image/*" class="hidden"
                        @change="fileName = $event.target.files[0]?.name ?? null">
                    <div class="border-2 border-dashed rounded-[14px] py-8 px-4 text-center transition-colors"
                        :class="fileName ? 'border-emerald-400 bg-emerald-50' : 'border-blue-300 bg-blue-50/50 hover:bg-blue-50'">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-2 shadow-sm"
                            :class="fileName ? 'bg-emerald-500 shadow-emerald-500/30' : 'bg-blue-600 shadow-blue-500/30'">
                            <i class="fa-solid text-white text-lg" :class="fileName ? 'fa-check' : 'fa-cloud-arrow-up'"></i>
                        </div>
                        <p class="text-sm font-black mb-1" :class="fileName ? 'text-emerald-600' : 'text-blue-600'"
                            x-text="fileName || 'Klik Pilih Foto Struk/Resi'"></p>
                        <p class="text-[9px] text-slate-400" x-show="!fileName">JPG, PNG — Maks. 3MB</p>
                    </div>
                </label>

                <div class="mt-3 bg-slate-50 rounded-xl p-3 flex gap-2 text-[9px] text-slate-500 border border-slate-100">
                    <i class="fa-solid fa-circle-info text-blue-400 mt-0.5 shrink-0"></i>
                    <p class="leading-relaxed">Pelunasan akhir dihitung berdasarkan <strong>volume fisik riil</strong> di lapangan. Harga/ekor Anda sudah dikunci.</p>
                </div>
            </div>

        </form>

        {{-- STICKY BOTTOM --}}
        <div class="fixed bottom-[60px] md:bottom-0 left-0 right-0 bg-white border-t border-slate-100 px-4 py-3 z-40 shadow-[0_-6px_20px_rgba(0,0,0,0.06)]">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div>
                    <p class="text-[10px] text-slate-500 font-medium">Wajib Bayar DP 20%</p>
                    <p class="text-xl font-black text-rose-600 leading-tight">Rp{{ number_format($dpWajib, 0, ',', '.') }}</p>
                </div>
                <button type="submit" form="checkoutForm"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-black text-sm px-6 py-3.5 rounded-2xl shadow-lg shadow-blue-600/25 flex items-center gap-2 active:scale-95 transition-all">
                    Bayar Sekarang <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
            </div>
        </div>

    </div>
</x-customer-layout>