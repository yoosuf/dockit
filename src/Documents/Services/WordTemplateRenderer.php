<?php

namespace Yoosuf\Document\Documents\Services;

use Yoosuf\Document\Documents\Exceptions\DocumentException;
use Yoosuf\Document\Documents\Support\ErrorCodes;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipArchive;

class WordTemplateRenderer
{
    /**
     * @param array<string, mixed> $payload
     */
    public function render(string $templatePath, array $payload, string $outputPath): void
    {
        try {
            $processor = new TemplateProcessor($templatePath);

            foreach ($payload as $key => $value) {
                if (is_scalar($value) || $value === null) {
                    $processor->setValue((string) $key, (string) $value);
                }
            }

            $processor->saveAs($outputPath);
        } catch (\Throwable $exception) {
            throw new DocumentException(
                ErrorCodes::TEMPLATE_RENDER_ERROR,
                'Failed to render Word template.',
                422,
                previous: $exception,
            );
        }
    }

    /**
     * @return array{placeholders: array<int, string>, warnings: array<int, string>, errors: array<int, string>}
     */
    public function inspectTemplate(string $templatePath): array
    {
        $warnings = [];
        $errors = [];

        $zip = new ZipArchive();
        if ($zip->open($templatePath) !== true) {
            return [
                'placeholders' => [],
                'warnings' => [],
                'errors' => ['Template is not a valid DOCX archive.'],
            ];
        }

        $documentXml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        if ($documentXml === '') {
            return [
                'placeholders' => [],
                'warnings' => [],
                'errors' => ['word/document.xml not found in template archive.'],
            ];
        }

        preg_match_all('/\$\{([^}]+)\}/', $documentXml, $matches);
        $placeholders = array_values(array_unique($matches[1] ?? []));

        if ($placeholders === []) {
            $warnings[] = 'No placeholders were detected in the Word template.';
        }

        if (preg_match('/\$\{[^}]*$/', $documentXml) === 1) {
            $warnings[] = 'Detected an unterminated placeholder token.';
        }

        $invalid = array_values(array_filter(
            $placeholders,
            static fn (string $field): bool => preg_match('/^[A-Za-z0-9_.-]+$/', $field) !== 1,
        ));
        if ($invalid !== []) {
            $warnings[] = 'Some placeholders use unusual characters: '.implode(', ', $invalid);
        }

        return [
            'placeholders' => $placeholders,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function extractPlaceholders(string $templatePath): array
    {
        return $this->inspectTemplate($templatePath)['placeholders'];
    }
}
