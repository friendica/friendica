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

namespace Friendica\Module\ActivityPub;

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * ActivityPub Objects
 */
class Objects extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['guid'])) {
			throw new HTTPException\BadRequestException();
		}

		if (!ActivityPub::isRequest()) {
			DI::baseUrl()->redirect(str_replace('objects/', 'display/', DI::args()->getQueryString()));
		}

		$itemuri = DBA::selectFirst('item-uri', ['id'], ['guid' => $this->parameters['guid']]);

		if (DBA::isResult($itemuri)) {
			Logger::info('Provided GUID found.', ['guid' => $this->parameters['guid'], 'uri-id' => $itemuri['id']]);
		} else {
			// The item URI does not always contain the GUID. This means that we have to search the URL instead
			$url = DI::baseUrl()->get() . '/' . DI::args()->getQueryString();
			$nurl = Strings::normaliseLink($url);
			$ssl_url = str_replace('http://', 'https://', $nurl);

			$itemuri = DBA::selectFirst('item-uri', ['guid', 'id'], ['uri' => [$url, $nurl, $ssl_url]]);
			if (DBA::isResult($itemuri)) {
				Logger::info('URL found.', ['url' => $url, 'guid' => $itemuri['guid'], 'uri-id' => $itemuri['id']]);
			} else {
				Logger::info('URL not found.', ['url' => $url]);
				throw new HTTPException\NotFoundException();
			}
		}

		$item = Post::selectFirst([], ['uri-id' => $itemuri['id'], 'origin' => true]);
		if (!DBA::isResult($item)) {
			throw new HTTPException\NotFoundException();
		}

		$owner = User::getById($item['uid'], ['hidewall']);

		$validated = empty($owner['hidewall']) && in_array($item['private'], [Item::PUBLIC, Item::UNLISTED]);

		if (!$validated) {
			$requester = HTTPSignature::getSigner('', $_SERVER);
			if (!empty($requester)) {
				$receivers = Item::enumeratePermissions($item, false);
				$receivers[] = $item['contact-id'];

				$validated = in_array(Contact::getIdForURL($requester, $item['uid']), $receivers);
				if (!$validated) {
					$validated = in_array(Contact::getIdForURL($requester), $receivers);
				}
			}
		}

		if (!$validated) {
			throw new HTTPException\NotFoundException();
		}

		$etag          = md5($this->parameters['guid'] . '-' . $item['changed']);
		$last_modified = $item['changed'];
		Network::checkEtagModified($etag, $last_modified);

		if (empty($this->parameters['activity']) && ($item['gravity'] != Item::GRAVITY_ACTIVITY)) {
			$activity = ActivityPub\Transmitter::createCachedActivityFromItem($item['id'], false, true);
			if (empty($activity['type'])) {
				throw new HTTPException\NotFoundException();
			}

			$activity['type'] = $activity['type'] == 'Update' ? 'Create' : $activity['type'];

			// Only display "Create" activity objects here, no reshares or anything else
			if (empty($activity['object']) || ($activity['type'] != 'Create')) {
				throw new HTTPException\NotFoundException();
			}

			$data = ['@context' => ActivityPub::CONTEXT];
			$data = array_merge($data, $activity['object']);
		} elseif (empty($this->parameters['activity']) || in_array($this->parameters['activity'],
			['Create', 'Announce', 'Update', 'Like', 'Dislike', 'Accept', 'Reject',
			'TentativeAccept', 'Follow', 'Add'])) {
			$data = ActivityPub\Transmitter::createCachedActivityFromItem($item['id']);
			if (empty($data)) {
				throw new HTTPException\NotFoundException();
			}
			if (!empty($this->parameters['activity']) && ($this->parameters['activity'] != 'Create')) {
				$data['type'] = $this->parameters['activity'];
				$data['id'] = str_replace('/Create', '/' . $this->parameters['activity'], $data['id']);
			}
		} else {
			throw new HTTPException\NotFoundException();
		}

		// Relaxed CORS header for public items
		header('Access-Control-Allow-Origin: *');

		System::jsonExit($data, 'application/activity+json');
	}
}
