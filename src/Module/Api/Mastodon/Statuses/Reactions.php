<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Database\DBA;
use Friendica\Factory\Api\Mastodon\Account;
use Friendica\Factory\Api\Mastodon\Error;
use Friendica\Factory\Api\Mastodon\Status;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Util\EmojiReaction;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Emoji reactions, only Unicode emojis are supported
 *
 * @see https://docs-develop.pleroma.social/backend/development/API/differences_in_mastoapi_responses/#emoji-reactions
 */
class Reactions extends BaseApi
{
	public function __construct(private readonly Account $mstdnAccount, private readonly Status $mstdnStatus, Error $errorFactory, AppHelper $appHelper, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $appHelper, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function rawContent(array $request = [])
	{
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		if (!Post::exists(['uri-id' => $this->parameters['id'], 'uid' => [0, $uid]])) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		$emoji     = rawurldecode($this->parameters['emoji'] ?? '');
		$reactions = [];
		foreach (Post\Counts::getReactions($this->parameters['id'], $uid) as $reaction) {
			if (($emoji !== '') && ($reaction['name'] !== $emoji)) {
				continue;
			}

			$accounts = [];
			foreach ($reaction['account_ids'] as $id) {
				$accounts[] = $this->mstdnAccount->createFromContactId($id, $uid);
			}

			$reactions[] = ['name' => $reaction['name'], 'count' => $reaction['count'], 'me' => $reaction['me'], 'accounts' => $accounts];
		}

		$this->earlyJsonExit($reactions);
	}

	protected function put(array $request = [])
	{
		$this->react(false);
	}

	/**
	 * "react" and "unreact" endpoints of Fedibird, kmyblue and Chuckya
	 */
	protected function post(array $request = [])
	{
		$this->react(($this->parameters['action'] ?? '') === 'unreact');
	}

	protected function delete(array $request = [])
	{
		$this->react(true);
	}

	private function react(bool $undo): void
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		$emoji = rawurldecode($this->parameters['emoji'] ?? '');
		if (empty($this->parameters['id']) || !EmojiReaction::isReaction($emoji)) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$item = Post::selectOriginalForUser($uid, ['id', 'uri-id'], ['uri-id' => $this->parameters['id'], 'uid' => [$uid, 0]]);
		if (!DBA::isResult($item)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		if (!Item::performActivity($item['id'], $undo ? 'unreact' : 'react', $uid, null, null, null, null, $emoji)) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$isReblog = $item['uri-id'] != $this->parameters['id'];
		$this->earlyJsonExit($this->mstdnStatus->createFromUriId($this->parameters['id'], $uid, $isReblog)->toArray());
	}
}
