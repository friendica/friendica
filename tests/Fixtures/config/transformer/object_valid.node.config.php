<?php

// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use Friendica\Test\Util\SerializableObjectDouble;
use ParagonIE\HiddenString\HiddenString;

return [
	'object' => [
		'toString' => new HiddenString('test'),
		'serializable' => new SerializableObjectDouble(),
	],
];
