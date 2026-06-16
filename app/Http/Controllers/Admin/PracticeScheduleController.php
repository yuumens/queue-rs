<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePracticeScheduleRequest;
use App\Models\Doctor;
use App\Models\Polyclinic;
use App\Models\PracticeSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PracticeScheduleController extends Controller
{
    /**
     * Day of week labels in Indonesian.
     */
    private const DAY_LABELS = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    /**
     * Display a list of practice schedules, sorted by day of week.
     */
    public function index(): View
    {
        $schedules = PracticeSchedule::with(['doctor', 'polyclinic'])
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->paginate(15);

        $dayLabels = self::DAY_LABELS;

        return view('admin.practice-schedules.index', compact('schedules', 'dayLabels'));
    }

    /**
     * Show the form for creating a new practice schedule.
     */
    public function create(): View
    {
        $doctors = Doctor::orderBy('name')->get();
        $polyclinics = Polyclinic::orderBy('name')->get();
        $dayLabels = self::DAY_LABELS;

        return view('admin.practice-schedules.create', compact('doctors', 'polyclinics', 'dayLabels'));
    }

    /**
     * Store a newly created practice schedule.
     */
    public function store(StorePracticeScheduleRequest $request): RedirectResponse
    {
        PracticeSchedule::create($request->validated());

        return redirect()->route('admin.practice-schedules.index')
            ->with('success', 'Jadwal praktik berhasil ditambahkan.');
    }

    /**
     * Display the specified practice schedule (redirects to index).
     */
    public function show(PracticeSchedule $practiceSchedule): RedirectResponse
    {
        return redirect()->route('admin.practice-schedules.index');
    }

    /**
     * Show the form for editing a practice schedule.
     */
    public function edit(PracticeSchedule $practiceSchedule): View
    {
        $doctors = Doctor::orderBy('name')->get();
        $polyclinics = Polyclinic::orderBy('name')->get();
        $dayLabels = self::DAY_LABELS;

        return view('admin.practice-schedules.edit', compact('practiceSchedule', 'doctors', 'polyclinics', 'dayLabels'));
    }

    /**
     * Update the specified practice schedule.
     */
    public function update(Request $request, PracticeSchedule $practiceSchedule): RedirectResponse
    {
        $validated = $request->validate([
            'doctor_id'     => ['required', 'integer', 'exists:doctors,id'],
            'polyclinic_id' => ['required', 'integer', 'exists:polyclinics,id'],
            'day_of_week'   => [
                'required',
                'integer',
                'between:0,6',
                Rule::unique('practice_schedules', 'day_of_week')
                    ->where('doctor_id', $request->input('doctor_id'))
                    ->where('polyclinic_id', $request->input('polyclinic_id'))
                    ->ignore($practiceSchedule->id),
            ],
            'start_time'    => ['required', 'date_format:H:i'],
            'end_time'      => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $practiceSchedule->update($validated);

        return redirect()->route('admin.practice-schedules.index')
            ->with('success', 'Jadwal praktik berhasil diperbarui.');
    }

    /**
     * Remove the specified practice schedule.
     */
    public function destroy(PracticeSchedule $practiceSchedule): RedirectResponse
    {
        $practiceSchedule->delete();

        return redirect()->route('admin.practice-schedules.index')
            ->with('success', 'Jadwal praktik berhasil dihapus.');
    }
}
