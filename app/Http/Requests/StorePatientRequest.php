<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientRequest extends FormRequest
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
            'full_name'     => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'address'       => ['required', 'string'],
            'nik'           => ['required', 'digits:16', 'unique:patients,nik'],
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
            // full_name
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'full_name.string'   => 'Nama lengkap harus berupa teks.',
            'full_name.max'      => 'Nama lengkap tidak boleh lebih dari 255 karakter.',

            // date_of_birth
            'date_of_birth.required'         => 'Tanggal lahir wajib diisi.',
            'date_of_birth.date'             => 'Tanggal lahir harus berupa tanggal yang valid.',
            'date_of_birth.before_or_equal'  => 'Tanggal lahir tidak boleh melebihi tanggal hari ini.',

            // address
            'address.required' => 'Alamat wajib diisi.',
            'address.string'   => 'Alamat harus berupa teks.',

            // nik
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits'   => 'NIK harus terdiri dari tepat 16 digit angka.',
            'nik.unique'   => 'NIK sudah terdaftar dalam sistem.',
        ];
    }
}
