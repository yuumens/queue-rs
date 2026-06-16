<div>
    @if ($noClinicsAvailable)
        <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-blue-700">
            <p>Tidak ada poliklinik yang tersedia hari ini.</p>
        </div>
    @else
        {{-- Polyclinic grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            @foreach ($polyclinics as $polyclinic)
                <button
                    wire:click="$set('selectedPolyclinicId', {{ $polyclinic->id }})"
                    class="p-4 rounded-lg border-2 text-left transition
                        {{ $selectedPolyclinicId == $polyclinic->id ? 'ring-2 ring-blue-500 border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300' }}"
                >
                    <span class="font-semibold">{{ $polyclinic->name }}</span>
                    <span class="text-sm text-gray-500 block">Kode: {{ $polyclinic->code }}</span>
                </button>
            @endforeach
        </div>

        {{-- Doctor list --}}
        @if ($selectedPolyclinicId)
            <div class="mt-4">
                <h3 class="text-lg font-semibold mb-3">Pilih Dokter</h3>

                @if (empty($doctors))
                    <div class="rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-yellow-700">
                        <p>Tidak ada dokter yang tersedia untuk poliklinik ini hari ini.</p>
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach ($doctors as $schedule)
                            <button
                                wire:click="$set('selectedDoctorId', {{ $schedule['doctor_id'] }})"
                                class="w-full p-3 rounded-lg border-2 text-left transition
                                    {{ $selectedDoctorId == $schedule['doctor_id'] ? 'ring-2 ring-blue-500 border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300' }}"
                            >
                                <span class="font-medium">{{ $schedule['doctor']['name'] }}</span>
                                <span class="text-sm text-gray-500 block">
                                    {{ \Carbon\Carbon::parse($schedule['start_time'])->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule['end_time'])->format('H:i') }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        {{-- Proceed button --}}
        <div class="mt-6">
            <button
                wire:click="confirmSelection"
                @if (!$selectedPolyclinicId || !$selectedDoctorId) disabled @endif
                class="w-full py-3 px-6 rounded-lg text-white font-semibold transition
                    {{ $selectedPolyclinicId && $selectedDoctorId ? 'bg-blue-500 hover:bg-blue-600 cursor-pointer' : 'bg-gray-300 cursor-not-allowed' }}"
            >
                Lanjutkan
            </button>
        </div>
    @endif
</div>
