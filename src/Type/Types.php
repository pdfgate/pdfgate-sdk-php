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
 * @phpstan-type GeneratePdfRequestPayload array{
 *   html?: string,
 *   url?: string,
 *   preSignedUrlExpiresIn?: int,
 *   pageSizeType?: string,
 *   width?: float|int,
 *   height?: float|int,
 *   orientation?: string,
 *   header?: string,
 *   footer?: string,
 *   margin?: MarginPayload,
 *   timeout?: int,
 *   javascript?: string,
 *   css?: string,
 *   emulateMediaType?: string,
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
 * @phpstan-type ExtractPdfFormDataRequestPayload array{
 *   documentId: string,
 *   metadata?: array<string,mixed>
 * }
 */
interface Types
{
}
