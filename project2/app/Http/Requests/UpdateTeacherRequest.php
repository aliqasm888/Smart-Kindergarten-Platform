<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // بيانات المستخدم
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->route('teacher')), // لو الباراميتر اسمه teacher
            ],
            'phone' => ['sometimes', 'string', 'max:20'],
            'password' => ['sometimes', 'string', 'min:8'],

            // بيانات المعلم
            'gender' => ['sometimes', 'in:male,female'],
            'birth_date' => ['sometimes', 'date'],
            'experience_years' => ['sometimes', 'integer', 'min:0'],
            'profile_image' => ['sometimes', 'file', 'image', 'max:2048'],
            'certificate_file' => ['sometimes', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'work_days' => ['sometimes', 'array'],
            'work_days.*' => ['string', 'in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'],
            'work_hours' => ['sometimes', 'string', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'], // مثل "09:00-17:00"
        ];
    }
}
