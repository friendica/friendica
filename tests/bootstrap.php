<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

use PHPUnit\Framework\TestCase;

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
	die('Vendor path not found. Please execute "bin/composer.phar install" on the command line in the web root.');
}

require __DIR__ . '/../vendor/autoload.php';

// Backward compatibility
if (!class_exists(TestCase::class)) {
	class_alias(\PHPUnit\Framework\TestCase::class, TestCase::class);
}
