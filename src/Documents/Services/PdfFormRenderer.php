<?php

namespace Yoosuf\Document\Documents\Services;

use Yoosuf\Document\Documents\Exceptions\DocumentException;
use Yoosuf\Document\Documents\Support\ErrorCodes;
use mikehaertl\pdftk\Pdf;
use Symfony\Component\Process\Process;

class PdfFormRenderer
{
    /**
     * @param array<string, mixed> $payload
     */
    public function render(string $templatePath, array $payload, string $outputPath): void
    {
        $flatPayload = [];

        foreach ($payload as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $flatPayload[(string) $key] = (string) $value;
            }
        }

        $pdf = new Pdf($templatePath, [
            'command' => config('document.pdf_tool_binary', 'pdftk'),
        ]);

        if (!$pdf->fillForm($flatPayload)->needAppearances()->saveAs($outputPath)) {
            throw new DocumentException(
                ErrorCodes::TEMPLATE_RENDER_ERROR,
                'Failed to render PDF form template.',
                422,
            );
        }
    }

    /**
     * @return array{placeholders: array<int, string>, fields: array<int, array<string, mixed>>, warnings: array<int, string>, errors: array<int, string>}
     */
    public function inspectFields(string $templatePath): array
    {
        $warnings = [];
        $errors = [];

        if (!is_file($templatePath)) {
            return [
                'placeholders' => [],
                'fields' => [],
                'warnings' => [],
                'errors' => ['Template file does not exist.'],
            ];
        }

        if (!$this->isPdfToolkitAvailable()) {
            return [
                'placeholders' => [],
                'fields' => [],
                'warnings' => [],
                'errors' => ['pdftk binary is not available; PDF field inspection is unavailable.'],
            ];
        }

        $pdf = new Pdf($templatePath, [
            'command' => config('document.pdf_tool_binary', 'pdftk'),
        ]);

        $output = null;
        if (method_exists($pdf, 'getDataFieldsUtf8')) {
            $output = $pdf->getDataFieldsUtf8();
        } elseif (method_exists($pdf, 'getDataFields')) {
            $output = $pdf->getDataFields();
        }

        if (!is_string($output) || $output === '') {
            return [
                'placeholders' => [],
                'fields' => [],
                'warnings' => ['No field metadata could be extracted from PDF template.'],
                'errors' => [],
            ];
        }

        $records = preg_split('/\R\s*\R/', trim($output)) ?: [];
        $fields = [];
        $placeholders = [];

        foreach ($records as $record) {
            $name = null;
            $type = null;
            $flags = null;

            foreach (preg_split('/\R/', $record) ?: [] as $line) {
                if (preg_match('/^FieldName: (.+)$/', $line, $match) === 1) {
                    $name = trim($match[1]);
                }
                if (preg_match('/^FieldType: (.+)$/', $line, $match) === 1) {
                    $type = trim($match[1]);
                }
                if (preg_match('/^FieldFlags: (.+)$/', $line, $match) === 1) {
                    $flags = trim($match[1]);
                }
            }

            if ($name === null || $name === '') {
                continue;
            }

            $flagValue = $flags !== null && is_numeric($flags) ? (int) $flags : 0;
            $isReadOnly = ($flagValue & 1) === 1;
            $isRequired = ($flagValue & 2) === 2;

            $fields[] = [
                'name' => $name,
                'type' => $type ?? 'unknown',
                'read_only' => $isReadOnly,
                'required' => $isRequired,
            ];

            $placeholders[] = $name;
        }

        if ($fields === []) {
            $warnings[] = 'No PDF AcroForm fields were detected.';
        }

        $duplicates = array_diff_assoc($placeholders, array_unique($placeholders));
        if ($duplicates !== []) {
            $warnings[] = 'Duplicate PDF field names detected: '.implode(', ', array_unique($duplicates));
        }

        return [
            'placeholders' => array_values(array_unique($placeholders)),
            'fields' => $fields,
            'warnings' => $warnings,
            'errors' => $errors,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function extractFields(string $templatePath): array
    {
        return $this->inspectFields($templatePath)['placeholders'];
    }

    private function isPdfToolkitAvailable(): bool
    {
        $binary = (string) config('document.pdf_tool_binary', 'pdftk');

        $process = new Process([$binary, '--version']);
        $process->setTimeout(5);

        try {
            $process->mustRun();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
