# Docit for Laravel

Production-ready document generation for Laravel from DOCX and fillable PDF templates.

Docit helps teams ship contracts, invoices, certificates, and operational documents with a reliable API-first workflow.

## Why teams adopt Docit

- Ship document automation without building your own fragile rendering pipeline.
- Keep requests safe with idempotency for retried API calls.
- Run generation sync or async with queue retries/backoff controls.
- Inspect templates before production use to catch placeholder and field issues early.
- Persist generation metadata for auditability and troubleshooting.

## Core capabilities

- Convention-based template discovery
- DOCX rendering with PhpWord TemplateProcessor
- Fillable PDF AcroForm rendering with pdftk
- DOC and DOCX conversion with LibreOffice
- Template inspection endpoint with placeholders, warnings, and errors
- Idempotency key support for create requests
- Queue-backed async generation with configurable retry policy
- Metadata persistence with Laravel models

## Installation

```bash
composer require yoosuf/docit
```

## Quick start (5 minutes)

```bash
php artisan vendor:publish --tag=document-config
php artisan vendor:publish --tag=document-migrations
php artisan migrate
```

Store templates in your configured templates directory and call your document endpoint.

## Configuration

Primary config file:

- `config/document.php`

Important environment variables:

- `DOCUMENT_API_PREFIX`
- `DOCUMENT_LOAD_ROUTES`
- `DOCUMENT_TEMPLATES_DIR`
- `DOCUMENT_OUTPUT_DIR`
- `LIBREOFFICE_BINARY`
- `DOCUMENT_CONVERSION_TIMEOUT_SECONDS`
- `PDFTK_BINARY`
- `DOCUMENT_QUEUE_ATTEMPTS`
- `DOCUMENT_QUEUE_BACKOFF_FIRST_SECONDS`
- `DOCUMENT_QUEUE_BACKOFF_SECOND_SECONDS`
- `DOCUMENT_QUEUE_BACKOFF_THIRD_SECONDS`
- `DOCUMENT_QUEUE_MAX_EXCEPTIONS`
- `DOCUMENT_QUEUE_TIMEOUT_SECONDS`

## Positioning statement

If your team is generating operational documents inside Laravel and needs reliability, traceability, and queue-safe retries, Docit is purpose-built for that workflow.

## Contributing

Issues and pull requests are welcome.

- Issues: https://github.com/yoosuf/dockit/issues
- Source: https://github.com/yoosuf/dockit

## License

MIT
