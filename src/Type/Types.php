<?php

declare(strict_types=1);

namespace PdfGate\Type;

/**
 * Centralized PHPStan type aliases used across the SDK.
 *
 * @phpstan-type MarginPayload array{
 *   top?: string,
 *   bottom?: string,
 *   left?: string,
 *   right?: string
 * }
 * @phpstan-type ClickSelectorChainPayload array{
 *   selectors: list<string>
 * }
 * @phpstan-type ClickSelectorChainSetupPayload array{
 *   ignoreFailingChains: bool,
 *   chains: list<ClickSelectorChainPayload>
 * }
 * @phpstan-type GeneratePdfPageSizeType 'a0'|'a1'|'a2'|'a3'|'a4'|'a5'|'a6'|'ledger'|'tabloid'|'legal'|'letter'
 * @phpstan-type GeneratePdfOrientation 'portrait'|'landscape'
 * @phpstan-type GeneratePdfEmulateMediaType 'screen'|'print'
 * @phpstan-type ProtectPdfAlgorithm 'AES256'|'AES128'
 * @phpstan-type GeneratePdfRequestPayload array{
 *   html?: string,
 *   url?: string,
 *   preSignedUrlExpiresIn?: int,
 *   pageSizeType?: GeneratePdfPageSizeType,
 *   width?: float|int,
 *   height?: float|int,
 *   orientation?: GeneratePdfOrientation,
 *   header?: string,
 *   footer?: string,
 *   margin?: MarginPayload,
 *   timeout?: int,
 *   javascript?: string,
 *   css?: string,
 *   emulateMediaType?: GeneratePdfEmulateMediaType,
 *   waitForSelector?: string,
 *   clickSelector?: string,
 *   clickSelectorChainSetup?: ClickSelectorChainSetupPayload,
 *   waitForNetworkIdle?: bool,
 *   delay?: int,
 *   loadImages?: bool,
 *   scale?: float|int,
 *   pageRanges?: string,
 *   printBackground?: bool,
 *   userAgent?: string,
 *   httpHeaders?: array<string,mixed>,
 *   authentication?: array<string,mixed>,
 *   viewport?: array<string,mixed>,
 *   enableFormFields?: bool,
 *   metadata?: array<string,mixed>
 * }
 * @phpstan-type FlattenPdfRequestPayload array{
 *   documentId: string,
 *   preSignedUrlExpiresIn?: int,
 *   metadata?: array<string,mixed>
 * }
 * @phpstan-type CompressPdfRequestPayload array{
 *   documentId: string,
 *   linearize?: bool,
 *   preSignedUrlExpiresIn?: int,
 *   metadata?: array<string,mixed>
 * }
 * @phpstan-type ProtectPdfRequestPayload array{
 *   documentId: string,
 *   algorithm?: ProtectPdfAlgorithm,
 *   userPassword?: string,
 *   ownerPassword?: string,
 *   disablePrint?: bool,
 *   disableCopy?: bool,
 *   disableEditing?: bool,
 *   encryptMetadata?: bool,
 *   preSignedUrlExpiresIn?: int,
 *   metadata?: array<string,mixed>
 * }
 * @phpstan-type ExtractPdfFormDataRequestPayload array{
 *   documentId: string,
 *   metadata?: array<string,mixed>
 * }
 * @phpstan-type GetDocumentQueryPayload array{
 *   preSignedUrlExpiresIn?: int
 * }
 */
interface Types
{
}
