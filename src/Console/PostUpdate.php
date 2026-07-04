<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Console;

use Friendica\App\Mode;
use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Core\L10n;
use Friendica\Core\Update;
use Friendica\DI;

/**
 * Performs database post updates
 */
class PostUpdate extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];
	/**
	 * @var string
	 */
	private $basePath;

	protected function getHelp()
	{
		$help = <<<HELP
console postupdate - Performs database post updates
Usage
        bin/console postupdate [-h|--help|-?] [--reset <version>]

Options
    -h|--help|-?      Show help information
    --reset <version> Reset the post update version
HELP;
		return $help;
	}

	public function __construct(
		private readonly Mode $appMode,
		private readonly IManageKeyValuePairs $keyValue,
		private readonly L10n $l10n,
		?array $argv = null,
	) {
		parent::__construct($argv);
		$this->basePath = DI::appHelper()->getBasePath();
	}

	protected function doExecute(): int
	{
		if ($this->getOption($this->helpOptions)) {
			$this->out($this->getHelp());
			return 0;
		}

		$reset_version = $this->getOption('reset');
		if (is_bool($reset_version)) {
			$this->out($this->getHelp());
			return 0;
		} elseif ($reset_version) {
			$this->keyValue->set('post_update_version', $reset_version);
			echo $this->l10n->t('Post update version number has been set to %s.', $reset_version) . "\n";
			return 0;
		}

		if ($this->appMode->isInstall()) {
			throw new \RuntimeException('Database isn\'t ready or populated yet');
		}

		echo $this->l10n->t('Check for pending update actions.') . "\n";
		Update::run($this->basePath, true, false, true, false);
		echo $this->l10n->t('Done.') . "\n";

		echo $this->l10n->t('Execute pending post updates.') . "\n";

		while (!\Friendica\Database\PostUpdate::update()) {
			echo '.';
		}

		echo "\n" . $this->l10n->t('All pending post updates are done.') . "\n";

		return 0;
	}
}
