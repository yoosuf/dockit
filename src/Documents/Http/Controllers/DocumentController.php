<?php

namespace Yoosuf\Document\Documents\Http\Controllers;

use Yoosuf\Document\Documents\DTO\CreateDocumentInput;
use Yoosuf\Document\Documents\Http\Requests\CreateDocumentRequest;
use Yoosuf\Document\Documents\Http\Resources\DocumentResource;
use Yoosuf\Document\Documents\Exceptions\DocumentException;
use Yoosuf\Document\Documents\Models\Document;
use Yoosuf\Document\Documents\Models\GenerationEvent;
use Yoosuf\Document\Documents\Services\DocumentGenerationService;
use Yoosuf\Document\Documents\Services\GenerationEventService;
use Yoosuf\Document\Documents\Services\IdempotencyService;
use Yoosuf\Document\Documents\Services\DocumentStorageService;
use Yoosuf\Document\Documents\Support\ErrorCodes;
use Yoosuf\Document\Jobs\GenerateDocumentJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DocumentController
{
    public function __construct(
        private readonly DocumentGenerationService $documentGenerationService,
        private readonly IdempotencyService $idempotencyService,
        private readonly GenerationEventService $generationEventService,
        private readonly DocumentStorageService $documentStorageService,
    ) {
    }

    public function store(CreateDocumentRequest $request)
    {
        $input = new CreateDocumentInput(
            documentType: (string) $request->string('document_type'),
            version: $request->input('version') ? (string) $request->input('version') : null,
            templateFormat: $request->input('template_format') ? (string) $request->input('template_format') : null,
            outputFormat: (string) $request->string('output_format'),
            data: $request->input('data'),
            salesOrderId: $request->input('sales_order_id') !== null ? (int) $request->input('sales_order_id') : null,
        );

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey !== '') {
            $existing = $this->idempotencyService->resolveExisting($idempotencyKey, $request->validated());
            if ($existing !== null) {
                $statusCode = in_array($existing->status, ['queued', 'processing'], true) ? 202 : 200;

                return (new DocumentResource($existing))
                    ->response()
                    ->setStatusCode($statusCode)
                    ->header('Location', url('/api/v1/documents/'.$existing->document_id));
            }
        }

        $isAsync = (bool) $request->boolean('async', false);
        $document = $isAsync
            ? $this->documentGenerationService->queue($input)
            : $this->documentGenerationService->generate($input);

        if ($idempotencyKey !== '') {
            $this->idempotencyService->remember($idempotencyKey, $request->validated(), $document->document_id);
        }

        $statusCode = $isAsync ? 202 : 201;

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode($statusCode)
            ->header('Location', url('/api/v1/documents/'.$document->document_id));
    }

    public function index(Request $request)
    {
        $documents = Document::query()->orderByDesc('created_at')->paginate((int) $request->query('per_page', 25));

        return DocumentResource::collection($documents);
    }

    public function show(string $documentId): DocumentResource
    {
        $document = Document::query()->where('document_id', $documentId)->firstOrFail();

        return new DocumentResource($document);
    }

    public function content(string $documentId)
    {
        $document = Document::query()->where('document_id', $documentId)->firstOrFail();

        if ($document->status !== 'completed' || $document->storage_path === '') {
            throw new DocumentException(
                ErrorCodes::DOCUMENT_NOT_READY,
                'Document content is not available yet.',
                409,
            );
        }

        $absolutePath = $this->documentStorageService->absolutePath($document->storage_path);

        return response()->download($absolutePath, $document->filename);
    }

    public function destroy(string $documentId): Response
    {
        $document = Document::query()->where('document_id', $documentId)->firstOrFail();
        if ($document->storage_path !== '') {
            $this->documentStorageService->delete($document->storage_path);
        }
        $document->delete();

        return response()->noContent();
    }

    public function events(string $documentId)
    {
        Document::query()->where('document_id', $documentId)->firstOrFail();

        return response()->json([
            'data' => GenerationEvent::query()
                ->where('document_id', $documentId)
                ->orderBy('created_at')
                ->get()
                ->map(static fn (GenerationEvent $event): array => [
                    'event_type' => $event->event_type,
                    'message' => $event->message,
                    'context_json' => $event->context_json,
                    'created_at' => optional($event->created_at)->toISOString(),
                ]),
        ]);
    }

    public function requeue(string $documentId)
    {
        $document = Document::query()->where('document_id', $documentId)->firstOrFail();

        if ($document->status !== 'failed') {
            throw new DocumentException(
                ErrorCodes::INVALID_DOCUMENT_STATUS,
                'Only failed documents can be requeued.',
                409,
            );
        }

        $document->status = 'queued';
        $document->error_code = null;
        $document->error_message = null;
        $document->save();

        $payload = is_array($document->request_payload_json) ? $document->request_payload_json : [];

        GenerateDocumentJob::dispatch(
            documentId: $document->document_id,
            documentType: (string) ($payload['document_type'] ?? $document->document_type),
            version: isset($payload['version']) ? (string) $payload['version'] : $document->version,
            templateFormat: isset($payload['template_format']) ? (string) $payload['template_format'] : $document->template_format,
            outputFormat: (string) ($payload['output_format'] ?? $document->output_format),
            data: isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : null,
            salesOrderId: isset($payload['sales_order_id']) ? (int) $payload['sales_order_id'] : null,
        );

        $this->generationEventService->log($document->document_id, 'requeued', 'Failed document was manually requeued.');

        return (new DocumentResource($document))
            ->response()
            ->setStatusCode(202)
            ->header('Location', url('/api/v1/documents/'.$document->document_id));
    }
}
