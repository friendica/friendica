<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * This event is fired when the jot networks are being built.
 *
 * Can be used by addons to modify the jot networks fields.
 */
final class JotNetworksEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.jot_networks';

	/**
	 * @internal
	 *
	 * @param array<int, array> $jotnets_fields
	 */
	public function __construct(
		private array $jotnets_fields,
	) {
		parent::__construct(self::NAME);
	}

	/** @return array<int, array> */
	public function getJotnetsFields(): array
	{
		return $this->jotnets_fields;
	}

	/** @param array<int, array> $jotnets_fields */
	public function setJotnetsFields(array $jotnets_fields): void
	{
		$this->jotnets_fields = $jotnets_fields;
	}
}
