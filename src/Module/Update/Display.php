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

namespace Friendica\Module\Update;

use Friendica\Model\Post;
use Friendica\Module\Item\Display as DisplayModule;
use Friendica\Util\DateTimeFormat;
use Friendica\Network\HTTPException;

/**
 * Asynchronous update class for the display
 */
class Display extends DisplayModule
{
	protected function content(array $request = []): string
	{
		if ($this->config->get('system', 'block_public') && !$this->session->isAuthenticated()) {
			throw new HTTPException\UnauthorizedException($this->t('Access denied.'));
		}

		$profileUid = $request['p']      ?? 0;
		$force      = $request['force']  ?? false;
		$uriId      = $request['uri_id'] ?? 0;

		if (empty($uriId)) {
			throw new HTTPException\BadRequestException($this->t('Parameter uri_id is missing.'));
		}

		$item = Post::selectFirst(
			['uid', 'parent-uri-id', 'uri-id'],
			['uri-id' => $uriId, 'uid' => [0, $profileUid]],
			['order'  => ['uid' => true]]
		);

		if (empty($item)) {
			throw new HTTPException\NotFoundException($this->t('The requested item doesn\'t exist or has been deleted.'));
		}

		$this->app->setProfileOwner($item['uid'] ?: $profileUid);
		$parentUriId = $item['parent-uri-id'];

		if (empty($force)) {
			$browserUpdate = intval($this->pConfig->get($profileUid, 'system', 'update_interval') ?? 40000);
			if ($browserUpdate >= 1000) {
				$updateDate = date(DateTimeFormat::MYSQL, time() - ($browserUpdate * 2 / 1000));
				if (!Post::exists([
					"`parent-uri-id` = ? AND `uid` IN (?, ?) AND `received` > ?",
					$parentUriId, 0,
					$profileUid, $updateDate])) {
					$this->logger->debug('No updated content. Ending process',
						['uri-id' => $uriId, 'uid' => $profileUid, 'updated' => $updateDate]);
					return '';
				} else {
					$this->logger->debug('Updated content found.',
						['uri-id' => $uriId, 'uid' => $profileUid, 'updated' => $updateDate]);
				}
			}
		} else {
			$this->logger->debug('Forced content update.', ['uri-id' => $uriId, 'uid' => $profileUid]);
		}

		if (!$this->pConfig->get($this->session->getLocalUserId(), 'system', 'detailed_notif')) {
			$this->notification->setAllSeenForUser($this->session->getLocalUserId(), ['parent-uri-id' => $item['parent-uri-id']]);
			$this->notify->setAllSeenForUser($this->session->getLocalUserId(), ['parent-uri-id' => $item['parent-uri-id']]);
		}

		return $this->getDisplayData($item, true, $profileUid, $force);
	}
}
