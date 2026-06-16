<x-layouts.app>
    <x-slot:title>Kelola Jadwal Praktik</x-slot:title>

    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-gray-900">Daftar Jadwal Praktik</h1>
            <a href="{{ route('admin.practice-schedules.create') }}"
               class="inline-flex items-center rounded-md bg-blue-500 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Jadwal
            </a>
        </div>

        <!-- Success Message -->
        @if (session('success'))
            <div class="rounded-md bg-green-50 border border-green-200 p-4">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        <!-- Table -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-blue-700">
                            Hari
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-blue-700">
                            Dokter
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-blue-700">
                            Poliklinik
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-blue-700">
                            Jam Praktik
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-blue-700">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($schedules as $schedule)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                <span class="inline-flex items-center rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">
                                    {{ $dayLabels[$schedule->day_of_week] ?? '-' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                {{ $schedule->doctor->name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ $schedule->polyclinic->name }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <a href="{{ route('admin.practice-schedules.edit', $schedule) }}"
                                   class="inline-flex items-center rounded px-3 py-1 text-sm font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 transition-colors">
                                    Edit
                                </a>
                                <form action="{{ route('admin.practice-schedules.destroy', $schedule) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('Yakin ingin menghapus jadwal praktik ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center rounded px-3 py-1 text-sm font-medium text-red-600 hover:text-red-800 hover:bg-red-50 transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                Belum ada data jadwal praktik.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($schedules->hasPages())
            <div class="mt-4">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
