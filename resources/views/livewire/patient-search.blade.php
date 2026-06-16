<div class="max-w-2xl mx-auto">
    {{-- Search Mode Toggle --}}
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Cari berdasarkan:</label>
        <div class="flex items-center space-x-6">
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" wire:model.live="searchMode" value="mr_number" class="form-radio text-blue-500 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Nomor Rekam Medis</span>
            </label>
            <label class="inline-flex items-center cursor-pointer">
                <input type="radio" wire:model.live="searchMode" value="name" class="form-radio text-blue-500 focus:ring-blue-500">
                <span class="ml-2 text-sm text-gray-700">Nama Pasien</span>
            </label>
        </div>
    </div>

    {{-- Search Input --}}
    <div class="flex items-center space-x-2 mb-6">
        <input
            type="text"
            wire:model.live.debounce.400ms="query"
            placeholder="{{ $searchMode === 'mr_number' ? 'Masukkan nomor rekam medis...' : 'Masukkan nama pasien...' }}"
            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
        >
        <button
            wire:click="search"
            type="button"
            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-md shadow-sm transition"
        >
            Cari
        </button>
    </div>

    {{-- Search Results Table --}}
    @if(count($results) > 0)
        <div class="overflow-x-auto mb-6">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-md">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. RM</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Lahir</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($results as $patient)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $patient['full_name'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ $patient['medical_record_number'] }}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">{{ \Carbon\Carbon::parse($patient['date_of_birth'])->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <button
                                    wire:click="selectPatient({{ $patient['id'] }})"
                                    type="button"
                                    class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white text-xs font-medium rounded-md transition"
                                >
                                    Pilih
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Patient Not Found Message --}}
    @if(count($results) === 0 && trim($query) !== '')
        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <p class="text-sm text-yellow-800">
                Pasien tidak ditemukan.
                <a href="/register" class="text-blue-500 hover:text-blue-600 underline font-medium">Daftar sebagai pasien baru</a>
            </p>
        </div>
    @endif

    {{-- Selected Patient Identity Card --}}
    @if($selectedPatient !== null)
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
                    <span class="text-gray-900">{{ \Carbon\Carbon::parse($selectedPatient->date_of_birth)->format('d/m/Y') }}</span>
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
            <button
                type="submit"
                class="w-full px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-md shadow-sm transition"
            >
                Konfirmasi &amp; Lanjutkan
            </button>
        </form>
    @endif
</div>
