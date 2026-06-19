<x-layouts.app>
    <x-slot:title>Edit Poliklinik — {{ $polyclinic->name }}</x-slot:title>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex justify-between gap-4">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Poliklinik</h1>
            <a href="{{ route('admin.polyclinics.index') }}"
               class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
        </div>

        <!-- Form -->
        <div class="mx-auto max-w-lg rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
            <form action="{{ route('admin.polyclinics.update', $polyclinic) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Nama Poliklinik <span class="text-red-500">*</span>
                    </label>
                    <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('name') border-red-300 ring-2 ring-red-100 @else border-gray-200  @enderror">
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name', $polyclinic->name) }}"
                               required
                               class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0 rounded-xl"
                               placeholder="Contoh: Penyakit Dalam">
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Code -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Kode <span class="text-red-500">*</span>
                    </label>
                    <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('code') border-red-300 ring-2 ring-red-100 @else border-gray-200 @enderror">
                        <input type="text"
                               name="code"
                               id="code"
                               value="{{ old('code', $polyclinic->code) }}"
                               required
                               maxlength="5"
                               class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0 rounded-xl"
                               placeholder="Contoh: A">
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Maksimal 5 karakter alfabet (A-Z)</p>
                    @error('code')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit -->
                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="rounded-lg bg-blue-500 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition-all duration-200 hover:bg-blue-600 hover:shadow active:scale-95">
                        Perbarui
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
