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

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Emoji
 *
 * @see https://docs.joinmastodon.org/entities/emoji/
 */
class Emoji extends BaseDataTransferObject
{
	//Required attributes
	/** @var string */
	protected $shortcode;
	/** @var string (URL)*/
	protected $static_url;
	/** @var string (URL)*/
	protected $url;
	/**
	 * Unsupported
	 * @var bool
	 */
	protected $visible_in_picker = true;

	// Optional attributes
	/**
	 * Unsupported
	 * @var string
	 */
	//protected $category;

	public function __construct(string $shortcode, string $url)
	{
		$this->shortcode = $shortcode;
		$this->url = $url;
		$this->static_url = $url;
	}

	/**
	 * @param Emoji  $prototype
	 * @param string $shortcode
	 * @param string $url
	 * @return Emoji
	 */
	public static function createFromPrototype(Emoji $prototype, string $shortcode, string $url)
	{
		$emoji = clone $prototype;
		$emoji->shortcode = $shortcode;
		$emoji->url = $url;
		$emoji->static_url = $url;

		return $emoji;
	}
}
