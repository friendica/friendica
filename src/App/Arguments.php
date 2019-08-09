<?php

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
	/**
	 * @var string The complete query string
	 */
	private $queryString;
	/**
	 * @var string The current Friendica command
	 */
	private $command;
	/**
	 * @var array The arguments of the current execution
	 */
	private $argv;
	/**
	 * @var int The count of arguments
	 */
	private $argc;

	public function __construct(string $queryString = '', string $command = '', array $argv = [Module::DEFAULT], int $argc = 1)
	{
		$this->queryString = $queryString;
		$this->command     = $command;
		$this->argv        = $argv;
		$this->argc        = $argc;
	}

	/**
	 * @return string The whole query string of this call
	 */
	public function getQueryString()
	{
		return $this->queryString;
	}

	/**
	 * @return string The whole command of this call
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * @return array All arguments of this call
	 */
	public function getArgv()
	{
		return $this->argv;
	}

	/**
	 * @return int The count of arguments of this call
	 */
	public function getArgc()
	{
		return $this->argc;
	}

	/**
	 * Returns the value of a argv key
	 * TODO there are a lot of $a->argv usages in combination with defaults() which can be replaced with this method
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
	public function has(int $position)
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
	public function determine(array $server, array $get)
	{
		$queryString = '';

		if (!empty($server['QUERY_STRING']) && strpos($server['QUERY_STRING'], 'pagename=') === 0) {
			$queryString = substr($server['QUERY_STRING'], 9);
		} elseif (!empty($server['QUERY_STRING']) && strpos($server['QUERY_STRING'], 'q=') === 0) {
			$queryString = substr($server['QUERY_STRING'], 2);
		}

		// eventually strip ZRL
		$queryString = $this->stripZRLs($queryString);

		// eventually strip OWT
		$queryString = $this->stripQueryParam($queryString, 'owt');

		// removing trailing / - maybe a nginx problem
		$queryString = ltrim($queryString, '/');

		if (!empty($get['pagename'])) {
			$command = trim($get['pagename'], '/\\');
		} elseif (!empty($get['q'])) {
			$command = trim($get['q'], '/\\');
		} else {
			$command = Module::DEFAULT;
		}


		// fix query_string
		if (!empty($command)) {
			$queryString = str_replace(
				$command . '&',
				$command . '?',
				$queryString
			);
		}

		// unix style "homedir"
		if (substr($command, 0, 1) === '~') {
			$command = 'profile/' . substr($command, 1);
		}

		// Diaspora style profile url
		if (substr($command, 0, 2) === 'u/') {
			$command = 'profile/' . substr($command, 2);
		}

		/*
		 * Break the URL path into C style argc/argv style arguments for our
		 * modules. Given "http://example.com/module/arg1/arg2", $this->argc
		 * will be 3 (integer) and $this->argv will contain:
		 *   [0] => 'module'
		 *   [1] => 'arg1'
		 *   [2] => 'arg2'
		 *
		 *
		 * There will always be one argument. If provided a naked domain
		 * URL, $this->argv[0] is set to "home".
		 */

		$argv = explode('/', $command);
		$argc = count($argv);


		return new Arguments($queryString, $command, $argv, $argc);
	}

	/**
	 * Strip zrl parameter from a string.
	 *
	 * @param string $queryString The input string.
	 *
	 * @return string The zrl.
	 */
	public function stripZRLs(string $queryString)
	{
		return preg_replace('/[?&]zrl=(.*?)(&|$)/ism', '$2', $queryString);
	}

	/**
	 * Strip query parameter from a string.
	 *
	 * @param string $queryString The input string.
	 * @param string $param
	 *
	 * @return string The query parameter.
	 */
	public function stripQueryParam(string $queryString, string $param)
	{
		return preg_replace('/[?&]' . $param . '=(.*?)(&|$)/ism', '$2', $queryString);
	}
}
