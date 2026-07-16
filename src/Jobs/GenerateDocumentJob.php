<?php

namespace Yoosuf\Document\Jobs;

use Yoosuf\Document\Documents\DTO\CreateDocumentInput;
use Yoosuf\Document\Documents\Models\Document;
use Yoosuf\Document\Documents\Services\DocumentGenerationService;
use Yoosuf\Document\Documents\Services\GenerationEventService;
use Yoosuf\Document\Documents\Support\ErrorCodes;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDocumentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public int $maxExceptions;

    public int $timeout;

    /**
     * @param array<string, mixed>|null $data
     */
    public function __construct(
        public readonly string $documentId,
        public readonly string $documentType,
        public readonly ?string $version,
        public readonly ?string $templateFormat,
        public readonly string $outputFormat,
        public readonly ?array $data,
        public readonly ?int $salesOrderId,
    ) {
        $this->tries = (int) config('document.queue.attempts', 3);
        $this->maxExceptions = (int) config('document.queue.max_exceptions', 2);
        $this->timeout = (int) config('document.queue.timeout_seconds', 120);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        $configured = config('document.queue.backoff_seconds', [10, 30, 60]);

        if (!is_array($configured) || $configured === []) {
            return [10, 30, 60];
        }

        return array_values(array_map(static fn ($value): int => max(1, (int) $value), $configured));
    }

    public function handle(DocumentGenerationService $generationService): void
    {
        $document = Document::query()->where('document_id', $this->documentId)->first();
        if ($document === null) {
            return;
        }

        $input = new CreateDocumentInput(
            documentType: $this->documentType,
            version: $this->version,
            templateFormat: $this->templateFormat,
            outputFormat: $this->outputFormat,
            data: $this->data,
            salesOrderId: $this->salesOrderId,
        );

        $generationService->processQueuedDocument($document, $input);
    }

    public function failed(\Throwable $exception): void
    {
        $document = Document::query()->where('document_id', $this->documentId)->first();
        if ($document === null) {
            return;
        }

        $document->status = 'failed';
        $document->error_code = ErrorCodes::HTTP_ERROR;
        $document->error_message = $exception->getMessage();
        $document->save();

        app(GenerationEventService::class)->log(
            $document->document_id,
            'failed',
            'Document job exhausted retries and failed.',
            ['error' => $exception->getMessage()],
        );
    }
}
