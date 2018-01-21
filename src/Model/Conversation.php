<?php
/**
 * @file src/Model/Conversation
 */
namespace Friendica\Model;

use Friendica\Database\DBM;
use dba;

require_once "include/dba.php";

class Conversation
{
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

			$old_conv = dba::fetch_first("SELECT `item-uri`, `reply-to-uri`, `conversation-uri`, `conversation-href`, `protocol`, `source`
					FROM `conversation` WHERE `item-uri` = ?", $conversation['item-uri']);
			if (DBM::isResult($old_conv)) {
				// Don't update when only the source has changed.
				// Only do this when there had been no source before.
				if ($old_conv['source'] != '') {
					unset($old_conv['source']);
				}
				// Update structure data all the time but the source only when its from a better protocol.
				if (($old_conv['protocol'] < $conversation['protocol']) && ($old_conv['protocol'] != 0)) {
					unset($conversation['protocol']);
					unset($conversation['source']);
				}
				if (!dba::update('conversation', $conversation, ['item-uri' => $conversation['item-uri']], $old_conv)) {
					logger('Conversation: update for '.$conversation['item-uri'].' from '.$conv['protocol'].' to '.$conversation['protocol'].' failed', LOGGER_DEBUG);
				}
			} else {
				if (!dba::insert('conversation', $conversation, true)) {
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
