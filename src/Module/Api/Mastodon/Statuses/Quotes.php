<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Content\Conversation\PostInteractions;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Factory\Api\Mastodon\Error;
use Friendica\Factory\Api\Mastodon\Status;
use Friendica\Model\Post;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/#quotes
 */
class Quotes extends BaseApi
{
	public function __construct(private readonly PostInteractions $postInteractions, private readonly Status $mstdnStatus, Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$request = $this->getRequest([
			'limit'    => 20, // Maximum number of results to return. Defaults to 20, the maximum is 40.
			'max_id'   => 0,  // Return results older than this ID
			'since_id' => 0,  // Return results newer than this ID
			'min_id'   => 0,  // Return results immediately newer than this ID
		], $request);

		if (!$post = Post::selectOriginal(['uri-id'], ['uri-id' => $this->parameters['id'], 'uid' => [0, $uid]])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		// The quotes are selected like in the interaction lists of the web interface, so that visibility and blocked contacts are handled in the same way
		$condition = $this->postInteractions->getQuoteCondition($post['uri-id'], $uid);

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['min_id']]);
		}

		$params = ['order' => ['uri-id' => empty($request['min_id'])], 'limit' => min(max((int) $request['limit'], 1), 40)];

		$statuses = [];
		foreach (array_column(Post::toArray(Post::selectPosts(['uri-id'], $condition, $params)), 'uri-id') as $uriId) {
			self::setBoundaries($uriId);
			try {
				$statuses[] = $this->mstdnStatus->createFromUriId($uriId, $uid);
			} catch (\Exception $exception) {
				$this->logger->info('Post not fetchable', ['uri-id' => $uriId, 'uid' => $uid, 'exception' => $exception]);
			}
		}

		if (!empty($request['min_id'])) {
			$statuses = array_reverse($statuses);
		}

		$this->setPaginationLinkHeader();
		$this->earlyJsonExit($statuses);
	}
}
