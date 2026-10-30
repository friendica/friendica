<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Polls;

use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/polls/#vote
 */
class Votes extends BaseApi
{
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
			Post\QuestionVote::vote($question['uri-id'], $uid, (array) $request['choices']);
		} catch (HTTPException\NotFoundException) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		} catch (HTTPException\UnprocessableEntityException $e) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity($e->getMessage()));
		}

		$this->earlyJsonExit(DI::mstdnPoll()->createFromId($this->parameters['id'], $uid)->toArray());
	}
}
