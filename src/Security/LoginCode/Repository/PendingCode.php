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

/**
 * Keeps the pending code of an account in the cache, at most one per account.
 */
class PendingCode
{
	public function __construct(
		private readonly ICanCache $cache,
		private readonly PendingCodeFactory $factory,
	) {}

	public function selectByUid(int $uid): ?PendingCodeObject
	{
		$entry = $this->cache->get($this->key($uid));
		if (!is_array($entry) || !is_string($entry['nonce'] ?? null) || !is_string($entry['hash'] ?? null)) {
			return null;
		}

		return $this->factory->createFromCacheEntry($uid, $entry);
	}

	/**
	 * Replaces the pending code of the account.
	 */
	public function save(PendingCodeObject $code, int $ttl): void
	{
		$this->cache->set($this->key($code->uid), ['nonce' => $code->nonce, 'hash' => $code->hash], $ttl);
	}

	public function deleteByUid(int $uid): void
	{
		$this->cache->delete($this->key($uid));
	}

	private function key(int $uid): string
	{
		return 'login-code:' . $uid;
	}
}
