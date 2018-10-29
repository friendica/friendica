<?php
/**
 * @file src/Model/Conversation
 */

namespace Friendica\Model;

use Friendica\App;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\Util\DateTimeFormat;

require_once "include/dba.php";

class Conversation
{
	/*
	 * These constants represent the parcel format used to transport a conversation independently of the message protocol.
	 * It currently is stored in the "protocol" field for legacy reasons.
	 */
	const PARCEL_ACTIVITYPUB        = 0;
	const PARCEL_DFRN               = 1;
	const PARCEL_DIASPORA           = 2;
	const PARCEL_SALMON             = 3;
	const PARCEL_FEED               = 4; // Deprecated
	const PARCEL_SPLIT_CONVERSATION = 6;
	const PARCEL_TWITTER            = 67;
	const PARCEL_UNKNOWN            = 255;

	public static function getByItemUri($item_uri)
	{
		return DBA::selectFirst('conversation', [], ['item-uri' => $item_uri]);
	}

	/**
	 * @brief Store the conversation data
	 *
	 * @param array $arr Item array with conversation data
	 * @return array Item array with removed conversation data
	 */
	public static function insert(array $arr)
	{
		if (in_array(defaults($arr, 'network', Protocol::PHANTOM),
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

			if (isset($arr['source'])) {
				$conversation['source'] = $arr['source'];
			}

			$fields = ['item-uri', 'reply-to-uri', 'conversation-uri', 'conversation-href', 'protocol', 'source'];
			$old_conv = DBA::selectFirst('conversation', $fields, ['item-uri' => $conversation['item-uri']]);
			if (DBA::isResult($old_conv)) {
				// Don't update when only the source has changed.
				// Only do this when there had been no source before.
				if ($old_conv['source'] != '') {
					unset($old_conv['source']);
				}
				// Update structure data all the time but the source only when its from a better protocol.
				if (empty($conversation['source']) || (!empty($old_conv['source']) &&
					($old_conv['protocol'] < defaults($conversation, 'protocol', PARCEL_UNKNOWN)))) {
					unset($conversation['protocol']);
					unset($conversation['source']);
				}
				if (!DBA::update('conversation', $conversation, ['item-uri' => $conversation['item-uri']], $old_conv)) {
					App::logger('Conversation: update for ' . $conversation['item-uri'] . ' from ' . $old_conv['protocol'] . ' to ' . $conversation['protocol'] . ' failed',
						LOGGER_DEBUG);
				}
			} else {
				if (!DBA::insert('conversation', $conversation, true)) {
					App::logger('Conversation: insert for ' . $conversation['item-uri'] . ' (protocol ' . $conversation['protocol'] . ') failed',
						LOGGER_DEBUG);
				}
			}
		}

		unset($arr['conversation-uri']);
		unset($arr['conversation-href']);
		unset($arr['protocol']);
		unset($arr['source']);

		return $arr;
	}
}
