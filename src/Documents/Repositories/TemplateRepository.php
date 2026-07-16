<?php

namespace Yoosuf\Document\Documents\Repositories;

use Yoosuf\Document\Documents\DTO\TemplateDescriptor;
use Yoosuf\Document\Documents\Exceptions\DocumentException;
use Yoosuf\Document\Documents\Support\ErrorCodes;

class TemplateRepository
{
    /**
     * @return array<int, TemplateDescriptor>
     */
    public function listDescriptors(): array
    {
        $directory = base_path(config('document.templates_dir'));

        if (!is_dir($directory)) {
            return [];
        }

        $files = scandir($directory) ?: [];
        $descriptors = [];

        foreach ($files as $filename) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }

            $absolutePath = $directory.DIRECTORY_SEPARATOR.$filename;
            if (!is_file($absolutePath)) {
                continue;
            }

            $descriptor = $this->parseFilename($filename, $absolutePath);
            if ($descriptor !== null) {
                $descriptors[] = $descriptor;
            }
        }

        return $descriptors;
    }

    public function resolve(string $documentType, ?string $version, ?string $templateFormat): TemplateDescriptor
    {
        $normalizedType = $this->normalizeDocumentType($documentType);
        $descriptors = array_values(array_filter(
            $this->listDescriptors(),
            static fn (TemplateDescriptor $descriptor): bool => $descriptor->documentType === $normalizedType,
        ));

        if ($descriptors === []) {
            throw new DocumentException(ErrorCodes::TEMPLATE_NOT_FOUND, 'No templates found for the requested document type.', 404);
        }

        $resolvedVersion = $version !== null ? $this->normalizeVersion($version) : $this->latestVersion($descriptors);
        $descriptors = array_values(array_filter(
            $descriptors,
            static fn (TemplateDescriptor $descriptor): bool => $descriptor->version === $resolvedVersion,
        ));

        if ($descriptors === []) {
            throw new DocumentException(ErrorCodes::TEMPLATE_NOT_FOUND, 'No templates found for the requested version.', 404);
        }

        if ($templateFormat !== null) {
            $descriptors = array_values(array_filter(
                $descriptors,
                static fn (TemplateDescriptor $descriptor): bool => $descriptor->templateFormat === strtolower($templateFormat),
            ));

            if ($descriptors === []) {
                throw new DocumentException(ErrorCodes::TEMPLATE_NOT_FOUND, 'No templates found for the requested template format.', 404);
            }
        } else {
            $formats = array_values(array_unique(array_map(
                static fn (TemplateDescriptor $descriptor): string => $descriptor->templateFormat,
                $descriptors,
            )));

            if (count($formats) > 1) {
                throw new DocumentException(
                    ErrorCodes::AMBIGUOUS_TEMPLATE_SELECTION,
                    'Multiple template formats are available. Specify template_format explicitly.',
                    400,
                );
            }
        }

        usort($descriptors, static function (TemplateDescriptor $a, TemplateDescriptor $b): int {
            $priority = ['docx' => 1, 'doc' => 2, 'pdf' => 3];
            return ($priority[$a->extension] ?? 99) <=> ($priority[$b->extension] ?? 99);
        });

        return $descriptors[0];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listDocumentTypes(): array
    {
        $grouped = [];

        foreach ($this->listDescriptors() as $descriptor) {
            $key = $descriptor->documentType.'|'.$descriptor->version.'|'.$descriptor->templateFormat;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'document_type' => $descriptor->documentType,
                    'version' => $descriptor->version,
                    'template_format' => $descriptor->templateFormat,
                    'extensions' => [],
                    'supported_output_formats' => $descriptor->supportedOutputFormats(),
                ];
            }

            $grouped[$key]['extensions'][] = $descriptor->extension;
        }

        return array_values($grouped);
    }

    private function normalizeDocumentType(string $documentType): string
    {
        $normalized = strtolower(trim($documentType));

        if (!preg_match('/^[a-z][a-z0-9_]*$/', $normalized)) {
            throw new DocumentException(
                ErrorCodes::VALIDATION_ERROR,
                'document_type must be lowercase snake_case.',
                422,
                [['field' => 'document_type', 'message' => 'document_type must be lowercase snake_case.']],
            );
        }

        return $normalized;
    }

    private function normalizeVersion(string $version): string
    {
        if (preg_match('/^v?(\d+)$/i', trim($version), $matches) !== 1) {
            throw new DocumentException(
                ErrorCodes::VALIDATION_ERROR,
                'version must be numeric with optional v prefix.',
                422,
                [['field' => 'version', 'message' => 'version must be numeric with optional v prefix.']],
            );
        }

        return 'v'.(int) $matches[1];
    }

    /**
     * @param array<int, TemplateDescriptor> $descriptors
     */
    private function latestVersion(array $descriptors): string
    {
        $max = 0;
        foreach ($descriptors as $descriptor) {
            $number = (int) ltrim($descriptor->version, 'v');
            $max = max($max, $number);
        }

        return 'v'.$max;
    }

    private function parseFilename(string $filename, string $absolutePath): ?TemplateDescriptor
    {
        if (preg_match('/^([a-z][a-z0-9_]*)_v(\d+)\.(docx|doc|pdf)$/', $filename, $matches) !== 1) {
            return null;
        }

        $extension = strtolower($matches[3]);

        return new TemplateDescriptor(
            documentType: $matches[1],
            version: 'v'.(int) $matches[2],
            templateFormat: $extension === 'pdf' ? 'pdf' : 'word',
            extension: $extension,
            filename: $filename,
            absolutePath: $absolutePath,
        );
    }
}
