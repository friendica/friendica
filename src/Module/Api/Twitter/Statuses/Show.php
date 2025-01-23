<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Api\Twitter\Statuses;

use Friendica\Database\DBA;
use Friendica\Module\BaseApi;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException\BadRequestException;

/**
 * Returns a single status.
 *
 * @see https://developer.twitter.com/en/docs/tweets/post-and-engage/api-reference/get-statuses-show-id
 */
class Show extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$this->checkAllowedScope(BaseApi::SCOPE_READ);
		$uid = BaseApi::getCurrentUserID();

		$id = $this->getRequestValue($request, 'id', 0);
		$id = $this->getRequestValue($this->parameters, 'id', $id);
		if (empty($id)) {
			throw new BadRequestException('An id is missing.');
		}

		$this->logger->notice('API: api_statuses_show: ' . $id);

		$conversation = !empty($request['conversation']);

		// try to fetch the item for the local user - or the public item, if there is no local one
		$item = Post::selectFirst(['id'], ['uri-id' => $id, 'uid' => [0, $uid]], ['order' => ['uid' => true]]);
		if (!DBA::isResult($item)) {
			throw new BadRequestException(sprintf("There is no status with the uri-id %d for the given user.", $id));
		}

		$item_id = $item['id'];

		if ($conversation) {
			$condition = ['parent' => $item_id, 'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT]];
			$params    = ['order' => ['uri-id' => true]];
		} else {
			$condition = ['id' => $item_id, 'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT]];
			$params    = [];
		}

		$statuses = Post::selectForUser($uid, [], $condition, $params);

		/// @TODO How about copying this to above methods which don't check $r ?
		if (!DBA::isResult($statuses)) {
			throw new BadRequestException(sprintf("There is no status or conversation with the id %d.", $id));
		}

		$include_entities = $this->getRequestValue($request, 'include_entities', false);

		$ret = [];
		while ($status = DBA::fetch($statuses)) {
			$ret[] = DI::twitterStatus()->createFromUriId($status['uri-id'], $status['uid'], $include_entities)->toArray();
		}
		DBA::close($statuses);

		if ($conversation) {
			$data = ['status' => $ret];
			$this->response->addFormattedContent('statuses', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
		} else {
			$data = ['status' => $ret[0]];
			$this->response->addFormattedContent('status', $data, $this->parameters['extension'] ?? null, Contact::getPublicIdByUserId($uid));
		}
	}
}
