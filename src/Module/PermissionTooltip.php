<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\Core\Hook;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Group;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;

/**
 * Outputs the permission tooltip HTML content for the provided item, photo or event id.
 */
class PermissionTooltip extends \Friendica\BaseModule
{
	protected function rawContent(array $request = [])
	{
		$type = $this->parameters['type'];
		$referenceId = $this->parameters['id'];

		$expectedTypes = ['item', 'photo', 'event'];
		if (!in_array($type, $expectedTypes)) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('Wrong type "%s", expected one of: %s', $type, implode(', ', $expectedTypes)));
		}

		$condition = ['id' => $referenceId];
		if ($type == 'item') {
			$fields = ['uid', 'psid', 'private'];
			$model = Post::selectFirst($fields, $condition);
		} else {
			$fields = ['uid', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid'];
			$model = DBA::selectFirst($type, $fields, $condition);
			$model['allow_cid'] = DI::aclFormatter()->expand($model['allow_cid']);
			$model['allow_gid'] = DI::aclFormatter()->expand($model['allow_gid']);
			$model['deny_cid']  = DI::aclFormatter()->expand($model['deny_cid']);
			$model['deny_gid']  = DI::aclFormatter()->expand($model['deny_gid']);
		}

		if (!DBA::isResult($model)) {
			throw new HttpException\NotFoundException(DI::l10n()->t('Model not found'));
		}

		if (isset($model['psid'])) {
			$permissionSet = DI::permissionSet()->selectOneById($model['psid'], $model['uid']);
			$model['allow_cid'] = $permissionSet->allow_cid;
			$model['allow_gid'] = $permissionSet->allow_gid;
			$model['deny_cid']  = $permissionSet->deny_cid;
			$model['deny_gid']  = $permissionSet->deny_gid;
		}

		// Kept for backwards compatiblity
		Hook::callAll('lockview_content', $model);

		if ($model['uid'] != local_user() ||
			isset($model['private'])
			&& $model['private'] == Item::PRIVATE
			&& empty($model['allow_cid'])
			&& empty($model['allow_gid'])
			&& empty($model['deny_cid'])
			&& empty($model['deny_gid']))
		{
			echo DI::l10n()->t('Remote privacy information not available.');
			exit;
		}

		$allowed_users  = $model['allow_cid'];
		$allowed_groups = $model['allow_gid'];
		$deny_users     = $model['deny_cid'];
		$deny_groups    = $model['deny_gid'];

		$o = DI::l10n()->t('Visible to:') . '<br />';
		$l = [];

		if (count($allowed_groups)) {
			$key = array_search(Group::FOLLOWERS, $allowed_groups);
			if ($key !== false) {
				$l[] = '<b>' . DI::l10n()->t('Followers') . '</b>';
				unset($allowed_groups[$key]);
			}

			$key = array_search(Group::MUTUALS, $allowed_groups);
			if ($key !== false) {
				$l[] = '<b>' . DI::l10n()->t('Mutuals') . '</b>';
				unset($allowed_groups[$key]);
			}

			foreach (DI::dba()->selectToArray('group', ['name'], ['id' => $allowed_groups]) as $group) {
				$l[] = '<b>' . $group['name'] . '</b>';
			}
		}

		foreach (DI::dba()->selectToArray('contact', ['name'], ['id' => $allowed_users]) as $contact) {
			$l[] = $contact['name'];
		}

		if (count($deny_groups)) {
			$key = array_search(Group::FOLLOWERS, $deny_groups);
			if ($key !== false) {
				$l[] = '<b><strike>' . DI::l10n()->t('Followers') . '</strike></b>';
				unset($deny_groups[$key]);
			}

			$key = array_search(Group::MUTUALS, $deny_groups);
			if ($key !== false) {
				$l[] = '<b><strike>' . DI::l10n()->t('Mutuals') . '</strike></b>';
				unset($deny_groups[$key]);
			}

			foreach (DI::dba()->selectToArray('group', ['name'], ['id' => $allowed_groups]) as $group) {
				$l[] = '<b><strike>' . $group['name'] . '</strike></b>';
			}
		}

		foreach (DI::dba()->selectToArray('contact', ['name'], ['id' => $deny_users]) as $contact) {
			$l[] = '<strike>' . $contact['name'] . '</strike>';
		}

		echo $o . implode(', ', $l);
		exit();
	}
}
