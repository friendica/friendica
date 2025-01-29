<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Event;

/**
 * One-way Event to inform listener about something happend.
 *
 * @internal
 */
class Event implements NamedEvent
{
	/**
	 * Friendica is initialized.
	 */
	public const INIT = 'friendica.init';

	private string $name;

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function getName(): string
	{
		return $this->name;
	}
}
