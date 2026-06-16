<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePracticeScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id'     => ['required', 'integer', 'exists:doctors,id'],
            'polyclinic_id' => ['required', 'integer', 'exists:polyclinics,id'],

            // Composite uniqueness: one doctor may only have one schedule
            // per polyclinic per day of the week.
            'day_of_week'   => [
                'required',
                'integer',
                'between:0,6',
                Rule::unique('practice_schedules', 'day_of_week')
                    ->where('doctor_id', $this->input('doctor_id'))
                    ->where('polyclinic_id', $this->input('polyclinic_id')),
            ],

            'start_time'    => ['required', 'date_format:H:i'],
            'end_time'      => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    /**
     * Get custom error messages (in Indonesian) for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // doctor_id
            'doctor_id.required' => 'Dokter wajib dipilih.',
            'doctor_id.integer'  => 'ID dokter harus berupa angka.',
            'doctor_id.exists'   => 'Dokter yang dipilih tidak ditemukan dalam sistem.',

            // polyclinic_id
            'polyclinic_id.required' => 'Poliklinik wajib dipilih.',
            'polyclinic_id.integer'  => 'ID poliklinik harus berupa angka.',
            'polyclinic_id.exists'   => 'Poliklinik yang dipilih tidak ditemukan dalam sistem.',

            // day_of_week
            'day_of_week.required' => 'Hari praktik wajib dipilih.',
            'day_of_week.integer'  => 'Hari praktik harus berupa angka.',
            'day_of_week.between'  => 'Hari praktik harus bernilai antara 0 (Minggu) hingga 6 (Sabtu).',
            'day_of_week.unique'   => 'Dokter ini sudah memiliki jadwal praktik pada hari dan poliklinik yang sama.',

            // start_time
            'start_time.required'     => 'Jam mulai praktik wajib diisi.',
            'start_time.date_format'  => 'Jam mulai harus menggunakan format HH:MM (contoh: 08:00).',

            // end_time
            'end_time.required'     => 'Jam selesai praktik wajib diisi.',
            'end_time.date_format'  => 'Jam selesai harus menggunakan format HH:MM (contoh: 17:00).',
            'end_time.after'        => 'Jam selesai harus lebih akhir dari jam mulai.',
        ];
    }
}
