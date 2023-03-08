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

namespace Friendica\Module\Api\Twitter\Lists;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Factory\Api\Friendica\Group as FriendicaGroup;
use Friendica\Module\BaseApi;
use Friendica\Model\Contact;
use Friendica\Module\Api\ApiResponse;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Returns all groups the user owns.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-ownerships
 */
class Ownership extends BaseApi
{
	/** @var friendicaGroup */
	private $friendicaGroup;

	/** @var Database */
	private $dba;

	public function __construct(Database $dba, FriendicaGroup $friendicaGroup, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba            = $dba;
		$this->friendicaGroup = $friendicaGroup;
	}
	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$groups = $this->dba->select('group', [], ['deleted' => false, 'uid' => $uid, 'cid' => null]);

		// loop through all groups
		$lists = [];
		foreach ($groups as $group) {
			$lists[] = $this->friendicaGroup->createFromId($group['id']);
		}

		$this->response->exit('statuses', ['lists' => ['lists' => $lists]], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
