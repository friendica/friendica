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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;

class Group extends BaseModule
{
	protected function post(array $request = [])
	{
		if (DI::mode()->isAjax()) {
			$this->ajaxPost();
		}

		if (!DI::userSession()->getLocalUserId()) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
			DI::baseUrl()->redirect();
		}

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() == 2) && (DI::args()->getArgv()[1] === 'new')) {
			BaseModule::checkFormSecurityTokenRedirectOnError('/group/new', 'group_edit');

			$name = trim($request['groupname']);
			$r = Model\Group::create(DI::userSession()->getLocalUserId(), $name);
			if ($r) {
				$r = Model\Group::getIdByName(DI::userSession()->getLocalUserId(), $name);
				if ($r) {
					DI::baseUrl()->redirect('group/' . $r);
				}
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Could not create group.'));
			}
			DI::baseUrl()->redirect('group');
		}

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() == 2) && intval(DI::args()->getArgv()[1])) {
			BaseModule::checkFormSecurityTokenRedirectOnError('/group', 'group_edit');

			$group = DBA::selectFirst('group', ['id', 'name'], ['id' => DI::args()->getArgv()[1], 'uid' => DI::userSession()->getLocalUserId()]);
			if (!DBA::isResult($group)) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Group not found.'));
				DI::baseUrl()->redirect('contact');
			}
			$groupname = trim($_POST['groupname']);
			if (strlen($groupname) && ($groupname != $group['name'])) {
				if (!Model\Group::update($group['id'], $groupname)) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Group name was not changed.'));
				}
			}
		}
	}

	public function ajaxPost()
	{
		try {
			if (!DI::userSession()->getLocalUserId()) {
				throw new \Exception(DI::l10n()->t('Permission denied.'), 403);
			}

			if (isset($this->parameters['command'])) {
				$group_id = $this->parameters['group'];
				$contact_id = $this->parameters['contact'];

				if (!Model\Group::exists($group_id, DI::userSession()->getLocalUserId())) {
					throw new \Exception(DI::l10n()->t('Unknown group.'), 404);
				}

				// @TODO Backward compatibility with user contacts, remove by version 2022.03
				$cdata = Model\Contact::getPublicAndUserContactID($contact_id, DI::userSession()->getLocalUserId());
				if (empty($cdata['public'])) {
					throw new \Exception(DI::l10n()->t('Contact not found.'), 404);
				}

				if (empty($cdata['user'])) {
					throw new \Exception(DI::l10n()->t('Invalid contact.'), 404);
				}

				$contact = Model\Contact::getById($cdata['user'], ['deleted']);
				if (!DBA::isResult($contact)) {
					throw new \Exception(DI::l10n()->t('Contact not found.'), 404);
				}

				if ($contact['deleted']) {
					throw new \Exception(DI::l10n()->t('Contact is deleted.'), 410);
				}

				switch($this->parameters['command']) {
					case 'add':
						if (!Model\Group::addMember($group_id, $cdata['user'])) {
							throw new \Exception(DI::l10n()->t('Unable to add the contact to the group.'), 500);
						}

						$message = DI::l10n()->t('Contact successfully added to group.');
						break;
					case 'remove':
						if (!Model\Group::removeMember($group_id, $cdata['user'])) {
							throw new \Exception(DI::l10n()->t('Unable to remove the contact from the group.'), 500);
						}

						$message = DI::l10n()->t('Contact successfully removed from group.');
						break;
				}
			} else {
				throw new \Exception(DI::l10n()->t('Bad request.'), 400);
			}

			DI::sysmsg()->addInfo($message);
			System::jsonExit(['status' => 'OK', 'message' => $message]);
		} catch (\Exception $e) {
			DI::sysmsg()->addNotice($e->getMessage());
			System::jsonError($e->getCode(), ['status' => 'error', 'message' => $e->getMessage()]);
		}
	}

	protected function content(array $request = []): string
	{
		$change = false;

		if (!DI::userSession()->getLocalUserId()) {
			throw new \Friendica\Network\HTTPException\ForbiddenException();
		}

		$a = DI::app();

		DI::page()['aside'] = Model\Group::sidebarWidget('contact', 'group', 'extended', ((DI::args()->getArgc() > 1) ? DI::args()->getArgv()[1] : 'everyone'));

		// With no group number provided we jump to the unassigned contacts as a starting point
		// @TODO: Replace with parameter from router
		if (DI::args()->getArgc() == 1) {
			DI::baseUrl()->redirect('group/none');
		}

		// Switch to text mode interface if we have more than 'n' contacts or group members
		$switchtotext = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'groupedit_image_limit');
		if (is_null($switchtotext)) {
			$switchtotext = DI::config()->get('system', 'groupedit_image_limit', 200);
		}

		$tpl = Renderer::getMarkupTemplate('group_edit.tpl');


		$context = [
			'$submit' => DI::l10n()->t('Save Group'),
			'$submit_filter' => DI::l10n()->t('Filter'),
		];

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() == 2) && (DI::args()->getArgv()[1] === 'new')) {
			return Renderer::replaceMacros($tpl, $context + [
				'$title' => DI::l10n()->t('Create a group of contacts/friends.'),
				'$gname' => ['groupname', DI::l10n()->t('Group Name: '), '', ''],
				'$gid' => 'new',
				'$form_security_token' => BaseModule::getFormSecurityToken("group_edit"),
			]);
		}

		$nogroup = false;

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() == 2) && (DI::args()->getArgv()[1] === 'none') ||
			(DI::args()->getArgc() == 1) && (DI::args()->getArgv()[0] === 'nogroup')) {
			$id = -1;
			$nogroup = true;
			$group = [
				'id' => $id,
				'name' => DI::l10n()->t('Contacts not in any group'),
			];

			$members = [];
			$preselected = [];

			$context = $context + [
				'$title' => $group['name'],
				'$gname' => ['groupname', DI::l10n()->t('Group Name: '), $group['name'], ''],
				'$gid' => $id,
				'$editable' => 0,
			];
		}

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() == 3) && (DI::args()->getArgv()[1] === 'drop')) {
			BaseModule::checkFormSecurityTokenRedirectOnError('/group', 'group_drop', 't');

			// @TODO: Replace with parameter from router
			if (intval(DI::args()->getArgv()[2])) {
				if (!Model\Group::exists(DI::args()->getArgv()[2], DI::userSession()->getLocalUserId())) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Group not found.'));
					DI::baseUrl()->redirect('contact');
				}

				if (!Model\Group::remove(DI::args()->getArgv()[2])) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Unable to remove group.'));
				}
			}
			DI::baseUrl()->redirect('group');
		}

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() > 2) && intval(DI::args()->getArgv()[1]) && intval(DI::args()->getArgv()[2])) {
			BaseModule::checkFormSecurityTokenForbiddenOnError('group_member_change', 't');

			if (DBA::exists('contact', ['id' => DI::args()->getArgv()[2], 'uid' => DI::userSession()->getLocalUserId(), 'self' => false, 'pending' => false, 'blocked' => false])) {
				$change = intval(DI::args()->getArgv()[2]);
			}
		}

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() > 1) && intval(DI::args()->getArgv()[1])) {
			$group = DBA::selectFirst('group', ['id', 'name'], ['id' => DI::args()->getArgv()[1], 'uid' => DI::userSession()->getLocalUserId(), 'deleted' => false]);
			if (!DBA::isResult($group)) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Group not found.'));
				DI::baseUrl()->redirect('contact');
			}

			$members = Model\Contact\Group::getById($group['id']);
			$preselected = [];

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

				$members = Model\Contact\Group::getById($group['id']);
				$preselected = [];
				if (count($members)) {
					foreach ($members as $member) {
						$preselected[] = $member['id'];
					}
				}
			}

			$drop_tpl = Renderer::getMarkupTemplate('group_drop.tpl');
			$drop_txt = Renderer::replaceMacros($drop_tpl, [
				'$id' => $group['id'],
				'$delete' => DI::l10n()->t('Delete Group'),
				'$form_security_token' => BaseModule::getFormSecurityToken("group_drop"),
			]);

			$context = $context + [
				'$title' => $group['name'],
				'$gname' => ['groupname', DI::l10n()->t('Group Name: '), $group['name'], ''],
				'$gid' => $group['id'],
				'$drop' => $drop_txt,
				'$form_security_token' => BaseModule::getFormSecurityToken('group_edit'),
				'$edit_name' => DI::l10n()->t('Edit Group Name'),
				'$editable' => 1,
			];
		}

		if (!isset($group)) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		$groupeditor = [
			'label_members' => DI::l10n()->t('Members'),
			'members' => [],
			'label_contacts' => DI::l10n()->t('All Contacts'),
			'group_is_empty' => DI::l10n()->t('Group is empty'),
			'contacts' => [],
		];

		$sec_token = addslashes(BaseModule::getFormSecurityToken('group_member_change'));

		// Format the data of the group members
		foreach ($members as $member) {
			if ($member['url']) {
				$entry = Contact::getContactTemplateVars($member);
				$entry['label'] = 'members';
				$entry['photo_menu'] = '';
				$entry['change_member'] = [
					'title'     => DI::l10n()->t("Remove contact from group"),
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
			$contacts = Model\Contact\Group::listUngrouped(DI::userSession()->getLocalUserId());
		} else {
			$contacts_stmt = DBA::select('contact', [],
				['rel' => [Model\Contact::FOLLOWER, Model\Contact::FRIEND, Model\Contact::SHARING],
				'uid' => DI::userSession()->getLocalUserId(), 'pending' => false, 'blocked' => false, 'failed' => false, 'self' => false],
				['order' => ['name']]
			);
			$contacts = DBA::toArray($contacts_stmt);
			$context['$desc'] = DI::l10n()->t('Click on a contact to add or remove.');
		}

		if (DBA::isResult($contacts)) {
			// Format the data of the contacts who aren't in the contact group
			foreach ($contacts as $member) {
				if (!in_array($member['id'], $preselected)) {
					$entry = Contact::getContactTemplateVars($member);
					$entry['label'] = 'contacts';
					if (!$nogroup)
						$entry['photo_menu'] = [];

					if (!$nogroup) {
						$entry['change_member'] = [
							'title'     => DI::l10n()->t("Add contact to group"),
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
			$tpl = Renderer::getMarkupTemplate('groupeditor.tpl');
			echo Renderer::replaceMacros($tpl, $context);
			System::exit();
		}

		return Renderer::replaceMacros($tpl, $context);
	}
}