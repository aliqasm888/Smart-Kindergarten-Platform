<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassScheduleRequest extends FormRequest
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
                'classroom_id' => 'required|exists:class_rooms,id',
                'schedules' => 'required|array|size:5', // من الأحد للخميس
                'schedules.*.day' => 'required|in:Sunday,Monday,Tuesday,Wednesday,Thursday',
                'schedules.*.period_1' => 'required|string|max:255',
                'schedules.*.period_2' => 'required|string|max:255',
                'schedules.*.period_3' => 'required|string|max:255',
            ];

    }
}
