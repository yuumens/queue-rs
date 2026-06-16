<x-layouts.app>
    <div class="flex min-h-[60vh] flex-col items-center justify-center">
        {{-- App Logo / Name --}}
        <div class="mb-10 text-center">
            <div class="mb-4 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-blue-500" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">
                {{ config('app.name', 'Sistem Antrian Pasien') }}
            </h1>
            <p class="mt-2 text-gray-500">Silakan pilih jenis pendaftaran</p>
        </div>

        {{-- Navigation Buttons --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:gap-6">
            <a href="{{ route('patient.register') }}"
               class="inline-flex items-center justify-center rounded-lg bg-blue-500 px-10 py-4 text-lg font-semibold text-white shadow-md transition-colors hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-6 w-6" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                Pasien Baru
            </a>

            <a href="{{ route('patient.verify') }}"
               class="inline-flex items-center justify-center rounded-lg bg-blue-500 px-10 py-4 text-lg font-semibold text-white shadow-md transition-colors hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-2 h-6 w-6" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Pasien Lama
            </a>
        </div>
    </div>
</x-layouts.app>
