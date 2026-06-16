<x-layouts.app>
    <x-slot:title>Tambah Jadwal Praktik</x-slot:title>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.practice-schedules.index') }}"
               class="inline-flex items-center rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
            <h1 class="text-2xl font-semibold text-gray-900">Tambah Jadwal Praktik</h1>
        </div>

        <!-- Form -->
        <div class="mx-auto max-w-lg rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <form action="{{ route('admin.practice-schedules.store') }}" method="POST" class="space-y-5">
                @csrf

                <!-- Doctor -->
                <div>
                    <label for="doctor_id" class="block text-sm font-medium text-gray-700">
                        Dokter <span class="text-red-500">*</span>
                    </label>
                    <select name="doctor_id"
                            id="doctor_id"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('doctor_id') border-red-500 @enderror">
                        <option value="">— Pilih Dokter —</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>
                                {{ $doctor->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Polyclinic -->
                <div>
                    <label for="polyclinic_id" class="block text-sm font-medium text-gray-700">
                        Poliklinik <span class="text-red-500">*</span>
                    </label>
                    <select name="polyclinic_id"
                            id="polyclinic_id"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('polyclinic_id') border-red-500 @enderror">
                        <option value="">— Pilih Poliklinik —</option>
                        @foreach ($polyclinics as $polyclinic)
                            <option value="{{ $polyclinic->id }}" @selected(old('polyclinic_id') == $polyclinic->id)>
                                {{ $polyclinic->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('polyclinic_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Day of Week -->
                <div>
                    <label for="day_of_week" class="block text-sm font-medium text-gray-700">
                        Hari Praktik <span class="text-red-500">*</span>
                    </label>
                    <select name="day_of_week"
                            id="day_of_week"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('day_of_week') border-red-500 @enderror">
                        <option value="">— Pilih Hari —</option>
                        @foreach ($dayLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('day_of_week') !== null && old('day_of_week') == $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('day_of_week')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Start Time -->
                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700">
                        Jam Mulai <span class="text-red-500">*</span>
                    </label>
                    <input type="time"
                           name="start_time"
                           id="start_time"
                           value="{{ old('start_time') }}"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('start_time') border-red-500 @enderror">
                    @error('start_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- End Time -->
                <div>
                    <label for="end_time" class="block text-sm font-medium text-gray-700">
                        Jam Selesai <span class="text-red-500">*</span>
                    </label>
                    <input type="time"
                           name="end_time"
                           id="end_time"
                           value="{{ old('end_time') }}"
                           required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm @error('end_time') border-red-500 @enderror">
                    @error('end_time')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit -->
                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-600 transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
