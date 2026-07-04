<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Diaspora;

use Friendica\BaseModule;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Diaspora;
use Friendica\Util\Strings;

/**
 * This module is part of the Diaspora protocol.
 * It is used for fetching single public posts.
 */
class Fetch extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['guid'])) {
			throw new HTTPException\NotFoundException();
		}

		$guid = $this->parameters['guid'];

		// Fetch the item
		$condition = ['origin' => true, 'private' => [Item::PUBLIC, Item::UNLISTED], 'guid' => $guid,
			'gravity'             => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT], 'network' => [Protocol::DFRN, Protocol::DIASPORA]];
		$item = Post::selectFirst([], $condition);
		if (empty($item)) {
			$condition = ['guid' => $guid, 'network' => [Protocol::DFRN, Protocol::DIASPORA]];
			$item      = Post::selectFirst(['author-link'], $condition);
			if (!empty($item["author-link"])) {
				$parts = parse_url((string) $item["author-link"]);
				if (empty($parts["scheme"]) || empty($parts["host"])) {
					throw new HTTPException\InternalServerErrorException();
				}
				$host = $parts["scheme"] . "://" . $parts["host"];

				if (Strings::normaliseLink($host) != Strings::normaliseLink(DI::baseUrl())) {
					$location = $host . "/fetch/" . DI::args()->getArgv()[1] . "/" . urlencode((string) $guid);
					System::externalRedirect($location, 301);
				}
			}

			throw new HTTPException\NotFoundException();
		}

		// Fetch some data from the author (We could combine both queries - but I think this is more readable)
		$user = User::getOwnerDataById($item["uid"]);
		if (!$user) {
			throw new HTTPException\NotFoundException();
		}

		if ($item['gravity'] == Item::GRAVITY_PARENT) {
			$status = Diaspora::buildStatus($item, $user);
		} else {
			$status = ['type' => 'comment', 'message' => Diaspora::createCommentSignature($item)];
		}

		$xml = Diaspora::buildPostXml($status["type"], $status["message"]);

		// Send the envelope
		$this->httpExit(Diaspora::buildMagicEnvelope($xml, $user), Response::TYPE_XML, 'application/magic-envelope+xml');
	}
}
