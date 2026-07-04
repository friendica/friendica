<?php

// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

return [
		'config' => [
				'hostname' => 'localhost',
		],
		'system' => [
				'url' => 'http://localhost',
				"worker_dont_fork" => 1,
				"curl_timeout"=>  1,
				"xrd_timeout"=>  1,
		],
];
