<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreResumeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // User must be authenticated (handled by middleware)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resume' => [
                'required',
                'file',
                'mimes:pdf,docx,doc',
                'max:10240', // 10MB max file size (in KB)
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resume.required' => 'Please select a resume file to upload.',
            'resume.file' => 'The uploaded file is invalid.',
            'resume.mimes' => 'The resume must be a PDF or DOCX file.',
            'resume.max' => 'The resume file size must not exceed 10MB.',
        ];
    }
}
