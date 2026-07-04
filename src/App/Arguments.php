<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
	public const DEFAULT_MODULE = 'home';

	public function __construct(
		/**
		 * @var string The complete query string
		 */
		private readonly string $queryString = '',
		/**
		 * @var string The current Friendica command
		 */
		private readonly string $command = '',
		/**
		 * @var string The name of the current module
		 */
		private readonly string $moduleName = '',
		/**
		 * @var array The arguments of the current execution
		 */
		private array $argv = [],
		/**
		 * @var int The count of arguments
		 */
		private int $argc = 0,
		/**
		 * @var string The used HTTP method
		 */
		private readonly string $method = Router::GET,
	) {}

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
			$command = trim((string) $get['pagename'], '/\\');
		} elseif (!empty($queryParameters['pagename'])) {
			$command = trim($queryParameters['pagename'], '/\\');
		} elseif (!empty($get['q'])) {
			// Legacy page name parameter, now conflicts with the search query parameter
			$command = trim((string) $get['q'], '/\\');
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
