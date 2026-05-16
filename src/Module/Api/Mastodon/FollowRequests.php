<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon;

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
		$this->checkAllowedScope(self::SCOPE_FOLLOW);
		$uid = self::getCurrentUserID();

		$ucid = Contact::getUserContactId($this->parameters['id'], $uid);
		if (!$ucid) {
			throw new HTTPException\NotFoundException('Contact not found');
		}

		$introduction = DI::intro()->selectForContact($ucid);

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

		$this->jsonExit($relationship);
	}

	/**
	 * @throws HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @see https://docs.joinmastodon.org/methods/accounts/follow_requests/
	 */
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_READ);
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
			} catch (
				HTTPException\InternalServerErrorException|HTTPException\NotFoundException|\ImagickException
			) {
				DI::intro()->delete($introduction);
				unset($introductions[$key]);
			}
		}

		self::setLinkHeader();
		$this->jsonExit($return);
	}
}
