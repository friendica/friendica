<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\LoginCode;

use Friendica\Security\Account\Repository\Account;
use Friendica\Security\LoginCode\Factory\PendingCode as PendingCodeFactory;
use Friendica\Security\LoginCode\Repository\PendingCode as PendingCodeRepository;
use Friendica\Security\LoginCode\ValueObject\Ticket;
use Friendica\Security\Throttle\AttemptLimiter;
use Friendica\Security\Throttle\ValueObject\Limit;
use Psr\Log\LoggerInterface;

/**
 * Sign in with a one time code that is sent by e-mail.
 *
 * A code is bound to the ticket of the request that created it and can only be used once.
 */
final readonly class EmailCodeLogin
{
	/** Lifetime of a code in seconds */
	public const CODE_TTL = 600;
	/** Minimum time between two code requests for the same account or from the same address in seconds */
	public const REQUEST_INTERVAL = 60;
	/** Failed attempts per account within FAILURE_WINDOW after which no code is issued or accepted */
	public const MAX_FAILURES = 10;
	/** Time window for counting failed attempts in seconds */
	public const FAILURE_WINDOW = 86400;

	private const SCOPE       = 'login-code';
	private const CODE_LENGTH = 6;
	private const NONCE_BYTES = 16;

	private Limit $limit;

	public function __construct(
		private Account $accounts,
		private PendingCodeRepository $pendingCodes,
		private PendingCodeFactory $pendingCodeFactory,
		private AttemptLimiter $limiter,
		private CodeMail $mail,
		private LoggerInterface $logger,
	) {
		$this->limit = new Limit(self::SCOPE, self::MAX_FAILURES, self::FAILURE_WINDOW, self::REQUEST_INTERVAL);
	}

	/**
	 * Sends a new code to the account and replaces any pending one.
	 *
	 * The result doesn't reveal whether the account exists or the request was throttled: a ticket is always returned.
	 *
	 * @param string $login Nickname or e-mail address
	 */
	public function request(string $login, string $remoteAddress): Ticket
	{
		$account = $this->accounts->selectByLogin($login);
		if ($account === null) {
			$this->logger->notice('Login code requested for unknown account', ['ip' => $remoteAddress]);
			return new Ticket(Ticket::UNKNOWN_ACCOUNT, $this->newNonce());
		}

		$ticket = new Ticket($account->uid, $this->newNonce());

		if (!$this->limiter->tryRequest($this->limit, $remoteAddress)) {
			$this->logger->notice('Login code requested too often from this address', ['uid' => $ticket->uid, 'ip' => $remoteAddress]);
			return $ticket;
		}

		if ($this->limiter->isLockedOut($this->limit, $ticket->uid) || !$this->limiter->tryRequest($this->limit, 'uid:' . $ticket->uid)) {
			$this->logger->notice('Login code requested too often or account throttled', ['uid' => $ticket->uid, 'ip' => $remoteAddress]);
			return $ticket;
		}

		$code = str_pad((string) random_int(0, 10 ** self::CODE_LENGTH - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);
		$this->pendingCodes->save($this->pendingCodeFactory->createForTicket($ticket, $code), self::CODE_TTL);

		$this->mail->send($account, $code, intdiv(self::CODE_TTL, 60));
		$this->logger->info('Login code sent', ['uid' => $ticket->uid, 'ip' => $remoteAddress]);

		return $ticket;
	}

	/**
	 * Checks the code and consumes it on success.
	 */
	public function verify(Ticket $ticket, #[\SensitiveParameter] string $code, string $remoteAddress): bool
	{
		$valid = $ticket->uid > 0 && $ticket->nonce !== ''
			&& $this->limiter->attempt($this->limit, $ticket->uid, fn (): bool => $this->consume($ticket, $code));

		if ($valid) {
			$this->logger->info('Login with code', ['uid' => $ticket->uid, 'ip' => $remoteAddress]);
		} else {
			$this->logger->notice('Login with invalid code', ['uid' => $ticket->uid, 'ip' => $remoteAddress]);
		}

		return $valid;
	}

	public function isLockedOut(int $uid): bool
	{
		return $this->limiter->isLockedOut($this->limit, $uid);
	}

	/**
	 * Runs under the account lock of the limiter, so a code can't be used twice.
	 */
	private function consume(Ticket $ticket, #[\SensitiveParameter] string $code): bool
	{
		$pending = $this->pendingCodes->selectByUid($ticket->uid);
		if (!$pending?->equals($this->pendingCodeFactory->createForTicket($ticket, $code))) {
			return false;
		}

		$this->pendingCodes->deleteByUid($ticket->uid);
		return true;
	}

	private function newNonce(): string
	{
		return bin2hex(random_bytes(self::NONCE_BYTES));
	}
}
