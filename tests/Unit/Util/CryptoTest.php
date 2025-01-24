<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
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

	public function testRandomDigitsRandomInt()
	{
		$random_int = $this->getFunctionMock('Friendica\Util', 'random_int');
		$random_int->expects($this->any())->willReturnCallback(function ($min, $max) {
			return 12345678;
		});

		self::assertSame(12345678, Crypto::randomDigits(8));
	}

	public function testDiasporaPubRsaToMe()
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
			Crypto::rsaToPem(base64_decode($key))
		);
	}
}
