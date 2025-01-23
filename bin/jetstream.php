#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * @deprecated 2025.02 use `bin/console.php jetstream` instead
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

// Ensure that Jetstream.php is executed from the base path of the installation
chdir(dirname(__DIR__));

require dirname(__DIR__) . '/vendor/autoload.php';

fwrite(STDOUT, '`bin/jetstream.php` is deprecated since 2024.02 and will be removed in 5 months, please use `bin/console.php jetstream` instead.' . \PHP_EOL);

// BC: Add console command as second argument
$argv = $_SERVER['argv'] ?? [];
array_splice($argv, 1, 0, "jetstream");
$_SERVER['argv'] = $argv;

$container = \Friendica\Core\DiceContainer::fromBasePath(dirname(__DIR__));

$app = \Friendica\App::fromContainer($container);

$app->processConsole($_SERVER);
