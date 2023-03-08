<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Render;

/**
 * Interface for template engines
 */
abstract class TemplateEngine
{
	/** @var string */
	static $name;

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
	abstract public function testInstall(array &$errors = null);

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
