<?php

namespace Friendica\Core\Console;

use Friendica\Core\L10n;
use Friendica\Model\Contact;

/**
 * @brief tool to block an account from the node
 *
 * With this tool, you can block an account in such a way, that no postings
 * or comments this account writes are accepted to the node.
 *
 * License: AGPLv3 or later, same as Friendica
 *
 * @author Tobias Diekershoff <tobias.diekershoff@gmx.net>
 * @author Hypolite Petovan <hypolite@mrpetovan.com>
 */
class GlobalCommunityBlock extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console globalcommunityblock - Block remote profile from interacting with this node
Usage
	bin/console globalcommunityblock <profile_url> [-h|--help|-?] [-v]

Description
	Blocks an account in such a way that no postings or comments this account writes are accepted to this node.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	protected function doExecute()
	{
		$a = get_app();

		if ($this->getOption('v')) {
			$this->out('Class: ' . __CLASS__);
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

		if ($a->isInstallMode()) {
			throw new \RuntimeException('Database isn\'t ready or populated yet');
		}

		$contact_id = Contact::getIdForURL($this->getArgument(0));
		if (!$contact_id) {
			throw new \RuntimeException(L10n::t('Could not find any contact entry for this URL (%s)', $nurl));
		}
		if(Contact::block($contact_id)) {
			$this->out(L10n::t('The contact has been blocked from the node'));
		} else {
			throw new \RuntimeException('The contact block failed.');
		}

		return 0;
	}
}
