<x-layouts.app>
    <x-slot:title>Edit Poliklinik — {{ $polyclinic->name }}</x-slot:title>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.polyclinics.index') }}"
               class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
            <h1 class="text-2xl font-semibold text-gray-900">Edit Poliklinik</h1>
        </div>

        <!-- Form -->
        <div class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <form action="{{ route('admin.polyclinics.update', $polyclinic) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Nama Poliklinik <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           value="{{ old('name', $polyclinic->name) }}"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('name') border-red-500 @enderror"
                           placeholder="Contoh: Penyakit Dalam">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">
                        Kode <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="code"
                           id="code"
                           value="{{ old('code', $polyclinic->code) }}"
                           required
                           maxlength="5"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('code') border-red-500 @enderror"
                           placeholder="Contoh: A">
                    <p class="mt-1 text-xs text-gray-500">Maksimal 5 karakter alfabet (A-Z)</p>
                    @error('code')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-600 transition-colors">
                        Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
