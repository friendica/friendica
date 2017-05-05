<?php
/**
 * @file mod/group.php
 * @brief The group module (create and rename contact groups, add and
 *	remove contacts to the contact groups
 */

use Friendica\App;

function group_init(App $a) {
	if (local_user()) {
		require_once 'include/group.php';
		$a->page['aside'] = group_side('contacts', 'group', 'extended', (($a->argc > 1) ? intval($a->argv[1]) : 0));
	}
}

function group_post(App $a) {

	if (! local_user()) {
		notice(t('Permission denied.') . EOL);
		return;
	}

	if (($a->argc == 2) && ($a->argv[1] === 'new')) {
		check_form_security_token_redirectOnErr('/group/new', 'group_edit');

		$name = notags(trim($_POST['groupname']));
		$r = group_add(local_user(), $name);
		if ($r) {
			info(t('Group created.') . EOL);
			$r = group_byname(local_user(), $name);
			if ($r) {
				goaway(App::get_baseurl() . '/group/' . $r);
			}
		} else {
			notice(t('Could not create group.') . EOL);
		}
		goaway(App::get_baseurl() . '/group');
		return; // NOTREACHED
	}

	if (($a->argc == 2) && (intval($a->argv[1]))) {
		check_form_security_token_redirectOnErr('/group', 'group_edit');

		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (! dbm::is_result($r)) {
			notice(t('Group not found.') . EOL);
			goaway(App::get_baseurl() . '/contacts');
			return; // NOTREACHED
		}
		$group = $r[0];
		$groupname = notags(trim($_POST['groupname']));
		if ((strlen($groupname))  && ($groupname != $group['name'])) {
			$r = q("UPDATE `group` SET `name` = '%s' WHERE `uid` = %d AND `id` = %d",
				dbesc($groupname),
				intval(local_user()),
				intval($group['id'])
			);

			if ($r) {
				info(t('Group name changed.') . EOL);
			}
		}

		$a->page['aside'] = group_side();
	}
	return;
}

function group_content(App $a) {
	$change = false;

	if (! local_user()) {
		notice(t('Permission denied') . EOL);
		return;
	}

	// Switch to text mode interface if we have more than 'n' contacts or group members

	$switchtotext = get_pconfig(local_user(), 'system', 'groupedit_image_limit');
	if ($switchtotext === false) {
		$switchtotext = get_config('system', 'groupedit_image_limit');
	}
	if ($switchtotext === false) {
		$switchtotext = 400;
	}

	$tpl = get_markup_template('group_edit.tpl');

	$context = array(
			'$submit' => t('Save Group'),
	);

	if (($a->argc == 2) && ($a->argv[1] === 'new')) {
		return replace_macros($tpl, $context + array(
			'$title' => t('Create a group of contacts/friends.'),
			'$gname' => array('groupname', t('Group Name: '), '', ''),
			'$gid' => 'new',
			'$form_security_token' => get_form_security_token("group_edit"),
		));


	}

	if (($a->argc == 3) && ($a->argv[1] === 'drop')) {
		check_form_security_token_redirectOnErr('/group', 'group_drop', 't');

		if (intval($a->argv[2])) {
			$r = q("SELECT `name` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);

			$result = null;

			if (dbm::is_result($r)) {
				$result = group_rmv(local_user(), $r[0]['name']);
			}

			if ($result) {
				info(t('Group removed.') . EOL);
			} else {
				notice(t('Unable to remove group.') . EOL);
			}
		}
		goaway(App::get_baseurl() . '/group');
		// NOTREACHED
	}

	if (($a->argc > 2) && intval($a->argv[1]) && intval($a->argv[2])) {
		check_form_security_token_ForbiddenOnErr('group_member_change', 't');

		$r = q("SELECT `id` FROM `contact` WHERE `id` = %d AND `uid` = %d and `self` = 0 and `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if (dbm::is_result($r)) {
			$change = intval($a->argv[2]);
		}
	}

	if (($a->argc > 1) && (intval($a->argv[1]))) {
		require_once 'include/acl_selectors.php';
		require_once 'mod/contacts.php';

		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d AND `deleted` = 0 LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);

		if (! dbm::is_result($r)) {
			notice(t('Group not found.') . EOL);
			goaway(App::get_baseurl() . '/contacts');
		}

		$group = $r[0];
		$members = group_get_members($group['id']);
		$preselected = array();
		$entry = array();
		$id = 0;

		if (count($members)) {
			foreach ($members as $member) {
				$preselected[] = $member['id'];
			}
		}

		if ($change) {
			if (in_array($change, $preselected)) {
				group_rmv_member(local_user(), $group['name'], $change);
			} else {
				group_add_member(local_user(), $group['name'], $change);
			}

			$members = group_get_members($group['id']);
			$preselected = array();
			if (count($members)) {
				foreach ($members as $member) {
					$preselected[] = $member['id'];
				}
			}
		}

		$drop_tpl = get_markup_template('group_drop.tpl');
		$drop_txt = replace_macros($drop_tpl, array(
			'$id' => $group['id'],
			'$delete' => t('Delete Group'),
			'$form_security_token' => get_form_security_token("group_drop"),
		));


		$context = $context + array(
			'$title' => t('Group Editor'),
			'$gname' => array('groupname', t('Group Name: '), $group['name'], ''),
			'$gid' => $group['id'],
			'$drop' => $drop_txt,
			'$form_security_token' => get_form_security_token('group_edit'),
			'$edit_name' => t('Edit Group Name')
		);

	}

	if (! isset($group)) {
		return;
	}

	$groupeditor = array(
		'label_members' => t('Members'),
		'members' => array(),
		'label_contacts' => t('All Contacts'),
		'group_is_empty' => t('Group is empty'),
		'contacts' => array(),
	);

	$sec_token = addslashes(get_form_security_token('group_member_change'));

	// Format the data of the group members
	foreach ($members as $member) {
		if ($member['url']) {
			$entry = _contact_detail_for_template($member);
			$entry['label'] = 'members';
			$entry['photo_menu'] = '';
			$entry['change_member'] = array(
				'title'     => t("Remove Contact"),
				'gid'       => $group['id'],
				'cid'       => $member['id'],
				'sec_token' => $sec_token
			);

			$groupeditor['members'][] = $entry;
		} else {
			group_rmv_member(local_user(), $group['name'], $member['id']);
		}
	}

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND NOT `blocked` AND NOT `pending` AND NOT `self` ORDER BY `name` ASC",
		intval(local_user())
	);

	if (dbm::is_result($r)) {
		// Format the data of the contacts who aren't in the contact group
		foreach ($r as $member) {
			if (! in_array($member['id'], $preselected)) {
				$entry = _contact_detail_for_template($member);
				$entry['label'] = 'contacts';
				$entry['photo_menu'] = '';
				$entry['change_member'] = array(
					'title'     => t("Add Contact"),
					'gid'       => $group['id'],
					'cid'       => $member['id'],
					'sec_token' => $sec_token
				);

				$groupeditor['contacts'][] = $entry;
			}
		}
	}

	$context['$groupeditor'] = $groupeditor;
	$context['$desc'] = t('Click on a contact to add or remove.');

	// If there are to many contacts we could provide an alternative view mode
	$total = count($groupeditor['members']) + count($groupeditor['contacts']);
	$context['$shortmode'] = (($switchtotext && ($total > $switchtotext)) ? true : false);

	if ($change) {
		$tpl = get_markup_template('groupeditor.tpl');
		echo replace_macros($tpl, $context);
		killme();
	}

	return replace_macros($tpl, $context);

}
