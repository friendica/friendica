<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

use Friendica\Core\Event\AbstractEvent;

/**
 * Fired after the database view definition has been loaded, to allow addons to add, change or remove views.
 */
final class DbViewDefinitionEvent extends AbstractEvent
{
	public const NAME = 'friendica.data.db_view_definition';

	/**
	 * @internal
	 *
	 * @param array<string, array{fields?: array, query?: string}> $definition The database view definition
	 */
	public function __construct(
		private array $definition,
	) {
		parent::__construct(self::NAME);
	}

	/**
	 * @return array<string, array{fields?: array, query?: string}> The database view definition
	 */
	public function getDefinitionArray(): array
	{
		return $this->definition;
	}

	/**
	 * @param array<string, array{fields?: array, query?: string}> $definition The database view definition
	 */
	public function setDefinitionArray(array $definition): void
	{
		$this->definition = $definition;
	}
}
