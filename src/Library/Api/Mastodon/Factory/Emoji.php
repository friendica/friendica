<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Library\Api\Mastodon\Factory;

use Friendica\Library\BaseFactory;
use Friendica\Library\Api\Mastodon\Collection\Emojis;

class Emoji extends BaseFactory
{
	public function create(string $shortcode, string $url): \Friendica\Library\Api\Mastodon\Object\Emoji
	{
		return new \Friendica\Library\Api\Mastodon\Object\Emoji($shortcode, $url);
	}

	/**
	 * @param array $smilies
	 *
	 * @return Emojis
	 */
	public function createCollectionFromSmilies(array $smilies): Emojis
	{
		$prototype = null;

		$emojis = [];

		foreach ($smilies['texts'] as $key => $shortcode) {
			if (preg_match('/src="(.+?)"/', $smilies['icons'][$key], $matches)) {
				$url = $matches[1];

				if ($prototype === null) {
					$prototype = $this->create($shortcode, $url);
					$emojis[]  = $prototype;
				} else {
					$emojis[] = \Friendica\Library\Api\Mastodon\Object\Emoji::createFromPrototype($prototype, $shortcode, $url);
				}
			};
		}

		return new Emojis($emojis);
	}
}
