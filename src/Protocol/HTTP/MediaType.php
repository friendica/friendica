<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol\HTTP;

/**
 * @see https://httpwg.org/specs/rfc9110.html#media.type
 *
 * @property-read string $type
 * @property-read string $subType
 * @property-read string $parameters
 */
final class MediaType
{
	public const DQUOTE = '"';
	public const DIGIT  = '0-9';
	public const ALPHA  = 'a-zA-Z';

	// @see https://www.charset.org/charsets/us-ascii
	public const VCHAR = "\\x21-\\x7E";

	public const SYMBOL_NO_DELIM = "!#$%&'*+-.^_`|~";

	public const OBSTEXT = "\\x80-\\xFF";

	public const QDTEXT = "\t \\x21\\x23-\\x5B\\x5D-\\x7E" . self::OBSTEXT;

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $subType;

	/**
	 * @var string[]
	 */
	private $parameters;

	public function __construct(string $type, string $subType, array $parameters = [])
	{
		if (!self::isToken($type)) {
			throw new \InvalidArgumentException("Type isn't a valid token: " . $type);
		}

		if (!self::isToken($subType)) {
			throw new \InvalidArgumentException("Subtype isn't a valid token: " . $subType);
		}

		foreach ($parameters as $key => $value) {
			if (!self::isToken($key)) {
				throw new \InvalidArgumentException("Parameter key isn't a valid token: " . $key);
			}

			if (!self::isToken($value) && !self::isQuotableString($value)) {
				throw new \InvalidArgumentException("Parameter value isn't a valid token or a quotable string: " . $value);
			}
		}

		$this->type       = $type;
		$this->subType    = $subType;
		$this->parameters = $parameters;
	}

	public function __get(string $name)
	{
		if (!isset($this->$name)) {
			throw new \InvalidArgumentException('Unknown property ' . $name);
		}

		return $this->$name;
	}

	public static function fromContentType(string $contentType): self
	{
		if (!$contentType) {
			throw new \InvalidArgumentException('Provided string is empty');
		}

		$parts         = explode(';', $contentType);
		$mimeTypeParts = explode('/', trim(array_shift($parts)));
		if (count($mimeTypeParts) !== 2) {
			throw new \InvalidArgumentException('Provided string doesn\'t look like a MIME type: ' . $contentType);
		}

		[$type, $subType] = $mimeTypeParts;

		$parameters = [];
		foreach ($parts as $parameterString) {
			if (!trim($parameterString)) {
				continue;
			}

			$parameterParts = explode('=', trim($parameterString));

			if (count($parameterParts) < 2) {
				throw new \InvalidArgumentException('Parameter lacks a value: ' . $parameterString);
			}

			if (count($parameterParts) > 2) {
				throw new \InvalidArgumentException('Parameter has too many values: ' . $parameterString);
			}

			[$key, $value] = $parameterParts;

			if (!self::isToken($value) && !self::isQuotedString($value)) {
				throw new \InvalidArgumentException("Parameter value isn't a valid token or a quoted string: \"" . $value . '"');
			}

			if (self::isQuotedString($value)) {
				$value = self::extractQuotedStringValue($value);
			}

			// Parameter keys are case-insensitive, values are not
			$parameters[strtolower($key)] = $value;
		}

		return new self($type, $subType, $parameters);
	}

	public function __toString(): string
	{
		$parameters = $this->parameters;

		array_walk($parameters, function (&$value, $key) {
			$value = '; ' . $key . '=' . (self::isToken($value) ? $value : '"' . addcslashes($value, '"\\') . '"');
		});

		return $this->type . '/' . $this->subType . implode($parameters);
	}

	/**
	 * token          = 1*tchar
	 * tchar          = "!" / "#" / "$" / "%" / "&" / "'" / "*"
	 *                / "+" / "-" / "." / "^" / "_" / "`" / "|" / "~"
	 *                / DIGIT / ALPHA
	 *                ; any VCHAR, except delimiters
	 *
	 * @see https://httpwg.org/specs/rfc9110.html#tokens
	 *
	 * @param string $string
	 * @return false|int
	 */
	private static function isToken(string $string)
	{
		$symbol = preg_quote(self::SYMBOL_NO_DELIM, '/');
		$digit  = self::DIGIT;
		$alpha  = self::ALPHA;

		$pattern = "/^[$symbol$digit$alpha]+$/";

		return preg_match($pattern, $string);
	}

	/**
	 * quoted-string  = DQUOTE *( qdtext / quoted-pair ) DQUOTE
	 * qdtext         = HTAB / SP / %x21 / %x23-5B / %x5D-7E / obs-text
	 *
	 * @see https://httpwg.org/specs/rfc9110.html#quoted.strings
	 *
	 * @param string $string
	 * @return bool
	 */
	private static function isQuotedString(string $string): bool
	{
		$dquote = self::DQUOTE;

		$vchar = self::VCHAR;

		$obsText = self::OBSTEXT;

		$qdtext = '[' . self::QDTEXT . ']';

		$quotedPair = "\\\\[\t $vchar$obsText]";

		$pattern = "/^$dquote(?:$qdtext|$quotedPair)*$dquote$/";

		return preg_match($pattern, $string);
	}

	/**
	 * Is the string an extracted quoted string value?
	 *
	 * @param string $string
	 * @return bool
	 */
	private static function isQuotableString(string $string): bool
	{
		$vchar = self::VCHAR;

		$obsText = self::OBSTEXT;

		$qdtext = '[' . self::QDTEXT . ']';

		$quotedSingle = "[\t $vchar$obsText]";

		$pattern = "/^(?:$qdtext|$quotedSingle)*$/";

		return preg_match($pattern, $string);
	}

	/**
	 * Extracts the value from a quoted-string, removing quoted pairs
	 *
	 * @param string $value
	 * @return string
	 */
	private static function extractQuotedStringValue(string $value): string
	{
		return preg_replace_callback('/^"(.*)"$/', function ($matches) {
			$vchar   = self::VCHAR;
			$obsText = self::OBSTEXT;
			return preg_replace("/\\\\([\t $vchar$obsText])/", '$1', $matches[1]);
		}, $value);
	}
}
