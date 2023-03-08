<?php

return [
	'type_test' => [
		'bool_true' => true,
		'bool_false' => false,
		'int_1' => 1,
		'int_0' => 2,
		'int_12345' => 12345,
		'float' => 1.234,
		'double_E+' => 1.24E+20,
		'double_E-' => 7.0E-10,
		'null' => null,
		'array' => [1, '2', '3', 4.0E-10, 12345, 0, false, 'true', true],
		'array_keys' => [
			'int_1' => 1,
			'string_2' => '2',
			'string_3' => '3',
			'double' => 4.0E-10,
			'int' => 12345,
			'int_0' => 0,
			'false' => false,
			'string_true' => 'true',
			'true' => true,
		],
		'array_extended' => [
			[
				'key_1' => 'value_1',
				'key_2' => 'value_2',
				'key_3' => [
					'inner_key' => 'inner_value',
				],
			],
			[
				'key_2' => false,
				'0' => [
					'is_that' => true,
					'0' => [
						'working' => '?',
					],
				],
				'inner_array' => [
					[
						'key' => 'value',
						'key2' => 12,
					],
				],
				'key_3' => true,
			],
			['value', 'value2'],
			[
				[
					'key' => 123,
				],
				'test',
				'test52',
				'test23',
				[
					'key' => 456,
				],
			],
		],
	],
	'other_cat' => [
		'key' => 'value',
	],
	'other_cat2' => [
		[
			'key' => 'value',
		],
		[
			'key2' => 'value2',
		],
	],
];
