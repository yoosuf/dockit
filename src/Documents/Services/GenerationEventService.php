<?php

namespace Yoosuf\Document\Documents\Services;

use Yoosuf\Document\Documents\Models\GenerationEvent;

class GenerationEventService
{
    /**
     * @param array<string, mixed> $context
     */
    public function log(string $documentId, string $eventType, ?string $message = null, array $context = []): void
    {
        GenerationEvent::query()->create([
            'document_id' => $documentId,
            'event_type' => $eventType,
            'message' => $message,
            'context_json' => $context === [] ? null : json_encode($context, JSON_THROW_ON_ERROR),
            'created_at' => now('UTC'),
        ]);
    }
}
