<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Test\Unit\Security;

use DateTimeImmutable;
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Security\FormSecurityToken;
use Friendica\Util\Clock\FrozenClock;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the anonymous session only: a logged-in user adds guid and private key from the database to the hash.
 */
final class FormSecurityTokenTest extends TestCase
{
	private const NOW = 1_800_000_000;

	private function token(int $now = self::NOW): FormSecurityToken
	{
		$session = $this->createStub(IHandleUserSessions::class);
		$session->method('getLocalUserId')->willReturn(false);

		$l10n = $this->createStub(L10n::class);
		$l10n->method('t')->willReturnArgument(0);

		return new FormSecurityToken($session, new FrozenClock(new DateTimeImmutable('@' . $now)), $l10n);
	}

	public function testCreateKeepsTheLegacyFormat(): void
	{
		self::assertSame(
			self::NOW . '.' . hash('whirlpool', session_id() . self::NOW . 'calendar-view'),
			$this->token()->create('calendar-view'),
		);
	}

	public function testTokenIsValidForItsType(): void
	{
		$token = $this->token()->create('calendar-view');

		self::assertTrue($this->token()->isValid($token, 'calendar-view'));
		self::assertFalse($this->token()->isValid($token, 'settings'));
	}

	public function testTokenExpiresAfterThreeHours(): void
	{
		$token = $this->token()->create();

		self::assertTrue($this->token(self::NOW + 10800)->isValid($token));
		self::assertFalse($this->token(self::NOW + 10801)->isValid($token));
	}

	public static function malformedTokens(): array
	{
		return [
			'empty'          => [''],
			'no dot'         => [(string) self::NOW],
			'no timestamp'   => ['.abc'],
			'text timestamp' => ['abc.def'],
			'negative'       => ['-1.abc'],
			'wrong hash'     => [self::NOW . '.abc'],
			'magic hash'     => [self::NOW . '.0e1'],
		];
	}

	#[DataProvider('malformedTokens')]
	public function testMalformedTokenIsInvalid(string $token): void
	{
		self::assertFalse($this->token()->isValid($token));
	}

	public function testTamperedHashIsInvalid(): void
	{
		$token = $this->token()->create();

		self::assertFalse($this->token()->isValid(substr($token, 0, -1) . (str_ends_with($token, '0') ? '1' : '0')));
	}

	public function testRequestTokenFromFormField(): void
	{
		$token = $this->token()->create('form');

		self::assertTrue($this->token()->isRequestValid([FormSecurityToken::FIELD_NAME => $token], [], 'form'));
		self::assertTrue($this->token()->isRequestValid(['t' => $token], [], 'form', 't'));
		self::assertFalse($this->token()->isRequestValid(['t' => $token], [], 'form'));
	}

	public function testRequestHeaderTakesPrecedence(): void
	{
		$token = $this->token()->create('form');

		self::assertTrue($this->token()->isRequestValid([FormSecurityToken::FIELD_NAME => 'invalid'], [FormSecurityToken::HEADER_NAME => $token], 'form'));
		self::assertFalse($this->token()->isRequestValid([FormSecurityToken::FIELD_NAME => $token], [FormSecurityToken::HEADER_NAME => 'invalid'], 'form'));
	}

	public static function missingRequestTokens(): array
	{
		return [
			'missing' => [[]],
			'empty'   => [[FormSecurityToken::FIELD_NAME => '']],
			'array'   => [[FormSecurityToken::FIELD_NAME => ['x']]],
		];
	}

	#[DataProvider('missingRequestTokens')]
	public function testMissingRequestTokenIsInvalid(array $request): void
	{
		self::assertFalse($this->token()->isRequestValid($request, []));
	}

	public function testErrorMessageIsTranslated(): void
	{
		self::assertStringStartsWith('The form security token was not correct.', $this->token()->getErrorMessage());
	}
}
