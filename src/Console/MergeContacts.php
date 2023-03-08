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

use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Model\Contact;

/**
 * tool to find and merge duplicated contact entries.
 */
class MergeContacts extends \Asika\SimpleConsole\Console
{
	protected $helpOptions = ['h', 'help', '?'];

	/**
	 * @var $dba Database
	 */
	private $dba;

	/**
	 * @var L10n
	 */
	private $l10n;

	protected function getHelp()
	{
		$help = <<<HELP
console mergecontacts - Merge duplicated contact entries
Synopsis
	bin/console mergecontacts

Description
	bin/console mergecontacts
		Remove duplicated contact entries

Options
	-h|--help|-? Show help information
	-e|--execute Execute the merge
HELP;
		return $help;
	}

	public function __construct(Database $dba, L10n $l10n, array $argv = null)
	{
		parent::__construct($argv);

		$this->dba  = $dba;
		$this->l10n = $l10n;
	}

	protected function doExecute(): int
	{
		$duplicates = $this->dba->p("SELECT COUNT(*) AS `total`, `uri-id`, MAX(`url`) AS `url` FROM `contact` WHERE `uid` = 0 GROUP BY `uri-id` HAVING total > 1");
		while ($duplicate = $this->dba->fetch($duplicates)) {
			$this->out($this->l10n->t('%d %s, %d duplicates.', $duplicate['uri-id'], $duplicate['url'], $duplicate['total']));
			if ($this->getOption(['e', 'execute'], false)) {
				if (empty($duplicate['uri-id'])) {
					$this->err($this->l10n->t('uri-id is empty for contact %s.', $duplicate['url']));
					continue;
				}
				$this->mergeContacts($duplicate['uri-id']);
			}
		}
		return 0;
	}

	private function mergeContacts(int $uriid)
	{
		$first = $this->dba->selectFirst('contact', ['id', 'nurl', 'url'], ["`uri-id` = ? AND `nurl` != ? AND `url` != ?", $uriid, '', ''], ['order' => ['id']]);
		if (empty($first)) {
			$this->err($this->l10n->t('No valid first contact found for uri-id %d.', $uriid));
			return;
		}
		$this->out($first['url']);

		$duplicates = $this->dba->select('contact', ['id', 'nurl', 'url'], ['uri-id' => $uriid]);
		while ($duplicate = $this->dba->fetch($duplicates)) {
			if ($first['id'] == $duplicate['id']) {
				continue;
			}
			if ($first['url'] != $duplicate['url']) {
				$this->err($this->l10n->t('Wrong duplicate found for uri-id %d in %d (url: %s != %s).', $uriid, $duplicate['id'], $first['url'], $duplicate['url']));
				continue;
			}
			if ($first['nurl'] != $duplicate['nurl']) {
				$this->err($this->l10n->t('Wrong duplicate found for uri-id %d in %d (nurl: %s != %s).', $uriid, $duplicate['id'], $first['nurl'], $duplicate['nurl']));
				continue;
			}
			$this->out($duplicate['id'] . "\t" . $duplicate['url']);
			$this->mergeContactInTables($duplicate['id'], $first['id']);
		}
	}

	private function mergeContactInTables(int $from, int $to)
	{
		$this->out($from . "\t=> " . $to);

		foreach (['post', 'post-thread', 'post-thread-user', 'post-user'] as $table) {
			foreach (['author-id', 'causer-id', 'owner-id'] as $field) {
				$this->updateTable($table, $field, $from, $to, false);
			}
		}

		$this->updateTable('contact-relation', 'cid', $from, $to, true);
		$this->updateTable('contact-relation', 'relation-cid', $from, $to, true);
		$this->updateTable('event', 'cid', $from, $to, false);
		$this->updateTable('fsuggest', 'cid', $from, $to, false);
		$this->updateTable('group', 'cid', $from, $to, false);
		$this->updateTable('group_member', 'contact-id', $from, $to, true);
		$this->updateTable('intro', 'contact-id', $from, $to, false);
		$this->updateTable('intro', 'suggest-cid', $from, $to, false);
		$this->updateTable('mail', 'author-id', $from, $to, false);
		$this->updateTable('mail', 'contact-id', $from, $to, false);
		$this->updateTable('notification', 'actor-id', $from, $to, false);
		$this->updateTable('photo', 'contact-id', $from, $to, false);
		$this->updateTable('post-tag', 'cid', $from, $to, true);
		$this->updateTable('post-user', 'contact-id', $from, $to, false);
		$this->updateTable('post-thread-user', 'contact-id', $from, $to, false);
		$this->updateTable('user-contact', 'cid', $from, $to, true);

		if (!Contact::deleteById($from)) {
			$this->err($this->l10n->t('Deletion of id %d failed', $from));
		} else {
			$this->out($this->l10n->t('Deletion of id %d was successful', $from));
		}
	}

	private function updateTable(string $table, string $field, int $from, int $to, bool $in_unique_key)
	{
		$this->out($this->l10n->t('Updating "%s" in "%s" from %d to %d', $field, $table, $from, $to), false);
		if ($this->dba->exists($table, [$field => $from])) {
			$this->out($this->l10n->t(' - found'), false);
			if ($in_unique_key) {
				$params = ['ignore' => true];
			} else {
				$params = [];
			}
			if (!$this->dba->update($table, [$field => $to], [$field => $from], [], $params)) {
				$this->out($this->l10n->t(' - failed'), false);
			} else {
				$this->out($this->l10n->t(' - success'), false);
			}
			if ($in_unique_key && $this->dba->exists($table, [$field => $from])) {
				$this->dba->delete($table, [$field => $from]);
				$this->out($this->l10n->t(' - deleted'), false);
			}
		}
		$this->out($this->l10n->t(' - done'));
	}
}
