<x-layouts.app>
    <style>
        @media print {
            .no-print,
            nav,
            footer {
                display: none !important;
            }

            body {
                background: white !important;
                margin: 0;
                padding: 0;
            }

            main {
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }

            .ticket-content {
                width: 100%;
                margin: 0;
                padding: 2rem;
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>

    <div class="no-print mx-auto max-w-2xl mb-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-800">Tiket Antrian</h1>
            <button onclick="window.print()"
                    class="rounded-md bg-blue-500 px-4 py-2 text-sm font-medium text-white hover:bg-blue-600 transition-colors">
                Cetak Tiket
            </button>
        </div>
    </div>

    <div class="ticket-content mx-auto max-w-2xl rounded-lg border border-blue-200 bg-white p-6 shadow-sm">
        <!-- Header -->
        <div class="border-b border-blue-100 pb-4 mb-4 text-center">
            <h2 class="text-lg font-semibold text-blue-600">{{ config('app.name', 'Sistem Antrian Pasien') }}</h2>
            <p class="text-sm text-gray-500">Tiket Pendaftaran Pasien</p>
        </div>

        <!-- Queue Number (prominent display) -->
        <div class="my-6 flex justify-center">
            <div class="rounded-lg bg-blue-50 border-2 border-blue-300 px-10 py-6 text-center">
                <p class="text-sm font-medium text-blue-600 mb-1">Nomor Antrian</p>
                <p class="text-5xl font-bold text-blue-700">{{ $registration->queue_number }}</p>
            </div>
        </div>

        <!-- Patient and Registration Details -->
        <div class="mt-6 space-y-3">
            <div class="flex justify-between border-b border-gray-100 pb-2">
                <span class="text-sm font-medium text-gray-500">Nama Pasien</span>
                <span class="text-sm font-semibold text-gray-800">{{ $registration->patient->full_name }}</span>
            </div>

            <div class="flex justify-between border-b border-gray-100 pb-2">
                <span class="text-sm font-medium text-gray-500">No. Rekam Medis</span>
                <span class="text-sm font-semibold text-gray-800">{{ $registration->patient->medical_record_number }}</span>
            </div>

            <div class="flex justify-between border-b border-gray-100 pb-2">
                <span class="text-sm font-medium text-gray-500">Poliklinik</span>
                <span class="text-sm font-semibold text-gray-800">{{ $registration->polyclinic->name }}</span>
            </div>

            <div class="flex justify-between border-b border-gray-100 pb-2">
                <span class="text-sm font-medium text-gray-500">Dokter</span>
                <span class="text-sm font-semibold text-gray-800">{{ $registration->doctor->name }}</span>
            </div>

            <div class="flex justify-between">
                <span class="text-sm font-medium text-gray-500">Tanggal Pendaftaran</span>
                <span class="text-sm font-semibold text-gray-800">{{ $registration->registration_date->format('d F Y') }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 pt-4 border-t border-blue-100 text-center">
            <p class="text-xs text-gray-400">Harap tunjukkan tiket ini kepada petugas poliklinik.</p>
        </div>
    </div>

    <!-- Back to Home button (no-print) -->
    <div class="no-print mx-auto max-w-2xl mt-4 text-center">
        <a href="{{ route('home') }}"
           class="text-sm text-blue-500 hover:text-blue-600 hover:underline">
            ← Kembali ke Beranda
        </a>
    </div>
</x-layouts.app>
