<?php

namespace Yoosuf\Document\Documents\Exceptions;

use Exception;

class DocumentException extends Exception
{
    /**
     * @param array<int, array{field: string, message: string}> $fields
     */
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status = 400,
        public readonly array $fields = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
