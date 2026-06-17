<div class="max-w-2xl mx-auto">
    {{-- Search Mode Toggle (pill buttons) --}}
    <div class="mb-5">
        <div class="inline-flex rounded-lg bg-gray-100 p-1">
            <button
                wire:click="$set('searchMode', 'mr_number')"
                type="button"
                class="rounded-md px-4 py-2 text-sm font-medium transition-all duration-200
                    {{ $searchMode === 'mr_number' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="inline-block h-4 w-4 mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                </svg>
                No. Rekam Medis
            </button>
            <button
                wire:click="$set('searchMode', 'name')"
                type="button"
                class="rounded-md px-4 py-2 text-sm font-medium transition-all duration-200
                    {{ $searchMode === 'name' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="inline-block h-4 w-4 mr-1.5 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Nama Pasien
            </button>
        </div>
    </div>

    {{-- Search Input (modern unified search bar) --}}
    <div class="relative mb-6">
        <div class="relative flex items-center overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100">
            {{-- Search icon --}}
            <div class="pointer-events-none pl-4 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- Input --}}
            <input
                type="text"
                wire:model="query"
                wire:keydown.enter="search"
                placeholder="{{ $searchMode === 'mr_number' ? 'Cari nomor rekam medis': 'Ketik nama pasien...' }}"
                class="w-full border-0 bg-transparent px-3 py-3.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0"
            >

            {{-- Loading indicator --}}
            <div wire:loading wire:target="search" class="pr-3">
                <svg class="h-5 w-5 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            {{-- Search button --}}
            <button
                wire:click="search"
                type="button"
                class="m-1.5 rounded-lg bg-blue-500 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-200 hover:bg-blue-600 hover:shadow active:scale-95"
            >
                Cari
            </button>
        </div>

        {{-- Helper text --}}
        <p class="mt-2 text-xs text-gray-400 pl-1">
            {{ $searchMode === 'mr_number' ? 'Masukkan nomor rekam medis lengkap untuk pencarian tepat.' : 'Pencarian berdasarkan nama (tidak case-sensitive).' }}
        </p>
    </div>

    {{-- Search Results --}}
    @if (count($results) > 0)
        <div class="mb-6 space-y-3">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">{{ count($results) }} hasil ditemukan</p>
            @foreach ($results as $patient)
                <div class="group flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-blue-200 hover:shadow-md">
                    <div class="flex items-center gap-3">
                        {{-- Avatar --}}
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 font-semibold text-sm">
                            {{ strtoupper(substr($patient['full_name'], 0, 1)) }}
                        </div>
                        {{-- Info --}}
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $patient['full_name'] }}</p>
                            <div class="flex items-center gap-3 mt-0.5">
                                <span class="text-xs text-gray-500">{{ $patient['medical_record_number'] }}</span>
                                <span class="text-xs text-gray-300">•</span>
                                <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($patient['date_of_birth'])->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <button
                        wire:click="selectPatient({{ $patient['id'] }})"
                        type="button"
                        class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-600 transition-all duration-200 hover:bg-blue-500 hover:text-white hover:border-blue-500 active:scale-95"
                    >
                        Pilih
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Patient Not Found Message --}}
    @if ($hasSearched && count($results) === 0 && trim($query) !== '')
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <p class="text-sm text-yellow-800">
                Pasien tidak ditemukan.
                <a href="/register" class="text-blue-500 hover:text-blue-600 underline font-medium">Daftar sebagai
                    pasien baru</a>
            </p>
        </div>
    @endif

    {{-- Selected Patient Identity Card --}}
    @if ($selectedPatient !== null)
        <div class="mb-6 p-4 border-2 border-blue-500 bg-blue-50 rounded-md">
            <h3 class="text-lg font-semibold text-blue-800 mb-3">Data Pasien</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                <div>
                    <span class="font-medium text-gray-700">Nama:</span>
                    <span class="text-gray-900">{{ $selectedPatient->full_name }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700">No. Rekam Medis:</span>
                    <span class="text-gray-900">{{ $selectedPatient->medical_record_number }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Tanggal Lahir:</span>
                    <span
                        class="text-gray-900">{{ \Carbon\Carbon::parse($selectedPatient->date_of_birth)->format('d/m/Y') }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700">NIK:</span>
                    <span class="text-gray-900">{{ $selectedPatient->nik }}</span>
                </div>
                <div class="sm:col-span-2">
                    <span class="font-medium text-gray-700">Alamat:</span>
                    <span class="text-gray-900">{{ $selectedPatient->address }}</span>
                </div>
            </div>
        </div>

        {{-- Confirm Button --}}
        <form action="{{ route('patient.confirm') }}" method="POST">
            @csrf
            <input type="hidden" name="patient_id" value="{{ $selectedPatient->id }}">
            <button type="submit"
                class="w-full px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-md shadow-sm transition">
                Konfirmasi &amp; Lanjutkan
            </button>
        </form>
    @endif
</div>
