<x-layouts.app>
    <div class="mx-auto max-w-3xl">
        <h1 class="mb-6 text-2xl font-semibold text-gray-800">Pilih Poliklinik & Dokter</h1>

        <div x-data x-on:queue-selection-confirmed.window="
            document.getElementById('selected-polyclinic-id').value = $event.detail.polyclinicId;
            document.getElementById('selected-doctor-id').value = $event.detail.doctorId;
            document.getElementById('queue-selection-form').submit();
        ">
            <livewire:polyclinic-doctor-selector />
        </div>

        <!-- Hidden form that submits when Livewire dispatches queue-selection-confirmed -->
        <form id="queue-selection-form" method="POST" action="{{ route('queue.generate') }}" class="hidden">
            @csrf
            <input type="hidden" name="polyclinic_id" id="selected-polyclinic-id">
            <input type="hidden" name="doctor_id" id="selected-doctor-id">
        </form>
    </div>
</x-layouts.app>
