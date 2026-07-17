{{--
    PARTIAL: Form Upload Foto Produk
    Include di halaman monitoring/detail siklus admin:
    @include('admin.siklus.partials.foto-produk', ['siklus' => $siklus])
--}}

@php
    $fotoMap = \Illuminate\Support\Facades\DB::table('foto_produk_siklus')
        ->where('siklus_id', $siklus->id)
        ->get()
        ->keyBy('kategori');

    $kategoriList = [
        'skala_besar' => [
            'label' => 'Foto Skala Besar',
            'desc'  => 'Foto keseluruhan kolam atau wadah pembenihan',
            'icon'  => 'fa-expand',
            'color' => 'blue',
        ],
        'skala_kecil' => [
            'label' => 'Foto Skala Kecil',
            'desc'  => 'Close-up benur, detail fisik dan warna',
            'icon'  => 'fa-magnifying-glass-plus',
            'color' => 'teal',
        ],
        'ukuran' => [
            'label' => 'Foto Ukuran / PL',
            'desc'  => 'Foto benur berdampingan penggaris untuk verifikasi PL',
            'icon'  => 'fa-ruler',
            'color' => 'purple',
        ],
    ];
@endphp

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
    <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
        <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-images text-blue-600 text-sm"></i>
        </div>
        <div>
            <h3 class="text-sm font-black text-slate-900">Foto Produk Katalog</h3>
            <p class="text-[10px] text-slate-400 font-medium">Upload 3 foto untuk ditampilkan di katalog customer</p>
        </div>
        @php $totalFoto = $fotoMap->count(); @endphp
        <span class="ml-auto text-[10px] font-black px-2.5 py-1 rounded-full
            {{ $totalFoto == 3 ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-amber-50 text-amber-600 border border-amber-200' }}">
            {{ $totalFoto }}/3 Foto
        </span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @foreach($kategoriList as $key => $kat)
        @php $fotoAda = $fotoMap->get($key); @endphp
        <div class="border border-slate-200 rounded-xl overflow-hidden">

            {{-- Preview foto --}}
            <div class="relative aspect-[4/3] bg-slate-50">
                @if($fotoAda)
                    <img src="{{ asset('storage/'.$fotoAda->path_foto) }}"
                         class="w-full h-full object-cover">
                    {{-- Tombol hapus --}}
                    <form action="{{ route('admin.siklus.foto.hapus', $fotoAda->id) }}" method="POST"
                          class="absolute top-2 right-2">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            onclick="return confirm('Hapus foto ini?')"
                            class="w-7 h-7 bg-rose-500 hover:bg-rose-600 text-white rounded-lg flex items-center justify-center shadow-sm transition-colors">
                            <i class="fa-solid fa-trash text-[10px]"></i>
                        </button>
                    </form>
                    <span class="absolute bottom-2 left-2 bg-emerald-500 text-white text-[8px] font-black px-2 py-0.5 rounded-md">
                        <i class="fa-solid fa-check mr-0.5"></i> Terupload
                    </span>
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center gap-2 text-slate-300">
                        <i class="fa-solid {{ $kat['icon'] }} text-3xl"></i>
                        <p class="text-[9px] font-bold text-slate-400">Belum ada foto</p>
                    </div>
                @endif
            </div>

            {{-- Info & Form Upload --}}
            <div class="p-3 bg-white border-t border-slate-100">
                <p class="text-[10px] font-black text-slate-800 mb-0.5">{{ $kat['label'] }}</p>
                <p class="text-[9px] text-slate-400 font-medium mb-3 leading-snug">{{ $kat['desc'] }}</p>

                <form action="{{ route('admin.siklus.foto.upload', $siklus->id) }}"
                      method="POST" enctype="multipart/form-data"
                      x-data="{ namaFile: null }">
                    @csrf
                    <input type="hidden" name="kategori" value="{{ $key }}">

                    <label class="block cursor-pointer mb-2">
                        <input type="file" name="foto" accept="image/*" class="hidden"
                               @change="namaFile = $event.target.files[0]?.name">
                        <div class="border border-dashed rounded-lg py-2 px-3 text-center transition-colors
                            border-{{ $kat['color'] }}-200 bg-{{ $kat['color'] }}-50 hover:bg-{{ $kat['color'] }}-100">
                            <p class="text-[9px] font-black text-{{ $kat['color'] }}-600 truncate"
                               x-text="namaFile || 'Pilih foto...'"></p>
                        </div>
                    </label>

                    <input type="text" name="keterangan" placeholder="Keterangan (opsional)"
                        class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-[10px] text-slate-700 mb-2 focus:outline-none focus:border-blue-400 bg-slate-50">

                    <button type="submit"
                        class="w-full py-2 rounded-lg text-[10px] font-black text-white transition-colors
                            bg-{{ $kat['color'] }}-600 hover:bg-{{ $kat['color'] }}-700">
                        {{ $fotoAda ? 'Ganti Foto' : 'Upload Foto' }}
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    @if(session('success') && str_contains(session('success'), 'Foto'))
    <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-[11px] font-black text-emerald-700 flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-emerald-500"></i> {{ session('success') }}
    </div>
    @endif
</div>