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

## Commands and CLI workflow

Docit package itself does not register custom Artisan commands. It relies on Laravel's standard setup commands plus your host application's CLI commands.

### Package setup commands

```bash
php artisan vendor:publish --tag=document-config
php artisan vendor:publish --tag=document-migrations
php artisan migrate
```

How they work:

- `vendor:publish --tag=document-config`: copies package config to `config/document.php` so you can override defaults.
- `vendor:publish --tag=document-migrations`: publishes migration files to your app for full control over schema lifecycle.
- `migrate`: creates required tables (`documents`, `templates`, `generation_events`, `idempotency_keys`, `jobs`).

### Host app CLI commands (example from this repo)

If your host app includes command wrappers, you can run generation from terminal using the same service pipeline as the API.

```bash
php artisan document:make-sample-invoice-template
php artisan document:generate-document invoice pdf --template-format=word --data='{"invoice_number":"INV-1001"}'
php artisan document:generate-document invoice pdf --template-format=word --sales-order-id=1001 --async
```

What these commands do:

- `document:make-sample-invoice-template`: generates a starter DOCX template with placeholders.
- `document:generate-document`: validates input options, builds a document input object, and calls generate (sync) or queue (async).
- `--async`: dispatches a queue job; worker executes the same generation pipeline with retry/backoff policy.

Full command reference and internal flow:

- `docs/CLI_COMMANDS.md`

## Use case: Generate contracts, agreements, and certificates without HTML templates

Docit is designed for teams that want document output from real office templates (DOCX/PDF), not Blade or HTML-to-PDF rendering.

### Workflow

1. Your legal or operations team prepares templates in Word or fillable PDF:
	 - `employment_contract_v1.docx`
	 - `service_agreement_v1.docx`
	 - `completion_certificate_v1.docx`
2. Save templates in `DOCUMENT_TEMPLATES_DIR`.
3. Send business data as JSON to the document API.
4. Docit merges placeholders and returns a generated document file.

### Contract generation example

```json
{
	"document_type": "employment_contract",
	"output_format": "pdf",
	"template_format": "word",
	"data": {
		"employee_name": "Jane Doe",
		"employee_address": "21 Lake Road",
		"position": "Operations Manager",
		"start_date": "2026-08-01",
		"salary": "6500",
		"currency": "USD",
		"company_name": "Acme Logistics LLC"
	}
}
```

### Agreement generation example

```json
{
	"document_type": "service_agreement",
	"output_format": "docx",
	"template_format": "word",
	"data": {
		"agreement_number": "SA-2026-104",
		"client_name": "Northwind Trading",
		"provider_name": "Acme Consulting",
		"effective_date": "2026-07-16",
		"term_months": 12,
		"service_scope": "ERP implementation and support"
	}
}
```

### Certificate generation example

```json
{
	"document_type": "completion_certificate",
	"output_format": "pdf",
	"template_format": "pdf",
	"data": {
		"recipient_name": "Ali Hassan",
		"program_name": "Safety Compliance Training",
		"completion_date": "2026-07-15",
		"certificate_id": "CERT-2026-7781"
	}
}
```

### Why this approach scales

- Non-developers can update legal text/layout directly in DOCX or PDF templates.
- Engineering sends structured data only, no HTML/CSS print maintenance.
- Output is consistent across sync and queued async generation.
- Idempotency prevents duplicate documents during retries.

## Common use cases

Docit can power most operational document workflows where data lives in Laravel and output must be DOCX or PDF.

- Payslips and salary statements
- Employment contracts and offer letters
- Employment certificates and experience letters
- Service agreements and NDAs
- Invoices and tax documents
- Purchase orders and sales order confirmations
- Delivery notes and shipping documents
- Onboarding forms and compliance acknowledgements
- Training completion certificates
- Internal approval forms and signed records

## Scenario playbook

### Payroll and HR scenarios

- Payslips: generate monthly PDF payslips from a DOCX template with placeholders such as base salary, allowances, deductions, and net pay.
- Employment contracts: generate role-specific contract documents from legal-approved DOCX templates and output as PDF for final issuance.
- Employment certificates: generate service certificates with dynamic dates, department, and designation.
- Offer letters: produce DOCX for edit/review and PDF for final archival.

### Legal and operations scenarios

- Client agreements: render agreements from versioned templates per region/business unit.
- Vendor contracts: keep standard clauses in templates while injecting vendor and pricing data via JSON.
- Compliance forms: fill standardized PDF forms for regulatory workflows.
- Certificate issuance: generate branded certificates in bulk with queue mode.

### High-volume scenarios

- Monthly payroll runs (thousands of payslips) via async queue mode.
- Batch certificate generation after training completion.
- Re-issue flows with idempotency keys to avoid duplicate files.

## Document-to-PDF conversion scenarios in PHP

Docit supports conversion paths commonly needed in Laravel backends.

| Input template | Output requested | Backend path |
| --- | --- | --- |
| DOCX | DOCX | Placeholder merge using PhpWord TemplateProcessor |
| DOCX | PDF | Merge in DOCX, then convert via LibreOffice headless mode |
| DOC | DOCX | Convert legacy DOC to DOCX via LibreOffice |
| DOC | PDF | Convert DOC to DOCX/PDF via LibreOffice pipeline |
| Fillable PDF | PDF | Fill AcroForm fields with pdftk |

### Recommended conversion strategy

1. Keep a canonical editable template in DOCX for business/legal updates.
2. Generate PDF for distribution and long-term archival.
3. Use async mode for large runs to avoid request timeouts.
4. Use versioned template naming (`*_v1`, `*_v2`) for safe rollouts.

### Conversion caveats to plan for

- Font availability: ensure server has required fonts used by Word templates.
- Layout drift: complex Word features may render slightly differently in PDF conversion.
- Timeouts: long conversions should run in queue workers with tuned timeout/backoff.
- Binary availability: LibreOffice and pdftk must be installed and reachable by configured binaries.

## PDF form filling details

For fillable PDF templates (AcroForms), Docit maps JSON payload keys to PDF field names and renders a final PDF.

### How field mapping works

1. Build a fillable PDF template with stable field names.
2. Use the same field names as JSON keys in `data`.
3. Submit create request with `template_format` set to `pdf`.
4. Docit fills fields using pdftk and stores output metadata.

### Field types commonly supported

- Text fields (single-line and multi-line)
- Numeric/currency fields (as formatted strings)
- Date fields
- Checkbox fields (mapped with expected export values)
- Radio/select fields (template-dependent)

### PDF form best practices

- Keep field names machine-friendly and stable (`employee_name`, `gross_salary`).
- Avoid duplicate field names unless mirrored values are intentional.
- Validate templates with the inspect endpoint before production use.
- Keep legal static text in the PDF itself, and only inject dynamic fields from JSON.
- Test a sample payload per template version before enabling queue batches.

### Example: payslip PDF payload

```json
{
	"document_type": "monthly_payslip",
	"output_format": "pdf",
	"template_format": "pdf",
	"data": {
		"employee_name": "Sara Khan",
		"employee_id": "EMP-2199",
		"pay_period": "2026-07",
		"basic_salary": "4000.00",
		"allowances": "450.00",
		"deductions": "120.00",
		"net_pay": "4330.00"
	}
}
```

## Performance: why Docit is fast in real workloads

Docit is optimized for operational throughput where you may generate hundreds or thousands of files per run.

- Template-first processing avoids browser rendering overhead.
- No CSS layout engine warmup per job.
- Queue-native execution lets you scale workers horizontally.
- Idempotency avoids duplicate work during retries.
- Conversion pipeline is predictable and easy to benchmark.

### Practical speed expectations

- Small to medium templates typically complete quickly in sync mode.
- Bulk runs are best in async queue mode for steady throughput.
- Worker scaling can increase total documents/minute without changing application code.

Actual performance depends on template complexity, server CPU, storage speed, and installed binary versions.

## Benchmark report: Docit vs Browsershot

Docit is generally a better fit than Browsershot for contract, certificate, and payslip pipelines where throughput and operational reliability matter more than browser-accurate web rendering.

High-level findings:

- Better throughput consistency in queue-based batch runs.
- Lower memory pressure per worker in sustained generation jobs.
- Faster cold-start behavior because there is no Chromium bootstrapping.
- More stable output for legal and form-centric templates maintained outside frontend stacks.

Read the full benchmark report and methodology:

- `docs/BENCHMARK_REPORT.md`

## Documentation

- Architecture and problem-solution design: `docs/ARCHITECTURE.md`
- Benchmark report: `docs/BENCHMARK_REPORT.md`
- Command reference and CLI internals: `docs/CLI_COMMANDS.md`

## Why this is ideal vs HTML-to-PDF generation

HTML-to-PDF is useful for web-like layouts, but template-driven document generation is often better for legal and operational documents.

### 1) Better ownership model

- Legal, HR, and operations teams can maintain DOCX/PDF templates directly.
- Engineering focuses on data contracts and API reliability, not print CSS fixes.

### 2) Higher output consistency

- Word/PDF templates preserve document intent (sections, clauses, fixed legal wording).
- Fewer rendering surprises caused by browser/CSS differences.

### 3) Lower maintenance cost

- No parallel HTML print templates to maintain.
- No recurring CSS-for-paper troubleshooting cycle.

### 4) Stronger compliance and audit workflows

- Stable, versioned templates improve traceability.
- Metadata persistence and generation events support operational audits.

### 5) Safer at scale

- Idempotency + queues provide controlled retry behavior.
- Bulk jobs are resilient to transient failures and worker restarts.

### 6) More natural fit for form-centric workflows

- Fillable PDF forms map directly to domain fields.
- Structured JSON payloads can populate standard forms consistently.

## Marketing snapshot

Docit is the production document engine for Laravel teams that need speed, reliability, and business-friendly template ownership.

- Build once, generate forever: contracts, payslips, agreements, and certificates from real templates.
- Ship faster: remove HTML-to-PDF maintenance from your roadmap.
- Operate confidently: idempotency, queue retries, and generation tracking built in.
- Scale smoothly: from single documents to high-volume batch pipelines.

### One-line pitch

Docit turns Laravel into a reliable document factory for DOCX and PDF workflows without HTML template debt.

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
