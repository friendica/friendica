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

namespace Friendica\Console;

use Console_Table;
use Friendica\App;
use Friendica\DI;
use Friendica\Model\Contact as ContactModel;
use Friendica\Model\User as UserModel;
use Friendica\Network\Probe;
use Friendica\Util\Temporal;
use RuntimeException;
use Seld\CliPrompt\CliPrompt;

/**
 * tool to manage contacts of users of the current node
 */
class Contact extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var App\Mode
	 */
	private $appMode;
	/**
	 * @var IPConfig
	 */
	private $pConfig;

	protected function getHelp()
	{
		$help = <<<HELP
console contact - Modify contact settings per console commands.
Usage
	bin/console contact add <user nick> <URL> [<network>] [-h|--help|-?] [-v]
	bin/console contact remove <CID> [-h|--help|-?] [-v]
	bin/console contact search id <CID> [-h|--help|-?] [-v]
	bin/console contact search url <user nick> <URL> [-h|--help|-?] [-v]
	bin/console contact terminate <CID> [-h|--help|-?] [-v]

Description
	Modify contact settings per console commands.

Options
    -h|--help|-? Show help information
    -v           Show more debug information
    -y           Non-interactive mode, assume "yes" as answer to the user deletion prompt
HELP;
		return $help;
	}

	public function __construct(App\Mode $appMode, array $argv = null)
	{
		parent::__construct($argv);

		$this->appMode = $appMode;
	}

	protected function doExecute(): int
	{
		if ($this->getOption('v')) {
			$this->out('Class: ' . __CLASS__);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		if ($this->appMode->isInstall()) {
			throw new RuntimeException('Database isn\'t ready or populated yet');
		}

		$command = $this->getArgument(0);

		switch ($command) {
			case 'add':
				return $this->addContact();
			case 'remove':
				return $this->removeContact();
			case 'search':
				return $this->searchContact();
			case 'terminate':
				return $this->terminateContact();
			default:
				throw new \Asika\SimpleConsole\CommandArgsException('Wrong command.');
		}
	}

	/**
	 * Retrieves the user from a nick supplied as an argument or from a prompt
	 *
	 * @param int $arg_index Index of the nick argument in the arguments list
	 *
	 * @return array|boolean User record with uid field, or false if user is not found
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private function getUserByNick($arg_index)
	{
		$nick = $this->getArgument($arg_index);

		if (empty($nick)) {
			$this->out('Enter user nickname: ');
			$nick = CliPrompt::prompt();
			if (empty($nick)) {
				throw new RuntimeException('A user nickname must be specified.');
			}
		}

		$user = UserModel::getByNickname($nick, ['uid', 'nickname']);
		if (empty($user)) {
			throw new RuntimeException('User not found');
		}

		return $user;
	}

	/**
	 * Adds a contact to a user from a URL
	 *
	 * @return bool True, if the command was successful
	 */
	private function addContact()
	{
		$user = $this->getUserByNick(1);

		$url = $this->getArgument(2);
		if (empty($url)) {
			$this->out('Enter contact URL: ');
			$url = CliPrompt::prompt();
			if (empty($url)) {
				throw new RuntimeException('A contact URL must be specified.');
			}
		}

		$url = Probe::cleanURI($url);

		$contact = ContactModel::getByURL($url, null, [], $user['uid']);
		if (!empty($contact)) {
			throw new RuntimeException('Contact already exists');
		}

		$network = $this->getArgument(3);
		if ($network === null) {
			$this->out('Enter network, or leave blank: ');
			$network = CliPrompt::prompt();
		}

		$result = ContactModel::createFromProbeForUser($user['uid'], $url, $network);

		if ($result['success']) {
			$this->out('User ' . $user['nickname'] . ' now connected to ' . $url . ', contact ID ' . $result['cid']);
		} else {
			throw new RuntimeException($result['message']);
		}
	}

	/**
	 * Sends an unfriend message.
	 *
	 * @return bool True, if the command was successful
	 * @throws \Exception
	 */
	private function terminateContact(): bool
	{
		$cid = $this->getArgument(1);
		if (empty($cid)) {
			$this->out('Enter contact ID: ');
			$cid = CliPrompt::prompt();
			if (empty($cid)) {
				throw new RuntimeException('A contact ID must be specified.');
			}
		}

		$contact = ContactModel::getById($cid);
		if (empty($contact)) {
			throw new RuntimeException('Contact not found');
		}

		if (empty($contact['uid'])) {
			throw new RuntimeException('Contact must be user-specific (uid != 0)');
		}

		try {
			ContactModel::unfollow($contact);

			$this->out('Contact was successfully unfollowed');

			return true;
		} catch (\Exception $e) {
			DI::logger()->error($e->getMessage(), ['contact' => $contact]);
			throw new RuntimeException('Unable to unfollow this contact, please check the log');
		}
	}

	/**
	 * Marks a contact for removal
	 */
	private function removeContact()
	{
		$cid = $this->getArgument(1);
		if (empty($cid)) {
			$this->out('Enter contact ID: ');
			$cid = CliPrompt::prompt();
			if (empty($cid)) {
				throw new RuntimeException('A contact ID must be specified.');
			}
		}

		ContactModel::remove($cid);
	}

	/**
	 * Returns a contact based on search parameter
	 *
	 * @return bool True, if the command was successful
	 */
	private function searchContact()
	{
		$fields = [
			'id',
			'uid',
			'network',
			'name',
			'nick',
			'url',
			'addr',
			'created',
			'updated',
			'blocked',
			'deleted',
		];

		$subCmd = $this->getArgument(1);

		$table = new Console_Table();
		$table->setHeaders(['ID', 'UID', 'Network', 'Name', 'Nick', 'URL', 'E-Mail', 'Created', 'Updated', 'Blocked', 'Deleted']);

		$addRow = function ($row) use (&$table) {
			$table->addRow([
				$row['id'],
				$row['uid'],
				$row['network'],
				$row['name'],
				$row['nick'],
				$row['url'],
				$row['addr'],
				Temporal::getRelativeDate($row['created']),
				Temporal::getRelativeDate($row['updated']),
				$row['blocked'],
				$row['deleted'],
			]);
		};
		switch ($subCmd) {
			case 'id':
				$cid     = $this->getArgument(2);
				$contact = ContactModel::getById($cid, $fields);
				if (!empty($contact)) {
					$addRow($contact);
				}
				break;
			case 'url':
				$user    = $this->getUserByNick(2);
				$url     = $this->getArgument(3);
				$contact = ContactModel::getByURLForUser($url, $user['uid'], false, $fields);
				if (!empty($contact)) {
					$addRow($contact);
				}
				break;
			default:
				$this->out($this->getHelp());
				return false;
		}

		$this->out($table->getTable());

		return true;
	}
}
