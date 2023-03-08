<?php

use Friendica\Test\Util\SerializableObjectDouble;
use ParagonIE\HiddenString\HiddenString;

return [
	'object' => [
		'toString' => new HiddenString('test'),
		'serializable' => new SerializableObjectDouble(),
	],
];
