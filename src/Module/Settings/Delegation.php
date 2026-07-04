<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\User;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Account delegation settings module
 */
class Delegation extends BaseSettings
{
	public function __construct(private readonly Database $db, private readonly SystemMessages $systemMessages, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		if (!$this->session->isAuthenticated()) {
			return;
		}

		BaseModule::checkFormSecurityTokenRedirectOnError('settings/delegation', 'delegate');

		$parent_uid      = $request['parent_user']     ?? null;
		$parent_password = $request['parent_password'] ?? '';

		if ($parent_uid) {
			try {
				// An integer value will trigger the direct user query on uid in User::getAuthenticationInfo
				$parent_uid = (int) $parent_uid;
				User::getIdFromPasswordAuthentication($parent_uid, $parent_password);
				$this->systemMessages->addInfo($this->t('Delegation successfully granted.'));
			} catch (\Exception) {
				$this->systemMessages->addNotice($this->t('Parent user not found, unavailable or password doesn\'t match.'));
				return;
			}
		} else {
			$this->systemMessages->addInfo($this->t('Delegation successfully revoked.'));
		}

		$this->db->update('user', ['parent-uid' => $parent_uid], ['uid' => $this->session->getLocalUserId()]);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		if (!$this->session->isAuthenticated()) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		$action  = $this->parameters['action']  ?? '';
		$user_id = $this->parameters['user_id'] ?? 0;

		if ($action === 'add' && $user_id) {
			if ($this->session->getSubManagedUserId()) {
				$this->systemMessages->addNotice($this->t('Delegated administrators can view but not change delegation permissions.'));
				$this->baseUrl->redirect('settings/delegation');
			}

			$user = User::getById($user_id, ['nickname']);
			if ($this->db->isResult($user)) {
				$condition = [
					'uid'  => $this->session->getLocalUserId(),
					'nurl' => Strings::normaliseLink($this->baseUrl . '/profile/' . $user['nickname']),
				];
				if ($this->db->exists('contact', $condition)) {
					$this->db->insert('manage', ['uid' => $user_id, 'mid' => $this->session->getLocalUserId()]);
				}
			} else {
				$this->systemMessages->addNotice($this->t('Delegate user not found.'));
			}

			$this->baseUrl->redirect('settings/delegation');
		}

		if ($action === 'remove' && $user_id) {
			if ($this->session->getSubManagedUserId()) {
				$this->systemMessages->addNotice($this->t('Delegated administrators can view but not change delegation permissions.'));
				$this->baseUrl->redirect('settings/delegation');
			}

			$this->db->delete('manage', ['uid' => $user_id, 'mid' => $this->session->getLocalUserId()]);
			$this->baseUrl->redirect('settings/delegation');
		}

		// find everybody that currently has delegated management to this account/page
		$delegates = $this->db->selectToArray('user', [], ['`uid` IN (SELECT `uid` FROM `manage` WHERE `mid` = ?)', $this->session->getLocalUserId()]);

		$uids = [];
		foreach ($delegates as $user) {
			$uids[] = $user['uid'];
		}

		// find every contact who might be a candidate for delegation
		$potentials = [];
		$nicknames  = [];

		$condition = ['baseurl' => $this->baseUrl, 'self' => false, 'uid' => $this->session->getLocalUserId(), 'blocked' => false];
		$contacts  = $this->db->select('contact', ['nick'], $condition);
		while ($contact = $this->db->fetch($contacts)) {
			$nicknames[] = $contact['nick'];
		}
		$this->db->close($contacts);

		// get user records for all potential page delegates who are not already delegates or managers
		$potentialDelegateUsers = $this->db->selectToArray(
			'user',
			['uid', 'username', 'nickname'],
			[
				'nickname'        => $nicknames,
				'account_removed' => false,
				'account_expired' => false,
				'blocked'         => false,
			],
		);
		foreach ($potentialDelegateUsers as $user) {
			if (!in_array($user['uid'], $uids)) {
				$potentials[] = $user;
			}
		}

		$parent_user     = null;
		$parent_password = null;
		$user            = User::getById($this->session->getLocalUserId(), ['parent-uid', 'email']);
		if ($this->db->isResult($user) && !$this->db->exists('user', ['parent-uid' => $this->session->getLocalUserId()])) {
			$parent_uid = $user['parent-uid'];
			$parents    = [0 => $this->t('No parent user')];

			$fields       = ['uid', 'username', 'nickname'];
			$condition    = ['email' => $user['email'], 'verified' => true, 'blocked' => false, 'parent-uid' => null];
			$parent_users = $this->db->selectToArray('user', $fields, $condition);
			foreach ($parent_users as $parent) {
				if ($parent['uid'] != $this->session->getLocalUserId()) {
					$parents[$parent['uid']] = sprintf('%s (%s)', $parent['username'], $parent['nickname']);
				}
			}

			$parent_user     = ['parent_user', $this->t('Parent User'), $parent_uid, '', $parents];
			$parent_password = ['parent_password', $this->t('Parent Password:'), '', $this->t('Please enter the password of the parent account to legitimize your request.')];
		}

		$is_child_user = !empty($user['parent-uid']);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('settings/delegation.tpl'), [
			'$l10n' => [
				'account_header'   => $this->t('Additional Accounts'),
				'account_desc'     => $this->t('Register additional accounts that are automatically connected to your existing account so you can manage them from this account.'),
				'add_account'      => $this->t('Register an additional account'),
				'parent_header'    => $this->t('Parent User'),
				'parent_desc'      => $this->t('Parent users have total control about this account, including the account settings. Please double check whom you give this access.'),
				'submit'           => $this->t('Save Settings'),
				'header'           => $this->t('Manage Accounts'),
				'delegates_header' => $this->t('Delegates'),
				'desc'             => $this->t('Delegates are able to manage all aspects of this account/page except for basic account settings. Please do not delegate your personal account to anybody that you do not trust completely.'),
				'head_delegates'   => $this->t('Existing Page Delegates'),
				'head_potentials'  => $this->t('Potential Delegates'),
				'none'             => $this->t('No entries.'),
			],

			'$form_security_token' => BaseModule::getFormSecurityToken('delegate'),
			'$parent_user'         => $parent_user,
			'$parent_password'     => $parent_password,
			'$is_child_user'       => $is_child_user,
			'$delegates'           => $delegates,
			'$potentials'          => $potentials,
		]);
	}
}
