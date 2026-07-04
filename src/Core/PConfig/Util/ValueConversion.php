<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\PConfig\Util;

/**
 * Util class to help to convert from/to (p)config values
 */
class ValueConversion
{
	/**
	 * Formats a DB value to a config value
	 * - null   = The db-value isn't set
	 * - bool   = The db-value is either '0' or '1'
	 * - array  = The db-value is a serialized array
	 * - string = The db-value is a string
	 *
	 * Keep in mind that there aren't any numeric/integer config values in the database
	 *
	 * @param string|null $value
	 *
	 * @return null|array|string
	 */
	public static function toConfigValue(?string $value)
	{
		if (!isset($value)) {
			return null;
		}

		return match (true) {
			preg_match("|^a:[0-9]+:{.*}$|s", $value) === 1 => unserialize($value),
			default                                        => $value,
		};
	}

	/**
	 * Formats a config value to a DB value (string)
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function toDbValue($value): string
	{
		// if not set, save an empty string
		if (!isset($value)) {
			return '';
		}

		return match (true) {
			is_array($value) => serialize($value),
			default          => (string) $value,
		};
	}
}
