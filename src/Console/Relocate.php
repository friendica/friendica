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

use Asika\SimpleConsole\Console;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Worker;
use Friendica\Protocol\Delivery;
use Friendica\Util\Strings;

class Relocate extends Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var IManageConfigValues
	 */
	private $config;
	/**
	 * @var \Friendica\App\BaseURL
	 */
	private $baseUrl;
	/**
	 * @var \Friendica\Database\Database
	 */
	private $database;

	protected function getHelp()
	{
		$help = <<<HELP
console relocate - Update the node base URL
Usage
    bin/console relocate <new base URL> [-h|--help|-?] [-v]

Description
    Warning! Advanced function. Could make this server unreachable.

    Change the base URL for this server. Sends relocation message to all the Friendica and Diaspora* contacts of all local users.
    This process updates all the database fields that may contain a URL pointing at the current domain, as a result it takes
    a while and the node will be in maintenance mode for the whole duration.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
		return $help;
	}

	public function __construct(\Friendica\App\BaseURL $baseUrl, \Friendica\Database\Database $database, IManageConfigValues $config, $argv = null)
	{
		parent::__construct($argv);

		$this->baseUrl  = $baseUrl;
		$this->database = $database;
		$this->config   = $config;
	}

	protected function doExecute(): int
	{
		if (count($this->args) == 0) {
			$this->out($this->getHelp());
			return 0;
		}

		if (count($this->args) > 1) {
			throw new \Asika\SimpleConsole\CommandArgsException('Too many arguments');
		}

		$new_url = rtrim($this->getArgument(0), '/');

		$parsed = @parse_url($new_url);
		if (!is_array($parsed) || empty($parsed['host']) || empty($parsed['scheme'])) {
			throw new \InvalidArgumentException('Can not parse new base URL. Must have at least <scheme>://<domain>');
		}

		$this->out(sprintf('Relocation started from %s to %s. Could take a while to complete.', $this->baseUrl, $this->getArgument(0)));

		$old_url = $this->baseUrl;

		// Generate host names for relocation the addresses in the format user@address.tld
		$new_host = str_replace('http://', '@', Strings::normaliseLink($new_url));
		$old_host = str_replace('http://', '@', Strings::normaliseLink($old_url));

		$this->out('Entering maintenance mode');
		$this->config->beginTransaction()
					 ->set('system', 'maintenance', true)
					 ->set('system', 'maintenance_reason', 'Relocating node to ' . $new_url)
					 ->commit();
		try {
			if (!$this->database->transaction()) {
				throw new \Exception('Unable to start a transaction, please retry later.');
			}

			// update tables
			$this->out('Updating apcontact table fields');
			$this->database->replaceInTableFields('apcontact', ['url', 'inbox', 'outbox', 'sharedinbox', 'photo', 'header', 'alias', 'subscribe', 'baseurl'], $old_url, $new_url);
			$this->database->replaceInTableFields('apcontact', ['addr'], $old_host, $new_host);

			$this->out('Updating contact table fields');
			$this->database->replaceInTableFields('contact', ['photo', 'thumb', 'micro', 'url', 'alias', 'request', 'batch', 'notify', 'poll', 'subscribe', 'baseurl', 'confirm', 'poco', 'avatar', 'header'], $old_url, $new_url);
			$this->database->replaceInTableFields('contact', ['nurl'], Strings::normaliseLink($old_url), Strings::normaliseLink($new_url));
			$this->database->replaceInTableFields('contact', ['addr'], $old_host, $new_host);

			$this->out('Updating conv table fields');
			$this->database->replaceInTableFields('conv', ['creator', 'recips'], $old_host, $new_host);

			$this->out('Updating delayed-post table fields');
			$this->database->replaceInTableFields('delayed-post', ['uri'], $old_url, $new_url);

			$this->out('Updating endpoint table fields');
			$this->database->replaceInTableFields('endpoint', ['url'], $old_url, $new_url);

			$this->out('Updating event table fields');
			$this->database->replaceInTableFields('event', ['uri'], $old_url, $new_url);

			$this->out('Updating diaspora-contact table fields');
			$this->database->replaceInTableFields('diaspora-contact', ['alias', 'photo', 'photo-medium', 'photo-small', 'batch', 'notify', 'poll', 'subscribe'], $old_url, $new_url);
			$this->database->replaceInTableFields('diaspora-contact', ['addr'], $old_host, $new_host);

			$this->out('Updating fsuggest table fields');
			$this->database->replaceInTableFields('fsuggest', ['url', 'request', 'photo'], $old_url, $new_url);

			$this->out('Updating gserver table fields');
			$this->database->replaceInTableFields('gserver', ['url'], $old_url, $new_url);
			$this->database->replaceInTableFields('gserver', ['nurl'], Strings::normaliseLink($old_url), Strings::normaliseLink($new_url));

			$this->out('Updating inbox-status table fields');
			$this->database->replaceInTableFields('inbox-status', ['url'], $old_url, $new_url);

			$this->out('Updating item-uri table fields');
			$this->database->replaceInTableFields('item-uri', ['uri'], $old_url, $new_url);

			$this->out('Updating mail table fields');
			$this->database->replaceInTableFields('mail', ['from-photo', 'from-url', 'uri', 'thr-parent'], $old_url, $new_url);
			$this->database->replaceInTableFields('mail', ['parent-uri'], $old_host, $new_host);

			$this->out('Updating notify table fields');
			$this->database->replaceInTableFields('notify', ['url', 'photo', 'link', 'msg', 'name_cache', 'msg_cache'], $old_url, $new_url);

			$this->out('Updating profile table fields');
			$this->database->replaceInTableFields('profile', ['photo', 'thumb'], $old_url, $new_url);

			$this->out('Updating post-content table fields');
			$this->database->replaceInTableFields('post-content', ['body', 'raw-body', 'rendered-html', 'target', 'plink'], $old_url, $new_url);
			$this->database->replaceInTableFields('post-content', ['body', 'raw-body', 'rendered-html', 'target'], $old_host, $new_host);

			$this->out('Updating post-history table fields');
			$this->database->replaceInTableFields('post-history', ['body', 'raw-body', 'rendered-html', 'target', 'plink'], $old_url, $new_url);
			$this->database->replaceInTableFields('post-history', ['body', 'raw-body', 'rendered-html', 'target'], $old_host, $new_host);

			$this->out('Updating post-link table fields');
			$this->database->replaceInTableFields('post-link', ['url'], $old_url, $new_url);

			$this->out('Updating post-media table fields');
			$this->database->replaceInTableFields('post-media', ['url', 'preview', 'author-url', 'author-image', 'publisher-url', 'publisher-image'], $old_url, $new_url);

			$this->out('Updating tag table fields');
			$this->database->replaceInTableFields('tag', ['url'], $old_url, $new_url);

			// update config
			$this->out('Updating config values');
			$this->config->set('system', 'url', $new_url);

			$this->database->commit();
		} catch (\Throwable $e) {
			$this->database->rollback();

			$this->out('Process aborted with message: ' . $e->getMessage() . ' thrown in ' . $e->getFile() . ':' . $e->getLine());

			return 1;
		} finally {
			$this->out('Leaving maintenance mode');
			$this->config->beginTransaction()
						 ->set('system', 'maintenance', false)
						 ->delete('system', 'maintenance_reason')
						 ->commit();
		}

		// send relocate
		$this->out('Schedule relocation messages to remote Friendica and Diaspora hosts');
		$users = $this->database->selectToArray('user', ['uid'], ['account_removed' => false, 'account_expired' => false]);
		foreach ($users as $user) {
			Worker::add(Worker::PRIORITY_HIGH, 'Notifier', Delivery::RELOCATION, $user['uid']);
		}

		return 0;
	}
}
