<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when a network name is being determined.
 *
 * Can be used by addons to modify the network names.
 */
final class NetworkToNameEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.network_to_name';

	/**
	 * @internal
	 *
	 * @param array<string, string> $networks the network names keyed by network name
	 */
	public function __construct(
		private array $networks,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array<string, string>
	 */
	public function getNetworks(): array
	{
		return $this->networks;
	}

	/**
	 * @param array<string, string> $networks
	 */
	public function setNetworks(array $networks): void
	{
		$this->networks = $networks;
	}
}
