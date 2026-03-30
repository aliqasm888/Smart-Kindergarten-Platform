<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // بيانات المستخدم
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8'],

            // بيانات المعلم
            'gender' => ['required', 'in:male,female'],
            'birth_date' => ['required', 'date'],
            'experience_years' => ['required', 'integer', 'min:0'],
            'profile_image' => ['nullable', 'file', 'image', 'max:2048'],
            'certificate_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'work_days' => ['required', 'array'],
            'work_days.*' => ['string', 'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'],
            'work_hours' => ['required', 'string', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'],
        ];
    }
}
