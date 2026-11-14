<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security;

use Friendica\DI;
use Friendica\Network\HTTPException\ForbiddenException;

/**
 * Static access to the form security token for the static helpers in BaseModule.
 *
 * It keeps the DI:: calls out of BaseModule, so its subclasses don't inherit a dependency on the DI container.
 * New code injects FormSecurityToken instead.
 */
final class LegacyFormSecurityToken
{
	public static function create(string $typename = ''): string
	{
		return DI::formSecurityToken()->create($typename);
	}

	public static function check(string $typename = '', string $fieldName = FormSecurityToken::FIELD_NAME): bool
	{
		return DI::formSecurityToken()->isRequestValid($_REQUEST, $_SERVER, $typename, $fieldName);
	}

	public static function getErrorMessage(): string
	{
		return DI::formSecurityToken()->getErrorMessage();
	}

	/**
	 * Adds a notice and redirects to the given path when the token check fails.
	 */
	public static function checkRedirectOnError(string $errorRedirect, string $typename = '', string $fieldName = FormSecurityToken::FIELD_NAME): void
	{
		if (self::check($typename, $fieldName)) {
			return;
		}

		self::logFailure($typename);
		DI::sysmsg()->addNotice(self::getErrorMessage());
		DI::baseUrl()->redirect($errorRedirect);
	}

	/**
	 * Throws a ForbiddenException when the token check fails.
	 */
	public static function checkForbiddenOnError(string $typename = '', string $fieldName = FormSecurityToken::FIELD_NAME): void
	{
		if (self::check($typename, $fieldName)) {
			return;
		}

		self::logFailure($typename);

		throw new ForbiddenException();
	}

	private static function logFailure(string $typename): void
	{
		DI::logger()->notice('checkFormSecurityToken failed: user ' . DI::userSession()->getLocalUserNickname() . ' - form element ' . $typename);
		DI::logger()->debug('checkFormSecurityToken failed', ['request' => $_REQUEST]);
	}
}
