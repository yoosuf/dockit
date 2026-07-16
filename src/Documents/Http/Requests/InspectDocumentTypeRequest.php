<?php

namespace Yoosuf\Document\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InspectDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'version' => ['nullable', 'regex:/^v?\\d+$/i'],
            'template_format' => ['nullable', 'in:word,pdf'],
        ];
    }
}
