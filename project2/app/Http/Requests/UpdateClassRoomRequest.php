<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassRoomRequest extends FormRequest
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
            "user_name" => ['sometimes', 'string', 'exists:users,name'],
            "max_students" => ['sometimes', 'integer', 'min:1'],
            'class_name' => ['sometimes', 'string', 'max:255'],
            'level' => ['sometimes', 'string', 'in:KG1,KG2,KG3']
        ];
    }
}
