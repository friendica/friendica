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

class Circle extends BaseModule
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
			BaseModule::checkFormSecurityTokenRedirectOnError('/circle/new', 'circle_edit');

			$name = trim($request['circle_name']);
			$r = Model\Circle::create(DI::userSession()->getLocalUserId(), $name);
			if ($r) {
				$r = Model\Circle::getIdByName(DI::userSession()->getLocalUserId(), $name);
				if ($r) {
					DI::baseUrl()->redirect('circle/' . $r);
				}
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Could not create circle.'));
			}
			DI::baseUrl()->redirect('circle');
		}

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() == 2) && intval(DI::args()->getArgv()[1])) {
			BaseModule::checkFormSecurityTokenRedirectOnError('/circle', 'circle_edit');

			$circle = DBA::selectFirst('group', ['id', 'name'], ['id' => DI::args()->getArgv()[1], 'uid' => DI::userSession()->getLocalUserId()]);
			if (!DBA::isResult($circle)) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Circle not found.'));
				DI::baseUrl()->redirect('contact');
			}
			$circlename = trim($_POST['circle_name']);
			if (strlen($circlename) && ($circlename != $circle['name'])) {
				if (!Model\Circle::update($circle['id'], $circlename)) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Circle name was not changed.'));
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
				$circle_id = $this->parameters['circle'];
				$contact_id = $this->parameters['contact'];

				if (!Model\Circle::exists($circle_id, DI::userSession()->getLocalUserId())) {
					throw new \Exception(DI::l10n()->t('Unknown circle.'), 404);
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
						if (!Model\Circle::addMember($circle_id, $cdata['user'])) {
							throw new \Exception(DI::l10n()->t('Unable to add the contact to the circle.'), 500);
						}

						$message = DI::l10n()->t('Contact successfully added to circle.');
						break;
					case 'remove':
						if (!Model\Circle::removeMember($circle_id, $cdata['user'])) {
							throw new \Exception(DI::l10n()->t('Unable to remove the contact from the circle.'), 500);
						}

						$message = DI::l10n()->t('Contact successfully removed from circle.');
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

		DI::page()['aside'] = Model\Circle::sidebarWidget('contact', 'circle', 'extended', ((DI::args()->getArgc() > 1) ? DI::args()->getArgv()[1] : 'everyone'));

		// With no circle number provided we jump to the unassigned contacts as a starting point
		// @TODO: Replace with parameter from router
		if (DI::args()->getArgc() == 1) {
			DI::baseUrl()->redirect('circle/none');
		}

		// Switch to text mode interface if we have more than 'n' contacts or circle members
		$switchtotext = DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'circle_edit_image_limit') ??
			DI::pConfig()->get(DI::userSession()->getLocalUserId(), 'system', 'groupedit_image_limit');
		if (is_null($switchtotext)) {
			$switchtotext = DI::config()->get('system', 'groupedit_image_limit') ??
				DI::config()->get('system', 'circle_edit_image_limit');
		}

		$tpl = Renderer::getMarkupTemplate('circle_edit.tpl');


		$context = [
			'$submit' => DI::l10n()->t('Save Circle'),
			'$submit_filter' => DI::l10n()->t('Filter'),
		];

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() == 2) && (DI::args()->getArgv()[1] === 'new')) {
			return Renderer::replaceMacros($tpl, $context + [
				'$title' => DI::l10n()->t('Create a circle of contacts/friends.'),
				'$gname' => ['circle_name', DI::l10n()->t('Circle Name: '), '', ''],
				'$gid' => 'new',
				'$form_security_token' => BaseModule::getFormSecurityToken('circle_edit'),
			]);
		}

		$nocircle = false;

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() == 2) && (DI::args()->getArgv()[1] === 'none') ||
			(DI::args()->getArgc() == 1) && (DI::args()->getArgv()[0] === 'nocircle')) {
			$id = -1;
			$nocircle = true;
			$circle = [
				'id' => $id,
				'name' => DI::l10n()->t('Contacts not in any circle'),
			];

			$members = [];
			$preselected = [];

			$context = $context + [
				'$title' => $circle['name'],
				'$gname' => ['circle_name', DI::l10n()->t('Circle Name: '), $circle['name'], ''],
				'$gid' => $id,
				'$editable' => 0,
			];
		}

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() == 3) && (DI::args()->getArgv()[1] === 'drop')) {
			BaseModule::checkFormSecurityTokenRedirectOnError('/circle', 'circle_drop', 't');

			// @TODO: Replace with parameter from router
			if (intval(DI::args()->getArgv()[2])) {
				if (!Model\Circle::exists(DI::args()->getArgv()[2], DI::userSession()->getLocalUserId())) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Circle not found.'));
					DI::baseUrl()->redirect('contact');
				}

				if (!Model\Circle::remove(DI::args()->getArgv()[2])) {
					DI::sysmsg()->addNotice(DI::l10n()->t('Unable to remove circle.'));
				}
			}
			DI::baseUrl()->redirect('circle');
		}

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() > 2) && intval(DI::args()->getArgv()[1]) && intval(DI::args()->getArgv()[2])) {
			BaseModule::checkFormSecurityTokenForbiddenOnError('circle_member_change', 't');

			if (DBA::exists('contact', ['id' => DI::args()->getArgv()[2], 'uid' => DI::userSession()->getLocalUserId(), 'self' => false, 'pending' => false, 'blocked' => false])) {
				$change = intval(DI::args()->getArgv()[2]);
			}
		}

		// @TODO: Replace with parameter from router
		if ((DI::args()->getArgc() > 1) && intval(DI::args()->getArgv()[1])) {
			$circle = DBA::selectFirst('group', ['id', 'name'], ['id' => DI::args()->getArgv()[1], 'uid' => DI::userSession()->getLocalUserId(), 'deleted' => false]);
			if (!DBA::isResult($circle)) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Circle not found.'));
				DI::baseUrl()->redirect('contact');
			}

			$members = Model\Contact\Circle::getById($circle['id']);
			$preselected = [];

			if (count($members)) {
				foreach ($members as $member) {
					$preselected[] = $member['id'];
				}
			}

			if ($change) {
				if (in_array($change, $preselected)) {
					Model\Circle::removeMember($circle['id'], $change);
				} else {
					Model\Circle::addMember($circle['id'], $change);
				}

				$members = Model\Contact\Circle::getById($circle['id']);
				$preselected = [];
				if (count($members)) {
					foreach ($members as $member) {
						$preselected[] = $member['id'];
					}
				}
			}

			$drop_tpl = Renderer::getMarkupTemplate('circle_drop.tpl');
			$drop_txt = Renderer::replaceMacros($drop_tpl, [
				'$id' => $circle['id'],
				'$delete' => DI::l10n()->t('Delete Circle'),
				'$form_security_token' => BaseModule::getFormSecurityToken('circle_drop'),
			]);

			$context = $context + [
				'$title' => $circle['name'],
				'$gname' => ['circle_name', DI::l10n()->t('Circle Name: '), $circle['name'], ''],
				'$gid' => $circle['id'],
				'$drop' => $drop_txt,
				'$form_security_token' => BaseModule::getFormSecurityToken('circle_edit'),
				'$edit_name' => DI::l10n()->t('Edit Circle Name'),
				'$editable' => 1,
			];
		}

		if (!isset($circle)) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		$circle_editor = [
			'label_members' => DI::l10n()->t('Members'),
			'members' => [],
			'label_contacts' => DI::l10n()->t('All Contacts'),
			'circle_is_empty' => DI::l10n()->t('Circle is empty'),
			'contacts' => [],
		];

		$sec_token = addslashes(BaseModule::getFormSecurityToken('circle_member_change'));

		// Format the data of the circle members
		foreach ($members as $member) {
			if ($member['url']) {
				$entry = Contact::getContactTemplateVars($member);
				$entry['label'] = 'members';
				$entry['photo_menu'] = '';
				$entry['change_member'] = [
					'title'     => DI::l10n()->t('Remove contact from circle'),
					'gid'       => $circle['id'],
					'cid'       => $member['id'],
					'sec_token' => $sec_token
				];

				$circle_editor['members'][] = $entry;
			} else {
				Model\Circle::removeMember($circle['id'], $member['id']);
			}
		}

		if ($nocircle) {
			$contacts = Model\Contact\Circle::listUncircled(DI::userSession()->getLocalUserId());
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
			// Format the data of the contacts who aren't in the contact circle
			foreach ($contacts as $member) {
				if (!in_array($member['id'], $preselected)) {
					$entry = Contact::getContactTemplateVars($member);
					$entry['label'] = 'contacts';
					if (!$nocircle)
						$entry['photo_menu'] = [];

					if (!$nocircle) {
						$entry['change_member'] = [
							'title'     => DI::l10n()->t('Add contact to circle'),
							'gid'       => $circle['id'],
							'cid'       => $member['id'],
							'sec_token' => $sec_token
						];
					}

					$circle_editor['contacts'][] = $entry;
				}
			}
		}

		$context['$circle_editor'] = $circle_editor;

		// If there are to many contacts we could provide an alternative view mode
		$total = count($circle_editor['members']) + count($circle_editor['contacts']);
		$context['$shortmode'] = (($switchtotext && ($total > $switchtotext)) ? true : false);

		if ($change) {
			$tpl = Renderer::getMarkupTemplate('circle_editor.tpl');
			echo Renderer::replaceMacros($tpl, $context);
			System::exit();
		}

		return Renderer::replaceMacros($tpl, $context);
	}
}
