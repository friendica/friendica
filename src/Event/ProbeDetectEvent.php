<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired before trying to detect the target network of a URI.
 *
 * Can be used by addons to handle the probe for a foreign network by setting
 * the resulting contact array or reporting an unsuccessful probe.
 */
final class ProbeDetectEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.probe_detect';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $uri,
		private readonly string $network,
		private readonly int $uid,
		private array|false|null $result = null,
	) {
		parent::__construct(self::NAME);
	}

	public function getUri(): string
	{
		return $this->uri;
	}

	public function getNetwork(): string
	{
		return $this->network;
	}

	public function getUid(): int
	{
		return $this->uid;
	}

	public function getResult(): array|false|null
	{
		return $this->result;
	}

	public function setResult(array|false|null $result): void
	{
		$this->result = $result;
	}
}
