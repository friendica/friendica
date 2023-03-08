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

namespace Friendica\Module\Search;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Search;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * ACL selector json backend
 *
 * @package Friendica\Module\Search
 */
class Acl extends BaseModule
{
	const TYPE_GLOBAL_CONTACT        = 'x';
	const TYPE_MENTION_CONTACT       = 'c';
	const TYPE_MENTION_GROUP         = 'g';
	const TYPE_MENTION_CONTACT_GROUP = '';
	const TYPE_MENTION_FORUM         = 'f';
	const TYPE_PRIVATE_MESSAGE       = 'm';
	const TYPE_ANY_CONTACT           = 'a';

	/** @var IHandleUserSessions */
	private $session;
	/** @var Database */
	private $database;

	public function __construct(Database $database, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session  = $session;
		$this->database = $database;
	}

	protected function rawContent(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException($this->t('You must be logged in to use this module.'));
		}

		$type = $request['type'] ?? self::TYPE_MENTION_CONTACT_GROUP;
		if ($type === self::TYPE_GLOBAL_CONTACT) {
			$o = $this->globalContactSearch($request);
		} else {
			$o = $this->regularContactSearch($request, $type);
		}

		System::jsonExit($o);
	}

	private function globalContactSearch(array $request): array
	{
		// autocomplete for global contact search (e.g. navbar search)
		$search = trim($request['search']);
		$mode   = $request['smode'];
		$page   = $request['page'] ?? 1;

		$result = Search::searchContact($search, $mode, $page);

		$contacts = [];
		foreach ($result as $contact) {
			$contacts[] = [
				'photo'   => Contact::getMicro($contact, true),
				'name'    => htmlspecialchars($contact['name']),
				'nick'    => $contact['addr'] ?: $contact['url'],
				'network' => $contact['network'],
				'link'    => $contact['url'],
				'forum'   => $contact['contact-type'] == Contact::TYPE_COMMUNITY,
			];
		}

		return [
			'start' => ($page - 1) * 20,
			'count' => 1000,
			'items' => $contacts,
		];
	}

	private function regularContactSearch(array $request, string $type): array
	{
		$start   = $request['start'] ?? 0;
		$count   = $request['count'] ?? 100;
		$search  = $request['search'] ?? '';
		$conv_id = $request['conversation'] ?? null;

		// For use with jquery.textcomplete for private mail completion
		if (!empty($request['query'])) {
			if (!$type) {
				$type = self::TYPE_PRIVATE_MESSAGE;
			}
			$search = $request['query'];
		}

		$this->logger->info('ACL {action} - {subaction} - start', ['module' => 'acl', 'action' => 'content', 'subaction' => 'search', 'search' => $search, 'type' => $type, 'conversation' => $conv_id]);

		$sql_extra       = '';
		$condition       = ["`uid` = ? AND NOT `deleted` AND NOT `pending` AND NOT `archive`", $this->session->getLocalUserId()];
		$condition_group = ["`uid` = ? AND NOT `deleted`", $this->session->getLocalUserId()];

		if ($search != '') {
			$sql_extra       = "AND `name` LIKE '%%" . $this->database->escape($search) . "%%'";
			$condition       = DBA::mergeConditions($condition, ["(`attag` LIKE ? OR `name` LIKE ? OR `nick` LIKE ?)",
			                                                     '%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
			$condition_group = DBA::mergeConditions($condition_group, ["`name` LIKE ?", '%' . $search . '%']);
		}

		// count groups and contacts
		$group_count = 0;
		if ($type == self::TYPE_MENTION_CONTACT_GROUP || $type == self::TYPE_MENTION_GROUP) {
			$group_count = $this->database->count('group', $condition_group);
		}

		$networks  = Widget::unavailableNetworks();
		$condition = DBA::mergeConditions($condition, array_merge(["NOT `network` IN (" . substr(str_repeat("?, ", count($networks)), 0, -2) . ")"], $networks));

		switch ($type) {
			case self::TYPE_MENTION_CONTACT_GROUP:
				$condition = DBA::mergeConditions($condition,
					["NOT `self` AND NOT `blocked` AND `notify` != ? AND `network` != ?", '', Protocol::OSTATUS
					]);
				break;

			case self::TYPE_MENTION_CONTACT:
				$condition = DBA::mergeConditions($condition,
					["NOT `self` AND NOT `blocked` AND `notify` != ?", ''
					]);
				break;

			case self::TYPE_MENTION_FORUM:
				$condition = DBA::mergeConditions($condition,
					["NOT `self` AND NOT `blocked` AND `notify` != ? AND `contact-type` = ?", '', Contact::TYPE_COMMUNITY
					]);
				break;

			case self::TYPE_PRIVATE_MESSAGE:
				$condition = DBA::mergeConditions($condition,
					["NOT `self` AND NOT `blocked` AND `notify` != ? AND `network` IN (?, ?, ?)", '', Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA
					]);
				break;
		}

		$contact_count = $this->database->count('contact', $condition);

		$resultTotal = $group_count + $contact_count;

		$resultGroups   = [];
		$resultContacts = [];

		if ($type == self::TYPE_MENTION_CONTACT_GROUP || $type == self::TYPE_MENTION_GROUP) {
			/// @todo We should cache this query.
			// This can be done when we can delete cache entries via wildcard
			$groups = $this->database->toArray($this->database->p("SELECT `group`.`id`, `group`.`name`, GROUP_CONCAT(DISTINCT `group_member`.`contact-id` SEPARATOR ',') AS uids
				FROM `group`
				INNER JOIN `group_member` ON `group_member`.`gid`=`group`.`id`
				WHERE NOT `group`.`deleted` AND `group`.`uid` = ?
					$sql_extra
				GROUP BY `group`.`name`, `group`.`id`
				ORDER BY `group`.`name`
				LIMIT ?, ?",
				$this->session->getLocalUserId(),
				$start,
				$count
			));

			foreach ($groups as $group) {
				$resultGroups[] = [
					'type'  => 'g',
					'photo' => 'images/twopeople.png',
					'name'  => htmlspecialchars($group['name']),
					'id'    => intval($group['id']),
					'uids'  => array_map('intval', explode(',', $group['uids'])),
					'link'  => '',
					'forum' => '0'
				];
			}
			if ((count($resultGroups) > 0) && ($search == '')) {
				$resultGroups[] = ['separator' => true];
			}
		}

		$contacts = [];
		if ($type != self::TYPE_MENTION_GROUP) {
			$contacts = Contact::selectToArray([], $condition, ['order' => ['name']]);
		}

		$forums = [];
		foreach ($contacts as $contact) {
			$entry = [
				'type'    => 'c',
				'photo'   => Contact::getMicro($contact, true),
				'name'    => htmlspecialchars($contact['name']),
				'id'      => intval($contact['id']),
				'network' => $contact['network'],
				'link'    => $contact['url'],
				'nick'    => htmlentities(($contact['attag'] ?? '') ?: $contact['nick']),
				'addr'    => htmlentities(($contact['addr'] ?? '') ?: $contact['url']),
				'forum'   => $contact['contact-type'] == Contact::TYPE_COMMUNITY,
			];
			if ($entry['forum']) {
				$forums[] = $entry;
			} else {
				$resultContacts[] = $entry;
			}
		}

		if ($forums) {
			if ($search == '') {
				$forums[] = ['separator' => true];
			}

			$resultContacts = array_merge($forums, $resultContacts);
		}

		$resultItems = array_merge($resultGroups, $resultContacts);

		if ($conv_id) {
			// In multithreaded posts the conv_id is not the parent of the whole thread
			$parent_item = Post::selectFirst(['parent'], ['id' => $conv_id]);
			if ($parent_item) {
				$conv_id = $parent_item['parent'];
			}

			/*
			 * if $conv_id is set, get unknown contacts in thread
			 * but first get known contacts url to filter them out
			 */
			$known_contacts = array_column($resultContacts, 'link');

			$unknown_contacts = [];

			$condition    = ["`parent` = ?", $conv_id];
			$params       = ['order' => ['author-name' => true]];
			$authors      = Post::selectForUser($this->session->getLocalUserId(), ['author-link'], $condition, $params);
			$item_authors = [];
			while ($author = Post::fetch($authors)) {
				$item_authors[$author['author-link']] = $author['author-link'];
			}

			$this->database->close($authors);

			foreach (array_diff($item_authors, $known_contacts) as $author) {
				$contact = Contact::getByURL($author, false, ['micro', 'name', 'id', 'network', 'nick', 'addr', 'url', 'forum', 'avatar']);
				if ($contact) {
					$unknown_contacts[] = [
						'type'    => 'c',
						'photo'   => Contact::getMicro($contact, true),
						'name'    => htmlspecialchars($contact['name']),
						'id'      => intval($contact['id']),
						'network' => $contact['network'],
						'link'    => $contact['url'],
						'nick'    => htmlentities(($contact['nick'] ?? '') ?: $contact['addr']),
						'addr'    => htmlentities(($contact['addr'] ?? '') ?: $contact['url']),
						'forum'   => $contact['forum']
					];
				}
			}

			$resultItems = array_merge($resultItems, $unknown_contacts);
			$resultTotal += count($unknown_contacts);
		}

		$results = [
			'tot'      => $resultTotal,
			'start'    => $start,
			'count'    => $count,
			'groups'   => $resultGroups,
			'contacts' => $resultContacts,
			'items'    => $resultItems,
			'type'     => $type,
			'search'   => $search,
		];

		Hook::callAll('acl_lookup_end', $results);

		$o = [
			'tot'   => $results['tot'],
			'start' => $results['start'],
			'count' => $results['count'],
			'items' => $results['items'],
		];

		$this->logger->info('ACL {action} - {subaction} - done', ['module' => 'acl', 'action' => 'content', 'subaction' => 'search', 'search' => $search, 'type' => $type, 'conversation' => $conv_id]);
		return $o;
	}
}
