<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Polyclinic extends Model
{
    /** @use HasFactory<\Database\Factories\PolyclinicFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Get all doctors that primarily belong to this polyclinic.
     */
    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    /**
     * Get all practice schedules for this polyclinic.
     */
    public function practiceSchedules(): HasMany
    {
        return $this->hasMany(PracticeSchedule::class);
    }

    /**
     * Get all registrations for this polyclinic.
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Scope a query to only include polyclinics that have at least one
     * practice schedule on the given date's day of week.
     *
     * @param  Builder  $query
     * @param  Carbon  $date
     * @return Builder
     */
    public function scopeAvailableOn(Builder $query, Carbon $date): Builder
    {
        return $query->whereHas('practiceSchedules', function (Builder $q) use ($date) {
            $q->where('day_of_week', $date->dayOfWeek);
        })->distinct();
    }
}
