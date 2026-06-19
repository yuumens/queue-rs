<x-layouts.app>
    <x-slot:title>Edit Dokter — {{ $doctor->name }}</x-slot:title>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex justify-between gap-4">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Dokter</h1>
            <a href="{{ url()->previous() }}"
               class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
        </div>

        <!-- Form -->
        <div class="rounded-xl border border-gray-200 bg-white p-8 shadow-sm" style="width: 50rem">
            <form action="{{ route('admin.doctors.update', $doctor) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Nama Dokter <span class="text-red-500">*</span>
                    </label>
                    <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('name') border-red-300 ring-2 ring-red-100 @else border-gray-200 @enderror">
                        <input type="text"
                               name="name"
                               id="name"
                               value="{{ old('name', $doctor->name) }}"
                               required
                               class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0 rounded-xl"
                               placeholder="Contoh: dr. Ahmad Subandi">
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Polyclinic -->
                <div>
                    <label for="polyclinic_id" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Poliklinik <span class="text-red-500">*</span>
                    </label>
                    <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('polyclinic_id') border-red-300 ring-2 ring-red-100 @else border-gray-200 @enderror">
                        <select name="polyclinic_id"
                                id="polyclinic_id"
                                required
                                class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-0 rounded-xl appearance-none">
                            <option value="">— Pilih Poliklinik —</option>
                            @foreach ($polyclinics as $polyclinic)
                                <option value="{{ $polyclinic->id }}" @selected(old('polyclinic_id', $doctor->polyclinic_id) == $polyclinic->id)>
                                    {{ $polyclinic->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    @error('polyclinic_id')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Photo (img tag or URL) -->
                <div>
                    <label for="photo_url" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Foto Dokter
                    </label>
                    <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('photo_url') border-red-300 ring-2 ring-red-100 @else border-gray-200 @enderror">
                        <textarea name="photo_url"
                               id="photo_url"
                               rows="3"
                               class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-0 rounded-xl"
                               placeholder='Paste tag <img> dari imgbb, contoh: <img src="https://i.ibb.co.com/xxx/foto.jpg" alt="foto" border="0">'>{{ old('photo_url', $doctor->photo_url) }}</textarea>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Upload foto di <a href="https://imgbb.com/" target="_blank" class="text-blue-500 hover:underline">imgbb.com</a>, lalu paste kode HTML (tag &lt;img&gt;) di sini.</p>
                    @error('photo_url')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @if($doctor->photo_url)
                        <div class="mt-3">
                            <p class="text-xs text-gray-500 mb-1">Preview:</p>
                            <img src="{{ $doctor->photo_url }}" alt="{{ $doctor->name }}" class="h-20 w-20 rounded-full object-cover border border-gray-200">
                        </div>
                    @endif
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
