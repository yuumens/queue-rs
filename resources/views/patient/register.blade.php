<x-layouts.app>
    <div class="mx-auto max-w-lg">
        <h1 class="mb-6 text-2xl font-semibold text-gray-800">Pendaftaran Pasien Baru</h1>

        {{-- General error section --}}
        @if ($errors->has('general'))
            <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ $errors->first('general') }}
            </div>
        @endif

        <form action="{{ route('patient.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- Full Name --}}
            <div>
                <label for="full_name" class="mb-1 block text-sm font-medium text-gray-600">Nama Lengkap</label>
                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    value="{{ old('full_name') }}"
                    required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                @error('full_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Date of Birth --}}
            <div>
                <label for="date_of_birth" class="mb-1 block text-sm font-medium text-gray-600">Tanggal Lahir</label>
                <input
                    type="date"
                    id="date_of_birth"
                    name="date_of_birth"
                    value="{{ old('date_of_birth') }}"
                    max="{{ date('Y-m-d') }}"
                    required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                @error('date_of_birth')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Address --}}
            <div>
                <label for="address" class="mb-1 block text-sm font-medium text-gray-600">Alamat</label>
                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >{{ old('address') }}</textarea>
                @error('address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- NIK --}}
            <div>
                <label for="nik" class="mb-1 block text-sm font-medium text-gray-600">NIK (16 digit)</label>
                <input
                    type="text"
                    id="nik"
                    name="nik"
                    value="{{ old('nik') }}"
                    pattern="\d{16}"
                    maxlength="16"
                    inputmode="numeric"
                    required
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                @error('nik')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <div>
                <button
                    type="submit"
                    class="w-full rounded-md bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                >
                    Daftar
                </button>
            </div>
        </form>
    </div>
</x-layouts.app>
