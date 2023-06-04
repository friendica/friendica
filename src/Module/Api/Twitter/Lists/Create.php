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
use Friendica\Factory\Api\Friendica\Circle as FriendicaCircle;
use Friendica\Module\BaseApi;
use Friendica\Model\Contact;
use Friendica\Model\Circle;
use Friendica\Module\Api\ApiResponse;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Update information about a circle.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/post-lists-update
 */
class Create extends BaseApi
{
	/** @var FriendicaCircle */
	private $friendicaCircle;

	/** @var Database */
	private $dba;

	public function __construct(Database $dba, FriendicaCircle $friendicaCircle, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba             = $dba;
		$this->friendicaCircle = $friendicaCircle;
	}

	protected function rawContent(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		// params
		$name = $this->getRequestValue($request, 'name', '');

		if ($name == '') {
			throw new HTTPException\BadRequestException('circle name not specified');
		}

		// error message if specified circle name already exists
		if ($this->dba->exists('group', ['uid' => $uid, 'name' => $name, 'deleted' => false])) {
			throw new HTTPException\BadRequestException('circle name already exists');
		}

		$ret = Circle::create($uid, $name);
		if ($ret) {
			$gid = Circle::getIdByName($uid, $name);
		} else {
			throw new HTTPException\BadRequestException('other API error');
		}

		$grp = $this->friendicaCircle->createFromId($gid);

		$this->response->exit('statuses', ['lists' => ['lists' => $grp]], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
