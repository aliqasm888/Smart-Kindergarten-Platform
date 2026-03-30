<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_name' => ['sometimes', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'date'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'class_name' => ['sometimes', 'string', 'max:255'],
            'profile_image' => ['sometimes', 'file', 'image', 'max:2048'],
            'level' => ['sometimes', 'string', 'in:KG1,KG2,KG3']
        ];
    }
}
