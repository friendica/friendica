<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Admin;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseAdmin;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Roles extends BaseAdmin
{
	public function __construct(
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		private readonly IManageConfigValues $config,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		if (empty($_POST['page_roles'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/admin/roles', 'admin_roles');

		$action = (string) ($_POST['roles_action'] ?? '');
		$uid    = (int) ($_POST['moderator_uid'] ?? 0);

		if ($uid <= 0) {
			DI::sysmsg()->addNotice($this->t('Please select a valid user.'));
			$this->baseUrl->redirect('admin/roles');
		}

		$moderatorUids = array_values(array_filter(User::getModeratorUids(), function (int $moderatorUid): bool {
			return $moderatorUid !== 0;
		}));

		switch ($action) {
			case 'add':
				$user = User::getById($uid, ['uid', 'nickname', 'blocked', 'verified', 'account_removed', 'account_expired', 'parent-uid', 'account-type']);
				if (
					!DBA::isResult($user)
					|| (int) $user['account-type'] !== User::ACCOUNT_TYPE_PERSON
					|| !empty($user['parent-uid'])
					|| !empty($user['blocked'])
					|| empty($user['verified'])
					|| !empty($user['account_removed'])
					|| !empty($user['account_expired'])
				) {
					DI::sysmsg()->addNotice($this->t('The selected user cannot be assigned moderation rights.'));
					$this->baseUrl->redirect('admin/roles');
				}

				if (!in_array($uid, $moderatorUids, true)) {
					$moderatorUids[] = $uid;
					$this->persistModeratorUids($moderatorUids);
					DI::sysmsg()->addInfo($this->t('Moderation rights have been granted.'));
				}
				break;

			case 'remove':
				$updatedModeratorUids = array_values(array_filter($moderatorUids, function (int $moderatorUid) use ($uid): bool {
					return $moderatorUid !== $uid;
				}));

				if (count($updatedModeratorUids) !== count($moderatorUids)) {
					$this->persistModeratorUids($updatedModeratorUids);
					DI::sysmsg()->addInfo($this->t('Moderation rights have been removed.'));
				}
				break;

			default:
				DI::sysmsg()->addNotice($this->t('Unknown role action.'));
		}

		$this->baseUrl->redirect('admin/roles');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$fields = ['uid', 'nickname', 'username', 'email', 'account-type'];

		$adminUsers            = User::getAdminList($fields);
		$adminUsers            = $this->filterRoleEligibleUsers($adminUsers);
		$explicitModeratorList = User::getModeratorList($fields);
		$moderationUsers       = $this->buildModerationUsers($adminUsers, $explicitModeratorList);

		$availableUsers = DBA::selectToArray('user', $fields, [
			"`uid` != ? AND `account-type` = ? AND `parent-uid` IS NULL AND NOT `blocked` AND `verified` AND NOT `account_removed` AND NOT `account_expired`",
			0,
			User::ACCOUNT_TYPE_PERSON,
		], ['order' => ['nickname', 'username', 'uid']]);

		$moderationUserUids = [];
		foreach ($moderationUsers as $moderationUser) {
			$moderationUserUids[(int) $moderationUser['uid']] = true;
		}

		$availableUsers = array_values(array_filter($availableUsers, function (array $user) use ($moderationUserUids): bool {
			return empty($moderationUserUids[(int) $user['uid']]);
		}));

		$t = Renderer::getMarkupTemplate('admin/roles.tpl');

		return Renderer::replaceMacros($t, [
			'$title'               => $this->t('Administration'),
			'$page'                => $this->t('Roles'),
			'$intro'               => $this->t('Manage moderators for this node. Administrator rights are read-only and managed through the node configuration.'),
			'$admin_title'         => $this->t('Users with administrator rights (read-only)'),
			'$moderator_title'     => $this->t('Users with moderation rights'),
			'$user_header'         => $this->t('User'),
			'$email_header'        => $this->t('Email'),
			'$source_header'       => $this->t('Source'),
			'$actions_header'      => $this->t('Actions'),
			'$no_admin_users'      => $this->t('No administrators found.'),
			'$no_moderator_users'  => $this->t('No users with moderation rights found.'),
			'$admin_source'        => $this->t('Administrator'),
			'$moderator_source'    => $this->t('Moderator'),
			'$combined_source'     => $this->t('Administrator + Moderator'),
			'$remove'              => $this->t('Remove moderation rights'),
			'$cannot_remove_admin' => $this->t('Administrators always keep moderation rights.'),
			'$assign_title'        => $this->t('Assign moderation rights'),
			'$assign_label'        => $this->t('User'),
			'$assign_button'       => $this->t('Grant moderation rights'),
			'$no_assignable_users' => $this->t('All active users already have moderation rights.'),
			'$admin_users'         => $adminUsers,
			'$moderation_users'    => $moderationUsers,
			'$available_users'     => $availableUsers,
			'$form_security_token' => self::getFormSecurityToken('admin_roles'),
		]);
	}

	private function persistModeratorUids(array $moderatorUids): void
	{
		$uids = [];
		foreach ($moderatorUids as $moderatorUid) {
			$moderatorUid = (int) $moderatorUid;
			if ($moderatorUid > 0) {
				$uids[$moderatorUid] = $moderatorUid;
			}
		}

		$uids = array_values($uids);
		sort($uids);

		$this->config->set('system', 'moderator_users', implode(',', $uids));
	}

	private function buildModerationUsers(array $adminUsers, array $explicitModeratorUsers): array
	{
		$moderationUsers = [];

		foreach ($adminUsers as $adminUser) {
			$uid = (int) $adminUser['uid'];
			if ($uid === 0) {
				continue;
			}

			$adminUser['source']     = 'admin';
			$adminUser['can_remove'] = false;
			$moderationUsers[$uid]   = $adminUser;
		}

		foreach ($explicitModeratorUsers as $explicitModeratorUser) {
			$uid = (int) $explicitModeratorUser['uid'];
			if (!empty($moderationUsers[$uid])) {
				$moderationUsers[$uid]['source'] = 'both';
				continue;
			}

			$explicitModeratorUser['source']     = 'moderator';
			$explicitModeratorUser['can_remove'] = true;
			$moderationUsers[$uid]               = $explicitModeratorUser;
		}

		ksort($moderationUsers);

		return array_values($moderationUsers);
	}

	private function filterRoleEligibleUsers(array $users): array
	{
		return array_values(array_filter($users, function (array $user): bool {
			if ($user['uid'] === 0) {
				return false;
			}

			if ($user['account-type'] !== User::ACCOUNT_TYPE_PERSON) {
				return false;
			}

			return true;
		}));
	}
}
