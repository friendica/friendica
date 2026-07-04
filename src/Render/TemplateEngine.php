<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Render;

/**
 * Interface for template engines
 */
abstract class TemplateEngine
{
	/** @var string */
	public static $name;

	/** @var string */
	protected $theme;
	/** @var array */
	protected $theme_info;

	/**
	 * @param string $theme      The current theme name
	 * @param array  $theme_info The current theme info array
	 */
	abstract public function __construct(string $theme, array $theme_info);

	/**
	 * Checks the template engine is correctly installed and configured and reports error messages in the provided
	 * parameter or displays them directly if it's null.
	 *
	 * @param array|null $errors
	 * @return void
	 */
	abstract public function testInstall(?array &$errors = null);

	/**
	 * Returns the rendered template output from the template string and variables
	 *
	 * @param string $template
	 * @param array  $vars
	 * @return string Template output with replaced macros
	 */
	abstract public function replaceMacros(string $template, array $vars): string;

	/**
	 * Returns the template string from a file path and an optional sub-directory from the project root
	 *
	 * @param string $file
	 * @param string $subDir
	 * @return mixed
	 */
	abstract public function getTemplateFile(string $file, string $subDir = '');
}
