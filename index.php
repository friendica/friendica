<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

$start_time = microtime(true);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar run install:prod" on the command line in the web root.');
}

require __DIR__ . '/vendor/autoload.php';

$request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();

$container = \Friendica\Core\DiceContainer::fromBasePath(__DIR__);

$app = \Friendica\App::fromContainer($container);

$app->processRequest($request, $start_time);
