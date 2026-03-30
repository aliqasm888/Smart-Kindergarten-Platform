<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollmentRequest extends FormRequest
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
            "student_name"=> "required|string|min:3",
             "birth_date"=> "required|date",
            "phone"=> "required|digits:10",
            "class_name"=> "required|string|min:3",
            'profile_image' => ['nullable', 'file', 'image', 'max:2048'],
            'gender'=>'required|in:female,male',
            'level' => 'required|in:KG1,KG2,KG3'
        ];
    }
}
