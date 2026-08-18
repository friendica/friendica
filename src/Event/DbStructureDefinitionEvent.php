<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired after the database structure definition has been loaded, to allow addons to add, change or remove tables.
 */
final class DbStructureDefinitionEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.db_structure_definition';

	/**
	 * @internal
	 *
	 * @param array<string, array{comment?: string, fields?: array<string, array>, indexes?: array}> $definition The database structure definition
	 */
	public function __construct(
		private array $definition,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array<string, array{comment?: string, fields?: array<string, array>, indexes?: array}> The database structure definition
	 */
	public function getDefinitionArray(): array
	{
		return $this->definition;
	}

	/**
	 * @param array<string, array{comment?: string, fields?: array<string, array>, indexes?: array}> $definition The database structure definition
	 */
	public function setDefinitionArray(array $definition): void
	{
		$this->definition = $definition;
	}
}
