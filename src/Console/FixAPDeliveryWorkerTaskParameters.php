<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Friendica\App\Mode;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use RuntimeException;

/**
 * License: AGPLv3 or later, same as Friendica
 */
class FixAPDeliveryWorkerTaskParameters extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];
	/**
	 * @var int
	 */
	private $examined;
	/**
	 * @var int
	 */
	private $processed;
	/**
	 * @var int
	 */
	private $errored;

	protected function getHelp()
	{
		$help = <<<HELP
console fixapdeliveryworkertaskparameters - fix APDelivery worker task parameters corrupted during the 2020.12 RC period
Usage
	bin/console fixapdeliveryworkertaskparameters [-h|--help|-?] [-v]

Description
	During the 2020.12 RC period some worker task parameters have been corrupted, resulting in the impossibility to execute them.
	This command restores their expected parameters.
	If you didn't run Friendica during the 2020.12 RC period, you do not need to use this command.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(
		private readonly Mode $appMode,
		private readonly Database $dba,
		?array $argv = null,
	) {
		parent::__construct($argv);
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . self::class);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) > 0) {
			throw new CommandArgsException('Too many arguments');
		}

		if ($this->appMode->isInstall()) {
			throw new RuntimeException('Friendica isn\'t properly installed yet.');
		}

		$this->examined  = 0;
		$this->processed = 0;
		$this->errored   = 0;

		do {
			$result = $this->dba->select('workerqueue', ['id', 'parameter'], ["`command` = ? AND `parameter` LIKE ?", "APDelivery", "[\"%\",\"\",%"], ['limit' => [$this->examined, 100]]);
			while ($row = $this->dba->fetch($result)) {
				$this->examined++;
				$this->processRow($row);
			}
		} while ($this->dba->isResult($result));

		if ($this->getOption('v')) {
			$this->out('Examined: ' . $this->examined);
			$this->out('Processed: ' . $this->processed);
			$this->out('Errored: ' . $this->errored);
		}

		return 0;
	}

	private function processRow(array $workerqueueItem)
	{
		$parameters = json_decode((string) $workerqueueItem['parameter'], true);

		if (!$parameters) {
			$this->errored++;
			if ($this->getOption('v')) {
				$this->out('Unable to parse parameter JSON of the row with id ' . $workerqueueItem['id']);
				$this->out('JSON: ' . var_export($workerqueueItem['parameter'], true));
			}
		}

		if ($parameters[1] !== '' && !is_array($parameters[2])) {
			// Nothing to do, we save a write
			return;
		}

		if ($parameters[1] === '') {
			$parameters[1] = 0;
		}

		if (is_array($parameters[2])) {
			$parameters[4] = $parameters[2];
			$contact       = Contact::getById(current($parameters[2]), ['url']);
			$parameters[2] = $contact['url'];
		}

		$fields = ['parameter' => json_encode($parameters)];
		if ($this->dba->update('workerqueue', $fields, ['id' => $workerqueueItem['id']])) {
			$this->processed++;
		} else {
			$this->errored++;
			if ($this->getOption('v')) {
				$this->out('Unable to update the row with id ' . $workerqueueItem['id']);
				$this->out('Fields: ' . var_export($fields, true));
			}
		}
	}
}
