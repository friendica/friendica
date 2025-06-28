#!/usr/bin/env php
<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 */

if (php_sapi_name() !== 'cli') {
	header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden');
	exit();
}

// Ensure that te console is executed from the base path of the installation
chdir(dirname(__DIR__));

require dirname(__DIR__) . '/vendor/autoload.php';

$container = \Friendica\Core\DiceContainer::fromBasePath(dirname(__DIR__));

$app = \Friendica\App::fromContainer($container);

$app->processConsole($_SERVER);
