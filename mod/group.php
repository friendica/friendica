<?php
/**
 * @file mod/group.php
 * @brief The group module (create and rename contact groups, add and
 *	remove contacts to the contact groups
 */

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Text;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model;
use Friendica\Module;
use Friendica\Util\Security;

function group_init(App $a) {
	if (local_user()) {
		$a->page['aside'] = Model\Group::sidebarWidget('contacts', 'group', 'extended', (($a->argc > 1) ? $a->argv[1] : 'everyone'));
	}
}

function group_post(App $a) {

	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return;
	}

	if (($a->argc == 2) && ($a->argv[1] === 'new')) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/group/new', 'group_edit');

		$name = Text::noTags(trim($_POST['groupname']));
		$r = Model\Group::create(local_user(), $name);
		if ($r) {
			info(L10n::t('Group created.') . EOL);
			$r = Model\Group::getIdByName(local_user(), $name);
			if ($r) {
				$a->internalRedirect('group/' . $r);
			}
		} else {
			notice(L10n::t('Could not create group.') . EOL);
		}
		$a->internalRedirect('group');
		return; // NOTREACHED
	}

	if (($a->argc == 2) && intval($a->argv[1])) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/group', 'group_edit');

		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);
		if (!DBA::isResult($r)) {
			notice(L10n::t('Group not found.') . EOL);
			$a->internalRedirect('contact');
			return; // NOTREACHED
		}
		$group = $r[0];
		$groupname = Text::noTags(trim($_POST['groupname']));
		if (strlen($groupname) && ($groupname != $group['name'])) {
			$r = q("UPDATE `group` SET `name` = '%s' WHERE `uid` = %d AND `id` = %d",
				DBA::escape($groupname),
				intval(local_user()),
				intval($group['id'])
			);

			if ($r) {
				info(L10n::t('Group name changed.') . EOL);
			}
		}

		$a->page['aside'] = Model\Group::sidebarWidget();
	}
	return;
}

function group_content(App $a) {
	$change = false;

	if (!local_user()) {
		notice(L10n::t('Permission denied') . EOL);
		return;
	}

	// With no group number provided we jump to the unassigned contacts as a starting point
	if ($a->argc == 1) {
		$a->internalRedirect('group/none');
	}

	// Switch to text mode interface if we have more than 'n' contacts or group members
	$switchtotext = PConfig::get(local_user(), 'system', 'groupedit_image_limit');
	if (is_null($switchtotext)) {
		$switchtotext = Config::get('system', 'groupedit_image_limit', 400);
	}

	$tpl = Text::getMarkupTemplate('group_edit.tpl');

	$context = [
		'$submit' => L10n::t('Save Group'),
		'$submit_filter' => L10n::t('Filter'),
	];

	if (($a->argc == 2) && ($a->argv[1] === 'new')) {
		return Text::replaceMacros($tpl, $context + [
			'$title' => L10n::t('Create a group of contacts/friends.'),
			'$gname' => ['groupname', L10n::t('Group Name: '), '', ''],
			'$gid' => 'new',
			'$form_security_token' => BaseModule::getFormSecurityToken("group_edit"),
		]);


	}

	$nogroup = false;

	if (($a->argc == 2) && ($a->argv[1] === 'none')) {
		$id = -1;
		$nogroup = true;
		$group = [
			'id' => $id,
			'name' => L10n::t('Contacts not in any group'),
		];

		$members = [];
		$preselected = [];
		$entry = [];

		$context = $context + [
			'$title' => $group['name'],
			'$gname' => ['groupname', L10n::t('Group Name: '), $group['name'], ''],
			'$gid' => $id,
			'$editable' => 0,
		];
	}


	if (($a->argc == 3) && ($a->argv[1] === 'drop')) {
		BaseModule::checkFormSecurityTokenRedirectOnError('/group', 'group_drop', 't');

		if (intval($a->argv[2])) {
			$r = q("SELECT `name` FROM `group` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($a->argv[2]),
				intval(local_user())
			);

			$result = null;

			if (DBA::isResult($r)) {
				$result = Model\Group::removeByName(local_user(), $r[0]['name']);
			}

			if ($result) {
				info(L10n::t('Group removed.') . EOL);
			} else {
				notice(L10n::t('Unable to remove group.') . EOL);
			}
		}
		$a->internalRedirect('group');
		// NOTREACHED
	}

	if (($a->argc > 2) && intval($a->argv[1]) && intval($a->argv[2])) {
		BaseModule::checkFormSecurityTokenForbiddenOnError('group_member_change', 't');

		$r = q("SELECT `id` FROM `contact` WHERE `id` = %d AND `uid` = %d and `self` = 0 and `blocked` = 0 AND `pending` = 0 LIMIT 1",
			intval($a->argv[2]),
			intval(local_user())
		);
		if (DBA::isResult($r)) {
			$change = intval($a->argv[2]);
		}
	}

	if (($a->argc > 1) && intval($a->argv[1])) {
		$r = q("SELECT * FROM `group` WHERE `id` = %d AND `uid` = %d AND `deleted` = 0 LIMIT 1",
			intval($a->argv[1]),
			intval(local_user())
		);

		if (!DBA::isResult($r)) {
			notice(L10n::t('Group not found.') . EOL);
			$a->internalRedirect('contact');
		}

		$group = $r[0];
		$members = Model\Contact::getByGroupId($group['id']);
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
				Model\Group::removeMember($group['id'], $change);
			} else {
				Model\Group::addMember($group['id'], $change);
			}

			$members = Model\Contact::getByGroupId($group['id']);
			$preselected = [];
			if (count($members)) {
				foreach ($members as $member) {
					$preselected[] = $member['id'];
				}
			}
		}

		$drop_tpl = Text::getMarkupTemplate('group_drop.tpl');
		$drop_txt = Text::replaceMacros($drop_tpl, [
			'$id' => $group['id'],
			'$delete' => L10n::t('Delete Group'),
			'$form_security_token' => BaseModule::getFormSecurityToken("group_drop"),
		]);


		$context = $context + [
			'$title' => $group['name'],
			'$gname' => ['groupname', L10n::t('Group Name: '), $group['name'], ''],
			'$gid' => $group['id'],
			'$drop' => $drop_txt,
			'$form_security_token' => BaseModule::getFormSecurityToken('group_edit'),
			'$edit_name' => L10n::t('Edit Group Name'),
			'$editable' => 1,
		];

	}

	if (!isset($group)) {
		return;
	}

	$groupeditor = [
		'label_members' => L10n::t('Members'),
		'members' => [],
		'label_contacts' => L10n::t('All Contacts'),
		'group_is_empty' => L10n::t('Group is empty'),
		'contacts' => [],
	];

	$sec_token = addslashes(BaseModule::getFormSecurityToken('group_member_change'));

	// Format the data of the group members
	foreach ($members as $member) {
		if ($member['url']) {
			$entry = Module\Contact::getContactTemplateVars($member);
			$entry['label'] = 'members';
			$entry['photo_menu'] = '';
			$entry['change_member'] = [
				'title'     => L10n::t("Remove contact from group"),
				'gid'       => $group['id'],
				'cid'       => $member['id'],
				'sec_token' => $sec_token
			];

			$groupeditor['members'][] = $entry;
		} else {
			Model\Group::removeMember($group['id'], $member['id']);
		}
	}

	if ($nogroup) {
		$r = Model\Contact::getUngroupedList(local_user());
	} else {
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND NOT `blocked` AND NOT `pending` AND NOT `self` ORDER BY `name` ASC",
			intval(local_user())
		);
		$context['$desc'] = L10n::t('Click on a contact to add or remove.');
	}

	if (DBA::isResult($r)) {
		// Format the data of the contacts who aren't in the contact group
		foreach ($r as $member) {
			if (!in_array($member['id'], $preselected)) {
				$entry = Module\Contact::getContactTemplateVars($member);
				$entry['label'] = 'contacts';
				if (!$nogroup)
					$entry['photo_menu'] = [];

				if (!$nogroup) {
					$entry['change_member'] = [
						'title'     => L10n::t("Add contact to group"),
						'gid'       => $group['id'],
						'cid'       => $member['id'],
						'sec_token' => $sec_token
					];
				}

				$groupeditor['contacts'][] = $entry;
			}
		}
	}

	$context['$groupeditor'] = $groupeditor;

	// If there are to many contacts we could provide an alternative view mode
	$total = count($groupeditor['members']) + count($groupeditor['contacts']);
	$context['$shortmode'] = (($switchtotext && ($total > $switchtotext)) ? true : false);

	if ($change) {
		$tpl = Text::getMarkupTemplate('groupeditor.tpl');
		echo Text::replaceMacros($tpl, $context);
		killme();
	}

	return Text::replaceMacros($tpl, $context);

}
