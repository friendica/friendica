<?php

/**
 * @file include/tags.php
 */
use Friendica\App;
use Friendica\Content\Feature;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;

function create_tags_from_item($itemid)
{
	$profile_base = System::baseUrl();
	$profile_data = parse_url($profile_base);
	$profile_path = defaults($profile_data, 'path', '');
	$profile_base_friendica = $profile_data['host'] . $profile_path . '/profile/';
	$profile_base_diaspora = $profile_data['host'] . $profile_path . '/u/';

	$messages = q("SELECT `guid`, `uid`, `id`, `edited`, `deleted`, `created`, `received`, `title`, `body`, `tag`, `parent` FROM `item` WHERE `id` = %d LIMIT 1", intval($itemid));

	if (!$messages)
		return;

	$message = $messages[0];

	// Clean up all tags
	q("DELETE FROM `term` WHERE `otype` = %d AND `oid` = %d AND `type` IN (%d, %d)",
		intval(TERM_OBJ_POST),
		intval($itemid),
		intval(TERM_HASHTAG),
		intval(TERM_MENTION));

	if ($message['deleted']) {
		return;
	}

	$taglist = explode(',', $message['tag']);

	$tags = '';
	foreach ($taglist as $tag) {
		if ((substr(trim($tag), 0, 1) == '#') || (substr(trim($tag), 0, 1) == '@')) {
			$tags .= ' ' . trim($tag);
		} else {
			$tags .= ' #' . trim($tag);
		}
	}

	$data = ' ' . $message['title'] . ' ' . $message['body'] . ' ' . $tags . ' ';

	// ignore anything in a code block
	$data = preg_replace('/\[code\](.*?)\[\/code\]/sm', '', $data);

	$tags = [];

	$pattern = '/\W\#([^\[].*?)[\s\'".,:;\?!\[\]\/]/ism';
	if (preg_match_all($pattern, $data, $matches)) {
		foreach ($matches[1] as $match) {
			$tags['#' . strtolower($match)] = '';
		}
	}

	$pattern = '/\W([\#@])\[url\=(.*?)\](.*?)\[\/url\]/ism';
	if (preg_match_all($pattern, $data, $matches, PREG_SET_ORDER)) {
		foreach ($matches as $match) {
			$tags[$match[1] . strtolower(trim($match[3], ',.:;[]/\"?!'))] = $match[2];
		}
	}

	foreach ($tags as $tag => $link) {
		if (substr(trim($tag), 0, 1) == '#') {
			// try to ignore #039 or #1 or anything like that
			if (ctype_digit(substr(trim($tag), 1)))
				continue;
			// try to ignore html hex escapes, e.g. #x2317
			if ((substr(trim($tag), 1, 1) == 'x' || substr(trim($tag), 1, 1) == 'X') && ctype_digit(substr(trim($tag), 2)))
				continue;
			$type = TERM_HASHTAG;
			$term = substr($tag, 1);
		} elseif (substr(trim($tag), 0, 1) == '@') {
			$type = TERM_MENTION;
			$term = substr($tag, 1);
		} else { // This shouldn't happen
			$type = TERM_HASHTAG;
			$term = $tag;
		}

		if ($message['uid'] == 0) {
			$global = true;

			q("UPDATE `term` SET `global` = 1 WHERE `otype` = %d AND `guid` = '%s'",
				intval(TERM_OBJ_POST), dbesc($message['guid']));
		} else {
			$isglobal = q("SELECT `global` FROM `term` WHERE `uid` = 0 AND `otype` = %d AND `guid` = '%s'",
				intval(TERM_OBJ_POST), dbesc($message['guid']));

			$global = (count($isglobal) > 0);
		}

		q("INSERT INTO `term` (`uid`, `oid`, `otype`, `type`, `term`, `url`, `guid`, `created`, `received`, `global`)
				VALUES (%d, %d, %d, %d, '%s', '%s', '%s', '%s', '%s', %d)",
			intval($message['uid']), intval($itemid), intval(TERM_OBJ_POST), intval($type), dbesc($term),
			dbesc($link), dbesc($message['guid']), dbesc($message['created']), dbesc($message['received']), intval($global));

		// Search for mentions
		if ((substr($tag, 0, 1) == '@') && (strpos($link, $profile_base_friendica) || strpos($link, $profile_base_diaspora))) {
			$users = q("SELECT `uid` FROM `contact` WHERE self AND (`url` = '%s' OR `nurl` = '%s')", $link, $link);
			foreach ($users AS $user) {
				if ($user['uid'] == $message['uid']) {
					q("UPDATE `item` SET `mention` = 1 WHERE `id` = %d", intval($itemid));

					q("UPDATE `thread` SET `mention` = 1 WHERE `iid` = %d", intval($message['parent']));
				}
			}
		}
	}
}

function create_tags_from_itemuri($itemuri, $uid)
{
	$messages = q("SELECT `id` FROM `item` WHERE uri ='%s' AND uid=%d", dbesc($itemuri), intval($uid));

	if (count($messages)) {
		foreach ($messages as $message) {
			create_tags_from_item($message['id']);
		}
	}
}

function update_items()
{
	$messages = dba::p("SELECT `oid`,`item`.`guid`, `item`.`created`, `item`.`received` FROM `term` INNER JOIN `item` ON `item`.`id`=`term`.`oid` WHERE `term`.`otype` = 1 AND `term`.`guid` = ''");

	logger('fetched messages: ' . dba::num_rows($messages));
	while ($message = dba::fetch($messages)) {
		if ($message['uid'] == 0) {
			$global = true;

			q("UPDATE `term` SET `global` = 1 WHERE `otype` = %d AND `guid` = '%s'",
				intval(TERM_OBJ_POST), dbesc($message['guid']));
		} else {
			$isglobal = q("SELECT `global` FROM `term` WHERE `uid` = 0 AND `otype` = %d AND `guid` = '%s'",
				intval(TERM_OBJ_POST), dbesc($message['guid']));

			$global = (count($isglobal) > 0);
		}

		q("UPDATE `term` SET `guid` = '%s', `created` = '%s', `received` = '%s', `global` = %d WHERE `otype` = %d AND `oid` = %d",
			dbesc($message['guid']), dbesc($message['created']), dbesc($message['received']),
			intval($global), intval(TERM_OBJ_POST), intval($message['oid']));
	}

	dba::close($messages);

	$messages = dba::p("SELECT `guid` FROM `item` WHERE `uid` = 0");

	logger('fetched messages: ' . dba::num_rows($messages));
	while ($message = dba::fetch(messages)) {
		q("UPDATE `item` SET `global` = 1 WHERE `guid` = '%s'", dbesc($message['guid']));
	}

	dba::close($messages);
}

/**
 * @brief Get alphabetical sorted array of used tags/terms of an user including
 * a weighting by frequency of use.
 *
 * @param int $uid      The user ID.
 * @param int $count    Max number of displayed tags/terms.
 * @param int $owner_id The contact id of the owner of the tagged items.
 * @param string $flags Special item flags.
 * @param int $type     The tag/term type.
 *
 * @return arr          Alphabetical sorted array of used tags of an user.
 */
function tagadelic($uid, $count = 0, $owner_id = 0, $flags = '', $type = TERM_HASHTAG)
{
	require_once 'include/security.php';

	$item_condition = item_condition();
	$sql_options = item_permissions_sql($uid);
	$limit = $count ? sprintf('LIMIT %d', intval($count)) : '';

	if ($flags) {
		if ($flags === 'wall') {
			$sql_options .= ' AND `item`.`wall` ';
		}
	}

	if ($owner_id) {
		$sql_options .= ' AND `item`.`owner-id` = ' . intval($owner_id) . ' ';
	}

	// Fetch tags
	$r = dba::p("SELECT `term`, COUNT(`term`) AS `total` FROM `term`
		LEFT JOIN `item` ON `term`.`oid` = `item`.`id`
		WHERE `term`.`uid` = ? AND `term`.`type` = ?
		AND `term`.`otype` = ?
		AND $item_condition $sql_options
		GROUP BY `term` ORDER BY `total` DESC $limit",
		$uid,
		$type,
		TERM_OBJ_POST
	);
	if (!DBM::isResult($r)) {
		return [];
	}

	return tag_calc($r);
}

/**
 * @brief Construct a tag/term cloud block for an user.
 *
 * @param int $uid      The user ID.
 * @param int $count    Max number of displayed tags/terms.
 * @param int $owner_id The contact ID of the owner of the tagged items.
 * @param string $flags Special item flags.
 * @param int $type     The tag/term type.
 *
 * @return string       HTML formatted output.
 */
function wtagblock($uid, $count = 0, $owner_id = 0, $flags = '', $type = TERM_HASHTAG)
{
	$o = '';
	$r = tagadelic($uid, $count, $owner_id, $flags, $type);
	if (count($r)) {
		$contact = dba::selectFirst('contact', ['url'], ['id' => $uid]);
		$url = System::removedBaseUrl($contact['url']);

		foreach ($r as $rr) {
			$tag['level'] = $rr[2];
			$tag['url'] = $url . '?tag=' . urlencode($rr[0]);
			$tag['name'] = $rr[0];

			$tags[] = $tag;
		}

		$tpl = get_markup_template('tagblock_widget.tpl');
		$o = replace_macros($tpl, [
			'$title' => t('Tags'),
			'$tags' => $tags
		]);
	}
	return $o;
}

/**
 * @brief Calculate weighting of tags according to the frequency of use.
 *
 * @param array $arr Array of tags/terms with tag/term name and total count of use.
 * @return array     Alphabetical sorted array of used tags/terms of an user.
 */
function tag_calc($arr)
{
	$tags = [];
	$min = 1e9;
	$max = -1e9;
	$x = 0;

	if (!$arr) {
		return [];
	}

	foreach ($arr as $rr) {
		$tags[$x][0] = $rr['term'];
		$tags[$x][1] = log($rr['total']);
		$tags[$x][2] = 0;
		$min = min($min, $tags[$x][1]);
		$max = max($max, $tags[$x][1]);
		$x ++;
	}

	usort($tags, 'tags_sort');
	$range = max(.01, $max - $min) * 1.0001;

	for ($x = 0; $x < count($tags); $x ++) {
		$tags[$x][2] = 1 + floor(9 * ($tags[$x][1] - $min) / $range);
	}

	return $tags;
}

/**
 * @brief Compare function to sort tags/terms alphabetically.
 *
 * @param type $a
 * @param type $b
 *
 * @return int
 */
function tags_sort($a, $b)
{
	if (strtolower($a[0]) == strtolower($b[0])) {
		return 0;
	}
	return ((strtolower($a[0]) < strtolower($b[0])) ? -1 : 1);
}

/**
 * @brief Insert a tag cloud widget for the present profile.
 *
 * @param int     $limit Max number of displayed tags.
 * @return string HTML formattat output.
 */
function tagcloud_wall_widget($limit = 50)
{
	$a = get_app();

	if (!$a->profile['profile_uid'] || !$a->profile['url']) {
		return '';
	}

	if (Feature::isEnabled($a->profile['profile_uid'], 'tagadelic')) {
		$owner_id = Contact::getIdForURL($a->profile['url']);

		if (!$owner_id) {
			return '';
		}
		return wtagblock($a->profile['profile_uid'], $limit, $owner_id, 'wall');
	}

	return '';
}
