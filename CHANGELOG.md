# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-08-03

### Added

- `addFormFields()` to add interactive form fields to a PDF.
- `deleteDocument()` to permanently delete a stored document.
- Webhook management: `createWebhook()`, `getWebhook()`, `deleteWebhook()`, plus the
  `WebhookResponse` DTO and `WebhookStatus` / `WebhookEventType` enums.
- `fieldNames` option on `flattenPdf()` to flatten specific fields only.
- Recipient reminder fields (`reminderIntervalDays`, `reminderAttempts`) on envelope recipients.
- Envelope recipient `signingLink`/`previewLink`, and field `timezone`/`source`/`userValue`/`userTimezone`.
- `EnvelopeStatus::DRAFT`, `EnvelopeDocumentStatus::EXPIRED`, `DocumentRecipientStatus::EXPIRED`.

## [0.1.0] - 2026-03-05

### Added

- Initial public SDK release with PDF generation, processing, and retrieval APIs.
- Unit and acceptance test suites.
- PHPStan static analysis configuration.

