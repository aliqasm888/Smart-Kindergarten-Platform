<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', Rule::in(['image', 'file', 'video', 'link'])],
            'file' => [
                'sometimes',
                'file',
                'mimes:jpeg,png,gif,pdf,doc,docx,mp4,mov,avi',
                'max:5120'
            ],
            'url' => ['sometimes', 'url', 'max:255'],
            'link_title' => ['sometimes', 'string', 'max:100'],
            'link_description' => ['nullable', 'string', 'max:255']
        ];
    }
}
