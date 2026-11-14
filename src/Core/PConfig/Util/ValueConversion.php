<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
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
	 */
	public static function toConfigValue(?string $value): mixed
	{
		if (!isset($value)) {
			return null;
		}

		// CWE-502: Other classes are explicitly disallowed from unserialize to prevent malicious exploitation
		return preg_match("|^a:[0-9]+:{.*}$|s", $value)
			? unserialize($value, ['allowed_classes' => [\stdClass::class]])
			: $value;
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
