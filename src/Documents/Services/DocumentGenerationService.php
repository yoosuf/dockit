<?php

namespace Yoosuf\Document\Documents\Services;

use Yoosuf\Document\Documents\DTO\CreateDocumentInput;
use Yoosuf\Document\Documents\Exceptions\DocumentException;
use Yoosuf\Document\Documents\Models\Document;
use Yoosuf\Document\Documents\Repositories\SalesOrderRepository;
use Yoosuf\Document\Documents\Repositories\TemplateRepository;
use Yoosuf\Document\Documents\Support\ErrorCodes;
use Yoosuf\Document\Jobs\GenerateDocumentJob;
use Carbon\CarbonImmutable;

class DocumentGenerationService
{
    public function __construct(
        private readonly TemplateRepository $templateRepository,
        private readonly SalesOrderRepository $salesOrderRepository,
        private readonly WordTemplateRenderer $wordTemplateRenderer,
        private readonly PdfFormRenderer $pdfFormRenderer,
        private readonly ConversionService $conversionService,
        private readonly DocumentStorageService $documentStorageService,
        private readonly GenerationEventService $generationEventService,
    ) {
    }

    public function generate(CreateDocumentInput $input): Document
    {
        $documentId = bin2hex(random_bytes(16));
        $template = $this->templateRepository->resolve($input->documentType, $input->version, $input->templateFormat);

        $document = Document::query()->create([
            'document_id' => $documentId,
            'document_type' => $template->documentType,
            'version' => $template->version,
            'template_format' => $template->templateFormat,
            'output_format' => $input->outputFormat,
            'filename' => '',
            'storage_path' => '',
            'size_bytes' => 0,
            'source_mode' => $input->salesOrderId === null ? 'data' : 'sales_order',
            'request_payload_json' => $this->serializeRequestPayload($input),
            'status' => 'processing',
        ]);

        return $this->processQueuedDocument($document, $input);
    }

    public function queue(CreateDocumentInput $input): Document
    {
        $this->validateSourceMode($input);

        $template = $this->templateRepository->resolve($input->documentType, $input->version, $input->templateFormat);
        $documentId = bin2hex(random_bytes(16));

        $document = Document::query()->create([
            'document_id' => $documentId,
            'document_type' => $template->documentType,
            'version' => $template->version,
            'template_format' => $template->templateFormat,
            'output_format' => $input->outputFormat,
            'filename' => '',
            'storage_path' => '',
            'size_bytes' => 0,
            'source_mode' => $input->salesOrderId === null ? 'data' : 'sales_order',
            'request_payload_json' => $this->serializeRequestPayload($input),
            'status' => 'queued',
        ]);

        $this->generationEventService->log($documentId, 'queued', 'Document generation queued.');

        GenerateDocumentJob::dispatch(
            documentId: $documentId,
            documentType: $input->documentType,
            version: $input->version,
            templateFormat: $input->templateFormat,
            outputFormat: $input->outputFormat,
            data: $input->data,
            salesOrderId: $input->salesOrderId,
        );

        return $document;
    }

    public function processQueuedDocument(Document $document, CreateDocumentInput $input): Document
    {
        $this->validateSourceMode($input);

        $document->status = 'processing';
        $document->save();

        $this->generationEventService->log($document->document_id, 'processing', 'Document generation started.');

        $template = $this->templateRepository->resolve(
            $document->document_type,
            $document->version,
            $document->template_format,
        );
        $payload = $this->buildModel($input);
        $timestamp = CarbonImmutable::now('UTC')->format('Ymd\\THisuv\\Z');

        $workingDir = storage_path('app/tmp/documents/'.uniqid('', true));
        if (!is_dir($workingDir)) {
            mkdir($workingDir, 0755, true);
        }

        $outputExt = $document->output_format;
        $finalOutputPath = '';

        try {
            if ($template->templateFormat === 'pdf') {
                if ($document->output_format !== 'pdf') {
                    throw new DocumentException(
                        ErrorCodes::UNSUPPORTED_OUTPUT_FORMAT,
                        'PDF templates can only produce PDF output.',
                        400,
                    );
                }

                $finalOutputPath = $workingDir.'/output.pdf';
                $this->pdfFormRenderer->render($template->absolutePath, $payload, $finalOutputPath);
            } else {
                $renderTemplatePath = $template->absolutePath;
                if ($template->extension === 'doc') {
                    $renderTemplatePath = $this->conversionService->convertDocToDocx($template->absolutePath, $workingDir);
                }

                $renderedDocxPath = $workingDir.'/rendered.docx';
                $this->wordTemplateRenderer->render($renderTemplatePath, $payload, $renderedDocxPath);

                if ($document->output_format === 'docx') {
                    $finalOutputPath = $renderedDocxPath;
                } else {
                    $finalOutputPath = $this->conversionService->convertDocxToPdf($renderedDocxPath, $workingDir);
                    $outputExt = 'pdf';
                }
            }

            $filename = sprintf(
                '%s_%s_%s_%s_%s.%s',
                $template->documentType,
                $template->version,
                $template->templateFormat,
                $timestamp,
                $document->document_id,
                $outputExt,
            );

            $stored = $this->documentStorageService->persist($finalOutputPath, $filename);

            $document->filename = $filename;
            $document->storage_path = $stored['storage_path'];
            $document->size_bytes = $stored['size_bytes'];
            $document->status = 'completed';
            $document->error_code = null;
            $document->error_message = null;
            $document->save();

            $this->generationEventService->log($document->document_id, 'completed', 'Document generation completed.');
        } catch (\Throwable $exception) {
            $document->status = 'failed';
            $document->error_code = $exception instanceof DocumentException ? $exception->errorCode : ErrorCodes::HTTP_ERROR;
            $document->error_message = $exception->getMessage();
            $document->save();

            $this->generationEventService->log(
                $document->document_id,
                'failed',
                'Document generation failed.',
                ['error' => $exception->getMessage()],
            );

            throw $exception;
        }

        return $document;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildModel(CreateDocumentInput $input): array
    {
        if ($input->salesOrderId === null) {
            return $input->data ?? [];
        }

        if ($input->documentType !== 'invoice') {
            throw new DocumentException(
                ErrorCodes::DOCUMENT_MODEL_NOT_SUPPORTED,
                'sales_order_id mode is only supported for invoice documents in MVP.',
                400,
            );
        }

        $salesOrder = $this->salesOrderRepository->find($input->salesOrderId);
        if ($salesOrder === null) {
            throw new DocumentException(
                ErrorCodes::SALES_ORDER_NOT_FOUND,
                'Sales order was not found.',
                404,
            );
        }

        return [
            'sales_order_id' => $salesOrder->sales_order_id,
            'customer_name' => $salesOrder->customer_name,
            'customer_email' => $salesOrder->customer_email,
            'billing_address' => $salesOrder->billing_address,
            'shipping_address' => $salesOrder->shipping_address,
            'invoice_number' => $salesOrder->invoice_number,
            'currency' => $salesOrder->currency,
            'subtotal_amount' => $salesOrder->subtotal_amount,
            'tax_amount' => $salesOrder->tax_amount,
            'discount_amount' => $salesOrder->discount_amount,
            'total_amount' => $salesOrder->total_amount,
            'issued_at' => $salesOrder->issued_at,
            'due_at' => $salesOrder->due_at,
            'items' => $salesOrder->items_json ? json_decode((string) $salesOrder->items_json, true) : [],
        ];
    }

    private function validateSourceMode(CreateDocumentInput $input): void
    {
        $hasData = $input->data !== null;
        $hasSalesOrderId = $input->salesOrderId !== null;

        if ($hasData === $hasSalesOrderId) {
            throw new DocumentException(
                ErrorCodes::VALIDATION_ERROR,
                'Exactly one of data or sales_order_id must be supplied.',
                422,
                [[
                    'field' => 'data',
                    'message' => 'Either data or sales_order_id must be supplied.',
                ]],
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRequestPayload(CreateDocumentInput $input): array
    {
        return [
            'document_type' => $input->documentType,
            'version' => $input->version,
            'template_format' => $input->templateFormat,
            'output_format' => $input->outputFormat,
            'data' => $input->data,
            'sales_order_id' => $input->salesOrderId,
        ];
    }
}
