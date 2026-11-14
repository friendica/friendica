<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Util;

use Friendica\Util\Crypto;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

class CryptoTest extends TestCase
{
	use PHPMock;

	public function testRandomDigitsRandomInt(): void
	{
		$random_int = $this->getFunctionMock('Friendica\Util', 'random_int');
		$random_int->expects($this->any())->willReturnCallback(function ($min, $max) {
			return 1;
		});

		self::assertSame('1', Crypto::randomDigits(1));
		self::assertSame('11111111', Crypto::randomDigits(8));
	}

	public function testEd25519PublicKeyFromMultibase(): void
	{
		// RFC 9421 appendix B.1.4 test-key-ed25519, as a multibase Multikey value
		$raw       = base64_decode('JrQLj5P/89iXES9+vFgrIy29clF9CC/oPPsw3c5D0bs=');
		$multibase = 'z6Mkh4LmfP1ev9MNPGr7JbEbtD6BD4fsu1duEj83PMCs3xHG';

		self::assertSame($raw, Crypto::ed25519PublicKeyFromMultibase($multibase));

		// Not an Ed25519 Multikey
		self::assertSame('', Crypto::ed25519PublicKeyFromMultibase(''));
		self::assertSame('', Crypto::ed25519PublicKeyFromMultibase('f6Mkh4LmfP1ev9MNPGr7JbEbtD6BD4fsu1duEj83PMCs3xHG'));
		self::assertSame('', Crypto::ed25519PublicKeyFromMultibase('z111111'));
		self::assertSame('', Crypto::ed25519PublicKeyFromMultibase('z6MkO0'));
	}

	public function testBase58Decode(): void
	{
		self::assertSame('', Crypto::base58Decode(''));
		self::assertSame('hello world', Crypto::base58Decode('StV1DL6CwTryKyV'));
		self::assertSame("\x00\x00abc", Crypto::base58Decode('11ZiCa'));
		self::assertSame('', Crypto::base58Decode('invalid_0OIl'));
	}

	public function testDiasporaPubRsaToMe(): void
	{
		$key = 'LS0tLS1CRUdJTiBSU0EgUFVCTElDIEtFWS0tLS0tDQpNSUdKQW9HQkFORjVLTmJzN2k3aTByNVFZckNpRExEZ09pU1BWbmgvdlFnMXpnSk9VZVRheWVETk5yZTR6T1RVDQpSVDcyZGlLQ294OGpYOE5paElJTFJtcUtTOWxVYVNzd21QcVNFenVpdE5xeEhnQy8xS2ZuaXM1Qm96NnRwUUxjDQpsZDMwQjJSMWZIVWdFTHZWd0JkV29pRDhSRUt1dFNuRVBGd1RwVmV6aVlWYWtNY25pclRWQWdNQkFBRT0NCi0tLS0tRU5EIFJTQSBQVUJMSUMgS0VZLS0tLS0';

		// TODO PHPUnit 10: Replace with assertStringEqualsStringIgnoringLineEndings()
		self::assertSame(
			str_replace("\n", "\r\n", <<< TXT
			-----BEGIN PUBLIC KEY-----
			MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDReSjW7O4u4tK+UGKwogyw4Dok
			j1Z4f70INc4CTlHk2sngzTa3uMzk1EU+9nYigqMfI1/DYoSCC0ZqikvZVGkrMJj6
			khM7orTasR4Av9Sn54rOQaM+raUC3JXd9AdkdXx1IBC71cAXVqIg/ERCrrUpxDxc
			E6VXs4mFWpDHJ4q01QIDAQAB
			-----END PUBLIC KEY-----
			TXT),
			Crypto::rsaToPem(base64_decode($key)),
		);
	}
}
