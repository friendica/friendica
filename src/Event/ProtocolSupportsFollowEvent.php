<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired to assert whether a connector addon provides follow capabilities.
 *
 * Can be used by addons to report that they provide follow capabilities for
 * the given protocol.
 */
final class ProtocolSupportsFollowEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.protocol_supports_follow';

	/**
	 * @internal
	 */
	public function __construct(
		private readonly string $protocol,
		private ?bool $result = null,
	) {
		parent::__construct(self::NAME);
	}

	public function getProtocol(): string
	{
		return $this->protocol;
	}

	public function getResult(): ?bool
	{
		return $this->result;
	}

	public function setResult(?bool $result): void
	{
		$this->result = $result;
	}
}
