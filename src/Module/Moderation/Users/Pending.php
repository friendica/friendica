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

namespace Friendica\Module\Moderation\Users;

use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Model\Register;
use Friendica\Model\User;
use Friendica\Module\Moderation\BaseUsers;

class Pending extends BaseUsers
{
	protected function post(array $request = [])
	{
		$this->checkModerationAccess();

		self::checkFormSecurityTokenRedirectOnError('moderation/users/pending', 'admin_users_pending');

		$pending = $request['pending'] ?? [];

		if (!empty($request['page_users_approve'])) {
			foreach ($pending as $hash) {
				User::allow($hash);
			}
			$this->systemMessages->addInfo($this->tt('%s user approved', '%s users approved', count($pending)));
		}

		if (!empty($request['page_users_deny'])) {
			foreach ($pending as $hash) {
				User::deny($hash);
			}
			$this->systemMessages->addInfo($this->tt('%s registration revoked', '%s registrations revoked', count($pending)));
		}

		$this->baseUrl->redirect('moderation/users/pending');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$action = $this->parameters['action'] ?? '';
		$uid    = $this->parameters['uid'] ?? 0;

		if ($uid) {
			$user = User::getById($uid, ['username', 'blocked']);
			if (!$user) {
				$this->systemMessages->addNotice($this->t('User not found'));
				$this->baseUrl->redirect('moderation/users');
			}
		}

		switch ($action) {
			case 'allow':
				self::checkFormSecurityTokenRedirectOnError('moderation/users/pending', 'admin_users_pending', 't');
				User::allow(Register::getPendingForUser($uid)['hash'] ?? '');
				$this->systemMessages->addNotice($this->t('Account approved.'));
				$this->baseUrl->redirect('moderation/users/pending');
				break;
			case 'deny':
				self::checkFormSecurityTokenRedirectOnError('moderation/users/pending', 'admin_users_pending', 't');
				User::deny(Register::getPendingForUser($uid)['hash'] ?? '');
				$this->systemMessages->addNotice($this->t('Registration revoked'));
				$this->baseUrl->redirect('moderation/users/pending');
				break;
		}

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 100);

		$pending = Register::getPending($pager->getStart(), $pager->getItemsPerPage());

		$count = Register::getPendingCount();

		$t = Renderer::getMarkupTemplate('moderation/users/pending.tpl');
		return self::getTabsHTML('pending') . Renderer::replaceMacros($t, [
			// strings //
			'$title' => $this->t('Administration'),
			'$page' => $this->t('User registrations awaiting review'),
			'$select_all' => $this->t('select all'),
			'$th_pending' => [$this->t('Request date'), $this->t('Name'), $this->t('Email')],
			'$no_pending' => $this->t('No registrations.'),
			'$pendingnotetext' => $this->t('Note from the user'),
			'$approve' => $this->t('Approve'),
			'$deny' => $this->t('Deny'),

			'$form_security_token' => self::getFormSecurityToken('admin_users_pending'),

			// values //
			'$query_string' => $this->args->getQueryString(),

			'$pending' => $pending,
			'$count' => $count,
			'$pager' => $pager->renderFull($count),
		]);
	}
}
