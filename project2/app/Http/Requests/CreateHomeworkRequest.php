<?php
// app/Http/Requests/CreateHomeworkRequest.php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateHomeworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ممكن تضيف تحقق الرول (معلم فقط) هنا إذا بدك
    }

    public function rules(): array
    {
        return [
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'due_date'     => 'required|date',
        ];
    }
}
