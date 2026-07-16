# yoosuf/docit

Reusable Laravel package for document generation from Word/PDF templates.

## Features

- Convention-based template discovery
- DOCX rendering with PhpWord TemplateProcessor
- PDF AcroForm filling with pdftk
- DOC and DOCX conversion via LibreOffice
- Metadata persistence in SQLite/MySQL/PostgreSQL via Laravel models
- Idempotency keys
- Queue-based async generation with configurable retries/backoff
- Template inspection endpoint support (placeholders, warnings, errors)

## Install

```bash
composer require yoosuf/docit
```

## Publish

```bash
php artisan vendor:publish --tag=document-config
php artisan vendor:publish --tag=document-migrations
```

## Migrate

```bash
php artisan migrate
```

## Config

Main config file is `config/document.php`.

Important env vars:

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

## Notes

- By default the package auto-loads routes under `DOCUMENT_API_PREFIX`.
- Set `DOCUMENT_LOAD_ROUTES=false` to wire routes manually.
- Package throws domain exceptions that host apps should map to consistent API error envelopes.
