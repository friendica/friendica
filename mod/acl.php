<?php

/* ACL selector json backend */

use Friendica\App;
use Friendica\Content\Widget;
use Friendica\Core\Acl;
use Friendica\Core\Addon;
use Friendica\Database\DBM;
use Friendica\Model\Contact;

require_once 'include/dba.php';
require_once 'mod/proxy.php';

function acl_content(App $a)
{
	if (!local_user()) {
		return '';
	}

	$start = defaults($_REQUEST, 'start', 0);
	$count = defaults($_REQUEST, 'count', 100);
	$search = defaults($_REQUEST, 'search', '');
	$type = defaults($_REQUEST, 'type', '');
	$conv_id = defaults($_REQUEST, 'conversation', null);

	// For use with jquery.textcomplete for private mail completion
	if (x($_REQUEST, 'query')) {
		if (!$type) {
			$type = 'm';
		}
		$search = $_REQUEST['query'];
	}

	logger('Searching for ' . $search . ' - type ' . $type, LOGGER_DEBUG);

	if ($search != '') {
		$sql_extra = "AND `name` LIKE '%%" . dbesc($search) . "%%'";
		$sql_extra2 = "AND (`attag` LIKE '%%" . dbesc($search) . "%%' OR `name` LIKE '%%" . dbesc($search) . "%%' OR `nick` LIKE '%%" . dbesc($search) . "%%')";
	} else {
		/// @TODO Avoid these needless else blocks by putting variable-initialization atop of if()
		$sql_extra = $sql_extra2 = '';
	}

	// count groups and contacts
	if ($type == '' || $type == 'g') {
		$r = q("SELECT COUNT(*) AS g FROM `group` WHERE `deleted` = 0 AND `uid` = %d $sql_extra",
			intval(local_user())
		);
		$group_count = (int) $r[0]['g'];
	} else {
		$group_count = 0;
	}

	$sql_extra2 .= ' ' . Widget::unavailableNetworks();

	if ($type == '' || $type == 'c') {
		// autocomplete for editor mentions
		$r = q("SELECT COUNT(*) AS c FROM `contact`
				WHERE `uid` = %d AND NOT `self`
				AND NOT `blocked` AND NOT `pending` AND NOT `archive`
				AND `success_update` >= `failure_update`
				AND `notify` != '' $sql_extra2",
			intval(local_user())
		);
		$contact_count = (int) $r[0]['c'];
	} elseif ($type == 'f') {
		// autocomplete for editor mentions of forums
		$r = q("SELECT COUNT(*) AS c FROM `contact`
				WHERE `uid` = %d AND NOT `self`
				AND NOT `blocked` AND NOT `pending` AND NOT `archive`
				AND (`forum` OR `prv`)
				AND `success_update` >= `failure_update`
				AND `notify` != '' $sql_extra2",
			intval(local_user())
		);
		$contact_count = (int) $r[0]['c'];
	} elseif ($type == 'm') {
		// autocomplete for Private Messages
		$r = q("SELECT COUNT(*) AS c FROM `contact`
				WHERE `uid` = %d AND NOT `self`
				AND NOT `blocked` AND NOT `pending` AND NOT `archive`
				AND `success_update` >= `failure_update`
				AND `network` IN ('%s', '%s') $sql_extra2",
			intval(local_user()),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA)
		);
		$contact_count = (int) $r[0]['c'];
	} elseif ($type == 'a') {
		// autocomplete for Contacts
		$r = q("SELECT COUNT(*) AS c FROM `contact`
				WHERE `uid` = %d AND NOT `self`
				AND NOT `pending` $sql_extra2",
			intval(local_user())
		);
		$contact_count = (int) $r[0]['c'];
	} else {
		$contact_count = 0;
	}

	$tot = $group_count + $contact_count;

	$groups = [];
	$contacts = [];

	if ($type == '' || $type == 'g') {
		/// @todo We should cache this query.
		// This can be done when we can delete cache entries via wildcard
		$r = q("SELECT `group`.`id`, `group`.`name`, GROUP_CONCAT(DISTINCT `group_member`.`contact-id` SEPARATOR ',') AS uids
				FROM `group`
				INNER JOIN `group_member` ON `group_member`.`gid`=`group`.`id`
				WHERE NOT `group`.`deleted` AND `group`.`uid` = %d
					$sql_extra
				GROUP BY `group`.`name`, `group`.`id`
				ORDER BY `group`.`name`
				LIMIT %d,%d",
			intval(local_user()),
			intval($start),
			intval($count)
		);

		foreach ($r as $g) {
			$groups[] = [
				'type' => 'g',
				'photo' => 'images/twopeople.png',
				'name' => htmlentities($g['name']),
				'id' => intval($g['id']),
				'uids' => array_map('intval', explode(',', $g['uids'])),
				'link' => '',
				'forum' => '0'
			];
		}
		if ((count($groups) > 0) && ($search == '')) {
			$groups[] = ['separator' => true];
		}
	}

	if ($type == '') {
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag`, `addr`, `forum`, `prv`, (`prv` OR `forum`) AS `frm` FROM `contact`
			WHERE `uid` = %d AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND `notify` != ''
			AND `success_update` >= `failure_update` AND NOT (`network` IN ('%s', '%s'))
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user()),
			dbesc(NETWORK_OSTATUS),
			dbesc(NETWORK_STATUSNET)
		);
	} elseif ($type == 'c') {
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag`, `addr`, `forum`, `prv` FROM `contact`
			WHERE `uid` = %d AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND `notify` != ''
			AND `success_update` >= `failure_update` AND NOT (`network` IN ('%s'))
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user()),
			dbesc(NETWORK_STATUSNET)
		);
	} elseif ($type == 'f') {
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag`, `addr`, `forum`, `prv` FROM `contact`
			WHERE `uid` = %d AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive` AND `notify` != ''
			AND `success_update` >= `failure_update` AND NOT (`network` IN ('%s'))
			AND (`forum` OR `prv`)
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user()),
			dbesc(NETWORK_STATUSNET)
		);
	} elseif ($type == 'm') {
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag`, `addr` FROM `contact`
			WHERE `uid` = %d AND NOT `self` AND NOT `blocked` AND NOT `pending` AND NOT `archive`
			AND `success_update` >= `failure_update` AND `network` IN ('%s', '%s')
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user()),
			dbesc(NETWORK_DFRN),
			dbesc(NETWORK_DIASPORA)
		);
	} elseif ($type == 'a') {
		$r = q("SELECT `id`, `name`, `nick`, `micro`, `network`, `url`, `attag`, `addr`, `forum`, `prv` FROM `contact`
			WHERE `uid` = %d AND `pending` = 0 AND `success_update` >= `failure_update`
			$sql_extra2
			ORDER BY `name` ASC ",
			intval(local_user())
		);
	} elseif ($type == 'x') {
		// autocomplete for global contact search (e.g. navbar search)
		$search = notags(trim($_REQUEST['search']));
		$mode = $_REQUEST['smode'];

		$r = Acl::contactAutocomplete($search, $mode);

		$contacts = [];
		foreach ($r as $g) {
			$contacts[] = [
				'photo'   => proxy_url($g['photo'], false, PROXY_SIZE_MICRO),
				'name'    => $g['name'],
				'nick'    => (x($g['addr']) ? $g['addr'] : $g['url']),
				'network' => $g['network'],
				'link'    => $g['url'],
				'forum'   => (x($g['community']) ? 1 : 0),
			];
		}
		$o = [
			'start' => $start,
			'count' => $count,
			'items' => $contacts,
		];
		echo json_encode($o);
		killme();
	} else {
		$r = [];
	}

	if (DBM::is_result($r)) {
		$forums = [];
		foreach ($r as $g) {
			$entry = [
				'type'    => 'c',
				'photo'   => proxy_url($g['micro'], false, PROXY_SIZE_MICRO),
				'name'    => htmlentities($g['name']),
				'id'      => intval($g['id']),
				'network' => $g['network'],
				'link'    => $g['url'],
				'nick'    => htmlentities(($g['attag']) ? $g['attag'] : $g['nick']),
				'addr'    => htmlentities(($g['addr']) ? $g['addr'] : $g['url']),
				'forum'   => ((x($g, 'forum') || x($g, 'prv')) ? 1 : 0),
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
		/*
		 * if $conv_id is set, get unknown contacts in thread
		 * but first get known contacts url to filter them out
		 */
		$known_contacts = array_map(function ($i) {
			return dbesc($i['link']);
		}, $contacts);

		$unknown_contacts = [];
		$r = q("SELECT `author-link`
				FROM `item` WHERE `parent` = %d
					AND (`author-name` LIKE '%%%s%%' OR `author-link` LIKE '%%%s%%')
					AND `author-link` NOT IN ('%s')
				GROUP BY `author-link`, `author-avatar`, `author-name`
				ORDER BY `author-name` ASC
				",
			intval($conv_id),
			dbesc($search),
			dbesc($search),
			implode("', '", $known_contacts)
		);
		if (DBM::is_result($r)) {
			foreach ($r as $row) {
				$contact = Contact::getDetailsByURL($row['author-link']);

				if (count($contact) > 0) {
					$unknown_contacts[] = [
						'type' => 'c',
						'photo' => proxy_url($contact['micro'], false, PROXY_SIZE_MICRO),
						'name' => htmlentities($contact['name']),
						'id' => intval($contact['cid']),
						'network' => $contact['network'],
						'link' => $contact['url'],
						'nick' => htmlentities($contact['nick'] ?: $contact['addr']),
						'addr' => htmlentities(($contact['addr']) ? $contact['addr'] : $contact['url']),
						'forum' => $contact['forum']
					];
				}
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

	Addon::callHooks('acl_lookup_end', $results);

	$o = [
		'tot' => $results['tot'],
		'start' => $results['start'],
		'count' => $results['count'],
		'items' => $results['items'],
	];

	echo json_encode($o);

	killme();
}
