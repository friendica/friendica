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
use Friendica\DI;
use Friendica\Model\Contact;

/**
 * Process follow request confirmations
 */
class FollowConfirm extends BaseModule
{
	protected function post(array $request = [])
	{
		parent::post($request);
		$uid = DI::userSession()->getLocalUserId();
		if (!$uid) {
			DI::sysmsg()->addNotice(DI::l10n()->t('Permission denied.'));
			return;
		}

		$intro_id = intval($_POST['intro_id']   ?? 0);
		$duplex   = intval($_POST['duplex']     ?? 0);
		$hidden   = intval($_POST['hidden']     ?? 0);

		$intro = DI::intro()->selectOneById($intro_id, DI::userSession()->getLocalUserId());

		Contact\Introduction::confirm($intro, $duplex, $hidden);
		DI::intro()->delete($intro);

		DI::baseUrl()->redirect('contact/' .  $intro->cid);
	}
}
