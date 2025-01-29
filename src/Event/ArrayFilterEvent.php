<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

/**
 * Allow Event listener to modify an array.
 *
 * @internal
 */
final class ArrayFilterEvent extends Event
{
	public const APP_MENU = 'friendica.data.app_menu';

	public const NAV_INFO = 'friendica.data.nav_info';

	private array $array;

	public function __construct(string $name, array $array)
	{
		parent::__construct($name);

		$this->array = $array;
	}

	public function getArray(): array
	{
		return $this->array;
	}

	public function setArray(array $array): void
	{
		$this->array = $array;
	}
}
