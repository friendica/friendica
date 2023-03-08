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

namespace Friendica\Module\Settings;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Account delegation settings module
 */
class Delegation extends BaseSettings
{
	protected function post(array $request = [])
	{
		if (!DI::app()->isLoggedIn()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		BaseModule::checkFormSecurityTokenRedirectOnError('settings/delegation', 'delegate');

		$parent_uid = (int)$_POST['parent_user'] ?? 0;
		$parent_password = $_POST['parent_password'] ?? '';

		if ($parent_uid != 0) {
			try {
				User::getIdFromPasswordAuthentication($parent_uid, $parent_password);
				DI::sysmsg()->addInfo(DI::l10n()->t('Delegation successfully granted.'));
			} catch (\Exception $ex) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Parent user not found, unavailable or password doesn\'t match.'));
				return;
			}
		} else {
			DI::sysmsg()->addInfo(DI::l10n()->t('Delegation successfully revoked.'));
		}

		DBA::update('user', ['parent-uid' => $parent_uid], ['uid' => DI::userSession()->getLocalUserId()]);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$args = DI::args();

		// @TODO Replace with router-provided arguments
		$action = $args->get(2);
		$user_id = $args->get(3);

		if ($action === 'add' && $user_id) {
			if (DI::userSession()->getSubManagedUserId()) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Delegated administrators can view but not change delegation permissions.'));
				DI::baseUrl()->redirect('settings/delegation');
			}

			$user = User::getById($user_id, ['nickname']);
			if (DBA::isResult($user)) {
				$condition = [
					'uid' => DI::userSession()->getLocalUserId(),
					'nurl' => Strings::normaliseLink(DI::baseUrl() . '/profile/' . $user['nickname'])
				];
				if (DBA::exists('contact', $condition)) {
					DBA::insert('manage', ['uid' => $user_id, 'mid' => DI::userSession()->getLocalUserId()]);
				}
			} else {
				DI::sysmsg()->addNotice(DI::l10n()->t('Delegate user not found.'));
			}

			DI::baseUrl()->redirect('settings/delegation');
		}

		if ($action === 'remove' && $user_id) {
			if (DI::userSession()->getSubManagedUserId()) {
				DI::sysmsg()->addNotice(DI::l10n()->t('Delegated administrators can view but not change delegation permissions.'));
				DI::baseUrl()->redirect('settings/delegation');
			}

			DBA::delete('manage', ['uid' => $user_id, 'mid' => DI::userSession()->getLocalUserId()]);
			DI::baseUrl()->redirect('settings/delegation');
		}

		// find everybody that currently has delegated management to this account/page
		$delegates = DBA::selectToArray('user', [], ['`uid` IN (SELECT `uid` FROM `manage` WHERE `mid` = ?)', DI::userSession()->getLocalUserId()]);

		$uids = [];
		foreach ($delegates as $user) {
			$uids[] = $user['uid'];
		}

		// find every contact who might be a candidate for delegation
		$potentials = [];
		$nicknames = [];

		$condition = ['baseurl' => DI::baseUrl(), 'self' => false, 'uid' => DI::userSession()->getLocalUserId(), 'blocked' => false];
		$contacts = DBA::select('contact', ['nick'], $condition);
		while ($contact = DBA::fetch($contacts)) {
			$nicknames[] = $contact['nick'];
		}
		DBA::close($contacts);

		// get user records for all potential page delegates who are not already delegates or managers
		$potentialDelegateUsers = DBA::selectToArray('user', ['uid', 'username', 'nickname'], ['nickname' => $nicknames]);
		foreach ($potentialDelegateUsers as $user) {
			if (!in_array($user['uid'], $uids)) {
				$potentials[] = $user;
			}
		}

		$parent_user = null;
		$parent_password = null;
		$user = User::getById(DI::userSession()->getLocalUserId(), ['parent-uid', 'email']);
		if (DBA::isResult($user) && !DBA::exists('user', ['parent-uid' => DI::userSession()->getLocalUserId()])) {
			$parent_uid = $user['parent-uid'];
			$parents = [0 => DI::l10n()->t('No parent user')];

			$fields = ['uid', 'username', 'nickname'];
			$condition = ['email' => $user['email'], 'verified' => true, 'blocked' => false, 'parent-uid' => 0];
			$parent_users = DBA::selectToArray('user', $fields, $condition);
			foreach($parent_users as $parent) {
				if ($parent['uid'] != DI::userSession()->getLocalUserId()) {
					$parents[$parent['uid']] = sprintf('%s (%s)', $parent['username'], $parent['nickname']);
				}
			}

			$parent_user = ['parent_user', DI::l10n()->t('Parent User'), $parent_uid, '', $parents];
			$parent_password = ['parent_password', DI::l10n()->t('Parent Password:'), '', DI::l10n()->t('Please enter the password of the parent account to legitimize your request.')];
		}

		$is_child_user = !empty($user['parent-uid']);

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/delegation.tpl'), [
			'$form_security_token' => BaseModule::getFormSecurityToken('delegate'),
			'$account_header' => DI::l10n()->t('Additional Accounts'),
			'$account_desc' => DI::l10n()->t('Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'),
			'$add_account' => DI::l10n()->t('Register an additional account'),
			'$parent_header' => DI::l10n()->t('Parent User'),
			'$parent_user' => $parent_user,
			'$parent_password' => $parent_password,
			'$parent_desc' => DI::l10n()->t('Parent users have total control about this account, including the account settings. Please double check whom you give this access.'),
			'$is_child_user' => $is_child_user,
			'$submit' => DI::l10n()->t('Save Settings'),
			'$header' => DI::l10n()->t('Manage Accounts'),
			'$delegates_header' => DI::l10n()->t('Delegates'),
			'$base' => DI::baseUrl(),
			'$desc' => DI::l10n()->t('Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'),
			'$head_delegates' => DI::l10n()->t('Existing Page Delegates'),
			'$delegates' => $delegates,
			'$head_potentials' => DI::l10n()->t('Potential Delegates'),
			'$potentials' => $potentials,
			'$remove' => DI::l10n()->t('Remove'),
			'$add' => DI::l10n()->t('Add'),
			'$none' => DI::l10n()->t('No entries.')
		]);

		return $o;
	}
}
