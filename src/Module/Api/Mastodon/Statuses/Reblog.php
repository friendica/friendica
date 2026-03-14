<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Mastodon\Statuses;

use Friendica\Content\ContactSelector;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Module\BaseApi;
use Friendica\Protocol\Diaspora;

/**
 * @see https://docs.joinmastodon.org/methods/statuses/
 */
class Reblog extends BaseApi
{
	protected function post(array $request = [])
	{
		$this->checkAllowedScope(self::SCOPE_WRITE);
		$uid = self::getCurrentUserID();

		if (empty($this->parameters['id'])) {
			$this->logAndJsonError(422, $this->errorFactory->UnprocessableEntity());
		}

		$item = Post::selectOriginalForUser($uid, ['id', 'uri-id', 'network'], ['uri-id' => $this->parameters['id'], 'uid' => [$uid, 0]]);
		if (!DBA::isResult($item)) {
			$this->logAndJsonError(404, $this->errorFactory->RecordNotFound());
		}

		if ($item['network'] == Protocol::DIASPORA) {
			Diaspora::performReshare($this->parameters['id'], $uid);
		} elseif (!in_array($item['network'], [Protocol::DFRN, Protocol::ACTIVITYPUB, Protocol::BLUESKY, Protocol::TUMBLR, Protocol::TWITTER])) {
			$this->logAndJsonError(
				422,
				$this->errorFactory->UnprocessableEntity($this->t("Posts from %s can't be shared", ContactSelector::networkToName($item['network'])))
			);
		} else {
			Item::performActivity($item['id'], 'announce', $uid);
		}

		// @TODO Remove once mstdnStatus()->createFromUriId is fixed so that it returns posts not reshared posts if given an ID to an original post that has been reshared
		// Introduced in this PR: https://github.com/friendica/friendica/pull/13175
		// Issue tracking the behavior of createFromUriId: https://github.com/friendica/friendica/issues/13350
		$isReblog = $item['uri-id'] != $this->parameters['id'];

		$this->jsonExit(DI::mstdnStatus()->createFromUriId($this->parameters['id'], $uid, $isReblog)->toArray());
	}
}
