<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

// Router for the built-in PHP web server used by HTTPClientTest

switch (parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
	case '/redirect':
		header('Location: /final', true, 302);
		echo 'redirect body that is longer than the final body';
		break;

	case '/slow':
		// 20 MB in 10 seconds
		header('Content-Length: 20000000');
		for ($i = 0; $i < 20; $i++) {
			echo str_repeat('x', 1000000);
			flush();
			usleep(500000);
		}
		break;

	case '/big':
		// 3 MB with Content-Length
		header('Content-Length: 3000000');
		echo str_repeat('x', 3000000);
		break;

	case '/big-unannounced':
		// 3 MB without Content-Length
		for ($i = 0; $i < 3; $i++) {
			echo str_repeat('x', 1000000);
			flush();
		}
		break;

	case '/redirect-big':
		// Each body stays below 1 MB, both together exceed it
		header('Location: /final-big', true, 302);
		echo str_repeat('r', 600000);
		break;

	case '/final-big':
		echo str_repeat('f', 600000);
		break;

	case '/redirect-loopback':
		// Same server under another loopback name, which is not in allowed_internal_hosts
		header('Location: http://localhost:' . $_SERVER['SERVER_PORT'] . '/hit', true, 302);
		break;

	case '/og':
		echo '<html><head><meta property="og:url" content="/final-og"><link rel="canonical" href="/final-canonical"></head></html>';
		break;

	case '/canonical':
		echo '<html><head><link rel="canonical" href="/final-canonical"></head></html>';
		break;

	case '/og-loopback':
		// og:url into the private network under another loopback name
		echo '<html><head><meta property="og:url" content="http://localhost:' . $_SERVER['SERVER_PORT'] . '/hit"></head></html>';
		break;

	case '/og-big':
		// og:url in the head of a page above max_response_data_size
		echo '<html><head><meta property="og:url" content="/final-og"></head><body>';
		flush();
		for ($i = 0; $i < 3; $i++) {
			echo str_repeat('x', 1000000);
			flush();
		}
		break;

	case '/redirect-image':
		header('Location: /image', true, 302);
		break;

	case '/image':
		header('Content-Type: image/png');
		echo str_repeat("\x89", 1000);
		break;

	case '/redirect-missing':
		header('Location: /missing', true, 302);
		break;

	case '/missing':
		http_response_code(404);
		echo '<html><head><meta property="og:url" content="/final-og"></head></html>';
		break;

	case '/hit':
		touch(sys_get_temp_dir() . '/friendica-sink-hit-' . $_SERVER['SERVER_PORT']);
		echo 'hit';
		break;

	default:
		echo 'final';
}
