<?php

namespace Yoosuf\Document\Documents\DTO;

class TemplateDescriptor
{
    public function __construct(
        public readonly string $documentType,
        public readonly string $version,
        public readonly string $templateFormat,
        public readonly string $extension,
        public readonly string $filename,
        public readonly string $absolutePath,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function supportedOutputFormats(): array
    {
        if ($this->templateFormat === 'pdf') {
            return ['pdf'];
        }

        return ['docx', 'pdf'];
    }
}
