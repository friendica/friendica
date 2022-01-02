<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Search;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

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

	protected function rawContent(array $request = [])
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('You must be logged in to use this module.'));
		}

		$type = $_REQUEST['type'] ?? self::TYPE_MENTION_CONTACT_GROUP;
		if ($type === self::TYPE_GLOBAL_CONTACT) {
			$o = self::globalContactSearch();
		} else {
			$o = self::regularContactSearch($type);
		}

		echo json_encode($o);
		exit;
	}

	private static function globalContactSearch()
	{
		// autocomplete for global contact search (e.g. navbar search)
		$search = trim($_REQUEST['search']);
		$mode = $_REQUEST['smode'];
		$page = $_REQUEST['page'] ?? 1;

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

		$o = [
			'start' => ($page - 1) * 20,
			'count' => 1000,
			'items' => $contacts,
		];

		return $o;
	}

	private static function regularContactSearch(string $type)
	{
		$start   = $_REQUEST['start']        ?? 0;
		$count   = $_REQUEST['count']        ?? 100;
		$search  = $_REQUEST['search']       ?? '';
		$conv_id = $_REQUEST['conversation'] ?? null;

		// For use with jquery.textcomplete for private mail completion
		if (!empty($_REQUEST['query'])) {
			if (!$type) {
				$type = self::TYPE_PRIVATE_MESSAGE;
			}
			$search = $_REQUEST['query'];
		}

		Logger::info('ACL {action} - {subaction}', ['module' => 'acl', 'action' => 'content', 'subaction' => 'search', 'search' => $search, 'type' => $type, 'conversation' => $conv_id]);

		$sql_extra = '';
		$condition       = ["`uid` = ? AND NOT `deleted` AND NOT `pending` AND NOT `archive`", local_user()];
		$condition_group = ["`uid` = ? AND NOT `deleted`", local_user()];

		if ($search != '') {
			$sql_extra = "AND `name` LIKE '%%" . DBA::escape($search) . "%%'";
			$condition       = DBA::mergeConditions($condition, ["(`attag` LIKE ? OR `name` LIKE ? OR `nick` LIKE ?)",
				'%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
			$condition_group = DBA::mergeConditions($condition_group, ["`name` LIKE ?", '%' . $search . '%']);
		}

		// count groups and contacts
		$group_count = 0;
		if ($type == self::TYPE_MENTION_CONTACT_GROUP || $type == self::TYPE_MENTION_GROUP) {
			$group_count = DBA::count('group', $condition_group);
		}

		$networks = Widget::unavailableNetworks();
		$condition = DBA::mergeConditions($condition, array_merge(["NOT `network` IN (" . substr(str_repeat("?, ", count($networks)), 0, -2) . ")"], $networks));

		switch ($type) {
			case self::TYPE_MENTION_CONTACT_GROUP:
				$condition = DBA::mergeConditions($condition,
					["NOT `self` AND NOT `blocked` AND `notify` != ? AND NOT `network` IN (?, ?)", '', Protocol::OSTATUS, Protocol::STATUSNET
				]);
				break;

			case self::TYPE_MENTION_CONTACT:
				$condition = DBA::mergeConditions($condition,
					["NOT `self` AND NOT `blocked` AND `notify` != ? AND `network` != ?", '', Protocol::STATUSNET
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

		$contact_count = DBA::count('contact', $condition);

		$tot = $group_count + $contact_count;

		$groups = [];
		$contacts = [];

		if ($type == self::TYPE_MENTION_CONTACT_GROUP || $type == self::TYPE_MENTION_GROUP) {
			/// @todo We should cache this query.
			// This can be done when we can delete cache entries via wildcard
			$r = DBA::toArray(DBA::p("SELECT `group`.`id`, `group`.`name`, GROUP_CONCAT(DISTINCT `group_member`.`contact-id` SEPARATOR ',') AS uids
				FROM `group`
				INNER JOIN `group_member` ON `group_member`.`gid`=`group`.`id`
				WHERE NOT `group`.`deleted` AND `group`.`uid` = ?
					$sql_extra
				GROUP BY `group`.`name`, `group`.`id`
				ORDER BY `group`.`name`
				LIMIT ?, ?",
				local_user(),
				$start,
				$count
			));

			foreach ($r as $g) {
				$groups[] = [
					'type'  => 'g',
					'photo' => 'images/twopeople.png',
					'name'  => htmlspecialchars($g['name']),
					'id'    => intval($g['id']),
					'uids'  => array_map('intval', explode(',', $g['uids'])),
					'link'  => '',
					'forum' => '0'
				];
			}
			if ((count($groups) > 0) && ($search == '')) {
				$groups[] = ['separator' => true];
			}
		}

		$r = [];
		if ($type != self::TYPE_MENTION_GROUP) {
			$r = Contact::selectToArray([], $condition, ['order' => ['name']]);
		}

		if (DBA::isResult($r)) {
			$forums = [];
			foreach ($r as $g) {
				$entry = [
					'type'    => 'c',
					'photo'   => Contact::getMicro($g),
					'name'    => htmlspecialchars($g['name']),
					'id'      => intval($g['id']),
					'network' => $g['network'],
					'link'    => $g['url'],
					'nick'    => htmlentities(($g['attag'] ?? '') ?: $g['nick']),
					'addr'    => htmlentities(($g['addr'] ?? '') ?: $g['url']),
					'forum'   => $g['contact-type'] == Contact::TYPE_COMMUNITY,
				];
				if ($entry['forum']) {
					$forums[] = $entry;
				} else {
					$contacts[] = $entry;
				}
			}
			if (count($forums) > 0) {
				if ($search == '') {
					$forums[] = ['separator' => true];
				}
				$contacts = array_merge($forums, $contacts);
			}
		}

		$items = array_merge($groups, $contacts);

		if ($conv_id) {
			// In multi threaded posts the conv_id is not the parent of the whole thread
			$parent_item = Post::selectFirst(['parent'], ['id' => $conv_id]);
			if (DBA::isResult($parent_item)) {
				$conv_id = $parent_item['parent'];
			}

			/*
			 * if $conv_id is set, get unknown contacts in thread
			 * but first get known contacts url to filter them out
			 */
			$known_contacts = array_map(function ($i) {
				return $i['link'];
			}, $contacts);

			$unknown_contacts = [];

			$condition = ["`parent` = ?", $conv_id];
			$params = ['order' => ['author-name' => true]];
			$authors = Post::selectForUser(local_user(), ['author-link'], $condition, $params);
			$item_authors = [];
			while ($author = Post::fetch($authors)) {
				$item_authors[$author['author-link']] = $author['author-link'];
			}
			DBA::close($authors);

			foreach ($item_authors as $author) {
				if (in_array($author, $known_contacts)) {
					continue;
				}

				$contact = Contact::getByURL($author, false, ['micro', 'name', 'id', 'network', 'nick', 'addr', 'url', 'forum', 'avatar']);

				if (count($contact) > 0) {
					$unknown_contacts[] = [
						'type'    => 'c',
						'photo'   => Contact::getMicro($contact),
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

			$items = array_merge($items, $unknown_contacts);
			$tot += count($unknown_contacts);
		}

		$results = [
			'tot'      => $tot,
			'start'    => $start,
			'count'    => $count,
			'groups'   => $groups,
			'contacts' => $contacts,
			'items'    => $items,
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

		return $o;
	}
}
