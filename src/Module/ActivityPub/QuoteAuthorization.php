<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\ActivityPub;

use Friendica\BaseModule;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;

/**
 * ActivityPub QuoteAuthorization
 */
class QuoteAuthorization extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['guid'])) {
			throw new HTTPException\BadRequestException();
		}

		if (empty($this->parameters['remote'])) {
			throw new HTTPException\BadRequestException();
		}

		$local = Post::selectFirst(['uri', 'author-link'], ['guid' => $this->parameters['guid'], 'origin' => true, 'private' => [Item::PUBLIC, Item::UNLISTED]]);
		if (!isset($local['uri'])) {
			throw new HTTPException\NotFoundException();
		}

		$remote = Post::selectFirstPost(['uri'], ['guid' => $this->parameters['remote']]);
		if (!isset($remote['uri'])) {
			throw new HTTPException\NotFoundException();
		}

		$data = [
			'@context'          => ActivityPub::CONTEXT,
			'type'              => 'QuoteAuthorization',
			'id'                => $local['uri'] . '/quote_authorization/' . $this->parameters['remote'],
			'attributedTo'      => $local['author-link'],
			'interactingObject' => $remote['uri'],
			'interactionTarget' => $local['uri']
		];

		// Relaxed CORS header for public items
		header('Access-Control-Allow-Origin: *');
		$this->jsonExit($data, 'application/activity+json');
	}
}
