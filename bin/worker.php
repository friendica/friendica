#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Starts the background processing
 *
 * @deprecated 2025.02 use `bin/console.php worker` instead
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

// Ensure that worker.php is executed from the base path of the installation
chdir(dirname(__DIR__));

require dirname(__DIR__) . '/vendor/autoload.php';

fwrite(STDOUT, '`bin/worker.php` is deprecated since 2024.02 and will be removed in 5 months, please use `bin/console.php worker` instead.' . \PHP_EOL);

// BC: Add console command as second argument
$argv = $_SERVER['argv'] ?? [];
array_splice($argv, 1, 0, "worker");
$_SERVER['argv'] = $argv;

$container = \Friendica\Core\DiceContainer::fromBasePath(dirname(__DIR__));

$app = \Friendica\App::fromContainer($container);

$app->processConsole($_SERVER);
