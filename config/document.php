<?php

return [
    'route_prefix' => env('DOCUMENT_API_PREFIX', 'api/v1'),
    'load_routes' => env('DOCUMENT_LOAD_ROUTES', true),
    'templates_dir' => env('DOCUMENT_TEMPLATES_DIR', 'storage/app/templates'),
    'output_dir' => env('DOCUMENT_OUTPUT_DIR', 'storage/app/generated'),
    'libreoffice_binary' => env('LIBREOFFICE_BINARY', 'soffice'),
    'conversion_timeout_seconds' => (int) env('DOCUMENT_CONVERSION_TIMEOUT_SECONDS', 60),
    'pdf_tool_binary' => env('PDFTK_BINARY', 'pdftk'),
    'queue' => [
        'attempts' => (int) env('DOCUMENT_QUEUE_ATTEMPTS', 3),
        'backoff_seconds' => [
            (int) env('DOCUMENT_QUEUE_BACKOFF_FIRST_SECONDS', 10),
            (int) env('DOCUMENT_QUEUE_BACKOFF_SECOND_SECONDS', 30),
            (int) env('DOCUMENT_QUEUE_BACKOFF_THIRD_SECONDS', 60),
        ],
        'max_exceptions' => (int) env('DOCUMENT_QUEUE_MAX_EXCEPTIONS', 2),
        'timeout_seconds' => (int) env('DOCUMENT_QUEUE_TIMEOUT_SECONDS', 120),
    ],
];
