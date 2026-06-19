<x-layouts.app>
    <x-slot:title>Admin Dashboard</x-slot:title>

    <div class="space-y-8">
        <!-- Page Header -->
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Dashboard Admin</h1>
            <p class="mt-1 text-sm text-gray-500">Selamat datang, {{ auth()->user()->name }}. Kelola data sistem antrian pasien dari sini.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['polyclinics'] }}</p>
                        <p class="text-xs text-gray-500">Poliklinik</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['doctors'] }}</p>
                        <p class="text-xs text-gray-500">Dokter</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['schedules'] }}</p>
                        <p class="text-xs text-gray-500">Jadwal Praktik</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['patients'] }}</p>
                        <p class="text-xs text-gray-500">Total Pasien</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ $stats['registrations_today'] }}</p>
                        <p class="text-xs text-gray-500">Antrian Hari Ini</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Section -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Kelola Data</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                <!-- Polyclinics -->
                <div class="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-200 hover:border-blue-200 hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-50 text-blue-600 mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900">Poliklinik</h3>
                            <p class="mt-1 text-sm text-gray-500">Kelola data poliklinik yang tersedia di fasilitas kesehatan.</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('admin.polyclinics.index') }}"
                           class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-600 transition-all duration-200 hover:bg-blue-500 hover:text-white hover:border-blue-500">
                            Lihat Semua
                        </a>
                        <a href="{{ route('admin.polyclinics.create') }}"
                           class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-600 transition-all duration-200 hover:bg-gray-50">
                            + Tambah
                        </a>
                    </div>
                </div>

                <!-- Doctors -->
                <div class="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-200 hover:border-green-200 hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50 text-green-600 mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900">Dokter</h3>
                            <p class="mt-1 text-sm text-gray-500">Kelola data dokter dan afiliasinya dengan poliklinik.</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('admin.doctors.index') }}"
                           class="inline-flex items-center rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-xs font-semibold text-green-600 transition-all duration-200 hover:bg-green-500 hover:text-white hover:border-green-500">
                            Lihat Semua
                        </a>
                        <a href="{{ route('admin.doctors.create') }}"
                           class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-600 transition-all duration-200 hover:bg-gray-50">
                            + Tambah
                        </a>
                    </div>
                </div>

                <!-- Practice Schedules -->
                <div class="group rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-200 hover:border-purple-200 hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-50 text-purple-600 mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-semibold text-gray-900">Jadwal Praktik</h3>
                            <p class="mt-1 text-sm text-gray-500">Atur jadwal praktik dokter per poliklinik per hari.</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('admin.practice-schedules.index') }}"
                           class="inline-flex items-center rounded-lg border border-purple-200 bg-purple-50 px-4 py-2 text-xs font-semibold text-purple-600 transition-all duration-200 hover:bg-purple-500 hover:text-white hover:border-purple-500">
                            Lihat Semua
                        </a>
                        <a href="{{ route('admin.practice-schedules.create') }}"
                           class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-600 transition-all duration-200 hover:bg-gray-50">
                            + Tambah
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-layouts.app>
