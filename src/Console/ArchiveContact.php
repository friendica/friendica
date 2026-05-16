<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Friendica\App\Mode;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\Strings;
use RuntimeException;

/**
 * tool to archive a contact on the server
 *
 * With this tool you can archive a contact when you know that it isn't existing anymore.
 * Normally this does happen automatically after a few days.
 *
 * License: AGPLv3 or later, same as Friendica
 *
 */
class ArchiveContact extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var Mode
	 */
	private $appMode;
	/**
	 * @var Database
	 */
	private $dba;
	/**
	 * @var \Friendica\Core\L10n
	 */
	private $l10n;

	protected function getHelp()
	{
		$help = <<<HELP
console archivecontact - archive a contact
Usage
	bin/console archivecontact <profile_url> [-h|--help|-?] [-v]

Description
	Archive a contact when you know that it isn't existing anymore. Normally this does happen automatically after a few days.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(Mode $appMode, Database $dba, \Friendica\Core\L10n $l10n, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
		$this->dba     = $dba;
		$this->l10n    = $l10n;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . self::class);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		if (count($this->args) > 1) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		if ($this->appMode->isInstall()) {
			throw new RuntimeException('Friendica isn\'t properly installed yet.');
		}

		$nurl = Strings::normaliseLink($this->getArgument(0));
		if (!$this->dba->exists('contact', ['nurl' => $nurl, 'archive' => false])) {
			throw new RuntimeException(DI::l10n()->t('Could not find any unarchived contact entry for this URL (%s)', $nurl));
		}
		if (Contact::update(['archive' => true], ['nurl' => $nurl])) {
			$this->out($this->l10n->t('The contact entries have been archived'));
		} else {
			throw new RuntimeException('The contact archival failed.');
		}

		return 0;
	}
}
