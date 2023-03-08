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

namespace Friendica\Module\Api\Twitter\DirectMessages;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Model\Contact;
use Friendica\Model\Mail;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Sends a new direct message.
 *
 * @see https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/new-message
 */
class NewDM extends BaseApi
{
	/** @var Database */
	private $dba;

	/** @var DirectMessage */
	private $directMessage;

	public function __construct(DirectMessage $directMessage, Database $dba, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba           = $dba;
		$this->directMessage = $directMessage;
	}

	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		if (empty($request['text']) || empty($request['screen_name']) && empty($request['user_id'])) {
			return;
		}

		$cid = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'screen_name', ''), $this->getRequestValue($request, 'profileurl', ''), $this->getRequestValue($request, 'user_id', 0), 0);
		if (empty($cid)) {
			throw new NotFoundException('Recipient not found');
		}

		$replyto = '';
		if (!empty($request['replyto'])) {
			$mail    = $this->dba->selectFirst('mail', ['parent-uri', 'title'], ['uid' => $uid, 'id' => $request['replyto']]);
			$replyto = $mail['parent-uri'];
			$sub     = $mail['title'];
		} else {
			if (!empty($request['title'])) {
				$sub = $request['title'];
			} else {
				$sub = ((strlen($request['text']) > 10) ? substr($request['text'], 0, 10) . '...' : $request['text']);
			}
		}

		$cdata = Contact::getPublicAndUserContactID($cid, $uid);

		$id = Mail::send($cdata['user'], $request['text'], $sub, $replyto);

		if ($id > -1) {
			$ret = $this->directMessage->createFromMailId($id, $uid, $this->getRequestValue($request, 'getText', ''));
		} else {
			$ret = ['error' => $id];
		}

		$this->response->exit('direct-messages', ['direct_message' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
