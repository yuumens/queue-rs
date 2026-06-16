<?php

namespace App\Livewire;

use App\Models\Polyclinic;
use App\Models\PracticeSchedule;
use Illuminate\Support\Collection;
use Livewire\Component;

class PolyclinicDoctorSelector extends Component
{
    public string $date;

    public Collection $polyclinics;

    public ?int $selectedPolyclinicId = null;

    public array $doctors = [];

    public ?int $selectedDoctorId = null;

    public bool $noClinicsAvailable = false;

    /**
     * Mount the component and load today's available polyclinics.
     */
    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->polyclinics = Polyclinic::availableOn(now())->get();
        $this->noClinicsAvailable = $this->polyclinics->isEmpty();
    }

    /**
     * Called automatically when selectedPolyclinicId is updated.
     * Reloads the doctors list for the selected polyclinic on today's day of week.
     */
    public function updatedSelectedPolyclinicId(): void
    {
        $this->selectedDoctorId = null;

        if ($this->selectedPolyclinicId === null) {
            $this->doctors = [];
            return;
        }

        $this->doctors = PracticeSchedule::with('doctor')
            ->where('polyclinic_id', $this->selectedPolyclinicId)
            ->where('day_of_week', now()->dayOfWeek)
            ->get()
            ->toArray();
    }

    /**
     * Confirm the polyclinic and doctor selection.
     * Dispatches a browser event with the selected IDs.
     */
    public function confirmSelection(): void
    {
        $this->dispatch('queue-selection-confirmed', polyclinicId: $this->selectedPolyclinicId, doctorId: $this->selectedDoctorId);
    }

    public function render()
    {
        return view('livewire.polyclinic-doctor-selector');
    }
}
