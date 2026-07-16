<?php

namespace Yoosuf\Document\Documents\Http\Controllers;

use Yoosuf\Document\Documents\Http\Requests\InspectDocumentTypeRequest;
use Yoosuf\Document\Documents\Repositories\TemplateRepository;
use Yoosuf\Document\Documents\Services\PdfFormRenderer;
use Yoosuf\Document\Documents\Services\WordTemplateRenderer;

class DocumentTypeController
{
    public function __construct(
        private readonly TemplateRepository $templateRepository,
        private readonly WordTemplateRenderer $wordTemplateRenderer,
        private readonly PdfFormRenderer $pdfFormRenderer,
    ) {
    }

    public function index()
    {
        return response()->json([
            'data' => $this->templateRepository->listDocumentTypes(),
        ]);
    }

    public function inspect(string $documentType, InspectDocumentTypeRequest $request)
    {
        $descriptor = $this->templateRepository->resolve(
            $documentType,
            $request->input('version') ? (string) $request->input('version') : null,
            $request->input('template_format') ? (string) $request->input('template_format') : null,
        );

        $warnings = [];
        $errors = [];
        $placeholders = [];
        $fieldDetails = [];

        if ($descriptor->templateFormat === 'word') {
            $inspection = $this->wordTemplateRenderer->inspectTemplate($descriptor->absolutePath);
            $placeholders = $inspection['placeholders'];
            $warnings = array_merge($warnings, $inspection['warnings']);
            $errors = array_merge($errors, $inspection['errors']);

            if ($descriptor->extension === 'doc') {
                $warnings[] = 'Legacy .doc templates require conversion before rendering and may lose formatting fidelity.';
            }
        }

        if ($descriptor->templateFormat === 'pdf') {
            $inspection = $this->pdfFormRenderer->inspectFields($descriptor->absolutePath);
            $placeholders = $inspection['placeholders'];
            $fieldDetails = $inspection['fields'];
            $warnings = array_merge($warnings, $inspection['warnings']);
            $errors = array_merge($errors, $inspection['errors']);

            if ($placeholders !== []) {
                $warnings[] = 'PDF field mapping is case-sensitive; payload keys must exactly match field names.';
            }
        }

        return response()->json([
            'document_type' => $descriptor->documentType,
            'version' => $descriptor->version,
            'template_format' => $descriptor->templateFormat,
            'placeholders' => $placeholders,
            'fields' => $fieldDetails,
            'warnings' => $warnings,
            'errors' => $errors,
            'supported_output_formats' => $descriptor->supportedOutputFormats(),
        ]);
    }
}
