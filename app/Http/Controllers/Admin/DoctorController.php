<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Polyclinic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoctorController extends Controller
{
    /**
     * Display a paginated list of doctors with their polyclinic.
     */
    public function index(): View
    {
        $doctors = Doctor::with('polyclinic')->orderBy('name')->paginate(10);

        return view('admin.doctors.index', compact('doctors'));
    }

    /**
     * Show the form for creating a new doctor.
     */
    public function create(): View
    {
        $polyclinics = Polyclinic::orderBy('name')->get();

        return view('admin.doctors.create', compact('polyclinics'));
    }

    /**
     * Store a newly created doctor in the database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'polyclinic_id' => ['required', 'exists:polyclinics,id'],
            'photo_url' => ['nullable', 'string', 'max:2048'],
        ]);

        if (!empty($validated['photo_url'])) {
            $validated['photo_url'] = $this->extractImgSrc($validated['photo_url']);
        }

        Doctor::create($validated);

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Dokter berhasil ditambahkan.');
    }

    /**
     * Display the specified doctor.
     */
    public function show(Doctor $doctor): RedirectResponse
    {
        return redirect()->route('admin.doctors.index');
    }

    /**
     * Show the form for editing the specified doctor.
     */
    public function edit(Doctor $doctor): View
    {
        $polyclinics = Polyclinic::orderBy('name')->get();

        return view('admin.doctors.edit', compact('doctor', 'polyclinics'));
    }

    /**
     * Update the specified doctor in the database.
     */
    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'polyclinic_id' => ['required', 'exists:polyclinics,id'],
            'photo_url' => ['nullable', 'string', 'max:2048'],
        ]);

        if (!empty($validated['photo_url'])) {
            $validated['photo_url'] = $this->extractImgSrc($validated['photo_url']);
        }

        $doctor->update($validated);

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Dokter berhasil diperbarui.');
    }

    /**
     * Remove the specified doctor from the database.
     */
    public function destroy(Doctor $doctor): RedirectResponse
    {
        $doctor->delete();

        return redirect()->route('admin.doctors.index')
            ->with('success', 'Dokter berhasil dihapus.');
    }

    /**
     * Extract the src attribute from an <img> tag.
     * If the input is already a plain URL, return it as-is.
     */
    private function extractImgSrc(string $input): string
    {
        $input = trim($input);

        // If it looks like it contains an <img tag, extract the src
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $input, $matches)) {
            return $matches[1];
        }

        // Otherwise return as-is (might be a plain URL)
        return $input;
    }
}
