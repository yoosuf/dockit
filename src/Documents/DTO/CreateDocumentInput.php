<?php

namespace Yoosuf\Document\Documents\DTO;

class CreateDocumentInput
{
    /**
     * @param array<string, mixed>|null $data
     */
    public function __construct(
        public readonly string $documentType,
        public readonly ?string $version,
        public readonly ?string $templateFormat,
        public readonly string $outputFormat,
        public readonly ?array $data,
        public readonly ?int $salesOrderId,
    ) {
    }
}
