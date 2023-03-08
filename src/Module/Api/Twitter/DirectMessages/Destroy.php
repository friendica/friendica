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
use Friendica\Database\DBA;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * delete a direct_message from mail table through api
 *
 * @see   https://developer.twitter.com/en/docs/direct-messages/sending-and-receiving/api-reference/delete-message
 */
class Destroy extends BaseApi
{
	/** @var Database */
	private $dba;

	public function __construct(Database $dba, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba = $dba;
	}
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		$id = $this->getRequestValue($request, 'id', 0);
		$id = $this->getRequestValue($this->parameters, 'id', $id);

		$verbose   = $this->getRequestValue($request, 'friendica_verbose', false);
		$parenturi = $this->getRequestValue($request, 'friendica_parenturi', '');

		// error if no id or parenturi specified (for clients posting parent-uri as well)
		if ($verbose && $id == 0 && $parenturi == "") {
			$answer = ['result' => 'error', 'message' => 'message id or parenturi not specified'];
			$this->response->exit('direct_messages_delete', ['direct_messages_delete' => $answer], $this->parameters['extension'] ?? null);
			return;
		}

		// add parent-uri to sql command if specified by calling app
		$sql_extra = ($parenturi != "" ? " AND `parent-uri` = '" . DBA::escape($parenturi) . "'" : "");

		// error message if specified id is not in database
		if (!$this->dba->exists('mail', ["`uid` = ? AND `id` = ? " . $sql_extra, $uid, $id])) {
			if ($verbose) {
				$answer = ['result' => 'error', 'message' => 'message id not in database'];
				$this->response->exit('direct_messages_delete', ['direct_messages_delete' => $answer], $this->parameters['extension'] ?? null);
				return;
			}
			throw new BadRequestException('message id not in database');
		}

		// delete message
		$result = $this->dba->delete('mail', ["`uid` = ? AND `id` = ? " . $sql_extra, $uid, $id]);

		if ($verbose) {
			if ($result) {
				// return success
				$answer = ['result' => 'ok', 'message' => 'message deleted'];
				$this->response->exit('direct_messages_delete', ['direct_messages_delete' => $answer], $this->parameters['extension'] ?? null);
			} else {
				$answer = ['result' => 'error', 'message' => 'unknown error'];
				$this->response->exit('direct_messages_delete', ['direct_messages_delete' => $answer], $this->parameters['extension'] ?? null);
			}
		}
	}
}
