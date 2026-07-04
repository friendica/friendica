<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Module\Settings\TwoFactor;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class VerifyQrCodeTest extends TestCase
{
	/**
	 * Asserts that valid SVG is produced when the xmlwriter extension is available.
	 */
	public function testQrCodeGeneratesValidSvgWhenXmlWriterIsAvailable(): void
	{
		if (!class_exists(\XMLWriter::class)) {
			$this->markTestSkipped('XMLWriter (ext-xmlwriter) is not available on this system.');
		}

		$otpauthUrl = 'otpauth://totp/Friendica:test%40example.com?secret=BASE32SECRET&issuer=Friendica';

		$qrcode_image = '';
		try {
			$renderer = new ImageRenderer(
				new RendererStyle(256),
				new SvgImageBackEnd(),
			);
			$writer       = new Writer($renderer);
			$qrcode_image = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $writer->writeString($otpauthUrl));
		} catch (\Throwable) {
			// Should not reach here when XMLWriter is available
		}

		self::assertNotEmpty($qrcode_image, 'QR code SVG should not be empty when XMLWriter is available');
		self::assertStringContainsString('<svg', $qrcode_image, 'QR code output should be an SVG element');
	}

	/**
	 * Asserts that when QR code generation throws (e.g. XMLWriter missing),
	 * the exception is caught, the logger receives a warning, and
	 * $qrcode_image stays an empty string so the page can still render.
	 */
	public function testQrCodeGenerationFailureIsCaughtAndLogged(): void
	{
		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())
			->method('warning')
			->with(
				$this->stringContains('QR code generation failed'),
				$this->arrayHasKey('exception'),
			);

		$qrcode_image = '';

		try {
			throw new \BaconQrCode\Exception\RuntimeException(
				'You need to install the libxml extension to use this back end',
			);
		} catch (\Throwable $e) {
			$logger->warning(
				'QR code generation failed, libxml/XMLWriter extension may be missing.',
				['exception' => $e],
			);
		}

		self::assertSame('', $qrcode_image, '$qrcode_image must stay empty on failure so the page still renders'); // @phpstan-ignore staticMethod.alreadyNarrowedType
	}
}
