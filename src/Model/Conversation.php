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

namespace Friendica\Model;

use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

class Conversation
{
	/*
	 * These constants represent the parcel format used to transport a conversation independently of the message protocol.
	 * It currently is stored in the "protocol" field for legacy reasons.
	 */
	const PARCEL_ACTIVITYPUB        = 0;
	const PARCEL_DFRN               = 1; // Deprecated
	const PARCEL_DIASPORA           = 2;
	const PARCEL_SALMON             = 3;
	const PARCEL_FEED               = 4; // Deprecated
	const PARCEL_SPLIT_CONVERSATION = 6;
	const PARCEL_LEGACY_DFRN        = 7; // @deprecated since version 2021.09
	const PARCEL_DIASPORA_DFRN      = 8;
	const PARCEL_LOCAL_DFRN         = 9;
	const PARCEL_DIRECT             = 10;
	const PARCEL_TWITTER            = 67;
	const PARCEL_UNKNOWN            = 255;

	/**
	 * Unknown message direction
	 */
	const UNKNOWN = 0;
	/**
	 * The message had been pushed to this sytem
	 */
	const PUSH    = 1;
	/**
	 * The message had been fetched by our system
	 */
	const PULL    = 2;
	/**
	 * The message had been pushed to this system via a relay server
	 */
	const RELAY   = 3;

	public static function getByItemUri($item_uri)
	{
		return DBA::selectFirst('conversation', [], ['item-uri' => $item_uri]);
	}

	/**
	 * Store the conversation data
	 *
	 * @param array $arr Item array with conversation data
	 * @return array Item array with removed conversation data
	 * @throws \Exception
	 */
	public static function insert(array $arr)
	{
		if (in_array(($arr['network'] ?? '') ?: Protocol::PHANTOM,
			[Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::TWITTER]) && !empty($arr['uri'])) {
			$conversation = ['item-uri' => $arr['uri'], 'received' => DateTimeFormat::utcNow()];

			if (isset($arr['parent-uri']) && ($arr['parent-uri'] != $arr['uri'])) {
				$conversation['reply-to-uri'] = $arr['parent-uri'];
			}

			if (isset($arr['thr-parent']) && ($arr['thr-parent'] != $arr['uri'])) {
				$conversation['reply-to-uri'] = $arr['thr-parent'];
			}

			if (isset($arr['conversation-uri'])) {
				$conversation['conversation-uri'] = $arr['conversation-uri'];
			}

			if (isset($arr['conversation-href'])) {
				$conversation['conversation-href'] = $arr['conversation-href'];
			}

			if (isset($arr['protocol'])) {
				$conversation['protocol'] = $arr['protocol'];
			}

			if (isset($arr['direction'])) {
				$conversation['direction'] = $arr['direction'];
			}

			if (isset($arr['source'])) {
				$conversation['source'] = $arr['source'];
			}

			if (!DBA::exists('conversation', ['item-uri' => $conversation['item-uri']])) {
				DBA::insert('conversation', $conversation, Database::INSERT_IGNORE);
			}
		}

		unset($arr['conversation-uri']);
		unset($arr['conversation-href']);
		unset($arr['source']);

		return $arr;
	}
}
