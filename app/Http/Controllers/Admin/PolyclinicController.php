<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Polyclinic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolyclinicController extends Controller
{
    /**
     * Display a paginated list of polyclinics.
     */
    public function index(): View
    {
        $polyclinics = Polyclinic::orderBy('name')->paginate(10);

        return view('admin.polyclinics.index', compact('polyclinics'));
    }

    /**
     * Show the form for creating a new polyclinic.
     */
    public function create(): View
    {
        return view('admin.polyclinics.create');
    }

    /**
     * Store a newly created polyclinic in the database.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:5', 'alpha', 'unique:polyclinics,code'],
        ]);

        Polyclinic::create($validated);

        return redirect()->route('admin.polyclinics.index')
            ->with('success', 'Poliklinik berhasil ditambahkan.');
    }

    /**
     * Display the specified polyclinic.
     */
    public function show(Polyclinic $polyclinic): RedirectResponse
    {
        return redirect()->route('admin.polyclinics.index');
    }

    /**
     * Show the form for editing the specified polyclinic.
     */
    public function edit(Polyclinic $polyclinic): View
    {
        return view('admin.polyclinics.edit', compact('polyclinic'));
    }

    /**
     * Update the specified polyclinic in the database.
     */
    public function update(Request $request, Polyclinic $polyclinic): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:5', 'alpha', 'unique:polyclinics,code,' . $polyclinic->id],
        ]);

        $polyclinic->update($validated);

        return redirect()->route('admin.polyclinics.index')
            ->with('success', 'Poliklinik berhasil diperbarui.');
    }

    /**
     * Remove the specified polyclinic from the database.
     */
    public function destroy(Polyclinic $polyclinic): RedirectResponse
    {
        $polyclinic->delete();

        return redirect()->route('admin.polyclinics.index')
            ->with('success', 'Poliklinik berhasil dihapus.');
    }
}
