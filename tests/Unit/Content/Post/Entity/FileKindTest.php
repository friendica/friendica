<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Content\Post\Entity;

use Friendica\Content\Post\Entity\FileKind;
use Friendica\Core\L10n;
use Friendica\Network\Entity\MimeType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FileKindTest extends TestCase
{
	public static function dataMimeType(): array
	{
		return [
			'subtype overrides type default' => ['application', 'pdf', FileKind::Pdf],
			'same subtype under text'        => ['text', 'csv', FileKind::Excel],
			'type default'                   => ['audio', 'ogg', FileKind::Audio],
			'unknown subtype'                => ['application', 'octet-stream', FileKind::Generic],
			'unknown type'                   => ['model', 'gltf', FileKind::Generic],
		];
	}

	#[DataProvider('dataMimeType')]
	public function testFromMimeType(string $type, string $subtype, FileKind $expected): void
	{
		self::assertSame($expected, FileKind::fromMimeType(new MimeType($type, $subtype)));
	}

	public function testLabel(): void
	{
		$l10n = self::createStub(L10n::class);
		$l10n->method('t')->willReturnArgument(0);

		self::assertSame('PDF document', FileKind::Pdf->label($l10n));
		self::assertSame('File', FileKind::Generic->label($l10n));
	}
}
