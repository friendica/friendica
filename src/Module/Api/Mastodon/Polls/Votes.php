<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Polls;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Factory\Api\Mastodon\Error;
use Friendica\Factory\Api\Mastodon\Poll;
use Friendica\Model\Post;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/polls/#vote
 */
class Votes extends BaseApi
{
	public function __construct(
		private readonly Poll $pollFactory,
		Error $errorFactory,
		AppHelper $appHelper,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		ApiResponse $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$request = $this->getRequest([
			'choices' => [], // Array of own votes containing index for each option (starting from 0)
		], $request);

		$question = Post\Question::getById($this->parameters['id'] ?? 0, ['uri-id']);
		if (empty($question)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		try {
			Post\QuestionVote::vote($question['uri-id'], $uid, (array) $request['choices'], $this->l10n);
		} catch (HTTPException\NotFoundException) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		} catch (HTTPException\UnprocessableEntityException $e) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity($e->getMessage()));
		}

		$this->earlyJsonExit($this->pollFactory->createFromId($this->parameters['id'], $uid)->toArray());
	}
}
