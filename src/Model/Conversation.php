<?php
/**
 * @file src/Model/Conversation
 */
namespace Friendica\Model;

use Friendica\Database\DBA;
use Friendica\Database\DBM;

require_once "include/dba.php";

class Conversation
{
	const PROTOCOL_UNKNOWN         = 0;
	const PROTOCOL_DFRN            = 1;
	const PROTOCOL_DIASPORA        = 2;
	const PROTOCOL_OSTATUS_SALMON  = 3;
	const PROTOCOL_OSTATUS_FEED    = 4; // Deprecated
	const PROTOCOL_GS_CONVERSATION = 5; // Deprecated
	const PROTOCOL_SPLITTED_CONV   = 6;

	/**
	 * @brief Store the conversation data
	 *
	 * @param array $arr Item array with conversation data
	 * @return array Item array with removed conversation data
	 */
	public static function insert($arr) {
		if (in_array(defaults($arr, 'network', NETWORK_PHANTOM), [NETWORK_DFRN, NETWORK_DIASPORA, NETWORK_OSTATUS]) && !empty($arr['uri'])) {
			$conversation = ['item-uri' => $arr['uri'], 'received' => DBM::date()];

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
			if (DBM::is_result($old_conv)) {
				// Don't update when only the source has changed.
				// Only do this when there had been no source before.
				if ($old_conv['source'] != '') {
					unset($old_conv['source']);
				}
				// Update structure data all the time but the source only when its from a better protocol.
				if (isset($conversation['protocol']) && isset($conversation['source']) && ($old_conv['protocol'] < $conversation['protocol']) && ($old_conv['protocol'] != 0)) {
					unset($conversation['protocol']);
					unset($conversation['source']);
				}
				if (!DBA::update('conversation', $conversation, ['item-uri' => $conversation['item-uri']], $old_conv)) {
					logger('Conversation: update for '.$conversation['item-uri'].' from '.$old_conv['protocol'].' to '.$conversation['protocol'].' failed', LOGGER_DEBUG);
				}
			} else {
				if (!DBA::insert('conversation', $conversation, true)) {
					logger('Conversation: insert for '.$conversation['item-uri'].' (protocol '.$conversation['protocol'].') failed', LOGGER_DEBUG);
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
