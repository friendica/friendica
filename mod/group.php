<?php
/**
 * @file mod/group.php
 * @brief The group module (create and rename contact groups, add and
 *	remove contacts to the contact groups
 */

use Friendica\App;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Group;

function group_init(App $a) {
	if (local_user()) {
		$a->page['aside'] = Group::sidebarWidget('contacts', 'group', 'extended', (($a->argc > 1) ? intval($a->argv[1]) : 0));
	}
}

function group_post(App $a) {

	if (! local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if (($a->argc == 2) && ($a->argv[1] === 'new')) {
		check_form_security_token_redirectOnErr('/group/new', 'group_edit');

		$name = notags(trim($_POST['groupname']));
		$r = Group::create(local_user(), $name);
		if ($r) {
			info(L10n::t('Group created.') . EOL);
			$r = Group::getIdByName(local_user(), $name);
			if ($r) {
				goaway(System::baseUrl() . '/group/' . $r);
			}
		} else {
			notice(L10n::t('Could not create group.') . EOL);
		}
		goaway(System::baseUrl() . '/group');
		return; // NOTREACHED
	}

	if (($a->argc == 2) && (intval($a->argv[1]))) {
		check_form_security_token_redirectOnErr('/group', 'group_edit');

		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (! DBM::is_result($r)) {
			notice(L10n::t('Group not found.') . EOL);
			goaway(System::baseUrl() . '/contacts');
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
				info(L10n::t('Group name changed.') . EOL);
			}
		}

		$a->page['aside'] = Group::sidebarWidget();
	}
	return;
}

function group_content(App $a) {
	$change = false;

	if (! local_user()) {
		notice(L10n::t('Permission denied') . EOL);
		return;
	}

	// Switch to text mode interface if we have more than 'n' contacts or group members

	$switchtotext = PConfig::get(local_user(), 'system', 'groupedit_image_limit');
	if (is_null($switchtotext)) {
		$switchtotext = Config::get('system', 'groupedit_image_limit', 400);
	}

	$tpl = get_markup_template('group_edit.tpl');

	$context = [
			'$submit' => L10n::t('Save Group'),
	];

	if (($a->argc == 2) && ($a->argv[1] === 'new')) {
		return replace_macros($tpl, $context + [
			'$title' => L10n::t('Create a group of contacts/friends.'),
			'$gname' => ['groupname', L10n::t('Group Name: '), '', ''],
			'$gid' => 'new',
			'$form_security_token' => get_form_security_token("group_edit"),
		]);


	}

	if (($a->argc == 3) && ($a->argv[1] === 'drop')) {
		check_form_security_token_redirectOnErr('/group', 'group_drop', 't');

		if (intval($a->argv[2])) {
			$r = q("SELECT `name` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);

			$result = null;

			if (DBM::is_result($r)) {
				$result = Group::removeByName(local_user(), $r[0]['name']);
			}

			if ($result) {
				info(L10n::t('Group removed.') . EOL);
			} else {
				notice(L10n::t('Unable to remove group.') . EOL);
			}
		}
		goaway(System::baseUrl() . '/group');
		// NOTREACHED
	}

	if (($a->argc > 2) && intval($a->argv[1]) && intval($a->argv[2])) {
		check_form_security_token_ForbiddenOnErr('group_member_change', 't');

		$r = q("SELECT `id` FROM `contact` WHERE `id` = %d AND `uid` = %d and `self` = 0 and `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if (DBM::is_result($r)) {
			$change = intval($a->argv[2]);
		}
	}

	if (($a->argc > 1) && (intval($a->argv[1]))) {
		require_once 'mod/contacts.php';

		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d AND `deleted` = 0 LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);

		if (! DBM::is_result($r)) {
			notice(L10n::t('Group not found.') . EOL);
			goaway(System::baseUrl() . '/contacts');
		}

		$group = $r[0];
		$members = Contact::getByGroupId($group['id']);
		$preselected = [];
		$entry = [];
		$id = 0;

		if (count($members)) {
			foreach ($members as $member) {
				$preselected[] = $member['id'];
			}
		}

		if ($change) {
			if (in_array($change, $preselected)) {
				Group::removeMember($group['id'], $change);
			} else {
				Group::addMember($group['id'], $change);
			}

			$members = Contact::getByGroupId($group['id']);
			$preselected = [];
			if (count($members)) {
				foreach ($members as $member) {
					$preselected[] = $member['id'];
				}
			}
		}

		$drop_tpl = get_markup_template('group_drop.tpl');
		$drop_txt = replace_macros($drop_tpl, [
			'$id' => $group['id'],
			'$delete' => L10n::t('Delete Group'),
			'$form_security_token' => get_form_security_token("group_drop"),
		]);


		$context = $context + [
			'$title' => L10n::t('Group Editor'),
			'$gname' => ['groupname', L10n::t('Group Name: '), $group['name'], ''],
			'$gid' => $group['id'],
			'$drop' => $drop_txt,
			'$form_security_token' => get_form_security_token('group_edit'),
			'$edit_name' => L10n::t('Edit Group Name')
		];

	}

	if (! isset($group)) {
		return;
	}

	$groupeditor = [
		'label_members' => L10n::t('Members'),
		'members' => [],
		'label_contacts' => L10n::t('All Contacts'),
		'group_is_empty' => L10n::t('Group is empty'),
		'contacts' => [],
	];

	$sec_token = addslashes(get_form_security_token('group_member_change'));

	// Format the data of the group members
	foreach ($members as $member) {
		if ($member['url']) {
			$entry = _contact_detail_for_template($member);
			$entry['label'] = 'members';
			$entry['photo_menu'] = '';
			$entry['change_member'] = [
				'title'     => L10n::t("Remove Contact"),
				'gid'       => $group['id'],
				'cid'       => $member['id'],
				'sec_token' => $sec_token
			];

			$groupeditor['members'][] = $entry;
		} else {
			Group::removeMember($group['id'], $member['id']);
		}
	}

	$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND NOT `blocked` AND NOT `pending` AND NOT `self` ORDER BY `name` ASC",
		intval(local_user())
	);

	if (DBM::is_result($r)) {
		// Format the data of the contacts who aren't in the contact group
		foreach ($r as $member) {
			if (! in_array($member['id'], $preselected)) {
				$entry = _contact_detail_for_template($member);
				$entry['label'] = 'contacts';
				$entry['photo_menu'] = '';
				$entry['change_member'] = [
					'title'     => L10n::t("Add Contact"),
					'gid'       => $group['id'],
					'cid'       => $member['id'],
					'sec_token' => $sec_token
				];

				$groupeditor['contacts'][] = $entry;
			}
		}
	}

	$context['$groupeditor'] = $groupeditor;
	$context['$desc'] = L10n::t('Click on a contact to add or remove.');

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
