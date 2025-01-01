<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Network\Factory;

use Friendica\BaseFactory;
use Friendica\Network\Entity;

/**
 * Implementation of the Content-Type header value from the MIME type RFC
 *
 * @see https://www.rfc-editor.org/rfc/rfc2045#section-5
 */
class MimeType extends BaseFactory
{
	public function createFromContentType(?string $contentType): Entity\MimeType
	{
		if ($contentType) {
			$parameterStrings = explode(';', $contentType);
			$mimetype         = array_shift($parameterStrings);

			$types = explode('/', $mimetype);
			if (count($types) >= 2) {
				$filetype = strtolower($types[0]);
				$subtype  = strtolower($types[1]);
			} else {
				$this->logger->notice('Unknown MimeType', ['type' => $contentType]);
			}

			$parameters = [];
			foreach ($parameterStrings as $parameterString) {
				$parameterString = trim($parameterString);
				$parameterParts  = explode('=', $parameterString, 2);
				if (count($parameterParts) < 2) {
					continue;
				}

				$attribute   = trim($parameterParts[0]);
				$valueString = trim($parameterParts[1]);

				if ($valueString[0] == '"' && $valueString[strlen($valueString) - 1] == '"') {
					$valueString = substr(str_replace(['\\"', '\\\\', "\\\r"], ['"', '\\', "\r"], $valueString), 1, -1);
				}

				$value = preg_replace('#\s*\([^()]*?\)#', '', $valueString);

				$parameters[$attribute] = $value;
			}
		}

		return new Entity\MimeType(
			$filetype   ?? 'unkn',
			$subtype    ?? 'unkn',
			$parameters ?? [],
		);
	}
}
