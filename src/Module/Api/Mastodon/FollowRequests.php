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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests
 */
class FollowRequests extends BaseApi
{
	/**
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws HTTPException\UnauthorizedException
	 * @throws \ImagickException
	 *
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#accept-follow
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests#reject-follow
	 */
	protected function post(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_FOLLOW);
		$uid = self::getCurrentUserID();

		$cdata = Contact::getPublicAndUserContactID($this->parameters['id'], $uid);
		if (empty($cdata['user'])) {
			throw new HTTPException\NotFoundException('Contact not found');
		}

		$introduction = DI::intro()->selectForContact($cdata['user']);

		$contactId = $introduction->cid;

		switch ($this->parameters['action']) {
			case 'authorize':
				Contact\Introduction::confirm($introduction);
				$relationship = DI::mstdnRelationship()->createFromContactId($contactId, $uid);

				DI::intro()->delete($introduction);
				break;
			case 'ignore':
				$introduction->ignore();
				$relationship = DI::mstdnRelationship()->createFromContactId($contactId, $uid);

				DI::intro()->save($introduction);
				break;
			case 'reject':
				Contact\Introduction::discard($introduction);
				$relationship = DI::mstdnRelationship()->createFromContactId($contactId, $uid);

				DI::intro()->delete($introduction);
				break;
			default:
				throw new HTTPException\BadRequestException('Unexpected action parameter, expecting "authorize", "ignore" or "reject"');
		}

		System::jsonExit($relationship);
	}

	/**
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests/
	 */
	protected function rawContent(array $request = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'min_id' => 0,
			'max_id' => 0,
			'limit'  => 40, // Maximum number of results to return. Defaults to 40. Paginate using the HTTP Link header.
		], $request);

		$introductions = DI::intro()->selectForUser($uid, $request['min_id'], $request['max_id'], $request['limit']);

		$return = [];

		foreach ($introductions as $key => $introduction) {
			try {
				self::setBoundaries($introduction->id);
				$return[] = DI::mstdnAccount()->createFromContactId($introduction->cid, $introduction->uid);
			} catch (HTTPException\InternalServerErrorException
				| HTTPException\NotFoundException
				| \ImagickException $exception) {
				DI::intro()->delete($introduction);
				unset($introductions[$key]);
			}
		}

		self::setLinkHeader();
		System::jsonExit($return);
	}
}
