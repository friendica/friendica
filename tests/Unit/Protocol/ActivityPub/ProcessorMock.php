<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Test\Unit\Protocol\ActivityPub;

use Friendica\Protocol\ActivityPub\Processor;

/**
 * Class ProcessorMock
 *
 * Exposes protected methods for test in the inherited class
 *
 * @method static string addMentionLinks(string $body, array $tags)
 * @method static string normalizeMentionLinks(string $body)
 */
class ProcessorMock extends Processor
{
	public static function __callStatic($name, $arguments)
	{
		return self::$name(...$arguments);
	}
}
