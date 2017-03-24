<?php
require_once("include/diaspora.php");

/**
 * @brief add/remove activity to an item
 *
 * Toggle activities as like,dislike,attend of an item
 *
 * @param string $item_id
 * @param string $verb
 * 		Activity verb. One of
 * 			like, unlike, dislike, undislike, attendyes, unattendyes,
 * 			attendno, unattendno, attendmaybe, unattendmaybe
 * @hook 'post_local_end'
 * 		array $arr
 * 			'post_id' => ID of posted item
 */
function do_like($item_id, $verb) {
	$a = get_app();

	if (! local_user() && ! remote_user()) {
		return false;
	}

	switch ($verb) {
		case 'like':
			$bodyverb = t('%1$s likes %2$s\'s %3$s');
			$activity = ACTIVITY_LIKE;
			break;
		case 'unlike':
			$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');
			$activity = ACTIVITY_LIKE;
			break;
		case 'dislike':
		case 'undislike':
			$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');
			$activity = ACTIVITY_DISLIKE;
			break;
		case 'attendyes':
		case 'unattendyes':
			$bodyverb = t('%1$s is attending %2$s\'s %3$s');
			$activity = ACTIVITY_ATTEND;
			break;
		case 'attendno':
		case 'unattendno':
			$bodyverb = t('%1$s is not attending %2$s\'s %3$s');
			$activity = ACTIVITY_ATTENDNO;
			break;
		case 'attendmaybe':
		case 'unattendmaybe':
			$bodyverb = t('%1$s may attend %2$s\'s %3$s');
			$activity = ACTIVITY_ATTENDMAYBE;
			break;
		default:
			logger('like: unknown verb ' . $verb . ' for item ' . $item_id);
			return false;
	}

	// Enable activity toggling instead of on/off
	$event_verb_flag = $activity === ACTIVITY_ATTEND || $activity === ACTIVITY_ATTENDNO || $activity === ACTIVITY_ATTENDMAYBE;

	logger('like: verb ' . $verb . ' item ' . $item_id);

	// Retrieve item
	$items = q("SELECT * FROM `item` WHERE `id` = '%s' OR `uri` = '%s' LIMIT 1",
		dbesc($item_id),
		dbesc($item_id)
	);

	if (! $item_id || ! dbm::is_result($items)) {
		logger('like: unknown item ' . $item_id);
		return false;
	}

	$item = $items[0];

	if (! can_write_wall($a, $item['uid'])) {
		logger('like: unable to write on wall ' . $item['uid']);
		return false;
	}

	// Retrieves the local post owner
	$owners = q("SELECT `contact`.* FROM `contact`
		WHERE `contact`.`self` = 1
		AND `contact`.`uid` = %d",
		intval($item['uid'])
	);
	if (dbm::is_result($owners)) {
		$owner_self_contact = $owners[0];
	} else {
		logger('like: unknown owner ' . $item['uid']);
		return false;
	}

	// Retrieve the current logged in user's public contact
	$author_id = public_contact();

	$contacts = q("SELECT * FROM `contact` WHERE `id` = %d",
		intval($author_id)
	);
	if (dbm::is_result($contacts)) {
		$author_contact = $contacts[0];
	} else {
		logger('like: unknown author ' . $author_id);
		return false;
	}

	// Contact-id is the uid-dependant author contact
	if (local_user() == $item['uid']) {
		$item_contact_id = $owner_self_contact['id'];
		$item_contact = $owner_self_contact;
	} else {
		$item_contact_id = get_contact($author_contact['url'], $item['uid']);

		$contacts = q("SELECT * FROM `contact` WHERE `id` = %d",
			intval($item_contact_id)
		);
		if (dbm::is_result($contacts)) {
			$item_contact = $contacts[0];
		} else {
			logger('like: unknown item contact ' . $item_contact_id);
			return false;
		}
	}

	// Look for an existing verb row
	// event participation are essentially radio toggles. If you make a subsequent choice,
	// we need to eradicate your first choice.
	if ($event_verb_flag) {
		$verbs = "'" . dbesc(ACTIVITY_ATTEND) . "', '" . dbesc(ACTIVITY_ATTENDNO) . "', '" . dbesc(ACTIVITY_ATTENDMAYBE) . "'";
	} else {
		$verbs = "'".dbesc($activity)."'";
	}

	$existing_like = q("SELECT `id`, `guid`, `verb` FROM `item`
		WHERE `verb` IN ($verbs)
		AND `deleted` = 0
		AND `author-id` = %d
		AND `uid` = %d
		AND (`parent` = '%s' OR `parent-uri` = '%s' OR `thr-parent` = '%s')
		LIMIT 1",
		intval($author_contact['id']),
		intval($item['uid']),
		dbesc($item_id), dbesc($item_id), dbesc($item['uri'])
	);

	// If it exists, mark it as deleted
	if (dbm::is_result($existing_like)) {
		$like_item = $existing_like[0];

		// Already voted, undo it
		q("UPDATE `item` SET `deleted` = 1, `unseen` = 1, `changed` = '%s' WHERE `id` = %d",
			dbesc(datetime_convert()),
			intval($like_item['id'])
		);

		// Clean up the Diaspora signatures for this like
		// Go ahead and do it even if Diaspora support is disabled. We still want to clean up
		// if it had been enabled in the past
		q("DELETE FROM `sign` WHERE `iid` = %d",
			intval($like_item['id'])
		);

		$like_item_id = $like_item['id'];
		proc_run(PRIORITY_HIGH, "include/notifier.php", "like", $like_item_id);

		if (!$event_verb_flag || $like_item['verb'] == $activity) {
			return true;
		}
	}

	// Verb is "un-something", just trying to delete existing entries
	if (strpos($verb, 'un') === 0) {
		return true;
	}

	// Else or if event verb different from existing row, create a new item row
	$post_type = (($item['resource-id']) ? t('photo') : t('status'));
	if ($item['object-type'] === ACTIVITY_OBJ_EVENT) {
		$post_type = t('event');
	}
	$objtype = $item['resource-id'] ? ACTIVITY_OBJ_IMAGE : ACTIVITY_OBJ_NOTE ;
	$link = xmlify('<link rel="alternate" type="text/html" href="' . App::get_baseurl() . '/display/' . $owner_self_contact['nick'] . '/' . $item['id'] . '" />' . "\n") ;
	$body = $item['body'];

	$obj = <<< EOT

	<object>
		<type>$objtype</type>
		<local>1</local>
		<id>{$item['uri']}</id>
		<link>$link</link>
		<title></title>
		<content>$body</content>
	</object>
EOT;

	$ulink = '[url=' . $author_contact['url'] . ']' . $author_contact['name'] . '[/url]';
	$alink = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
	$plink = '[url=' . App::get_baseurl() . '/display/' . $owner_self_contact['nick'] . '/' . $item['id'] . ']' . $post_type . '[/url]';

	$new_item = array(
		'guid'          => get_guid(32),
		'uri'           => item_new_uri($a->get_hostname(), $item['uid']),
		'uid'           => $item['uid'],
		'contact-id'    => $item_contact_id,
		'type'          => 'activity',
		'wall'          => $item['wall'],
		'origin'        => 1,
		'gravity'       => GRAVITY_LIKE,
		'parent'        => $item['id'],
		'parent-uri'    => $item['uri'],
		'thr-parent'    => $item['uri'],
		'owner-id'      => $item['owner-id'],
		'owner-name'    => $item['owner-name'],
		'owner-link'    => $item['owner-link'],
		'owner-avatar'  => $item['owner-avatar'],
		'author-id'     => $author_contact['id'],
		'author-name'   => $author_contact['name'],
		'author-link'   => $author_contact['url'],
		'author-avatar' => $author_contact['thumb'],
		'body'          => sprintf($bodyverb, $ulink, $alink, $plink),
		'verb'          => $activity,
		'object-type'   => $objtype,
		'object'        => $obj,
		'allow_cid'     => $item['allow_cid'],
		'allow_gid'     => $item['allow_gid'],
		'deny_cid'      => $item['deny_cid'],
		'deny_gid'      => $item['deny_gid'],
		'visible'       => 1,
		'unseen'        => 1,
		'last-child'    => 0
	);

	$new_item_id = item_store($new_item);

	// @todo: Explain this block
	if (! $item['visible']) {
		q("UPDATE `item` SET `visible` = 1 WHERE `id` = %d AND `uid` = %d",
			intval($item['id']),
			intval($item['uid'])
		);
	}

	// Save the author information for the like in case we need to relay to Diaspora
	Diaspora::store_like_signature($item_contact, $new_item_id);

	$new_item['id'] = $new_item_id;

	call_hooks('post_local_end', $new_item);

	proc_run(PRIORITY_HIGH, "include/notifier.php", "like", $new_item_id);

	return true;
}
