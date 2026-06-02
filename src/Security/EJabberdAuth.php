<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

/*
 * Originally written for joomla by Dalibor Karlovic <dado@krizevci.info>
 * modified for Friendica by Michael Vogel <icarus@dabo.de>
 * published under GPL
 */

declare(strict_types=1);

namespace Friendica\Security;

use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Database\Database;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Network\HTTPException\ForbiddenException;
use Throwable;

/**
 * Handles user existence checks and password authentication for the ejabberd external auth protocol.
 *
 * This class contains only business logic and has no knowledge of the binary wire protocol,
 * I/O streams, or the console command lifecycle. It is intentionally thin and fully injectable
 * so it can be unit-tested in isolation.
 */
class EJabberdAuth
{
	/** @var int HTTP status code that indicates success for the remote checks */
	private const HTTP_OK = 200;

	/** @var int Default timeout in seconds for outgoing HTTP requests */
	private const DEFAULT_HTTP_TIMEOUT = 5;

	public function __construct(
		private readonly IManageConfigValues $config,
		private readonly IManagePersonalConfigValues $pConfig,
		private readonly Database $dba,
		private readonly BaseURL $baseURL,
		private readonly ICanSendHttpRequests $httpClient,
	) {}

	/**
	 * Returns whether the underlying database connection is still alive.
	 *
	 * The long-running daemon uses this to detect a dropped connection: without a database
	 * every authentication would silently fail (look like a wrong password), so the daemon
	 * should rather quit and let ejabberd respawn a fresh worker.
	 */
	public function isConnected(): bool
	{
		return $this->dba->isConnected();
	}

	/**
	 * Returns true if the user exists on the given server.
	 *
	 * Checks the local database when the server matches the configured base URL,
	 * then falls back to a remote /noscrape lookup.
	 */
	public function isUser(string $username, string $server): bool
	{
		$username = $this->normaliseNick($username);

		// For our own host the local database is authoritative: a missing user is simply
		// unknown. We deliberately do NOT fall back to a remote lookup against ourselves,
		// which would be a wasted HTTP round-trip on every check (ejabberd calls isuser a lot).
		if ($this->baseURL->getHost() === $server) {
			return $this->dba->exists('user', ['nickname' => $username]);
		}

		return $this->checkUserRemote($username, $server);
	}

	/**
	 * Returns true if the supplied password is correct for the given user on the given server.
	 *
	 * For the local server: tries the primary password, then a per-user XMPP application password.
	 * For remote servers: falls back to an HTTP credential check.
	 */
	public function authenticate(string $username, string $server, string $password): bool
	{
		$username = $this->normaliseNick($username);

		if ($this->baseURL->getHost() === $server) {
			return $this->authenticateLocal($username, $password);
		}

		return $this->checkCredentialsRemote($server, $username, $password);
	}

	/**
	 * Normalizes a nick: replaces the ejabberd %-escapes back to their original characters
	 * and lowercases the result to match Friendica's stored nickname format.
	 * "%20" → " ", "(a)" → "@"
	 */
	private function normaliseNick(string $nick): string
	{
		return strtolower(str_replace(['%20', '(a)'], [' ', '@'], $nick));
	}

	private function authenticateLocal(string $username, string $password): bool
	{
		try {
			User::getIdFromPasswordAuthentication($username, $password, true);
			return true;
		} catch (ForbiddenException) {
			// User exists but primary password was wrong — try the per-user XMPP password.
			return $this->checkXmppPassword($username, $password);
		} catch (Throwable) {
			return false;
		}
	}

	private function checkXmppPassword(string $username, string $password): bool
	{
		$user = User::getByNickname($username, ['uid']);
		if (empty($user['uid'])) {
			return false;
		}

		// pConfig wraps any "password" key in a ParagonIE HiddenString to keep it out of logs,
		// so we must cast to string before comparing. A missing value yields null → no match.
		$xmppPassword = $this->pConfig->get($user['uid'], 'xmpp', 'password', null, true);
		if ($xmppPassword === null) {
			return false;
		}

		return hash_equals((string) $xmppPassword, $password);
	}

	/**
	 * Checks whether a user exists on a remote Friendica server via the /noscrape endpoint.
	 */
	private function checkUserRemote(string $user, string $host): bool
	{
		try {
			$result = $this->httpClient->get(
				'https://' . $host . '/noscrape/' . $user,
				HttpClientAccept::JSON,
				$this->httpOptions(),
			);
		} catch (Throwable) {
			return false;
		}

		if (!$result->isSuccess() || (int) $result->getReturnCode() !== self::HTTP_OK) {
			return false;
		}

		$json = @json_decode($result->getBodyString());
		return is_object($json) && isset($json->nick) && $json->nick === $user;
	}

	/**
	 * Verifies credentials against a remote Friendica server via the Mastodon-compatible
	 * verify_credentials endpoint.
	 */
	private function checkCredentialsRemote(string $host, string $user, string $password): bool
	{
		try {
			$result = $this->httpClient->head(
				'https://' . $host . '/api/account/verify_credentials.json?skip_status=true',
				$this->httpOptions([HttpClientOptions::AUTH => [$user, $password]]),
			);
		} catch (Throwable) {
			return false;
		}

		return $result->isSuccess() && (int) $result->getReturnCode() === self::HTTP_OK;
	}

	/**
	 * Builds the HTTP client options shared by every remote check: the contact-verifier
	 * user agent and the bounded timeout (the single source for both). Any extra options,
	 * e.g. basic-auth credentials, are merged on top.
	 *
	 * The timeout must stay well below ejabberd's own extauth call timeout (~30 s) so that a
	 * slow remote host cannot keep a pooled auth worker busy long enough to be orphaned by
	 * the ejabberd supervisor.
	 *
	 * @param array $extra additional HttpClientOptions to merge in
	 */
	private function httpOptions(array $extra = []): array
	{
		return [
			HttpClientOptions::REQUEST => HttpClientRequest::CONTACTVERIFIER,
			HttpClientOptions::TIMEOUT => (int) $this->config->get('jabber', 'auth_http_timeout', self::DEFAULT_HTTP_TIMEOUT),
		] + $extra;
	}
}
