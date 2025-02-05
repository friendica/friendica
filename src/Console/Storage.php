<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Friendica\Core\Storage\Repository\StorageManager;
use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Exception\StorageException;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Photo;

/**
 * tool to manage storage backend and stored data from CLI
 */
class Storage extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/** @var StorageManager */
	private $storageManager;

	/**
	 * @param StorageManager $storageManager
	 */
	public function __construct(StorageManager $storageManager, array $argv = [])
	{
		parent::__construct($argv);

		$this->storageManager = $storageManager;
	}

	protected function getHelp()
	{
		$help = <<<HELP
console storage - manage storage backend and stored data
Synopsis
    bin/console storage [-h|--help|-?] [-v]
        Show this help

    bin/console storage list
        List available storage backends

    bin/console storage clear
        Remove the contact avatar cache data

    bin/console storage set <name>
        Set current storage backend
            name        storage backend to use. see "list".

    bin/console storage move [table] [-n 5000]
        Move stored data to current storage backend.
            table       one of "photo" or "attach". default to both
            -n          limit of processed entry batch size
HELP;
		return $help;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return -1;
		}

		switch ($this->args[0]) {
			case 'list':
				return $this->doList();
				break;
			case 'clear':
				return $this->clear();
				break;
			case 'set':
				return $this->doSet();
				break;
			case 'move':
				return $this->doMove();
				break;
		}

		$this->out(sprintf('Invalid action "%s"', $this->args[0]));
		return -1;
	}

	protected function doList()
	{
		$rowfmt  = ' %-3s | %-20s';
		$current = $this->storageManager->getBackend();
		$this->out(sprintf($rowfmt, 'Sel', 'Name'));
		$this->out('-----------------------');
		$isregisterd = false;
		foreach ($this->storageManager->listBackends() as $name) {
			$issel = ' ';
			if ($current && $current::getName() == $name) {
				$issel       = '*';
				$isregisterd = true;
			};
			$this->out(sprintf($rowfmt, $issel, $name));
		}

		if ($current === '') {
			$this->out();
			$this->out('This system is using legacy storage system');
		}
		if ($current !== '' && !$isregisterd) {
			$this->out();
			$this->out('The current storage class (' . $current . ') is not registered!');
		}
		return 0;
	}

	protected function clear()
	{
		$fields = ['photo' => '', 'thumb' => '', 'micro' => ''];
		$photos = DBA::select('photo', ['id', 'contact-id'], ['uid' => 0, 'photo-type' => [Photo::CONTACT_AVATAR, Photo::CONTACT_BANNER]]);
		while ($photo = DBA::fetch($photos)) {
			if (Photo::delete(['id' => $photo['id']])) {
				Contact::update($fields, ['id' => $photo['contact-id']]);
				$this->out('Cleared photo id ' . $photo['id'] . ' - contact id ' . $photo['contact-id']);
			} else {
				$this->out('Photo id ' . $photo['id'] . ' was not deleted.');
			}
		}
		DBA::close($photos);
		return 0;
	}

	protected function doSet()
	{
		if (count($this->args) !== 2 || empty($this->args[1])) {
			throw new CommandArgsException('Invalid arguments');
		}

		$name = $this->args[1];
		try {
			$class = $this->storageManager->getWritableStorageByName($name);

			if (!$this->storageManager->setBackend($class)) {
				$this->out($class . ' is not a valid backend storage class.');
				return -1;
			}
		} catch (ReferenceStorageException $exception) {
			$this->out($name . ' is not a registered backend.');
			return -1;
		}

		return 0;
	}

	protected function doMove()
	{
		if (count($this->args) < 1 || count($this->args) > 2) {
			throw new CommandArgsException('Invalid arguments');
		}

		if (count($this->args) == 2) {
			$table = strtolower($this->args[1]);
			if (!in_array($table, ['photo', 'attach'])) {
				throw new CommandArgsException('Invalid table');
			}
			$tables = [$table];
		} else {
			$tables = StorageManager::TABLES;
		}

		$current = $this->storageManager->getBackend();
		$total   = 0;

		if (is_null($current)) {
			throw new StorageException(sprintf("Cannot move to legacy storage. Please select a storage backend."));
		}

		do {
			$moved = $this->storageManager->move($current, $tables, $this->getOption('n', 5000));
			if ($moved) {
				$this->out(date('[Y-m-d H:i:s] ') . sprintf('Moved %d files', $moved));
			}

			$total += $moved;
		} while ($moved);

		$this->out(sprintf(date('[Y-m-d H:i:s] ') . 'Moved %d files total', $total));

		return 0;
	}
}
