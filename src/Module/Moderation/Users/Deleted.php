<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation\Users;

use Friendica\Content\Pager;
use Friendica\Core\Renderer;
use Friendica\Model\User;
use Friendica\Module\Moderation\BaseUsers;

class Deleted extends BaseUsers
{
	protected function post(array $request = [])
	{
		$this->checkModerationAccess();

		self::checkFormSecurityTokenRedirectOnError('/moderation/users/deleted', 'moderation_users_deleted');

		// @TODO: Implement user deletion cancellation

		$this->baseUrl->redirect('moderation/users/deleted');
	}

	protected function content(array $request = []): string
	{
		parent::content();

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

		$users = User::getList($pager->getStart(), $pager->getItemsPerPage(), 'removed', $order, ($order_direction == '-'));

		$users = array_map($this->setupUserCallback(), $users);

		$count = $this->database->count('user', ['account_removed' => true]);

		$t = Renderer::getMarkupTemplate('moderation/users/deleted.tpl');
		return self::getTabsHTML('deleted') . Renderer::replaceMacros($t, [
			// strings //
			'$title' => $this->t('Moderation'),
			'$page'  => $this->t('Users awaiting permanent deletion'),

			'$th_deleted' => [$this->t('Name'), $this->t('Email'), $this->t('Register date'), $this->t('Last activity'), $this->t('Last public item'), $this->t('Permanent deletion')],

			'$form_security_token' => self::getFormSecurityToken('moderation_users_deleted'),

			// values //
			'$query_string' => $this->args->getQueryString(),

			'$users' => $users,
			'$count' => $count,
			'$pager' => $pager->renderFull($count),
		]);
	}
}
