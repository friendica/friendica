<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Friendships;

use Exception;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
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

	public function __construct(\Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, TwitterUser $twitterUser, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->twitterUser = $twitterUser;
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
		$uid = BaseApi::getCurrentUserID();

		$owner = User::getOwnerDataById($uid);
		if (!$owner) {
			$this->logger->notice(BaseApi::LOG_PREFIX . 'No owner {uid} found', ['module' => 'api', 'action' => 'friendships_destroy', 'uid' => $uid]);
			throw new HTTPException\NotFoundException('Error Processing Request');
		}

		$contact_id = BaseApi::getContactIDForSearchterm($this->getRequestValue($request, 'screen_name', ''), $this->getRequestValue($request, 'profileurl', ''), $this->getRequestValue($request, 'user_id', 0), 0);

		if (empty($contact_id)) {
			$this->logger->notice(BaseApi::LOG_PREFIX . 'No user_id specified', ['module' => 'api', 'action' => 'friendships_destroy']);
			throw new HTTPException\BadRequestException('no user_id specified');
		}

		// Get Contact by given id
		$ucid = Contact::getUserContactId($contact_id, $uid);
		if (!$ucid) {
			$this->logger->notice(BaseApi::LOG_PREFIX . 'Not following contact', ['module' => 'api', 'action' => 'friendships_destroy']);
			throw new HTTPException\NotFoundException('Not following Contact');
		}

		$contact = Contact::getById($ucid);
		$user    = $this->twitterUser->createFromContactId($contact_id, $uid, true)->toArray();

		try {
			Contact::unfollow($contact);
		} catch (Exception $e) {
			$this->logger->error(BaseApi::LOG_PREFIX . $e->getMessage(), ['contact' => $contact]);
			throw new HTTPException\InternalServerErrorException('Unable to unfollow this contact, please contact your administrator');
		}

		$this->response->addFormattedContent('friendships', ['user' => $user], $this->parameters['extension'] ?? null);
	}
}
