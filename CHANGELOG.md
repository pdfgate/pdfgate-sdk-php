# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- `voidEnvelope()` to cancel an envelope in `created` or `in_progress` status, with an optional recipient-visible reason.
- `deleteEnvelope()` to permanently delete an envelope and the files it produced.
- `expiresInDays` on the create envelope payload to control envelope and signing link expiration (min 1, max 90 days).
- `getExpiresAt()`, `getVoidedAt()` and `getVoidReason()` on `PdfGateEnvelope`.
- `VOIDED` constants on `EnvelopeStatus`, `EnvelopeDocumentStatus` and `DocumentRecipientStatus`.
- `ENVELOPE_VOIDED`, `ENVELOPE_DELETED` and `ENVELOPE_RECIPIENT_SIGNED` webhook event types.
- `createEmbedLink()` to create a short-lived signing link for an embedded recipient, plus the `EmbedLinkResponse` DTO.
- Recipient directory: `createRecipient()`, `listRecipients()`, `getRecipient()`, `updateRecipient()`, plus the `PdfGateRecipient` DTO.
- `recipientId` and `embedded` on envelope creation recipients; recipient `email`/`name` are now optional when `recipientId` is provided.
- `getRecipientId()` on `EnvelopeRecipientResponse`.
- `signingOrder` on envelope creation recipients to make recipients sign one after another.
- `getSigningOrder()` and `getActivatedAt()` on `EnvelopeRecipientResponse`.
- `ENVELOPE_RECIPIENT_ACTIVATED` webhook event type, fired when it becomes a recipient's turn to sign on a document with a signing order.

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

