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

namespace Friendica\Module\ActivityPub;

use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Module\Special\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Network;
use Psr\Http\Message\ResponseInterface;

/**
 * ActivityPub Inbox
 */
class Inbox extends BaseApi
{
	public function run(HTTPException $httpException, array $request = [], bool $scopecheck = true): ResponseInterface
	{
		return parent::run($httpException, $request, false);
	}

	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid  = self::getCurrentUserID();
		$page = $request['page'] ?? null;

		if (empty($page) && empty($request['max_id'])) {
			$page = 1;
		}

		if (!empty($this->parameters['nickname'])) {
			$owner = User::getOwnerDataByNick($this->parameters['nickname']);
			if (empty($owner)) {
				throw new \Friendica\Network\HTTPException\NotFoundException();
			}
			if ($owner['uid'] != $uid) {
				throw new \Friendica\Network\HTTPException\ForbiddenException();
			}
			$inbox = ActivityPub\ClientToServer::getInbox($uid, $page, $request['max_id'] ?? null);
		} else {
			$inbox = ActivityPub\ClientToServer::getPublicInbox($uid, $page, $request['max_id'] ?? null);
		}

		System::jsonExit($inbox, 'application/activity+json');
	}

	protected function post(array $request = [])
	{
		$postdata = Network::postdata();

		if (empty($postdata)) {
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		if (DI::config()->get('debug', 'ap_inbox_log')) {
			if (HTTPSignature::getSigner($postdata, $_SERVER)) {
				$filename = 'signed-activitypub';
			} else {
				$filename = 'failed-activitypub';
			}
			$tempfile = tempnam(System::getTempPath(), $filename);
			file_put_contents($tempfile, json_encode(['parameters' => $this->parameters, 'header' => $_SERVER, 'body' => $postdata], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
			Logger::notice('Incoming message stored', ['file' => $tempfile]);
		}

		if (!empty($this->parameters['nickname'])) {
			$user = DBA::selectFirst('user', ['uid'], ['nickname' => $this->parameters['nickname']]);
			if (!DBA::isResult($user)) {
				throw new \Friendica\Network\HTTPException\NotFoundException();
			}
			$uid = $user['uid'];
		} else {
			$uid = 0;
		}

		ActivityPub\Receiver::processInbox($postdata, $_SERVER, $uid);

		throw new \Friendica\Network\HTTPException\AcceptedException();
	}
}
