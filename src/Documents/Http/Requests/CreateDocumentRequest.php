<?php

namespace Yoosuf\Document\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDocumentRequest extends FormRequest
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
            'document_type' => ['required', 'regex:/^[a-z][a-z0-9_]*$/'],
            'version' => ['nullable', 'regex:/^v?\\d+$/i'],
            'template_format' => ['nullable', 'in:word,pdf'],
            'output_format' => ['required', 'in:docx,pdf'],
            'data' => ['nullable', 'array'],
            'sales_order_id' => ['nullable', 'integer'],
            'async' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $hasData = $this->filled('data') || is_array($this->input('data'));
            $hasSalesOrder = $this->filled('sales_order_id');

            if ($hasData === $hasSalesOrder) {
                $validator->errors()->add('data', 'Either data or sales_order_id must be supplied.');
            }
        });
    }
}
