<?php

namespace Yoosuf\Document\Documents\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'document_id' => $this->document_id,
            'document_type' => $this->document_type,
            'version' => $this->version,
            'template_format' => $this->template_format,
            'output_format' => $this->output_format,
            'filename' => $this->filename,
            'content_url' => url('/api/v1/documents/'.$this->document_id.'/content'),
            'size_bytes' => $this->size_bytes,
            'status' => $this->status,
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
