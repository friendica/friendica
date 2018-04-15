<?php

namespace Friendica\Core\Console;

use Friendica\Core\Protocol;
use Friendica\Database\DBM;
use Friendica\Network\Probe;

require_once 'include/text.php';

/**
 * @brief tool to silence accounts on the global community page
 *
 * With this tool, you can silence an account on the global community page.
 * Postings from silenced accounts will not be displayed on the community
 * page. This silencing does only affect the display on the community page,
 * accounts following the silenced accounts will still get their postings.
 *
 * License: AGPLv3 or later, same as Friendica
 *
 * @author Tobias Diekershoff
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class GlobalCommunitySilence extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{
		$help = <<<HELP
console globalcommunitysilence - Silence remote profile from global community page
Usage
	bin/console globalcommunitysilence <profile_url> [-h|--help|-?] [-v]

Description
	With this tool, you can silence an account on the global community page.
	Postings from silenced accounts will not be displayed on the community page.
	This silencing does only affect the display on the community page, accounts
	following the silenced accounts will still get their postings.

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

		require_once 'config/.htconfig.php';
		$result = \dba::connect($db_host, $db_user, $db_pass, $db_data);
		unset($db_host, $db_user, $db_pass, $db_data);

		if (!$result) {
			throw new \RuntimeException('Unable to connect to database');
		}

		/**
		 * 1. make nurl from last parameter
		 * 2. check DB (contact) if there is a contact with uid=0 and that nurl, get the ID
		 * 3. set the flag hidden=1 for the contact entry with the found ID
		 * */
		$net = Probe::uri($this->getArgument(0));
		if (in_array($net['network'], [Protocol::PHANTOM, Protocol::MAIL])) {
			throw new \RuntimeException('This account seems not to exist.');
		}

		$nurl = normalise_link($net['url']);
		$contact = \dba::selectFirst("contact", ["id"], ["nurl" => $nurl, "uid" => 0]);
		if (DBM::is_result($contact)) {
			\dba::update("contact", ["hidden" => true], ["id" => $contact["id"]]);
			$this->out('NOTICE: The account should be silenced from the global community page');
		} else {
			throw new \RuntimeException('NOTICE: Could not find any entry for this URL (' . $nurl . ')');
		}

		return 0;
	}
}
