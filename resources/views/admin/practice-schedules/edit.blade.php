<x-layouts.app>
    <x-slot:title>Edit Jadwal Praktik</x-slot:title>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex justify-between gap-4">
            <h1 class="text-2xl font-semibold text-gray-900">Edit Jadwal Praktik</h1>
            <a href="{{ url()->previous() }}"
               class="inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>
        </div>

        <!-- Form -->
        <div class="mx-auto max-w-lg rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
            <form action="{{ route('admin.practice-schedules.update', $practiceSchedule) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Doctor -->
                <div>
                    <label for="doctor_id" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Dokter <span class="text-red-500">*</span>
                    </label>
                    <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('doctor_id') border-red-300 ring-2 ring-red-100 @else border-gray-200  @enderror">
                        <select name="doctor_id"
                                id="doctor_id"
                                required
                                class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-0 rounded-xl appearance-none">
                            <option value="">— Pilih Dokter —</option>
                            @foreach ($doctors as $doctor)
                                <option value="{{ $doctor->id }}" @selected(old('doctor_id', $practiceSchedule->doctor_id) == $doctor->id)>
                                    {{ $doctor->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    @error('doctor_id')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Polyclinic -->
                <div>
                    <label for="polyclinic_id" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Poliklinik <span class="text-red-500">*</span>
                    </label>
                    <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('polyclinic_id') border-red-300 ring-2 ring-red-100 @else border-gray-200  @enderror">
                        <select name="polyclinic_id"
                                id="polyclinic_id"
                                required
                                class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-0 rounded-xl appearance-none">
                            <option value="">— Pilih Poliklinik —</option>
                            @foreach ($polyclinics as $polyclinic)
                                <option value="{{ $polyclinic->id }}" @selected(old('polyclinic_id', $practiceSchedule->polyclinic_id) == $polyclinic->id)>
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

                <!-- Day of Week -->
                <div>
                    <label for="day_of_week" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Hari Praktik <span class="text-red-500">*</span>
                    </label>
                    <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('day_of_week') border-red-300 ring-2 ring-red-100 @else border-gray-200  @enderror">
                        <select name="day_of_week"
                                id="day_of_week"
                                required
                                class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-0 rounded-xl appearance-none">
                            <option value="">— Pilih Hari —</option>
                            @foreach ($dayLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('day_of_week', $practiceSchedule->day_of_week) == $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    @error('day_of_week')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Time inputs row -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Start Time -->
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Jam Mulai <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('start_time') border-red-300 ring-2 ring-red-100 @else border-gray-200  @enderror">
                            <input type="time"
                                   name="start_time"
                                   id="start_time"
                                   value="{{ old('start_time', \Carbon\Carbon::parse($practiceSchedule->start_time)->format('H:i')) }}"
                                   required
                                   class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-0 rounded-xl">
                        </div>
                        @error('start_time')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- End Time -->
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Jam Selesai <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-xl border bg-white transition-all duration-200 focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 @error('end_time') border-red-300 ring-2 ring-red-100 @else border-gray-200  @enderror">
                            <input type="time"
                                   name="end_time"
                                   id="end_time"
                                   value="{{ old('end_time', \Carbon\Carbon::parse($practiceSchedule->end_time)->format('H:i')) }}"
                                   required
                                   class="w-full border-0 bg-transparent px-4 py-3 text-sm text-gray-900 focus:outline-none focus:ring-0 rounded-xl">
                        </div>
                        @error('end_time')
                            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
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
