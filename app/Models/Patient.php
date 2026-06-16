<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'medical_record_number',
        'nik',
        'full_name',
        'date_of_birth',
        'address',
    ];

    /**
     * Get all registrations for the patient.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Scope a query to search patients by full name (case-insensitive, partial match).
     *
     * @param  Builder  $query
     * @param  string  $name
     * @return Builder
     */
    public function scopeSearchByName(Builder $query, string $name): Builder
    {
        return $query->where('full_name', 'LIKE', '%' . $name . '%');
    }
}
