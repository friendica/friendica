<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

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
			'gravity' => [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT], 'network' => [Protocol::DFRN, Protocol::DIASPORA]];
		$item = Post::selectFirst([], $condition);
		if (empty($item)) {
			$condition = ['guid' => $guid, 'network' => [Protocol::DFRN, Protocol::DIASPORA]];
			$item = Post::selectFirst(['author-link'], $condition);
			if (!empty($item["author-link"])) {
				$parts = parse_url($item["author-link"]);
				if (empty($parts["scheme"]) || empty($parts["host"])) {
					throw new HTTPException\InternalServerErrorException();
				}
				$host = $parts["scheme"] . "://" . $parts["host"];

				if (Strings::normaliseLink($host) != Strings::normaliseLink(DI::baseUrl())) {
					$location = $host . "/fetch/" . DI::args()->getArgv()[1] . "/" . urlencode($guid);
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
		System::httpExit(Diaspora::buildMagicEnvelope($xml, $user), Response::TYPE_XML, 'application/magic-envelope+xml');
	}
}
