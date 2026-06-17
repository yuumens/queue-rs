<?php

namespace App\Livewire;

use App\Models\Patient;
use Livewire\Component;

class PatientSearch extends Component
{
    public string $searchMode = 'mr_number';

    public string $query = '';

    public array $results = [];

    public bool $hasSearched = false;

    public ?Patient $selectedPatient = null;

    /**
     * Search for patients based on the current search mode.
     */
    public function search(): void
    {
        $this->selectedPatient = null;
        $this->hasSearched = true;

        if (trim($this->query) === '') {
            $this->results = [];
            return;
        }

        if ($this->searchMode === 'mr_number') {
            $patient = Patient::where('medical_record_number', $this->query)->first();
            $this->results = $patient ? [$patient->toArray()] : [];
        } else {
            $this->results = Patient::searchByName($this->query)->get()->toArray();
        }
    }

    /**
     * Select a patient from the search results.
     */
    public function selectPatient(int $id): void
    {
        $this->selectedPatient = Patient::findOrFail($id);
    }

    public function render()
    {
        return view('livewire.patient-search');
    }
}
