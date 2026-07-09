<x-customer-layout>
    @php
        $cartData = [];
        foreach ($items as $item) {
            $hargaReal = \Illuminate\Support\Facades\DB::table('master_harga')
                ->where('jenis_id', function ($query) use ($item) {
                    $query->select('jenis_id')->from('siklus_kolam')->where('id', $item->siklus_id);
                })
                ->where('ukuran_id', function ($query) use ($item) {
                    $query->select('ukuran_id')->from('siklus_kolam')->where('id', $item->siklus_id);
                })
                ->where('grade_id', function ($query) use ($item) {
                    $query->select('grade_id')->from('siklus_kolam')->where('id', $item->siklus_id);
                })
                ->value('harga_jual') ?? ($item->harga_per_ekor ?? 0);

            $foto = \Illuminate\Support\Facades\DB::table('riwayat_sampling')
                ->where('siklus_id', $item->siklus_id)
                ->whereNotNull('path_foto')
                ->orderBy('tanggal_sampling', 'desc')
                ->value('path_foto');

            $cartData[] = [
                'id'           => $item->id,
                'siklus_id'    => $item->siklus_id,
                'nama_kolam'   => $item->nama_kolam,
                'nama_jenis'   => $item->nama_jenis ?? 'Vaname',
                'label_ukuran' => $item->label_ukuran ?? '10',
                'nama_grade'   => $item->nama_grade ?? 'Premium',
                'sak'          => (int) $item->jumlah_sak,
                'ecer'         => (int) $item->kantong_eceran,
                'harga'        => (float) $hargaReal,
                'foto'         => $foto ? asset('storage/' . $foto) : null,
                'delete_url'   => route('customer.keranjang.destroy', $item->id),
                'selected'     => true,
            ];
        }
    @endphp

    <div x-data="cartManager({{ json_encode($cartData) }})" class="font-sans text-slate-800 antialiased">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight mb-0.5">Keranjang Saya</h1>
                <p class="text-[11px] text-slate-500 font-medium">Konfirmasi pesanan sebelum checkout</p>
            </div>
            <div class="w-10 h-10 bg-white rounded-[14px] flex items-center justify-center text-blue-600 shadow-sm border border-slate-200 relative">
                <i class="fa-solid fa-cart-shopping"></i>
                <template x-if="items.length > 0">
                    <span class="absolute -top-1.5 -right-1.5 bg-blue-600 text-white text-[9px] font-black w-5 h-5 flex items-center justify-center rounded-full border-2 border-[#F1F5F9]"
                        x-text="items.length"></span>
                </template>
            </div>
        </div>

        {{-- ALERT INFO --}}
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 flex gap-3 items-start mb-6">
            <i class="fa-solid fa-circle-info text-blue-500 text-lg shrink-0 mt-0.5"></i>
            <div>
                <h3 class="text-[12px] font-black text-slate-900 mb-1">Sistem Pembayaran Transparan</h3>
                <p class="text-[11px] text-slate-600 font-medium leading-relaxed">Untuk mengamankan harga dan kuota, diperlukan <strong>DP 20%</strong> di awal. Sisa pelunasan (80%) dilakukan fleksibel sesuai volume aktual saat pengambilan di tambak.</p>
            </div>
        </div>

        {{-- ALERT KONVERSI SATUAN (REVISI: Edukasi satuan konsisten) --}}
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 flex gap-3 items-start mb-6">
            <i class="fa-solid fa-scale-balanced text-slate-400 text-lg shrink-0 mt-0.5"></i>
            <div>
                <h3 class="text-[12px] font-black text-slate-900 mb-1">Konversi Satuan</h3>
                <p class="text-[11px] text-slate-600 font-medium leading-relaxed">
                    <span class="font-black text-blue-600">1 Sak = 45 Kantong</span>,
                    <span class="font-black text-blue-600">1 Kantong = 1.700 Ekor</span>.
                    Jumlah ekor pada keranjang merupakan estimasi awal; volume final mengikuti hasil timbang muat di lapangan.
                </p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-[11px] font-black flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-circle-check text-emerald-500"></i> {{ session('success') }}
            </div>
        @endif

        {{-- KERANJANG KOSONG --}}
        <template x-if="items.length === 0">
            <div class="text-center py-20 flex flex-col items-center justify-center bg-white rounded-3xl border border-slate-100 shadow-sm">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4 text-slate-300 border border-slate-100">
                    <i class="fa-solid fa-cart-arrow-down text-3xl"></i>
                </div>
                <h3 class="text-base font-black text-slate-900 mb-1">Keranjang Anda Kosong</h3>
                <p class="text-[11px] text-slate-500 font-medium max-w-[200px] leading-relaxed mb-6">Mulai pilih benur terbaik untuk hasil panen yang maksimal.</p>
                <a href="{{ route('customer.katalog') }}"
                    class="bg-slate-900 text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg hover:bg-slate-800 transition-colors">
                    Belanja Sekarang
                </a>
            </div>
        </template>

        {{-- ISI KERANJANG --}}
        <template x-if="items.length > 0">
            <div class="flex flex-col lg:flex-row gap-6 items-start">

                {{-- KIRI: List Item --}}
                <div class="flex-1 w-full min-w-0">
                    <div class="flex items-center justify-between mb-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" x-model="selectAll" @change="toggleAll()"
                                class="w-5 h-5 rounded-md border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-bold text-slate-700">Pilih Semua (<span x-text="items.length"></span>)</span>
                        </label>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="item.id">
                            <div class="bg-white rounded-[20px] border border-slate-100 shadow-[0_2px_15px_rgba(0,0,0,0.03)] overflow-hidden transition-all"
                                :class="item.selected ? 'ring-2 ring-blue-500/20' : 'opacity-70'">

                                {{-- Baris atas: foto + info + hapus --}}
                                <div class="flex gap-3 p-4 pb-3">
                                    {{-- Checkbox --}}
                                    <div class="flex items-center shrink-0">
                                        <input type="checkbox" x-model="item.selected" @change="checkSelection()"
                                            class="w-5 h-5 rounded-md border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                                    </div>

                                    {{-- Foto --}}
                                    <div class="w-[72px] h-[72px] bg-slate-50 rounded-[14px] flex items-center justify-center shrink-0 border border-slate-100 overflow-hidden cursor-pointer"
                                        @click="item.selected = !item.selected; checkSelection()">
                                        <template x-if="item.foto">
                                            <img :src="item.foto" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!item.foto">
                                            <i class="fa-solid fa-shrimp text-blue-200 text-2xl"></i>
                                        </template>
                                    </div>

                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0 cursor-pointer" @click="item.selected = !item.selected; checkSelection()">
                                        <h3 class="text-sm font-black text-slate-900 leading-tight truncate" x-text="item.nama_kolam"></h3>
                                        <p class="text-[11px] font-bold text-slate-500 truncate mt-0.5" x-text="'Benur ' + item.nama_jenis"></p>
                                        <div class="flex gap-1.5 flex-wrap mt-1.5">
                                            <span class="bg-amber-50 text-amber-600 text-[9px] font-black px-2 py-0.5 rounded-md border border-amber-100 flex items-center gap-1">
                                                <i class="fa-solid fa-star text-amber-400 text-[7px]"></i> <span x-text="item.nama_grade"></span>
                                            </span>
                                            <span class="bg-blue-50 text-blue-600 text-[9px] font-black px-2 py-0.5 rounded-md border border-blue-100" x-text="'PL ' + item.label_ukuran"></span>
                                        </div>
                                    </div>

                                    {{-- Hapus --}}
                                    <form :action="item.delete_url" method="POST" class="shrink-0">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="w-8 h-8 rounded-xl border border-slate-200 flex items-center justify-center text-slate-400 hover:text-rose-500 hover:bg-rose-50 transition-colors shadow-sm">
                                            <i class="fa-regular fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                </div>

                                {{-- Harga & Subtotal + Input qty --}}
                                <div class="px-4 pb-4 border-t border-slate-50 pt-3">
                                    <div class="flex justify-between items-end mb-3">
                                        <div>
                                            <p class="text-[9px] font-bold text-slate-400 mb-0.5">Harga / Ekor</p>
                                            <p class="text-sm font-black text-slate-800" x-text="'Rp ' + formatRupiah(item.harga)"></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[9px] font-bold text-slate-400 mb-0.5">Subtotal</p>
                                            <p class="text-base font-black text-blue-600"
                                                x-text="'Rp ' + formatRupiah(((item.sak * 45) + item.ecer) * 1700 * item.harga)"></p>
                                        </div>
                                    </div>

                                    {{-- Input Sak & Kantong --}}
                                    {{-- REVISI: Label "Ecer/Eceran" diganti "Kantong" agar konsisten dengan modul lain --}}
                                    <div class="bg-slate-50 rounded-xl p-1 flex items-center justify-between border border-slate-100">
                                        <div class="flex-1 flex items-center justify-between px-3 py-1">
                                            <span class="text-xs font-bold text-slate-600">Sak</span>
                                            <div class="flex items-center gap-3">
                                                <button type="button" @click="updateQty(index, 'sak', 'sub')"
                                                    class="text-blue-600 font-black text-lg w-7 h-7 flex items-center justify-center active:scale-90 transition-transform rounded-lg hover:bg-blue-50">−</button>
                                                <span class="w-8 text-center text-sm font-black text-slate-900" x-text="item.sak"></span>
                                                <button type="button" @click="updateQty(index, 'sak', 'add')"
                                                    class="text-blue-600 font-black text-lg w-7 h-7 flex items-center justify-center active:scale-90 transition-transform rounded-lg hover:bg-blue-50">+</button>
                                            </div>
                                        </div>
                                        <div class="w-px h-8 bg-slate-200 shrink-0"></div>
                                        <div class="flex-1 flex items-center justify-between px-3 py-1">
                                            <span class="text-xs font-bold text-slate-600">Kantong</span>
                                            <div class="flex items-center gap-3">
                                                <button type="button" @click="updateQty(index, 'ecer', 'sub')"
                                                    class="text-blue-600 font-black text-lg w-7 h-7 flex items-center justify-center active:scale-90 transition-transform rounded-lg hover:bg-blue-50">−</button>
                                                <span class="w-8 text-center text-sm font-black text-slate-900" x-text="item.ecer"></span>
                                                <button type="button" @click="updateQty(index, 'ecer', 'add')"
                                                    class="text-blue-600 font-black text-lg w-7 h-7 flex items-center justify-center active:scale-90 transition-transform rounded-lg hover:bg-blue-50">+</button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- REVISI: rangkaian utuh Sak + Kantong = Total Kantong = Total Ekor --}}
                                    <div class="mt-2 bg-blue-50/60 border border-blue-100 rounded-lg px-3 py-2 flex items-center justify-center gap-1.5 text-[11px] flex-wrap">
                                        <span class="font-bold text-slate-500" x-text="item.sak + ' Sak'"></span>
                                        <span class="text-slate-300">+</span>
                                        <span class="font-bold text-slate-500" x-text="item.ecer + ' Kantong'"></span>
                                        <span class="text-slate-300">=</span>
                                        <span class="font-black text-slate-700" x-text="formatRupiah(item.sak*45 + item.ecer) + ' Kantong'"></span>
                                        <span class="text-slate-300">=</span>
                                        <span class="font-black text-blue-600" x-text="formatRupiah((item.sak*45 + item.ecer)*1700) + ' Ekor'"></span>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>
                </div>

                {{-- KANAN: Ringkasan Pesanan — sejajar dengan card item pertama, sticky di desktop --}}
                <div class="w-full lg:w-[320px] shrink-0 lg:sticky lg:top-[44px]">
                    {{-- REVISI: spacer setinggi label "Pilih Semua" agar top card sejajar dengan card item pertama --}}
                    <div class="hidden lg:block h-[36px]"></div>
                    <div class="bg-white rounded-[20px] border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100">
                            <h3 class="text-sm font-black text-slate-900">Ringkasan Pesanan</h3>
                        </div>

                        <div class="p-5 space-y-4">
                            {{-- Info total item --}}
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-box-open"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-black text-slate-900"><span x-text="selectedItems.length"></span> Item Dipilih</p>
                                </div>
                            </div>

                            {{-- REVISI: Total Volume sebagai rangkaian utuh Sak + Kantong = Total Kantong = Total Ekor --}}
                            <div class="bg-blue-50/60 border border-blue-100 rounded-xl px-3 py-2">
                                <p class="text-[10px] font-bold text-slate-500 mb-1">Total Volume</p>
                                <div class="flex items-center gap-1.5 text-[11px] flex-wrap">
                                    <span class="font-bold text-slate-600" x-text="totalSak + ' Sak'"></span>
                                    <span class="text-slate-300">+</span>
                                    <span class="font-bold text-slate-600" x-text="totalKantongEcer + ' Kantong'"></span>
                                    <span class="text-slate-300">=</span>
                                    <span class="font-black text-slate-700" x-text="formatRupiah(totalKantong) + ' Kantong'"></span>
                                    <span class="text-slate-300">=</span>
                                    <span class="font-black text-blue-600" x-text="formatRupiah(totalVolumeEkor) + ' Ekor'"></span>
                                </div>
                            </div>

                            {{-- Rincian per item --}}
                            {{-- REVISI: stacked 2 baris - nama kolam + total ekor di baris 1, breakdown sak/kantong di baris 2 (lebih kecil & rapi) --}}
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <template x-for="item in selectedItems" :key="'d'+item.id">
                                    <div class="pb-2 border-b border-slate-50 last:border-0 last:pb-0">
                                        <div class="flex justify-between items-center gap-2 mb-0.5">
                                            <span class="text-[11px] text-slate-700 font-bold truncate pr-2" x-text="item.nama_kolam"></span>
                                            <span class="text-[11px] text-blue-600 font-black shrink-0" x-text="formatRupiah((item.sak*45+item.ecer)*1700) + ' Ekor'"></span>
                                        </div>
                                        <p class="text-[9px] text-slate-400 font-medium"
                                           x-text="item.sak + ' Sak + ' + item.ecer + ' Kantong = ' + formatRupiah(item.sak*45+item.ecer) + ' Kantong'"></p>
                                    </div>
                                </template>
                            </div>

                            {{-- Kalkulasi --}}
                            <div class="bg-slate-50 rounded-xl p-3 space-y-2 border border-slate-100 text-[11px]">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Est. Total Nilai</span>
                                    <span class="font-black text-slate-800" x-text="'Rp '+formatRupiah(grandTotal)"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Est. Pelunasan Nanti (80%)</span>
                                    <span class="font-black text-slate-600" x-text="'Rp '+formatRupiah(pelunasan)"></span>
                                </div>
                                <div class="flex justify-between items-center border-t border-slate-200 pt-2 mt-1">
                                    <span class="text-sm font-black text-slate-800">Wajib Bayar DP 20%</span>
                                    <span class="text-lg font-black text-rose-600" x-text="'Rp '+formatRupiah(dpWajib)"></span>
                                </div>
                            </div>

                            {{-- Tombol Checkout --}}
                            <form action="{{ route('customer.checkout.init') }}" method="POST">
                                @csrf
                                <template x-for="item in selectedItems" :key="'i'+item.id">
                                    <input type="hidden" name="selected_ids[]" :value="item.id">
                                </template>
                                <button type="submit" :disabled="selectedItems.length === 0"
                                    :class="selectedItems.length === 0
                                        ? 'bg-slate-200 text-slate-400 cursor-not-allowed'
                                        : 'bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 active:scale-95'"
                                    class="w-full font-black text-sm py-3.5 rounded-xl transition-all flex items-center justify-center gap-2">
                                    Checkout (<span x-text="selectedItems.length"></span>)
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </template>

    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('cartManager', (initialItems) => ({
                items: initialItems,
                selectAll: true,

                get selectedItems() { return this.items.filter(i => i.selected); },

                // REVISI: getter total sak, total kantong eceran, total kantong (akumulasi), dan total ekor
                get totalSak() { return this.selectedItems.reduce((t,i) => t + i.sak, 0); },
                get totalKantongEcer() { return this.selectedItems.reduce((t,i) => t + i.ecer, 0); },
                get totalKantong() { return this.selectedItems.reduce((t,i) => t + ((i.sak*45)+i.ecer), 0); },
                get totalVolumeEkor() { return this.selectedItems.reduce((t,i) => t+(((i.sak*45)+i.ecer)*1700), 0); },

                get grandTotal() { return this.selectedItems.reduce((t,i) => t+((((i.sak*45)+i.ecer)*1700)*i.harga), 0); },
                get dpWajib() { return this.grandTotal * 0.20; },
                get pelunasan() { return this.grandTotal * 0.80; },

                formatRupiah(angka) { return new Intl.NumberFormat('id-ID').format(angka); },
                toggleAll() { this.items.forEach(i => i.selected = this.selectAll); },
                checkSelection() { this.selectAll = this.items.every(i => i.selected); },

                updateQty(index, type, action) {
                    let item = this.items[index];
                    if (type === 'sak') {
                        if (action === 'add') item.sak++;
                        if (action === 'sub' && item.sak > 0) item.sak--;
                    } else {
                        if (action === 'add') item.ecer++;
                        if (action === 'sub' && item.ecer > 0) item.ecer--;
                    }
                    this.syncBackend(item);
                },

                syncBackend(item) {
                    let fd = new FormData();
                    fd.append('_token', '{{ csrf_token() }}');
                    fd.append('id', item.id);
                    fd.append('jumlah_sak', item.sak);
                    fd.append('kantong_eceran', item.ecer);
                    fetch('{{ route('customer.keranjang.update') }}', { method:'POST', body:fd })
                        .catch(e => console.error('Sync gagal', e));
                }
            }));
        });
    </script>
</x-customer-layout>