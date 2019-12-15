<?php

namespace Friendica\Module\Settings;

use Friendica\App\Arguments;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseSettingsModule;
use Friendica\Network\HTTPException;
use Friendica\Util\Strings;

/**
 * Account delegation settings module
 */
class Delegation extends BaseSettingsModule
{
	public static function post(array $parameters = [])
	{
		if (!local_user() || !empty(DI::app()->user['uid']) && DI::app()->user['uid'] != local_user()) {
			throw new HTTPException\ForbiddenException(L10n::t('Permission denied.'));
		}

		BaseModule::checkFormSecurityTokenRedirectOnError('settings/delegation', 'delegate');

		$parent_uid = $_POST['parent_user'] ?? 0;
		$parent_password = $_POST['parent_password'] ?? '';

		if ($parent_uid != 0) {
			try {
				User::getIdFromPasswordAuthentication($parent_uid, $parent_password);
				info(L10n::t('Delegation successfully granted.'));
			} catch (\Exception $ex) {
				notice(L10n::t('Parent user not found, unavailable or password doesn\'t match.'));
				return;
			}
		} else {
			info(L10n::t('Delegation successfully revoked.'));
		}

		DBA::update('user', ['parent-uid' => $parent_uid], ['uid' => local_user()]);
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		if (!local_user()) {
			throw new HTTPException\ForbiddenException(L10n::t('Permission denied.'));
		}

		$args = DI::args();

		// @TODO Replace with router-provided arguments
		$action = $args->get(2);
		$user_id = $args->get(3);

		if ($action === 'add' && $user_id) {
			if (Session::get('submanage')) {
				notice(L10n::t('Delegated administrators can view but not change delegation permissions.'));
				DI::baseUrl()->redirect('settings/delegation');
			}

			$user = User::getById($user_id, ['nickname']);
			if (DBA::isResult($user)) {
				$condition = [
					'uid' => local_user(),
					'nurl' => Strings::normaliseLink(System::baseUrl() . '/profile/' . $user['nickname'])
				];
				if (DBA::exists('contact', $condition)) {
					DBA::insert('manage', ['uid' => $user_id, 'mid' => local_user()]);
				}
			} else {
				notice(L10n::t('Delegate user not found.'));
			}

			DI::baseUrl()->redirect('settings/delegation');
		}

		if ($action === 'remove' && $user_id) {
			if (Session::get('submanage')) {
				notice(L10n::t('Delegated administrators can view but not change delegation permissions.'));
				DI::baseUrl()->redirect('settings/delegation');
			}

			DBA::delete('manage', ['uid' => $user_id, 'mid' => local_user()]);
			DI::baseUrl()->redirect('settings/delegation');
		}

		// find everybody that currently has delegated management to this account/page
		$delegates = DBA::selectToArray('user', [], ['`uid` IN (SELECT `uid` FROM `manage` WHERE `mid` = ?)', local_user()]);

		$uids = [];
		foreach ($delegates as $user) {
			$uids[] = $user['uid'];
		}

		// find every contact who might be a candidate for delegation
		$potentials = [];

		$contacts = DBA::selectToArray(
			'contact',
			['nurl'],
			[
				"`self` = 0 AND SUBSTRING_INDEX(`nurl`, '/', 3) = ? AND `uid` = ? AND `network` = ?",
				Strings::normaliseLink(System::baseUrl()),
				local_user(),
				Protocol::DFRN,
			]
		);
		if ($contacts) {
			$nicknames = [];
			foreach ($contacts as $contact) {
				$nicknames[] = "'" . DBA::escape(basename($contact['nurl'])) . "'";
			}

			// get user records for all potential page delegates who are not already delegates or managers
			$potentialDelegateUsers = DBA::selectToArray('user', ['uid', 'username', 'nickname'], ['nickname' => $nicknames]);
			foreach ($potentialDelegateUsers as $user) {
				if (!in_array($user['uid'], $uids)) {
					$potentials[] = $user;
				}
			}
		}

		$parent_user = null;
		$parent_password = null;
		$user = User::getById(local_user(), ['parent-uid', 'email']);
		if (DBA::isResult($user) && !DBA::exists('user', ['parent-uid' => local_user()])) {
			$parent_uid = $user['parent-uid'];
			$parents = [0 => L10n::t('No parent user')];

			$fields = ['uid', 'username', 'nickname'];
			$condition = ['email' => $user['email'], 'verified' => true, 'blocked' => false, 'parent-uid' => 0];
			$parent_users = DBA::selectToArray('user', $fields, $condition);
			foreach($parent_users as $parent) {
				if ($parent['uid'] != local_user()) {
					$parents[$parent['uid']] = sprintf('%s (%s)', $parent['username'], $parent['nickname']);
				}
			}

			$parent_user = ['parent_user', '', $parent_uid, '', $parents];
			$parent_password = ['parent_password', L10n::t('Parent Password:'), '', L10n::t('Please enter the password of the parent account to legitimize your request.')];
		}

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/delegation.tpl'), [
			'$form_security_token' => BaseModule::getFormSecurityToken('delegate'),
			'$parent_header' => L10n::t('Parent User'),
			'$parent_user' => $parent_user,
			'$parent_password' => $parent_password,
			'$parent_desc' => L10n::t('Parent users have total control about this account, including the account settings. Please double check whom you give this access.'),
			'$submit' => L10n::t('Save Settings'),
			'$header' => L10n::t('Delegate Page Management'),
			'$delegates_header' => L10n::t('Delegates'),
			'$base' => System::baseUrl(),
			'$desc' => L10n::t('Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'),
			'$head_delegates' => L10n::t('Existing Page Delegates'),
			'$delegates' => $delegates,
			'$head_potentials' => L10n::t('Potential Delegates'),
			'$potentials' => $potentials,
			'$remove' => L10n::t('Remove'),
			'$add' => L10n::t('Add'),
			'$none' => L10n::t('No entries.')
		]);

		return $o;
	}
}
