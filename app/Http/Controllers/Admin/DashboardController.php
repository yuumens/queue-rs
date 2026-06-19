<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Polyclinic;
use App\Models\PracticeSchedule;
use App\Models\Registration;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'polyclinics' => Polyclinic::count(),
            'doctors' => Doctor::count(),
            'schedules' => PracticeSchedule::count(),
            'patients' => Patient::count(),
            'registrations_today' => Registration::whereDate('registration_date', today())->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
