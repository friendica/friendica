<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\DirectMessages;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
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
	public function __construct(private readonly DirectMessage $directMessage, private readonly Database $dba, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_WRITE);
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

		$ucid = Contact::getUserContactId($cid, $uid);

		$id = Mail::send($uid, $ucid, $request['text'], $sub, $replyto);

		if ($id > -1) {
			$ret = $this->directMessage->createFromMailId($id, $uid, $this->getRequestValue($request, 'getText', ''));
		} else {
			$ret = ['error' => $id];
		}

		$this->response->addFormattedContent('direct-messages', ['direct_message' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
