<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Friendica\Photoalbum;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Friendica\Photo as FriendicaPhoto;
use Friendica\Model\Contact;
use Friendica\Model\Photo;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * api/friendica/photoalbum/:name
 *
 * @package  Friendica\Module\Api\Friendica\Photoalbum
 */
class Show extends BaseApi
{
	public function __construct(private readonly FriendicaPhoto $friendicaPhoto, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid     = BaseApi::getCurrentUserID();
		$type    = $this->getRequestValue($this->parameters, 'extension', 'json');
		$request = $this->getRequest([
			'album'        => '',    // Get pictures in this album
			'offset'       => 0,     // Return results offset by this value
			'limit'        => 50,    // Maximum number of results to return. Defaults to 50. Max 500
			'latest_first' => false, // Whether to reverse the order so newest are first
		], $request);

		if (empty($request['album'])) {
			throw new HTTPException\BadRequestException('No album name specified.');
		}

		$orderDescending = $request['latest_first'];
		$album           = $request['album'];
		$condition       = ["`uid` = ? AND `album` = ?", $uid, $album];
		$params          = ['order' => ['id' => $orderDescending], 'group_by' => ['resource-id']];

		$limit = $request['limit'];
		if ($limit > 500) {
			$limit = 500;
		}

		if ($limit <= 0) {
			$limit = 1;
		}

		if (!empty($request['offset'])) {
			$params['limit'] = [$request['offset'], $limit];
		} else {
			$params['limit'] = $limit;
		}

		$photos = Photo::selectToArray(['resource-id'], $condition, $params);

		$data = ['photo' => []];
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

		$this->response->addFormattedContent('statuses', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
