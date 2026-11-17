<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Statuses;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Post;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Post\Deletion;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Destroys a specific status.
 *
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/post-statuses-destroy-id
 */
class Destroy extends BaseApi
{
	public function __construct(private readonly Deletion $deletion, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$id = $this->getRequestValue($request, 'id', 0);
		$id = $this->getRequestValue($this->parameters, 'id', $id);
		if (empty($id)) {
			throw new BadRequestException('An id is missing.');
		}

		$post = Post::selectFirst(['id'], ['uri-id' => $request['id'], 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (empty($post['id'])) {
			throw new BadRequestException('Item id not found');
		}

		$this->logger->notice('API: api_statuses_destroy: ' . $id);

		$include_entities = $this->getRequestValue($request, 'include_entities', false);

		$ret = DI::twitterStatus()->createFromUriId($id, $uid, $include_entities)->toArray();

		$this->deletion->deleteForUser(['id' => $post['id']], $uid);

		$this->response->addFormattedContent('status', ['status' => $ret], $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
	}
}
