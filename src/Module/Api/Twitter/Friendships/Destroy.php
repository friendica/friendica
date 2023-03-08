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

namespace Friendica\Module\Api\Twitter\Friendships;

use Exception;
use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Factory\Api\Twitter\User as TwitterUser;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\Api\Twitter\ContactEndpoint;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Unfollow Contact
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/follow-search-get-users/api-reference/post-friendships-destroy.html
 */
class Destroy extends ContactEndpoint
{
	/** @var TwitterUser */
	private $twitterUser;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, TwitterUser $twitterUser, array $server, array $parameters = [])
	{
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->twitterUser = $twitterUser;
	}

	protected function post(array $request = [])
	{
		BaseApi::checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		$owner = User::getOwnerDataById($uid);
		if (!$owner) {
			Logger::notice(BaseApi::LOG_PREFIX . 'No owner {uid} found', ['module' => 'api', 'action' => 'friendships_destroy', 'uid' => $uid]);
			throw new HTTPException\NotFoundException('Error Processing Request');
		}

		$contact_id = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'screen_name', ''), $this->getRequestValue($request, 'profileurl', ''), $this->getRequestValue($request, 'user_id', 0), 0);

		if (empty($contact_id)) {
			Logger::notice(BaseApi::LOG_PREFIX . 'No user_id specified', ['module' => 'api', 'action' => 'friendships_destroy']);
			throw new HTTPException\BadRequestException('no user_id specified');
		}

		// Get Contact by given id
		$cdata = Contact::getPublicAndUserContactID($contact_id, $uid);
		if (!empty($cdata['user'])) {
			Logger::notice(BaseApi::LOG_PREFIX . 'Not following contact', ['module' => 'api', 'action' => 'friendships_destroy']);
			throw new HTTPException\NotFoundException('Not following Contact');
		}

		$contact = Contact::getById($cdata['user']);
		$user    = $this->twitterUser->createFromContactId($contact_id, $uid, true)->toArray();

		try {
			Contact::unfollow($contact);
		} catch (Exception $e) {
			Logger::error(BaseApi::LOG_PREFIX . $e->getMessage(), ['contact' => $contact]);
			throw new HTTPException\InternalServerErrorException('Unable to unfollow this contact, please contact your administrator');
		}

		$this->response->exit('friendships', ['user' => $user], $this->parameters['extension'] ?? null);
	}
}
