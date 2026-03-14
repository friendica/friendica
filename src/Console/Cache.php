<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Friendica\App\Mode;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Cache\Capability\ICanCache;
use RuntimeException;

/**
 * tool to access the cache from the CLI
 *
 * With this script you can access the cache of your node from the CLI.
 * You can read current values stored in the cache and set new values
 * in cache keys.
 */
class Cache extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var Mode
	 */
	private $appMode;

	/**
	 * @var ICanCache
	 */
	private $cache;

	protected function getHelp()
	{
		$help = <<<HELP
console cache - Manage node cache
Synopsis
	bin/console cache list [-h|--help|-?] [-v]
	bin/console cache get <key> [-h|--help|-?] [-v]
	bin/console cache set <key> <value> [-h|--help|-?] [-v]
	bin/console cache flush [-h|--help|-?] [-v]
	bin/console cache clear [-h|--help|-?] [-v]

Description
	bin/console cache list [<prefix>]
		List all cache keys, optionally filtered by a prefix

	bin/console cache get <key>
		Shows the value of the provided cache key

	bin/console cache set <key> <value> [<ttl>]
		Sets the value of the provided cache key, optionally with the provided TTL (time to live) with a default of five minutes.

	bin/console cache flush
		Clears expired cache keys

	bin/console cache clear
		Clears all cache keys

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(Mode $appMode, ICanCache $cache, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
		$this->cache   = $cache;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . self::class);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (!$this->appMode->has(Mode::DBAVAILABLE)) {
			$this->out('Database isn\'t ready or populated yet, database cache won\'t be available');
		}

		if ($this->getOption('v')) {
			$this->out('Cache Driver Name: ' . $this->cache->getName());
			$this->out('Cache Driver Class: ' . get_class($this->cache));
		}

		switch ($this->getArgument(0)) {
			case 'list':
				$this->executeList();
				break;
			case 'get':
				$this->executeGet();
				break;
			case 'set':
				$this->executeSet();
				break;
			case 'flush':
				$this->executeFlush();
				break;
			case 'clear':
				$this->executeClear();
				break;
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		return 0;
	}

	private function executeList()
	{
		$prefix = $this->getArgument(1);
		$keys   = $this->cache->getAllKeys($prefix);

		if (empty($prefix)) {
			$this->out('Listing all cache keys:');
		} else {
			$this->out('Listing all cache keys starting with "' . $prefix . '":');
		}

		$count = 0;
		foreach ($keys as $key) {
			$this->out($key);
			$count++;
		}

		$this->out($count . ' keys found');
	}

	private function executeGet()
	{
		if (count($this->args) >= 2) {
			$key   = $this->getArgument(1);
			$value = $this->cache->get($key);

			$this->out("{$key} => " . var_export($value, true));
		} else {
			throw new CommandArgsException('Too few arguments for get');
		}
	}

	private function executeSet()
	{
		if (count($this->args) >= 3) {
			$key      = $this->getArgument(1);
			$value    = $this->getArgument(2);
			$duration = intval($this->getArgument(3, Duration::FIVE_MINUTES));

			if (is_array($this->cache->get($key))) {
				throw new RuntimeException("$key is an array and can't be set using this command.");
			}

			$result = $this->cache->set($key, $value, $duration);
			if ($result) {
				$this->out("{$key} <= " . $this->cache->get($key));
			} else {
				$this->out("Unable to set {$key}");
			}
		} else {
			throw new CommandArgsException('Too few arguments for set');
		}
	}

	private function executeFlush()
	{
		$result = $this->cache->clear();
		if ($result) {
			$this->out('Cache successfully flushed');
		} else {
			$this->out('Unable to flush the cache');
		}
	}

	private function executeClear()
	{
		$result = $this->cache->clear(false);
		if ($result) {
			$this->out('Cache successfully cleared');
		} else {
			$this->out('Unable to flush the cache');
		}
	}
}
