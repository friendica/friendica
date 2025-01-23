<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Trends;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Module\Conversation\Community;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/trends/#statuses
 */
class Statuses extends BaseApi
{
	/**
	 * @var IManageConfigValues
	 */
	private $config;

	public function __construct(IManageConfigValues $config, \Friendica\Factory\Api\Mastodon\Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
		$this->config = $config;
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		if ($this->config->get('system', 'block_public') || $this->config->get('system', 'community_page_style') == Community::DISABLED_VISITOR) {
			$this->checkAllowedScope(BaseApi::SCOPE_READ);
		}

		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'limit'  => 10, // Maximum number of results to return. Defaults to 10.
			'offset' => 0, // Offset in set, Defaults to 0.
		], $request);

		$condition = ["NOT `private` AND `commented` > ? AND `created` > ?", DateTimeFormat::utc('now -1 day'), DateTimeFormat::utc('now -1 week')];
		$condition = DBA::mergeConditions($condition, ['network' => Protocol::FEDERATED]);

		$display_quotes = self::appSupportsQuotes();

		$trending = [];
		$statuses = Post::selectPostThread(['uri-id'], $condition, ['limit' => [$request['offset'], $request['limit']],  'order' => ['total-actors' => true]]);
		while ($status = Post::fetch($statuses)) {
			try {
				$trending[] = DI::mstdnStatus()->createFromUriId($status['uri-id'], $uid, $display_quotes);
			} catch (\Exception $exception) {
				$this->logger->info('Post not fetchable', ['uri-id' => $status['uri-id'], 'uid' => $uid, 'exception' => $exception]);
			}
		}
		DBA::close($statuses);

		if (!empty($trending)) {
			self::setLinkHeaderByOffsetLimit($request['offset'], $request['limit']);
		}

		$this->jsonExit($trending);
	}
}
