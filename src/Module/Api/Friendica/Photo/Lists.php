<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Photo;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Factory\Api\Friendica\Photo as FriendicaPhoto;
use Friendica\Module\BaseApi;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Module\Api\ApiResponse;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Returns all lists the user subscribes to.
 *
 * @see https://developer.twitter.com/en/docs/accounts-and-users/create-manage-lists/api-reference/get-lists-list
 */
class Lists extends BaseApi
{
	public function __construct(private readonly FriendicaPhoto $friendicaPhoto, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid  = BaseApi::getCurrentUserID();
		$type = $this->getRequestValue($this->parameters, 'extension', 'json');

		$photos = Photo::selectToArray(
			['resource-id'],
			["`uid` = ? AND NOT `photo-type` IN (?, ?)", $uid, Photo::CONTACT_AVATAR, Photo::CONTACT_BANNER],
			['order' => ['id'], 'group_by' => ['resource-id']],
		);

		$data = ['photo' => []];
		if (DBA::isResult($photos)) {
			foreach ($photos as $photo) {
				$element = $this->friendicaPhoto->createFromId($photo['resource-id'], null, $uid, 'json', false);

				$element['thumb'] = end($element['link']);
				unset($element['link']);

				if ($type == 'xml') {
					$thumb = $element['thumb'];
					unset($element['thumb']);
					$data['photo'][] = ['@attributes' => $element, '1' => $thumb];
				} else {
					$data['photo'][] = $element;
				}
			}
		}

		$this->response->addFormattedContent('statuses', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
