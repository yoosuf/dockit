# Benchmark Report: Docit vs Browsershot

This report is designed for engineering and product teams deciding between template-driven document generation (Docit) and browser-rendered HTML-to-PDF pipelines (Browsershot).

## Executive summary

For document-heavy backend workflows such as payslips, employment contracts, and certificates, Docit is typically the stronger default.

Why:

- It avoids per-job browser rendering overhead.
- It scales naturally with queue workers for high-volume runs.
- It aligns with business-owned document templates (DOCX/PDF).
- It reduces frontend print-template maintenance burden.

Browsershot remains useful when your source of truth is already HTML/CSS and pixel parity with web UI is required.

## Test scenarios

Use these repeatable scenarios to compare both approaches on the same machine.

1. S1: Single document (small template, sync request)
2. S2: Burst of 100 documents (queue mode)
3. S3: Sustained batch of 1,000 documents (queue mode)
4. S4: Complex legal contract with long clauses and tables
5. S5: Fillable PDF form batch (500 documents)

## Metrics to capture

- P50, P95 generation latency per document
- Documents per minute (throughput)
- Peak memory per worker/process
- Failure and retry rate
- Time to recovery after worker restart
- Operational complexity (setup + maintenance effort)

## Why Docit is often faster in practice

1. No browser boot cost per job
Docit does not need Chromium startup for each render path.

2. No HTML/CSS layout recalculation pipeline
Word/PDF template workflows bypass browser-style rendering complexity.

3. Queue-friendly architecture
Docit is designed for retryable, idempotent generation flows in backend workers.

4. Better fit for document operations teams
Non-frontend teams can evolve templates directly in DOCX/PDF without frontend deployment cycles.

## Why Docit is often more reliable for legal/HR documents

- Contracts and certificates usually require stable clause formatting over responsive layout behavior.
- Fillable PDF field mapping supports deterministic value injection.
- Versioned templates improve auditability and rollback safety.

## Trade-off matrix

| Requirement | Docit | Browsershot |
| --- | --- | --- |
| High-volume backend generation | Strong | Medium |
| Legal/HR template ownership in Word/PDF | Strong | Weak |
| Web UI visual parity in PDF | Medium | Strong |
| Queue-safe idempotent retries | Strong | Medium |
| Frontend dependency for document updates | Low | High |

## Example benchmark result format

Use this table when publishing your measured numbers:

| Scenario | Approach | P50 (ms) | P95 (ms) | Docs/min | Peak memory | Failure rate |
| --- | --- | --- | --- | --- | --- | --- |
| S2 (100 docs) | Docit | TBD | TBD | TBD | TBD | TBD |
| S2 (100 docs) | Browsershot | TBD | TBD | TBD | TBD | TBD |
| S3 (1,000 docs) | Docit | TBD | TBD | TBD | TBD | TBD |
| S3 (1,000 docs) | Browsershot | TBD | TBD | TBD | TBD | TBD |

## Reproducible benchmark guidance

1. Run both systems on identical infrastructure.
2. Warm up each pipeline before measurement.
3. Use the same payloads, template complexity, and output format.
4. Repeat each scenario at least 3 times and average results.
5. Record environment details: CPU, RAM, storage, OS, binary versions.

## Recommendation

Choose Docit as the default for operational document automation in Laravel when your source templates are DOCX/PDF and you care about throughput, reliability, and long-term maintenance cost.

Choose Browsershot only when your document source of truth is already HTML/CSS and browser-accurate rendering is a hard requirement.
