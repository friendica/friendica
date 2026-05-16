<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\Entity;

use Friendica\BaseEntity;

/**
 * Implementation of the Content-Type header value from the MIME type RFC
 *
 * @see https://www.rfc-editor.org/rfc/rfc2045#section-5
 *
 * @property-read string $type
 * @property-read string $subtype
 * @property-read array $parameters
 */
class MimeType extends BaseEntity implements \Stringable
{
	/** @var string */
	protected $type;
	/** @var string */
	protected $subtype;
	/** @var array */
	protected $parameters;

	public function __construct(string $type, string $subtype, array $parameters = [])
	{
		$this->type       = $type;
		$this->subtype    = $subtype;
		$this->parameters = $parameters;
	}

	public function __toString(): string
	{
		$parameters = array_map(function (string $attribute, string $value) {
			if (
				str_contains($value, '"')
				|| str_contains($value, '\\')
				|| str_contains($value, "\r")
			) {
				$value = '"' . str_replace(['\\', '"', "\r"], ['\\\\', '\\"', "\\\r"], $value) . '"';
			}

			return '; ' . $attribute . '=' . $value;
		}, array_keys($this->parameters), array_values($this->parameters));

		return $this->type . '/'
			. $this->subtype
			. implode('', $parameters);
	}
}
