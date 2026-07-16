<?php

namespace Yoosuf\Document\Documents\Services;

use Yoosuf\Document\Documents\Exceptions\DocumentException;
use Yoosuf\Document\Documents\Models\Document;
use Yoosuf\Document\Documents\Models\IdempotencyKey;
use Yoosuf\Document\Documents\Support\ErrorCodes;

class IdempotencyService
{
    /**
     * @param array<string, mixed> $requestPayload
     */
    public function resolveExisting(string $idempotencyKey, array $requestPayload): ?Document
    {
        $requestHash = $this->hashRequest($requestPayload);

        $entry = IdempotencyKey::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($entry === null) {
            return null;
        }

        if (!hash_equals($entry->request_hash, $requestHash)) {
            throw new DocumentException(
                ErrorCodes::IDEMPOTENCY_KEY_CONFLICT,
                'The provided Idempotency-Key is already used with a different request payload.',
                409,
            );
        }

        return Document::query()->where('document_id', $entry->document_id)->first();
    }

    /**
     * @param array<string, mixed> $requestPayload
     */
    public function remember(string $idempotencyKey, array $requestPayload, string $documentId): void
    {
        $requestHash = $this->hashRequest($requestPayload);

        try {
            IdempotencyKey::query()->create([
                'idempotency_key' => $idempotencyKey,
                'request_hash' => $requestHash,
                'document_id' => $documentId,
                'created_at' => now('UTC'),
            ]);
        } catch (\Throwable $exception) {
            $existing = IdempotencyKey::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing === null) {
                throw $exception;
            }

            if (!hash_equals($existing->request_hash, $requestHash)) {
                throw new DocumentException(
                    ErrorCodes::IDEMPOTENCY_KEY_CONFLICT,
                    'The provided Idempotency-Key is already used with a different request payload.',
                    409,
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $requestPayload
     */
    private function hashRequest(array $requestPayload): string
    {
        $normalized = $this->normalizeArray($requestPayload);

        return hash('sha256', json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * @param array<string, mixed> $value
     * @return array<string, mixed>
     */
    private function normalizeArray(array $value): array
    {
        ksort($value);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->normalizeArray($item);
            }
        }

        return $value;
    }
}
