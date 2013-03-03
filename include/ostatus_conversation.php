<?php
define('OSTATUS_DEFAULT_POLL_INTERVAL', 30); // given in minutes
define('OSTATUS_DEFAULT_POLL_TIMEFRAME', 1440); // given in minutes

function check_conversations() {
        $last = get_config('system','ostatus_last_poll');

        $poll_interval = intval(get_config('system','ostatus_poll_interval'));
        if(! $poll_interval)
                $poll_interval = OSTATUS_DEFAULT_POLL_INTERVAL;

	// Don't poll if the interval is set negative
	if ($poll_interval < 0)
		return;

        $poll_timeframe = intval(get_config('system','ostatus_poll_timeframe'));
        if(! $poll_timeframe)
                $poll_timeframe = OSTATUS_DEFAULT_POLL_TIMEFRAME;

        if($last) {
                $next = $last + ($poll_interval * 60);
                if($next > time()) {
                        logger('complete_conversation: poll interval not reached');
                        return;
                }
        }

        logger('complete_conversation: cron_start');

        $start = date("Y-m-d H:i:s", time() - ($poll_timeframe * 60));
        $conversations = q("SELECT * FROM `term` WHERE `type` = 7 AND `term` > '%s'", 
                                dbesc($start));
        foreach ($conversations AS $conversation) {
                $id = $conversation['oid'];
                $url = $conversation['url'];
                complete_conversation($id, $url);
        }

        logger('complete_conversation: cron_end');

        set_config('system','ostatus_last_poll', time());
}

function complete_conversation($itemid, $conversation_url, $only_add_conversation = false) {
	global $a;

	//logger('complete_conversation: completing conversation url '.$conversation_url.' for id '.$itemid);

	$messages = q("SELECT `uid`, `parent` FROM `item` WHERE `id` = %d LIMIT 1", intval($itemid));
	if (!$messages)
		return;
	$message = $messages[0];

	// Store conversation url if not done before
	$conversation = q("SELECT `url` FROM `term` WHERE `uid` = %d AND `oid` = %d AND `otype` = %d AND `type` = %d",
		intval($message["uid"]), intval($itemid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION));

	if (!$conversation) {
		$r = q("INSERT INTO `term` (`uid`, `oid`, `otype`, `type`, `term`, `url`) VALUES (%d, %d, %d, %d, '%s', '%s')",
			intval($message["uid"]), intval($itemid), intval(TERM_OBJ_POST), intval(TERM_CONVERSATION), dbesc(datetime_convert()), dbesc($conversation_url));
		logger('complete_conversation: Storing conversation url '.$conversation_url.' for id '.$itemid);
	}

	if ($only_add_conversation)
		return;

	// Get the parent
	$parents = q("SELECT `id`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval($message["uid"]), intval($message["parent"]));
	if (!$parents)
		return;
	$parent = $parents[0];

	require_once('include/html2bbcode.php');
	require_once('include/items.php');

	$conv = str_replace("/conversation/", "/api/statusnet/conversation/", $conversation_url).".as";

	logger('complete_conversation: fetching conversation url '.$conv.' for '.$itemid);
	$conv_as = fetch_url($conv);

	if ($conv_as) {
		$conv_as = str_replace(',"statusnet:notice_info":', ',"statusnet_notice_info":', $conv_as);
		$conv_as = json_decode($conv_as);

		$first_id = "";

                if (!is_array($conv_as->items))
                    return;
		$items = array_reverse($conv_as->items);

		foreach ($items as $single_conv) {
			if ($first_id == "") {
				$first_id = $single_conv->id;

				$new_parents = q("SELECT `id`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `uid` = %d AND `uri` = '%s' LIMIT 1",
					intval($message["uid"]), dbesc($first_id));
				if ($new_parents) {
					$parent = $new_parents[0];
					logger('complete_conversation: adopting new parent '.$parent["id"].' for '.$itemid);
				} else {
					$parent["id"] = 0;
					$parent["uri"] = $first_id;
				}
			}

			if (isset($single_conv->context->inReplyTo->id))
				$parent_uri = $single_conv->context->inReplyTo->id;
			else
				$parent_uri = $parent["uri"];

			$message_exists = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `uri` = '%s' LIMIT 1",
							intval($message["uid"]), dbesc($single_conv->id));
			if ($message_exists) {
				if ($parent["id"] != 0) {
					$existing_message = $message_exists[0];
					$r = q("UPDATE `item` SET `parent` = %d, `parent-uri` = '%s', `thr-parent` = '%s' WHERE `id` = %d LIMIT 1",
						intval($parent["id"]),
						dbesc($parent["uri"]),
						dbesc($parent_uri),
						intval($existing_message["id"]));
				}
				continue;
			}

			$arr = array();
			$arr["uri"] = $single_conv->id;
			$arr["plink"] = $single_conv->id;
			$arr["uid"] = $message["uid"];
			$arr["contact-id"] = $parent["contact-id"]; // To-Do
			if ($parent["id"] != 0)
				$arr["parent"] = $parent["id"];
			$arr["parent-uri"] = $parent["uri"];
			$arr["thr-parent"] = $parent_uri;
			$arr["created"] = $single_conv->published;
			$arr["edited"] = $single_conv->published;
			//$arr["owner-name"] = $single_conv->actor->contact->displayName;
			$arr["owner-name"] = $single_conv->actor->contact->preferredUsername;
			$arr["owner-link"] = $single_conv->actor->id;
			$arr["owner-avatar"] = $single_conv->actor->image->url;
			//$arr["author-name"] = $single_conv->actor->contact->displayName;
			$arr["author-name"] = $single_conv->actor->contact->preferredUsername;
			$arr["author-link"] = $single_conv->actor->id;
			$arr["author-avatar"] = $single_conv->actor->image->url;
			$arr["body"] = html2bbcode($single_conv->content);
			$arr["app"] = strip_tags($single_conv->statusnet_notice_info->source);
			if ($arr["app"] == "")
				$arr["app"] = $single_conv->provider->displayName;
			$arr["verb"] = $parent["verb"];
			$arr["visible"] = $parent["visible"];
			$arr["location"] = $single_conv->location->displayName;
			$arr["coord"] = trim($single_conv->location->lat." ".$single_conv->location->lon);

			if ($arr["location"] == "")
				unset($arr["location"]);

			if ($arr["coord"] == "")
				unset($arr["coord"]);

			$newitem = item_store($arr);

			// Add the conversation entry (but don't fetch the whole conversation)
			complete_conversation($newitem, $conversation_url, true);

			// If the newly created item is the top item then change the parent settings of the thread
			if ($newitem AND ($arr["uri"] == $first_id)) {
				logger('complete_conversation: setting new parent to id '.$newitem);
				$new_parents = q("SELECT `id`, `uri`, `contact-id`, `type`, `verb`, `visible` FROM `item` WHERE `uid` = %d AND `id` = %d LIMIT 1",
					intval($message["uid"]), intval($newitem));
				if ($new_parents) {
					$parent = $new_parents[0];
					logger('complete_conversation: done changing parents to parent '.$newitem);
				}

				/*logger('complete_conversation: changing parents to parent '.$newitem.' old parent: '.$parent["id"].' new uri: '.$arr["uri"]);
				$r = q("UPDATE `item` SET `parent` = %d, `parent-uri` = '%s' WHERE `parent` = %d",
					intval($newitem),
					dbesc($arr["uri"]),
					intval($parent["id"]));
				logger('complete_conversation: done changing parents to parent '.$newitem.' '.print_r($r, true));*/
			}
		}
	}
}
?>
