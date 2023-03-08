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

namespace Friendica\App;

/**
 * Determine all arguments of the current call, including
 * - The whole querystring (except the pagename/q parameter)
 * - The command
 * - The arguments (C-Style based)
 * - The count of arguments
 */
class Arguments
{
	const DEFAULT_MODULE = 'home';

	/**
	 * @var string The complete query string
	 */
	private $queryString;
	/**
	 * @var string The current Friendica command
	 */
	private $command;
	/**
	 * @var string The name of the current module
	 */
	private $moduleName;
	/**
	 * @var array The arguments of the current execution
	 */
	private $argv;
	/**
	 * @var int The count of arguments
	 */
	private $argc;
	/**
	 * @var string The used HTTP method
	 */
	private $method;

	public function __construct(string $queryString = '', string $command = '', string $moduleName = '', array $argv = [], int $argc = 0, string $method = Router::GET)
	{
		$this->queryString = $queryString;
		$this->command     = $command;
		$this->moduleName  = $moduleName;
		$this->argv        = $argv;
		$this->argc        = $argc;
		$this->method      = $method;
	}

	/**
	 * @return string The whole query string of this call with url-encoded query parameters
	 */
	public function getQueryString()
	{
		return $this->queryString;
	}

	/**
	 * @return string The whole command of this call
	 */
	public function getCommand(): string
	{
		return $this->command;
	}

	/**
	 * @return string The module name based on the arguments
	 * @deprecated 2022.12 - With the new (sub-)routes, it's not trustworthy anymore, use the ModuleClass instead
	 * @see Router::getModuleClass()
	 */
	public function getModuleName(): string
	{
		return $this->moduleName;
	}

	/**
	 * @return array All arguments of this call
	 */
	public function getArgv(): array
	{
		return $this->argv;
	}

	/**
	 * @return string The used HTTP method
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * @return int The count of arguments of this call
	 */
	public function getArgc(): int
	{
		return $this->argc;
	}

	public function setArgv(array $argv)
	{
		$this->argv = $argv;
		$this->argc = count($argv);
	}

	public function setArgc(int $argc)
	{
		$this->argc = $argc;
	}

	/**
	 * Returns the value of a argv key
	 * @todo there are a lot of $a->argv usages in combination with ?? which can be replaced with this method
	 *
	 * @param int   $position the position of the argument
	 * @param mixed $default  the default value if not found
	 *
	 * @return mixed returns the value of the argument
	 */
	public function get(int $position, $default = '')
	{
		return $this->has($position) ? $this->argv[$position] : $default;
	}

	/**
	 * @param int $position
	 *
	 * @return bool if the argument position exists
	 */
	public function has(int $position): bool
	{
		return array_key_exists($position, $this->argv);
	}

	/**
	 * Determine the arguments of the current call
	 *
	 * @param array $server The $_SERVER variable
	 * @param array $get    The $_GET variable
	 *
	 * @return Arguments The determined arguments
	 */
	public function determine(array $server, array $get): Arguments
	{
		// removing leading / - maybe a nginx problem
		$server['QUERY_STRING'] = ltrim($server['QUERY_STRING'] ?? '', '/');

		$queryParameters = [];
		parse_str($server['QUERY_STRING'], $queryParameters);

		if (!empty($get['pagename'])) {
			$command = trim($get['pagename'], '/\\');
		} elseif (!empty($queryParameters['pagename'])) {
			$command = trim($queryParameters['pagename'], '/\\');
		} elseif (!empty($get['q'])) {
			// Legacy page name parameter, now conflicts with the search query parameter
			$command = trim($get['q'], '/\\');
		} else {
			$command = '';
		}

		// Remove generated and one-time use parameters
		unset($queryParameters['pagename']);
		unset($queryParameters['zrl']);
		unset($queryParameters['owt']);

		/*
		 * Break the URL path into C style argc/argv style arguments for our
		 * modules. Given "http://example.com/module/arg1/arg2", $this->argc
		 * will be 3 (integer) and $this->argv will contain:
		 *   [0] => 'module'
		 *   [1] => 'arg1'
		 *   [2] => 'arg2'
		 */
		if ($command) {
			$argv = explode('/', $command);
		} else {
			$argv = [];
		}

		$argc = count($argv);

		$queryString = $command . ($queryParameters ? '?' . http_build_query($queryParameters) : '');

		if ($argc > 0) {
			$module = str_replace('.', '_', $argv[0]);
			$module = str_replace('-', '_', $module);
		} else {
			$module = self::DEFAULT_MODULE;
		}

		// Compatibility with the Firefox App
		if (($module == "users") && ($command == "users/sign_in")) {
			$module = "login";
		}

		$httpMethod = in_array($server['REQUEST_METHOD'] ?? '', Router::ALLOWED_METHODS) ? $server['REQUEST_METHOD'] : Router::GET;

		return new Arguments($queryString, $command, $module, $argv, $argc, $httpMethod);
	}
}
