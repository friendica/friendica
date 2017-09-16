<?php


function group_add($uid,$name) {

	$ret = false;
	if (x($uid) && x($name)) {
		$r = group_byname($uid,$name); // check for dups
		if ($r !== false) {

			// This could be a problem.
			// Let's assume we've just created a group which we once deleted
			// all the old members are gone, but the group remains so we don't break any security
			// access lists. What we're doing here is reviving the dead group, but old content which
			// was restricted to this group may now be seen by the new group members.

			$z = q("SELECT * FROM `group` WHERE `id` = %d LIMIT 1",
				intval($r)
			);
			if (count($z) && $z[0]['deleted']) {
				$r = q("UPDATE `group` SET `deleted` = 0 WHERE `uid` = %d AND `name` = '%s'",
					intval($uid),
					dbesc($name)
				);
				notice( t('A deleted group with this name was revived. Existing item permissions <strong>may</strong> apply to this group and any future members. If this is not what you intended, please create another group with a different name.') . EOL); 
			}
			return true;
		}
		$r = q("INSERT INTO `group` ( `uid`, `name` )
			VALUES( %d, '%s' ) ",
			intval($uid),
			dbesc($name)
		);
		$ret = $r;
	}
	return $ret;
}


function group_rmv($uid,$name) {
	$ret = false;
	if (x($uid) && x($name)) {
		$r = q("SELECT id FROM `group` WHERE `uid` = %d AND `name` = '%s' LIMIT 1",
			intval($uid),
			dbesc($name)
		);
		if (dbm::is_result($r))
			$group_id = $r[0]['id'];
		if (! $group_id)
			return false;

		// remove group from default posting lists
		$r = q("SELECT def_gid, allow_gid, deny_gid FROM user WHERE uid = %d LIMIT 1",
		       intval($uid)
		);
		if ($r) {
			$user_info = $r[0];
			$change = false;

			if ($user_info['def_gid'] == $group_id) {
				$user_info['def_gid'] = 0;
				$change = true;
			}
			if (strpos($user_info['allow_gid'], '<' . $group_id . '>') !== false) {
				$user_info['allow_gid'] = str_replace('<' . $group_id . '>', '', $user_info['allow_gid']);
				$change = true;
			}
			if (strpos($user_info['deny_gid'], '<' . $group_id . '>') !== false) {
				$user_info['deny_gid'] = str_replace('<' . $group_id . '>', '', $user_info['deny_gid']);
				$change = true;
			}

			if ($change) {
				q("UPDATE user SET def_gid = %d, allow_gid = '%s', deny_gid = '%s' WHERE uid = %d",
				  intval($user_info['def_gid']),
				  dbesc($user_info['allow_gid']),
				  dbesc($user_info['deny_gid']),
				  intval($uid)
				);
			}
		}

		// remove all members
		$r = q("DELETE FROM `group_member` WHERE `uid` = %d AND `gid` = %d ",
			intval($uid),
			intval($group_id)
		);

		// remove group
		$r = q("UPDATE `group` SET `deleted` = 1 WHERE `uid` = %d AND `name` = '%s'",
			intval($uid),
			dbesc($name)
		);

		$ret = $r;

	}

	return $ret;
}

function group_byname($uid,$name) {
	if ((! $uid) || (! strlen($name)))
		return false;
	$r = q("SELECT * FROM `group` WHERE `uid` = %d AND `name` = '%s' LIMIT 1",
		intval($uid),
		dbesc($name)
	);
	if (dbm::is_result($r))
		return $r[0]['id'];
	return false;
}

function group_rmv_member($uid,$name,$member) {
	$gid = group_byname($uid,$name);
	if (! $gid)
		return false;
	if (! ( $uid && $gid && $member))
		return false;
	$r = q("DELETE FROM `group_member` WHERE `uid` = %d AND `gid` = %d AND `contact-id` = %d",
		intval($uid),
		intval($gid),
		intval($member)
	);
	return $r;


}


function group_add_member($uid,$name,$member,$gid = 0) {
	if (! $gid)
		$gid = group_byname($uid,$name);
	if ((! $gid) || (! $uid) || (! $member))
		return false;

	$r = q("SELECT * FROM `group_member` WHERE `uid` = %d AND `gid` = %d AND `contact-id` = %d LIMIT 1",
		intval($uid),
		intval($gid),
		intval($member)
	);
	if (dbm::is_result($r))
		return true;	// You might question this, but
				// we indicate success because the group member was in fact created
				// -- It was just created at another time
 	if (! dbm::is_result($r)) {
		$r = q("INSERT INTO `group_member` (`uid`, `gid`, `contact-id`)
			VALUES( %d, %d, %d ) ",
			intval($uid),
			intval($gid),
			intval($member)
		);
	}
	return $r;
}

function group_get_members($gid) {
	$ret = array();
	if (intval($gid)) {
		$r = q("SELECT `group_member`.`contact-id`, `contact`.* FROM `group_member`
			INNER JOIN `contact` ON `contact`.`id` = `group_member`.`contact-id`
			WHERE `gid` = %d AND `group_member`.`uid` = %d AND
				NOT `contact`.`self` AND NOT `contact`.`blocked` AND NOT `contact`.`pending`
				ORDER BY `contact`.`name` ASC ",
			intval($gid),
			intval(local_user())
		);
		if (dbm::is_result($r))
			$ret = $r;
	}
	return $ret;
}

function group_public_members($gid) {
	$ret = 0;
	if (intval($gid)) {
		$r = q("SELECT `contact`.`id` AS `contact-id` FROM `group_member`
			INNER JOIN `contact` ON `contact`.`id` = `group_member`.`contact-id`
			WHERE `gid` = %d AND `group_member`.`uid` = %d
			AND  `contact`.`network` = '%s' AND `contact`.`notify` != '' ",
			intval($gid),
			intval(local_user()),
			dbesc(NETWORK_OSTATUS)
		);
		if (dbm::is_result($r))
			$ret = count($r);
	}
	return $ret;
}


function mini_group_select($uid,$gid = 0, $label = "") {

	$grps = array();
	$o = '';

	$r = q("SELECT * FROM `group` WHERE `deleted` = 0 AND `uid` = %d ORDER BY `name` ASC",
		intval($uid)
	);
	$grps[] = array('name' => '', 'id' => '0', 'selected' => '');
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$grps[] = array('name' => $rr['name'], 'id' => $rr['id'], 'selected' => (($gid == $rr['id']) ? 'true' : ''));
		}

	}
	logger('groups: ' . print_r($grps,true));

	if ($label == "")
		$label = t('Default privacy group for new contacts');

	$o = replace_macros(get_markup_template('group_selection.tpl'), array(
		'$label' => $label,
		'$groups' => $grps
	));
	return $o;
}


/**
 * @brief Create group sidebar widget
 *
 * @param string $every
 * @param string $each
 * @param string $editmode
 *	'standard' => include link 'Edit groups'
 *	'extended' => include link 'Create new group'
 *	'full' => include link 'Create new group' and provide for each group a link to edit this group
 * @param int $group_id
 * @param int $cid
 * @return string
 */
function group_side($every="contacts",$each="group",$editmode = "standard", $group_id = 0, $cid = 0) {

	$o = '';

	if (! local_user())
		return '';

	$groups = array();

	$groups[] = array(
		'text' 	=> t('Everybody'),
		'id' => 0,
		'selected' => (($group_id == 0) ? 'group-selected' : ''),
		'href' 	=> $every,
	);



	$r = q("SELECT * FROM `group` WHERE `deleted` = 0 AND `uid` = %d ORDER BY `name` ASC",
		intval($_SESSION['uid'])
	);
	$member_of = array();
	if ($cid) {
		$member_of = groups_containing(local_user(),$cid);
	}

	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$selected = (($group_id == $rr['id']) ? ' group-selected' : '');

			if ($editmode == "full") {
				$groupedit = array(
					'href' => "group/".$rr['id'],
					'title' => t('edit'),
				);
			} else {
				$groupedit = null;
			}

			$groups[] = array(
				'id'		=> $rr['id'],
				'cid'		=> $cid,
				'text' 		=> $rr['name'],
				'selected' 	=> $selected,
				'href'		=> $each."/".$rr['id'],
				'edit'		=> $groupedit,
				'ismember'	=> in_array($rr['id'],$member_of),
			);
		}
	}


	$tpl = get_markup_template("group_side.tpl");
	$o = replace_macros($tpl, array(
		'$title'	=> t('Groups'),
		'newgroup'	=> (($editmode == "extended") || ($editmode == "full") ? 1 : ''),
		'$editgroupstext' => t('Edit groups'),
		'grouppage'	=> "group/",
		'$edittext'     => t('Edit group'),
		'$createtext' 	=> t('Create a new group'),
		'$creategroup'  => t('Group Name: '),
		'$form_security_token' => get_form_security_token("group_edit"),
		'$ungrouped'    => (($every === 'contacts') ? t('Contacts not in any group') : ''),
		'$groups'	=> $groups,
		'$add'		=> t('add'),
	));


	return $o;
}

function expand_groups($a,$check_dead = false, $use_gcontact = false) {
	if (! (is_array($a) && count($a)))
		return array();
	$groups = implode(',', $a);
	$groups = dbesc($groups);

	if ($use_gcontact)
		$r = q("SELECT `gcontact`.`id` AS `contact-id` FROM `group_member`
				INNER JOIN `contact` ON `contact`.`id` = `group_member`.`contact-id`
				INNER JOIN `gcontact` ON `gcontact`.`nurl` = `contact`.`nurl`
			WHERE `gid` IN ($groups)");
	else
		$r = q("SELECT `contact-id` FROM `group_member` WHERE `gid` IN ( $groups )");


	$ret = array();
	if (dbm::is_result($r))
		foreach ($r as $rr)
			$ret[] = $rr['contact-id'];
	if ($check_dead AND !$use_gcontact) {
		require_once('include/acl_selectors.php');
		$ret = prune_deadguys($ret);
	}
	return $ret;
}


function member_of($c) {

	$r = q("SELECT `group`.`name`, `group`.`id` FROM `group` INNER JOIN `group_member` ON `group_member`.`gid` = `group`.`id` WHERE `group_member`.`contact-id` = %d AND `group`.`deleted` = 0 ORDER BY `group`.`name`  ASC ",
		intval($c)
	);

	return $r;

}

function groups_containing($uid,$c) {

	$r = q("SELECT `gid` FROM `group_member` WHERE `uid` = %d AND `group_member`.`contact-id` = %d ",
		intval($uid),
		intval($c)
	);

	$ret = array();
	if (dbm::is_result($r)) {
		foreach ($r as $rr) {
			$ret[] = $rr['gid'];
		}
	}

	return $ret;
}
/**
 * @brief count unread group items
 *
 * Count unread items of each groups
 *
 * @return array
 *	'id' => group id
 *	'name' => group name
 *	'count' => counted unseen group items
 *
 */
function groups_count_unseen() {

	$r = q("SELECT `group`.`id`, `group`.`name`,
			(SELECT COUNT(*) FROM `item` FORCE INDEX (`uid_unseen_contactid`)
				WHERE `uid` = %d AND `unseen` AND
					`contact-id` IN (SELECT `contact-id` FROM `group_member`
								WHERE `group_member`.`gid` = `group`.`id` AND `group_member`.`uid` = %d)) AS `count`
			FROM `group` WHERE `group`.`uid` = %d;",
		intval(local_user()),
		intval(local_user()),
		intval(local_user())
	);

	return $r;
}

/**
 * @brief Returns the default group for a given user and network
 *
 * @param int $uid User id
 * @param string $network network name
 *
 * @return int group id
 */
function get_default_group($uid, $network = "") {

	$default_group = 0;

	if ($network == NETWORK_OSTATUS)
		$default_group = get_pconfig($uid, "ostatus", "default_group");

	if ($default_group != 0)
		return $default_group;

	$g = q("SELECT `def_gid` FROM `user` WHERE `uid` = %d LIMIT 1", intval($uid));
	if ($g && intval($g[0]["def_gid"]))
		$default_group = $g[0]["def_gid"];

	return $default_group;
}
