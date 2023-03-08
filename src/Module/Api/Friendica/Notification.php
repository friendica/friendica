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

namespace Friendica\Module\Api\Friendica;

use Friendica\Collection\Api\Notifications as ApiNotifications;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Object\Api\Friendica\Notification as ApiNotification;

/**
 * API endpoint: /api/friendica/notification
 */
class Notification extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$Notifies = DI::notify()->selectAllForUser($uid, 50);

		$notifications = new ApiNotifications();
		foreach ($Notifies as $Notify) {
			$notifications[] = new ApiNotification($Notify);
		}

		if (($this->parameters['extension'] ?? '') == 'xml') {
			$xmlnotes = [];
			foreach ($notifications as $notification) {
				$xmlnotes[] = ['@attributes' => $notification->toArray()];
			}

			$result = $xmlnotes;
		} elseif (count($notifications) > 0) {
			$result = $notifications->getArrayCopy();
		} else {
			$result = false;
		}

		$this->response->exit('notes', ['note' => $result], $this->parameters['extension'] ?? null);
	}
}
