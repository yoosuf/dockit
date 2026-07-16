# Laravel News Pitch Kit for Docit

Use this file to submit or email a launch pitch to Laravel News and other Laravel community newsletters.

## 1) Short pitch (for forms)

Docit is a new Laravel package for production document generation from DOCX and fillable PDF templates. It includes idempotency support, queue-friendly async generation, and template inspection to catch placeholder and field issues before runtime. It is aimed at teams building invoices, contracts, certificates, and operational documents in Laravel APIs.

## 2) Launch post draft

Title:
Docit: Production document generation for Laravel from DOCX and PDF templates

Body:
Hi Laravel News team,

I just released Docit, a new package focused on reliable document generation workflows in Laravel.

What it does:
- Generates documents from DOCX and fillable PDF templates
- Supports idempotent create requests for safe retries
- Supports sync and queue-based async generation
- Includes template inspection for placeholders/fields and validation warnings
- Persists generation metadata for operational visibility

Why I built it:
Many teams need internal and customer-facing documents, but custom generation pipelines become brittle under retries, queue failures, and template drift. Docit packages those production concerns into a reusable Laravel-first workflow.

Package:
- Composer: yoosuf/docit
- GitHub: https://github.com/yoosuf/dockit

If useful, I can also share a follow-up article with architecture notes and implementation details.

## 3) X/Twitter launch thread (copy-ready)

Post 1:
I just released Docit, a Laravel package for production document generation from DOCX and fillable PDF templates.

Post 2:
Docit includes idempotency support + queue-safe async generation so retried requests do not create duplicate documents.

Post 3:
It also includes template inspection to surface placeholder/field issues before they break production flows.

Post 4:
Great fit for invoices, contracts, certificates, and API-first document workflows in Laravel teams.

Post 5:
Composer: composer require yoosuf/docit
GitHub: https://github.com/yoosuf/dockit

## 4) One-line value proposition

Docit gives Laravel teams a reliable, API-first document generation pipeline with production safeguards out of the box.
