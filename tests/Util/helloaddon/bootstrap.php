<?php

// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Name: Hello Addon
 * Description: For testing purpose only
 * Version: 1.0
 * Author: Artur Weigandt <dont-mail-me@example.com>
 */

declare(strict_types=1);

/**
 * PSR-4 autoloader.
 *
 * @param string $class The fully-qualified class name.
 */
spl_autoload_register(function (string $class): void {
	// addon namespace prefix
	$prefix = 'FriendicaAddons\\HelloAddon\\';

	// base directory for the namespace prefix
	$base_dir = __DIR__ . '/src/';

	// does the class use the namespace prefix?
	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		// no, move to the next registered autoloader
		return;
	}

	// get the relative class name
	$relative_class = substr($class, $len);

	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

	// if the file exists, require it
	if (file_exists($file)) {
		require $file;
	}
});

return new \FriendicaAddons\HelloAddon\HelloAddon();
