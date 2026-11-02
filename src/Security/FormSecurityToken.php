<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security;

use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\User;
use Psr\Clock\ClockInterface;

/**
 * Creates and checks the security tokens that protect forms against Cross-Site Request Forgery.
 *
 * The token is based on values an attacker can't know: the session ID and the user's private key.
 * A token can be reused (resubmitted forms, repeated ajax calls), but only within its lifetime.
 * The type name separates the tokens of different forms, so a token leaked through a link only works for that form type.
 */
final readonly class FormSecurityToken
{
	/** Default name of the form field that carries the token */
	public const FIELD_NAME = 'form_security_token';
	/** Server variable of the X-CSRF-Token header, it takes precedence over the form field */
	public const HEADER_NAME = 'HTTP_X_CSRF_TOKEN';
	/** Lifetime of a token in seconds */
	private const LIFETIME = 10800;

	public function __construct(
		private IHandleUserSessions $session,
		private ClockInterface $clock,
		private L10n $l10n,
	) {}

	/**
	 * Returns a new token for the given form type, in the format "<timestamp>.<hash>".
	 */
	public function create(string $typename = ''): string
	{
		$timestamp = (string) $this->clock->now()->getTimestamp();

		return $timestamp . '.' . $this->sign($timestamp, $typename);
	}

	/**
	 * Checks a token for the given form type.
	 */
	public function isValid(string $token, string $typename = ''): bool
	{
		$parts = explode('.', $token, 2);
		if (count($parts) !== 2 || !ctype_digit($parts[0])) {
			return false;
		}

		if ($this->clock->now()->getTimestamp() > (int) $parts[0] + self::LIFETIME) {
			return false;
		}

		return hash_equals($this->sign($parts[0], $typename), $parts[1]);
	}

	/**
	 * Checks the token sent with a request, either in the X-CSRF-Token header or in the given form field.
	 *
	 * @param array $request The request parameters
	 * @param array $server  The server variables
	 */
	public function isRequestValid(array $request, array $server, string $typename = '', string $fieldName = self::FIELD_NAME): bool
	{
		$token = $request[$fieldName] ?? '';
		if (!empty($server[self::HEADER_NAME])) {
			$token = $server[self::HEADER_NAME];
		}

		if (!is_string($token) || $token === '') {
			return false;
		}

		return $this->isValid($token, $typename);
	}

	/**
	 * Returns the message shown to the user when a token check failed.
	 */
	public function getErrorMessage(): string
	{
		return $this->l10n->t('The form security token was not correct. This probably happened because the form has been opened for too long (>3 hours) before submitting it.');
	}

	private function sign(string $timestamp, string $typename): string
	{
		$user = User::getById((int) $this->session->getLocalUserId(), ['guid', 'prvkey']) ?: [];

		return hash('whirlpool', ($user['guid'] ?? '') . ($user['prvkey'] ?? '') . session_id() . $timestamp . $typename);
	}
}
