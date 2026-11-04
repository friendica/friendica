<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Security\LoginCode\Repository;

use Friendica\Core\Cache\Capability\ICanCache;
use Friendica\Security\LoginCode\Factory\PendingCode as PendingCodeFactory;
use Friendica\Security\LoginCode\ValueObject\PendingCode as PendingCodeObject;
use Friendica\Security\LoginCode\ValueObject\Ticket;

/**
 * Keeps the pending code of an account in the cache, at most one per session and account.
 */
class PendingCode
{
	public function __construct(
		private readonly ICanCache $cache,
		private readonly PendingCodeFactory $factory,
	) {}

	public function selectByTicket(Ticket $ticket): ?PendingCodeObject
	{
		$entry = $this->cache->get($this->key($ticket->uid, $ticket->nonce));
		if (!is_array($entry) || !is_string($entry['nonce'] ?? null) || !is_string($entry['hash'] ?? null)) {
			return null;
		}

		return $this->factory->createFromCacheEntry($ticket->uid, $entry);
	}

	/**
	 * Replaces the pending code of the session.
	 */
	public function save(PendingCodeObject $code, int $ttl): void
	{
		$this->cache->set($this->key($code->uid, $code->nonce), ['nonce' => $code->nonce, 'hash' => $code->hash], $ttl);
	}

	public function deleteByTicket(Ticket $ticket): void
	{
		$this->cache->delete($this->key($ticket->uid, $ticket->nonce));
	}

	private function key(int $uid, string $nonce): string
	{
		return 'login-code:' . $uid . ':' . $nonce;
	}
}
