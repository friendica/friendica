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

namespace Friendica\Module\Api\Twitter;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Factory\Api\Twitter\DirectMessage;
use Friendica\Model\Contact;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

abstract class DirectMessagesEndpoint extends BaseApi
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

	/**
	 * Handles a direct messages endpoint with the given condition
	 *
	 * @param array $request
	 * @param int   $uid
	 * @param array $condition
	 *
	 * @return void
	 */
	protected function getMessages(array $request, int $uid, array $condition)
	{
		// params
		$count    = $this->getRequestValue($request, 'count', 20, 1, 100);
		$page     = $this->getRequestValue($request, 'page', 1, 1);
		$since_id = $this->getRequestValue($request, 'since_id', 0, 0);
		$max_id   = $this->getRequestValue($request, 'max_id', 0, 0);
		$min_id   = $this->getRequestValue($request, 'min_id', 0, 0);
		$verbose  = $this->getRequestValue($request, 'friendica_verbose', false);

		// pagination
		$start = max(0, ($page - 1) * $count);

		$params = ['order' => ['id' => true], 'limit' => [$start, $count]];

		if (!empty($max_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` < ?", $max_id]);
		}

		if (!empty($since_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $since_id]);
		}

		if (!empty($min_id)) {
			$condition = DBA::mergeConditions($condition, ["`id` > ?", $min_id]);

			$params['order'] = ['id'];
		}

		$cid = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'screen_name', ''), $this->getRequestValue($request, 'profileurl', ''), $this->getRequestValue($request, 'user_id', 0), 0);
		if (!empty($cid)) {
			$cdata = Contact::getPublicAndUserContactID($cid, $uid);
			if (!empty($cdata['user'])) {
				$condition = DBA::mergeConditions($condition, ["`contact-id` = ?", $cdata['user']]);
			}
		}

		$condition = DBA::mergeConditions($condition, ["`uid` = ?", $uid]);

		$mails = $this->dba->selectToArray('mail', ['id'], $condition, $params);
		if ($verbose && !DBA::isResult($mails)) {
			$answer = ['result' => 'error', 'message' => 'no mails available'];
			$this->response->exit('direct-messages', ['direct_message' => $answer], $this->parameters['extension'] ?? null);
			return;
		}

		$ids = array_column($mails, 'id');

		if (!empty($min_id)) {
			$ids = array_reverse($ids);
		}

		$ret = [];
		foreach ($ids as $id) {
			$ret[] = $this->directMessage->createFromMailId($id, $uid, $this->getRequestValue($request, 'getText', ''));
		}

		self::setLinkHeader();

		$this->response->exit('direct-messages', ['direct_message' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
