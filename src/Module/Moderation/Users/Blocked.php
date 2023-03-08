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
use Friendica\Model\User;
use Friendica\Module\Moderation\BaseUsers;

class Blocked extends BaseUsers
{
	protected function post(array $request = [])
	{
		$this->checkModerationAccess();

		self::checkFormSecurityTokenRedirectOnError('/moderation/users/blocked', 'moderation_users_blocked');

		$users = $request['user'] ?? [];

		if (!empty($request['page_users_unblock'])) {
			foreach ($users as $uid) {
				User::block($uid, false);
			}
			$this->systemMessages->addInfo($this->tt('%s user unblocked', '%s users unblocked', count($users)));
		}

		if (!empty($request['page_users_delete'])) {
			foreach ($users as $uid) {
				if ($this->session->getLocalUserId() != $uid) {
					User::remove($uid);
				} else {
					$this->systemMessages->addNotice($this->t('You can\'t remove yourself'));
				}
			}

			$this->systemMessages->addInfo($this->tt('%s user deleted', '%s users deleted', count($users)));
		}

		$this->baseUrl->redirect('moderation/users/blocked');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$action = $this->parameters['action'] ?? '';
		$uid    = $this->parameters['uid']    ?? 0;

		if ($uid) {
			$user = User::getById($uid, ['username', 'blocked']);
			if (!$user) {
				$this->systemMessages->addNotice($this->t('User not found'));
				$this->baseUrl->redirect('moderation/users');
			}
		}

		switch ($action) {
			case 'delete':
				if ($this->session->getLocalUserId() != $uid) {
					self::checkFormSecurityTokenRedirectOnError('/moderation/users/blocked', 'moderation_users_blocked', 't');
					// delete user
					User::remove($uid);

					$this->systemMessages->addNotice($this->t('User "%s" deleted', $user['username']));
				} else {
					$this->systemMessages->addNotice($this->t('You can\'t remove yourself'));
				}
				$this->baseUrl->redirect('moderation/users/blocked');
				break;
			case 'unblock':
				self::checkFormSecurityTokenRedirectOnError('/moderation/users/blocked', 'moderation_users_blocked', 't');
				User::block($uid, false);
				$this->systemMessages->addNotice($this->t('User "%s" unblocked', $user['username']));
				$this->baseUrl->redirect('moderation/users/blocked');
				break;
		}

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 100);

		$valid_orders = [
			'name',
			'email',
			'register_date',
			'last-activity',
			'last-item',
			'page-flags',
		];

		$order           = 'name';
		$order_direction = '+';
		if (!empty($request['o'])) {
			$new_order = $request['o'];
			if ($new_order[0] === '-') {
				$order_direction = '-';
				$new_order       = substr($new_order, 1);
			}

			if (in_array($new_order, $valid_orders)) {
				$order = $new_order;
			}
		}

		$users = User::getList($pager->getStart(), $pager->getItemsPerPage(), 'blocked', $order, ($order_direction == '-'));

		$users = array_map($this->setupUserCallback(), $users);

		$th_users = array_map(null, [$this->t('Name'), $this->t('Email'), $this->t('Register date'), $this->t('Last login'), $this->t('Last public item'), $this->t('Type')], $valid_orders);

		$count = $this->database->count('user', ['blocked' => true, 'verified' => true]);

		$t = Renderer::getMarkupTemplate('moderation/users/blocked.tpl');
		return self::getTabsHTML('blocked') . Renderer::replaceMacros($t, [
			// strings //
			'$title'          => $this->t('Moderation'),
			'$page'           => $this->t('Blocked Users'),
			'$select_all'     => $this->t('select all'),
			'$delete'         => $this->t('Delete'),
			'$blocked'        => $this->t('User blocked'),
			'$unblock'        => $this->t('Unblock'),
			'$siteadmin'      => $this->t('Site admin'),
			'$accountexpired' => $this->t('Account expired'),

			'$th_users'              => $th_users,
			'$order_users'           => $order,
			'$order_direction_users' => $order_direction,

			'$confirm_delete_multi' => $this->t('Selected users will be deleted!\n\nEverything these users had posted on this site will be permanently deleted!\n\nAre you sure?'),
			'$confirm_delete'       => $this->t('The user {0} will be deleted!\n\nEverything this user has posted on this site will be permanently deleted!\n\nAre you sure?'),

			'$form_security_token' => self::getFormSecurityToken('moderation_users_blocked'),

			// values //
			'$query_string' => $this->args->getQueryString(),

			'$users' => $users,
			'$count' => $count,
			'$pager' => $pager->renderFull($count)
		]);
	}
}
