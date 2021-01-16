<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

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

	public static function rawContent(array $parameters = [])
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
		$search = Strings::escapeTags(trim($_REQUEST['search']));
		$mode = $_REQUEST['smode'];
		$page = $_REQUEST['page'] ?? 1;

		$result = Search::searchContact($search, $mode, $page);

		$contacts = [];
		foreach ($result as $contact) {
			$contacts[] = [
				'photo'   => Contact::getMicro($contact),
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
		$sql_extra2 = '';

		if ($search != '') {
			$sql_extra = "AND `name` LIKE '%%" . DBA::escape($search) . "%%'";
			$sql_extra2 = "AND (`attag` LIKE '%%" . DBA::escape($search) . "%%' OR `name` LIKE '%%" . DBA::escape($search) . "%%' OR `nick` LIKE '%%" . DBA::escape($search) . "%%')";
		}

		// count groups and contacts
		$group_count = 0;
		if ($type == self::TYPE_MENTION_CONTACT_GROUP || $type == self::TYPE_MENTION_GROUP) {
			$r = q("SELECT COUNT(*) AS g FROM `group` WHERE NOT `deleted` AND `uid` = %d $sql_extra",
				intval(local_user())
			);
			$group_count = (int) $r[0]['g'];
		}

		$sql_extra2 .= ' ' . Widget::unavailableNetworks();

		$contact_count = 0;
		switch ($type) {
			case self::TYPE_MENTION_CONTACT_GROUP:
			case self::TYPE_MENTION_CONTACT:
				// autocomplete for editor mentions
				$r = q("SELECT COUNT(*) AS c FROM `contact`
					WHERE `uid` = %d AND NOT `self` AND NOT `deleted`
					AND NOT `blocked` AND NOT `pending` AND NOT `archive`
					AND `notify` != '' $sql_extra2",
					intval(local_user())
				);
				$contact_count = (int) $r[0]['c'];
				break;

			case self::TYPE_MENTION_FORUM:
				// autocomplete for editor mentions of forums
				$r = q("SELECT COUNT(*) AS c FROM `contact`
					WHERE `uid` = %d AND NOT `self` AND NOT `deleted`
					AND NOT `blocked` AND NOT `pending` AND NOT `archive`
					AND (`forum` OR `prv`)
					AND `notify` != '' $sql_extra2",
					intval(local_user())
				);
				$contact_count = (int) $r[0]['c'];
				break;

			case self::TYPE_PRIVATE_MESSAGE:
				// autocomplete for Private Messages
				$r = q("SELECT COUNT(*) AS c FROM `contact`
					WHERE `uid` = %d AND NOT `self` AND NOT `deleted`
					AND NOT `blocked` AND NOT `pending` AND NOT `archive`
					AND `network` IN ('%s', '%s', '%s') $sql_extra2",
					intval(local_user()),
					DBA::escape(Protocol::ACTIVITYPUB),
					DBA::escape(Protocol::DFRN),
					DBA::escape(Protocol::DIASPORA)
				);
				$contact_count = (int) $r[0]['c'];
				break;

			case self::TYPE_ANY_CONTACT:
			default:
				// autocomplete for Contacts
				$r = q("SELECT COUNT(*) AS c FROM `contact`
					WHERE `uid` = %d AND NOT `self`
					AND NOT `pending` AND NOT `deleted` $sql_extra2",
					intval(local_user())
				);
				$contact_count = (int) $r[0]['c'];
				break;
		}

		$tot = $group_count + $contact_count;

		$groups = [];
		$contacts = [];

		if ($type == self::TYPE_MENTION_CONTACT_GROUP || $type == self::TYPE_MENTION_GROUP) {
			/// @todo We should cache this query.
			// This can be done when we can delete cache entries via wildcard
			$r = q("SELECT `group`.`id`, `group`.`name`, GROUP_CONCAT(DISTINCT `group_member`.`contact-id` SEPARATOR ',') AS uids
				FROM `group`
				INNER JOIN `group_member` ON `group_member`.`gid`=`group`.`id`
				WHERE NOT `group`.`deleted` AND `group`.`uid` = %d
					$sql_extra
				GROUP BY `group`.`name`, `group`.`id`
				ORDER BY `group`.`name`
				LIMIT %d, %d",
				intval(local_user()),
				intval($start),
				intval($count)
			);

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
		switch ($type) {
			case self::TYPE_MENTION_CONTACT_GROUP:
				$r = q("SELECT `id`, `name`, `nick`, `avatar`, `micro`, `network`, `url`, `attag`, `addr`, `forum`, `prv`, (`prv` OR `forum`) AS `frm` FROM `contact`
					WHERE `uid` = %d AND NOT `self` AND NOT `deleted` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND `notify` != ''
					AND NOT (`network` IN ('%s', '%s'))
					$sql_extra2
					ORDER BY `name`",
					intval(local_user()),
					DBA::escape(Protocol::OSTATUS),
					DBA::escape(Protocol::STATUSNET)
				);
				break;

			case self::TYPE_MENTION_CONTACT:
				$r = q("SELECT `id`, `name`, `nick`, `avatar`, `micro`, `network`, `url`, `attag`, `addr`, `forum`, `prv` FROM `contact`
					WHERE `uid` = %d AND NOT `self` AND NOT `deleted` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND `notify` != ''
					AND NOT (`network` IN ('%s'))
					$sql_extra2
					ORDER BY `name`",
					intval(local_user()),
					DBA::escape(Protocol::STATUSNET)
				);
				break;

			case self::TYPE_MENTION_FORUM:
				$r = q("SELECT `id`, `name`, `nick`, `avatar`, `micro`, `network`, `url`, `attag`, `addr`, `forum`, `prv` FROM `contact`
					WHERE `uid` = %d AND NOT `self` AND NOT `deleted` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND `notify` != ''
					AND NOT (`network` IN ('%s'))
					AND (`forum` OR `prv`)
					$sql_extra2
					ORDER BY `name`",
					intval(local_user()),
					DBA::escape(Protocol::STATUSNET)
				);
				break;

			case self::TYPE_PRIVATE_MESSAGE:
				$r = q("SELECT `id`, `name`, `nick`, `avatar`, `micro`, `network`, `url`, `attag`, `addr` FROM `contact`
					WHERE `uid` = %d AND NOT `self` AND NOT `deleted` AND NOT `blocked` AND NOT `pending` AND NOT `archive`
					AND `network` IN ('%s', '%s', '%s')
					$sql_extra2
					ORDER BY `name`",
					intval(local_user()),
					DBA::escape(Protocol::ACTIVITYPUB),
					DBA::escape(Protocol::DFRN),
					DBA::escape(Protocol::DIASPORA)
				);
				break;

			case self::TYPE_ANY_CONTACT:
			default:
				$r = q("SELECT `id`, `name`, `nick`, `avatar`, `micro`, `network`, `url`, `attag`, `addr`, `forum`, `prv`, `avatar` FROM `contact`
					WHERE `uid` = %d AND NOT `deleted` AND NOT `pending` AND NOT `archive`
					$sql_extra2
					ORDER BY `name`",
					intval(local_user())
				);
				break;
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
					'forum'   => !empty($g['forum']) || !empty($g['prv']) ? 1 : 0,
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
			$authors = Item::selectForUser(local_user(), ['author-link'], $condition, $params);
			$item_authors = [];
			while ($author = Item::fetch($authors)) {
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
