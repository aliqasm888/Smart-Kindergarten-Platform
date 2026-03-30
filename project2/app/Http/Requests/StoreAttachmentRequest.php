<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['image', 'file', 'video', 'link'])],
            'file' => [
                Rule::requiredIf(function () {
                    return $this->input('type') !== 'link';
                }),
                'file',
                'mimes:jpeg,png,gif,pdf,doc,docx,mp4,mov,avi',
                'max:5120' // 5MB
            ],
            'url' => [
                Rule::requiredIf(function () {
                    return $this->input('type') === 'link';
                }),
                'url',
                'max:255'
            ],
            'link_title' => [
                Rule::requiredIf(function () {
                    return $this->input('type') === 'link';
                }),
                'string',
                'max:100'
            ],
            'link_description' => ['nullable', 'string', 'max:255']
        ];
    }
}
