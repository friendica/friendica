<?php
/**
 * @file include/conversation.php
 */
use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\PConfig;
use Friendica\Core\System;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Object\Thread;
use Friendica\Object\Post;

require_once "include/bbcode.php";
require_once "include/acl_selectors.php";

function item_extract_images($body) {

	$saved_image = [];
	$orig_body = $body;
	$new_body = '';

	$cnt = 0;
	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	while (($img_st_close !== false) && ($img_end !== false)) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$img_end += $img_start;

		if (! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
			// This is an embedded image

			$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[!#saved_image' . $cnt . '#!]';

			$cnt++;
		} else {
			$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));
		}

		$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

		if ($orig_body === false) {
			// in case the body ends on a closing image tag
			$orig_body = '';
		}

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return ['body' => $new_body, 'images' => $saved_image];
}

function item_redir_and_replace_images($body, $images, $cid) {

	$origbody = $body;
	$newbody = '';

	$cnt = 1;
	$pos = get_bb_tag_pos($origbody, 'url', 1);
	while ($pos !== false && $cnt < 1000) {

		$search = '/\[url\=(.*?)\]\[!#saved_image([0-9]*)#!\]\[\/url\]' . '/is';
		$replace = '[url=' . System::baseUrl() . '/redir/' . $cid
				   . '?f=1&url=' . '$1' . '][!#saved_image' . '$2' .'#!][/url]';

		$newbody .= substr($origbody, 0, $pos['start']['open']);
		$subject = substr($origbody, $pos['start']['open'], $pos['end']['close'] - $pos['start']['open']);
		$origbody = substr($origbody, $pos['end']['close']);
		if ($origbody === false) {
			$origbody = '';
		}

		$subject = preg_replace($search, $replace, $subject);
		$newbody .= $subject;

		$cnt++;
		$pos = get_bb_tag_pos($origbody, 'url', 1);
	}
	$newbody .= $origbody;

	$cnt = 0;
	foreach ($images as $image) {
		/*
		 * We're depending on the property of 'foreach' (specified on the PHP website) that
		 * it loops over the array starting from the first element and going sequentially
		 * to the last element.
		 */
		$newbody = str_replace('[!#saved_image' . $cnt . '#!]', '[img]' . $image . '[/img]', $newbody);
		$cnt++;
	}
	return $newbody;
}

/**
 * Render actions localized
 */
function localize_item(&$item) {

	$extracted = item_extract_images($item['body']);
	if ($extracted['images']) {
		$item['body'] = item_redir_and_replace_images($extracted['body'], $extracted['images'], $item['contact-id']);
	}

	/// @Separted ???
	$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";
	if (activity_match($item['verb'], ACTIVITY_LIKE)
		|| activity_match($item['verb'], ACTIVITY_DISLIKE)
		|| activity_match($item['verb'], ACTIVITY_ATTEND)
		|| activity_match($item['verb'], ACTIVITY_ATTENDNO)
		|| activity_match($item['verb'], ACTIVITY_ATTENDMAYBE)) {

		/// @TODO may hurt performance
		$r = q("SELECT * FROM `item`, `contact`
			WHERE `item`.`contact-id`=`contact`.`id`
			AND `item`.`uri`='%s'",
			dbesc($item['parent-uri']));
		if (!DBM::is_result($r)) {
			return;
		}
		$obj = $r[0];

		$author  = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
		$objauthor =  '[url=' . $obj['author-link'] . ']' . $obj['author-name'] . '[/url]';

		switch ($obj['verb']) {
			case ACTIVITY_POST:
				switch ($obj['object-type']) {
					case ACTIVITY_OBJ_EVENT:
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if ($obj['resource-id']) {
					$post_type = t('photo');
					$m = [];
					preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}

		$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

		if (activity_match($item['verb'], ACTIVITY_LIKE)) {
			$bodyverb = t('%1$s likes %2$s\'s %3$s');
		}
		elseif (activity_match($item['verb'], ACTIVITY_DISLIKE)) {
			$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');
		}
		elseif (activity_match($item['verb'], ACTIVITY_ATTEND)) {
			$bodyverb = t('%1$s attends %2$s\'s %3$s');
		}
		elseif (activity_match($item['verb'], ACTIVITY_ATTENDNO)) {
			$bodyverb = t('%1$s doesn\'t attend %2$s\'s %3$s');
		}
		elseif (activity_match($item['verb'], ACTIVITY_ATTENDMAYBE)) {
			$bodyverb = t('%1$s attends maybe %2$s\'s %3$s');
		}
		$item['body'] = sprintf($bodyverb, $author, $objauthor, $plink);

	}
	if (activity_match($item['verb'], ACTIVITY_FRIEND)) {

		if ($item['object-type']=="" || $item['object-type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		$links = parse_xml_string($xmlhead."<links>".unxmlify($obj->link)."</links>");

		$Bname = $obj->title;
		$Blink = ""; $Bphoto = "";
		foreach ($links->link as $l) {
			$atts = $l->attributes();
			switch ($atts['rel']) {
				case "alternate": $Blink = $atts['href'];
				case "photo": $Bphoto = $atts['href'];
			}
		}

		$A = '[url=' . Profile::zrl($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . Profile::zrl($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto != "") {
			$Bphoto = '[url=' . Profile::zrl($Blink) . '][img]' . $Bphoto . '[/img][/url]';
		}

		$item['body'] = sprintf( t('%1$s is now friends with %2$s'), $A, $B)."\n\n\n".$Bphoto;

	}
	if (stristr($item['verb'], ACTIVITY_POKE)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if (! $verb) {
			return;
		}
		if ($item['object-type']=="" || $item['object-type']!== ACTIVITY_OBJ_PERSON) {
			return;
		}

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		$links = parse_xml_string($xmlhead."<links>".unxmlify($obj->link)."</links>");

		$Bname = $obj->title;
		$Blink = "";
		$Bphoto = "";
		foreach ($links->link as $l) {
			$atts = $l->attributes();
			switch ($atts['rel']) {
				case "alternate": $Blink = $atts['href'];
				case "photo": $Bphoto = $atts['href'];
			}
		}

		$A = '[url=' . Profile::zrl($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . Profile::zrl($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto != "") {
			$Bphoto = '[url=' . Profile::zrl($Blink) . '][img=80x80]' . $Bphoto . '[/img][/url]';
		}

		/*
		 * we can't have a translation string with three positions but no distinguishable text
		 * So here is the translate string.
		 */
		$txt = t('%1$s poked %2$s');

		// now translate the verb
		$poked_t = trim(sprintf($txt, "", ""));
		$txt = str_replace( $poked_t, t($verb), $txt);

		// then do the sprintf on the translation string

		$item['body'] = sprintf($txt, $A, $B). "\n\n\n" . $Bphoto;

	}

	if (activity_match($item['verb'], ACTIVITY_TAG)) {
		/// @TODO may hurt performance "joining" two tables + asterisk
		$r = q("SELECT * FROM `item`, `contact`
			WHERE `item`.`contact-id`=`contact`.`id`
			AND `item`.`uri`='%s'",
			dbesc($item['parent-uri']));

		if (!DBM::is_result($r)) {
			return;
		}

		$obj = $r[0];

		$author  = '[url=' . Profile::zrl($item['author-link']) . ']' . $item['author-name'] . '[/url]';
		$objauthor =  '[url=' . Profile::zrl($obj['author-link']) . ']' . $obj['author-name'] . '[/url]';

		switch ($obj['verb']) {
			case ACTIVITY_POST:
				switch ($obj['object-type']) {
					case ACTIVITY_OBJ_EVENT:
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if ($obj['resource-id']) {
					$post_type = t('photo');
					$m=[]; preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
				// Let's break everthing ... ;-)
				break;
		}
		$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

		$parsedobj = parse_xml_string($xmlhead.$item['object']);

		$tag = sprintf('#[url=%s]%s[/url]', $parsedobj->id, $parsedobj->content);
		$item['body'] = sprintf( t('%1$s tagged %2$s\'s %3$s with %4$s'), $author, $objauthor, $plink, $tag );

	}
	if (activity_match($item['verb'], ACTIVITY_FAVORITE)) {

		if ($item['object-type'] == "") {
			return;
		}

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		if (strlen($obj->id)) {
			$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($obj->id),
					intval($item['uid'])
			);

			if (DBM::is_result($r) && $r[0]['plink']) {
				$target = $r[0];
				$Bname = $target['author-name'];
				$Blink = $target['author-link'];
				$A = '[url=' . Profile::zrl($Alink) . ']' . $Aname . '[/url]';
				$B = '[url=' . Profile::zrl($Blink) . ']' . $Bname . '[/url]';
				$P = '[url=' . $target['plink'] . ']' . t('post/item') . '[/url]';
				$item['body'] = sprintf( t('%1$s marked %2$s\'s %3$s as favorite'), $A, $B, $P)."\n";
			}
		}
	}
	$matches = null;
	if (preg_match_all('/@\[url=(.*?)\]/is', $item['body'], $matches, PREG_SET_ORDER)) {
		foreach ($matches as $mtch) {
			if (! strpos($mtch[1], 'zrl=')) {
				$item['body'] = str_replace($mtch[0], '@[url=' . Profile::zrl($mtch[1]) . ']', $item['body']);
			}
		}
	}

	// add zrl's to public images
	$photo_pattern = "/\[url=(.*?)\/photos\/(.*?)\/image\/(.*?)\]\[img(.*?)\]h(.*?)\[\/img\]\[\/url\]/is";
	if (preg_match($photo_pattern, $item['body'])) {
		$photo_replace = '[url=' . Profile::zrl('$1' . '/photos/' . '$2' . '/image/' . '$3' ,true) . '][img' . '$4' . ']h' . '$5'  . '[/img][/url]';
		$item['body'] = bb_tag_preg_replace($photo_pattern, $photo_replace, 'url', $item['body']);
	}

	// add sparkle links to appropriate permalinks

	$x = stristr($item['plink'],'/display/');
	if ($x) {
		$sparkle = false;
		$y = best_link_url($item, $sparkle);

		if (strstr($y, '/redir/')) {
			$item['plink'] = $y . '?f=&url=' . $item['plink'];
		}
	}
}

/**
 * Count the total of comments on this item and its desendants
 * @TODO proper type-hint + doc-tag
 */
function count_descendants($item) {
	$total = count($item['children']);

	if ($total > 0) {
		foreach ($item['children'] as $child) {
			if (! visible_activity($child)) {
				$total --;
			}
			$total += count_descendants($child);
		}
	}

	return $total;
}

function visible_activity($item) {

	/*
	 * likes (etc.) can apply to other things besides posts. Check if they are post children,
	 * in which case we handle them specially
	 */
	$hidden_activities = [ACTIVITY_LIKE, ACTIVITY_DISLIKE, ACTIVITY_ATTEND, ACTIVITY_ATTENDNO, ACTIVITY_ATTENDMAYBE];
	foreach ($hidden_activities as $act) {
		if (activity_match($item['verb'], $act)) {
			return false;
		}
	}

	if (activity_match($item['verb'], ACTIVITY_FOLLOW) && $item['object-type'] === ACTIVITY_OBJ_NOTE) {
		if (! (($item['self']) && ($item['uid'] == local_user()))) {
			return false;
		}
	}

	return true;
}

/**
 * @brief SQL query for items
 */
function item_query() {
	return "SELECT " . item_fieldlists() . " FROM `item` " .
		item_joins() . " WHERE " . item_condition();
}

/**
 * @brief List of all data fields that are needed for displaying items
 */
function item_fieldlists() {

/*
These Fields are not added below (yet). They are here to for bug search.
`item`.`type`,
`item`.`extid`,
`item`.`changed`,
`item`.`moderated`,
`item`.`target-type`,
`item`.`target`,
`item`.`resource-id`,
`item`.`tag`,
`item`.`inform`,
`item`.`pubmail`,
`item`.`visible`,
`item`.`spam`,
`item`.`bookmark`,
`item`.`unseen`,
`item`.`deleted`,
`item`.`origin`,
`item`.`forum_mode`,
`item`.`mention`,
`item`.`global`,
`item`.`gcontact-id`,
`item`.`shadow`,
*/

	return "`item`.`author-id`, `item`.`author-link`, `item`.`author-name`, `item`.`author-avatar`,
		`item`.`owner-id`, `item`.`owner-link`, `item`.`owner-name`, `item`.`owner-avatar`,
		`item`.`contact-id`, `item`.`uid`, `item`.`id`, `item`.`parent`,
		`item`.`uri`, `item`.`thr-parent`, `item`.`parent-uri`,
		`item`.`commented`, `item`.`created`, `item`.`edited`, `item`.`received`,
		`item`.`verb`, `item`.`object-type`, `item`.`postopts`, `item`.`plink`,
		`item`.`guid`, `item`.`wall`, `item`.`private`, `item`.`starred`,
		`item`.`title`,	`item`.`body`, `item`.`file`, `item`.`event-id`,
		`item`.`location`, `item`.`coord`, `item`.`app`, `item`.`attach`,
		`item`.`rendered-hash`, `item`.`rendered-html`, `item`.`object`,
		`item`.`allow_cid`, `item`.`allow_gid`, `item`.`deny_cid`, `item`.`deny_gid`,
		`item`.`id` AS `item_id`, `item`.`network` AS `item_network`,

		`author`.`thumb` AS `author-thumb`, `owner`.`thumb` AS `owner-thumb`,

		`contact`.`network`, `contact`.`url`, `contact`.`name`, `contact`.`writable`,
		`contact`.`self`, `contact`.`id` AS `cid`, `contact`.`alias`,

		`event`.`created` AS `event-created`, `event`.`edited` AS `event-edited`,
		`event`.`start` AS `event-start`,`event`.`finish` AS `event-finish`,
		`event`.`summary` AS `event-summary`,`event`.`desc` AS `event-desc`,
		`event`.`location` AS `event-location`, `event`.`type` AS `event-type`,
		`event`.`nofinish` AS `event-nofinish`,`event`.`adjust` AS `event-adjust`,
		`event`.`ignore` AS `event-ignore`, `event`.`id` AS `event-id`";
}

/**
 * @brief SQL join for contacts that are needed for displaying items
 */
function item_joins() {
	return "STRAIGHT_JOIN `contact` ON `contact`.`id` = `item`.`contact-id` AND
		(NOT `contact`.`blocked` OR `contact`.`pending`)
		LEFT JOIN `contact` AS `author` ON `author`.`id`=`item`.`author-id`
		LEFT JOIN `contact` AS `owner` ON `owner`.`id`=`item`.`owner-id`
		LEFT JOIN `event` ON `event-id` = `event`.`id`";
}

/**
 * @brief SQL condition for items that are needed for displaying items
 */
function item_condition() {
	return "`item`.`visible` AND NOT `item`.`deleted` AND NOT `item`.`moderated`";
}

/**
 * "Render" a conversation or list of items for HTML display.
 * There are two major forms of display:
 *      - Sequential or unthreaded ("New Item View" or search results)
 *      - conversation view
 * The $mode parameter decides between the various renderings and also
 * figures out how to determine page owner and other contextual items
 * that are based on unique features of the calling module.
 *
 */
function conversation(App $a, $items, $mode, $update, $preview = false) {
	require_once 'include/bbcode.php';
	require_once 'mod/proxy.php';

	$ssl_state = ((local_user()) ? true : false);

	$profile_owner = 0;
	$live_update_div = '';

	$arr_blocked = null;

	if (local_user()) {
		$str_blocked = PConfig::get(local_user(), 'system', 'blocked');
		if ($str_blocked) {
			$arr_blocked = explode(',', $str_blocked);
			for ($x = 0; $x < count($arr_blocked); $x ++) {
				$arr_blocked[$x] = trim($arr_blocked[$x]);
			}
		}

	}

	$previewing = (($preview) ? ' preview ' : '');

	if ($mode === 'network') {
		$profile_owner = local_user();
		if (!$update) {
			/*
			 * The special div is needed for liveUpdate to kick in for this page.
			 * We only launch liveUpdate if you aren't filtering in some incompatible
			 * way and also you aren't writing a comment (discovered in javascript).
			 */
			$live_update_div = '<div id="live-network"></div>' . "\r\n"
				. "<script> var profile_uid = " . $_SESSION['uid']
				. "; var netargs = '" . substr($a->cmd, 8)
				. '?f='
				. ((x($_GET, 'cid'))    ? '&cid='    . $_GET['cid']    : '')
				. ((x($_GET, 'search')) ? '&search=' . $_GET['search'] : '')
				. ((x($_GET, 'star'))   ? '&star='   . $_GET['star']   : '')
				. ((x($_GET, 'order'))  ? '&order='  . $_GET['order']  : '')
				. ((x($_GET, 'bmark'))  ? '&bmark='  . $_GET['bmark']  : '')
				. ((x($_GET, 'liked'))  ? '&liked='  . $_GET['liked']  : '')
				. ((x($_GET, 'conv'))   ? '&conv='   . $_GET['conv']   : '')
				. ((x($_GET, 'spam'))   ? '&spam='   . $_GET['spam']   : '')
				. ((x($_GET, 'nets'))   ? '&nets='   . $_GET['nets']   : '')
				. ((x($_GET, 'cmin'))   ? '&cmin='   . $_GET['cmin']   : '')
				. ((x($_GET, 'cmax'))   ? '&cmax='   . $_GET['cmax']   : '')
				. ((x($_GET, 'file'))   ? '&file='   . $_GET['file']   : '')

				. "'; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
		}
	} elseif ($mode === 'profile') {
		$profile_owner = $a->profile['profile_uid'];

		if (!$update) {
			$tab = 'posts';
			if (x($_GET, 'tab')) {
				$tab = notags(trim($_GET['tab']));
			}
			if ($tab === 'posts') {
				/*
				 * This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
				 * because browser prefetching might change it on us. We have to deliver it with the page.
				 */

				$live_update_div = '<div id="live-profile"></div>' . "\r\n"
					. "<script> var profile_uid = " . $a->profile['profile_uid']
					. "; var netargs = '?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
			}
		}
	} elseif ($mode === 'notes') {
		$profile_owner = local_user();
		if (!$update) {
			$live_update_div = '<div id="live-notes"></div>' . "\r\n"
				. "<script> var profile_uid = " . local_user()
				. "; var netargs = '/?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
		}
	} elseif ($mode === 'display') {
		$profile_owner = $a->profile['uid'];
		if (!$update) {
			$live_update_div = '<div id="live-display"></div>' . "\r\n"
				. "<script> var profile_uid = " . $_SESSION['uid'] . ";"
				. " var profile_page = 1; </script>";
		}
	} elseif ($mode === 'community') {
		if (!$community_readonly) {
			$items = community_add_items($items);
		}
		$profile_owner = 0;
		if (!$update) {
			$live_update_div = '<div id="live-community"></div>' . "\r\n"
				. "<script> var profile_uid = -1; var netargs = '" . substr($a->cmd, 10)
				."/?f='; var profile_page = " . $a->pager['page'] . "; </script>\r\n";
		}
	} elseif ($mode === 'search') {
		$live_update_div = '<div id="live-search"></div>' . "\r\n";
	}

	$page_dropping = ((local_user() && local_user() == $profile_owner) ? true : false);

	if (!$update) {
		$_SESSION['return_url'] = $a->query_string;
	}

	$cb = ['items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview];
	Addon::callHooks('conversation_start',$cb);

	$items = $cb['items'];

	$conv_responses = [
		'like' => ['title' => t('Likes','title')], 'dislike' => ['title' => t('Dislikes','title')],
		'attendyes' => ['title' => t('Attending','title')], 'attendno' => ['title' => t('Not attending','title')], 'attendmaybe' => ['title' => t('Might attend','title')]
	];

	// array with html for each thread (parent+comments)
	$threads = [];
	$threadsid = -1;

	$page_template = get_markup_template("conversation.tpl");

	if ($items && count($items)) {
		$community_readonly = ($mode === 'community');

		// Currently behind a config value. This allows the commenting and sharing of every public item.
		if (Config::get('system', 'comment_public')) {
			if ($mode === 'community') {
				$community_readonly = false;
				$writable = true;
			} else {
				$writable = ($items[0]['uid'] == 0) && in_array($items[0]['network'], [NETWORK_OSTATUS, NETWORK_DIASPORA, NETWORK_DFRN]);
			}
		} else {
			$writable = false;
		}

		if (!local_user()) {
			$writable = false;
		}

		if (in_array($mode, ['network-new', 'search', 'contact-posts']) || $community_readonly) {

			/*
			 * "New Item View" on network page or search page results
			 * - just loop through the items and format them minimally for display
			 */

			/// @TODO old lost code?
			// $tpl = get_markup_template('search_item.tpl');
			$tpl = 'search_item.tpl';

			foreach ($items as $item) {

				if ($arr_blocked) {
					$blocked = false;
					foreach ($arr_blocked as $b) {
						if ($b && link_compare($item['author-link'], $b)) {
							$blocked = true;
							break;
						}
					}
					if ($blocked) {
						continue;
					}
				}


				$threadsid++;

				$owner_url   = '';
				$owner_name  = '';
				$sparkle     = '';

				// prevent private email from leaking.
				if ($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
					continue;
				}

				$profile_name = ((strlen($item['author-name'])) ? $item['author-name'] : $item['name']);
				if ($item['author-link'] && (! $item['author-name'])) {
					$profile_name = $item['author-link'];
				}

				$tags = [];
				$hashtags = [];
				$mentions = [];

				$searchpath = System::baseUrl()."/search?tag=";

				$taglist = dba::select('term', ['type', 'term', 'url'],
							["`otype` = ? AND `oid` = ? AND `type` IN (?, ?)", TERM_OBJ_POST, $item['id'], TERM_HASHTAG, TERM_MENTION],
							['order' => ['tid']]);

				while ($tag = dba::fetch($taglist)) {
					if ($tag["url"] == "") {
						$tag["url"] = $searchpath . strtolower($tag["term"]);
					}

					$tag["url"] = best_link_url($item, $sp, $tag["url"]);

					if ($tag["type"] == TERM_HASHTAG) {
						$hashtags[] = "#<a href=\"" . $tag["url"] . "\" target=\"_blank\">" . $tag["term"] . "</a>";
						$prefix = "#";
					} elseif ($tag["type"] == TERM_MENTION) {
						$mentions[] = "@<a href=\"" . $tag["url"] . "\" target=\"_blank\">" . $tag["term"] . "</a>";
						$prefix = "@";
					}
					$tags[] = $prefix."<a href=\"" . $tag["url"] . "\" target=\"_blank\">" . $tag["term"] . "</a>";
				}
				dba::close($taglist);

				$sp = false;
				$profile_link = best_link_url($item, $sp);
				if ($profile_link === 'mailbox') {
					$profile_link = '';
				}

				if ($sp) {
					$sparkle = ' sparkle';
				} else {
					$profile_link = Profile::zrl($profile_link);
				}

				if (!x($item, 'author-thumb') || ($item['author-thumb'] == "")) {
					$author_contact = Contact::getDetailsByURL($item['author-link'], $profile_owner);
					if ($author_contact["thumb"]) {
						$item['author-thumb'] = $author_contact["thumb"];
					} else {
						$item['author-thumb'] = $item['author-avatar'];
					}
				}

				if (!isset($item['owner-thumb']) || ($item['owner-thumb'] == "")) {
					$owner_contact = Contact::getDetailsByURL($item['owner-link'], $profile_owner);
					if ($owner_contact["thumb"]) {
						$item['owner-thumb'] = $owner_contact["thumb"];
					} else {
						$item['owner-thumb'] = $item['owner-avatar'];
					}
				}

				$locate = ['location' => $item['location'], 'coord' => $item['coord'], 'html' => ''];
				Addon::callHooks('render_location',$locate);

				$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_dummy($locate));

				localize_item($item);
				if ($mode === 'network-new') {
					$dropping = true;
				} else {
					$dropping = false;
				}

				$drop = [
					'dropping' => $dropping,
					'pagedrop' => $page_dropping,
					'select' => t('Select'),
					'delete' => t('Delete'),
				];

				$star = false;
				$isstarred = "unstarred";

				$lock = false;
				$likebuttons = false;

				$body = prepare_body($item, true, $preview);

				list($categories, $folders) = get_cats_and_terms($item);

				$profile_name_e = $profile_name;
				$item['title_e'] = $item['title'];
				$body_e = $body;
				$tags_e = $tags;
				$hashtags_e = $hashtags;
				$mentions_e = $mentions;
				$location_e = $location;
				$owner_name_e = $owner_name;

				if ($item['item_network'] == "") {
					$item['item_network'] = $item['network'];
				}

				$tmp_item = [
					'template' => $tpl,
					'id' => (($preview) ? 'P0' : $item['item_id']),
					'guid' => (($preview) ? 'Q0' : $item['guid']),
					'network' => $item['item_network'],
					'network_name' => ContactSelector::networkToName($item['item_network'], $profile_link),
					'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
					'profile_url' => $profile_link,
					'item_photo_menu' => item_photo_menu($item),
					'name' => $profile_name_e,
					'sparkle' => $sparkle,
					'lock' => $lock,
					'thumb' => System::removedBaseUrl(proxy_url($item['author-thumb'], false, PROXY_SIZE_THUMB)),
					'title' => $item['title_e'],
					'body' => $body_e,
					'tags' => $tags_e,
					'hashtags' => $hashtags_e,
					'mentions' => $mentions_e,
					'txt_cats' => t('Categories:'),
					'txt_folders' => t('Filed under:'),
					'has_cats' => ((count($categories)) ? 'true' : ''),
					'has_folders' => ((count($folders)) ? 'true' : ''),
					'categories' => $categories,
					'folders' => $folders,
					'text' => strip_tags($body_e),
					'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
					'ago' => (($item['app']) ? sprintf( t('%s from %s'),relative_date($item['created']),$item['app']) : relative_date($item['created'])),
					'location' => $location_e,
					'indent' => '',
					'owner_name' => $owner_name_e,
					'owner_url' => $owner_url,
					'owner_photo' => System::removedBaseUrl(proxy_url($item['owner-thumb'], false, PROXY_SIZE_THUMB)),
					'plink' => get_plink($item),
					'edpost' => false,
					'isstarred' => $isstarred,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					'conv' => (($preview) ? '' : ['href'=> 'display/'.$item['guid'], 'title'=> t('View in context')]),
					'previewing' => $previewing,
					'wait' => t('Please wait'),
					'thread_level' => 1,
				];

				$arr = ['item' => $item, 'output' => $tmp_item];
				Addon::callHooks('display_item', $arr);

				$threads[$threadsid]['id'] = $item['item_id'];
				$threads[$threadsid]['network'] = $item['item_network'];
				$threads[$threadsid]['items'] = [$arr['output']];

			}
		} else {
			// Normal View
			$page_template = get_markup_template("threaded_conversation.tpl");

			$conv = new Thread($mode, $preview, $writable);

			/*
			 * get all the topmost parents
			 * this shouldn't be needed, as we should have only them in our array
			 * But for now, this array respects the old style, just in case
			 */
			foreach ($items as $item) {
				if ($arr_blocked) {
					$blocked = false;
					foreach ($arr_blocked as $b) {
						if ($b && link_compare($item['author-link'], $b)) {
							$blocked = true;
							break;
						}
					}
					if ($blocked) {
						continue;
					}
				}

				// Can we put this after the visibility check?
				builtin_activity_puller($item, $conv_responses);

				// Only add what is visible
				if ($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
					continue;
				}

				if (! visible_activity($item)) {
					continue;
				}

				Addon::callHooks('display_item', $arr);

				$item['pagedrop'] = $page_dropping;

				if ($item['id'] == $item['parent']) {
					$item_object = new Post($item);
					$conv->addParent($item_object);
				}
			}

			$threads = $conv->getTemplateData($conv_responses);
			if (!$threads) {
				logger('[ERROR] conversation : Failed to get template data.', LOGGER_DEBUG);
				$threads = [];
			}
		}
	}

	$o = replace_macros($page_template, [
		'$baseurl' => System::baseUrl($ssl_state),
		'$return_path' => $a->query_string,
		'$live_update' => $live_update_div,
		'$remove' => t('remove'),
		'$mode' => $mode,
		'$user' => $a->user,
		'$threads' => $threads,
		'$dropping' => ($page_dropping && Feature::isEnabled(local_user(), 'multi_delete') ? t('Delete Selected Items') : False),
	]);

	return $o;
}

/**
 * @brief Add comments to top level entries that had been fetched before
 *
 * The system will fetch the comments for the local user whenever possible.
 * This behaviour is currently needed to allow commenting on Friendica posts.
 *
 * @param array $parents Parent items
 *
 * @return array items with parents and comments
 */
function community_add_items($parents) {
	$max_comments = Config::get("system", "max_comments", 100);

	$items = [];

	foreach ($parents AS $parent) {
		$thread_items = dba::p(item_query()." AND `item`.`uid` = ?
			AND `item`.`parent-uri` = ?
			ORDER BY `item`.`commented` DESC LIMIT ".intval($max_comments + 1),
			local_user(),
			$parent['uri']
		);
		$comments = dba::inArray($thread_items);

		// Check if the original item is in the result.
		// When commenting from the community page there can be incomplete threads
		if (count($comments) > 0) {
			$parent_found = false;
			foreach ($comments as $comment) {
				if ($comment['uri'] == $comment['parent-uri']) {
					$parent_found = true;
					break;
				}
			}
			if (!$parent_found) {
				$comments = [];
			}
		}

		if (count($comments) == 0) {
			$thread_items = dba::p(item_query()." AND `item`.`uid` = 0
				AND `item`.`parent-uri` = ?
				ORDER BY `item`.`commented` DESC LIMIT ".intval($max_comments + 1),
				$parent['uri']
			);
			$comments = dba::inArray($thread_items);
		}

		if (count($comments) != 0) {
			$items = array_merge($items, $comments);
		}
	}

	foreach ($items as $index => $item) {
		if ($item['uid'] == 0) {
			$items[$index]['writable'] = in_array($item['network'], [NETWORK_OSTATUS, NETWORK_DIASPORA, NETWORK_DFRN]);
		}
	}

	$items = conv_sort($items, "`commented`");

	return $items;
}

function best_link_url($item, &$sparkle, $url = '') {

	$best_url = '';
	$sparkle  = false;

	$clean_url = normalise_link($item['author-link']);

	if (local_user()) {
		$condition = [
			'network' => NETWORK_DFRN,
			'uid' => local_user(),
			'nurl' => normalise_link($clean_url),
			'pending' => false
		];
		$contact = dba::selectFirst('contact', ['id'], $condition);
		if (DBM::is_result($contact)) {
			$best_url = 'redir/' . $contact['id'];
			$sparkle = true;
			if ($url != '') {
				$hostname = get_app()->get_hostname();
				if (!strstr($url, $hostname)) {
					$best_url .= "?url=".$url;
				} else {
					$best_url = $url;
				}
			}
		}
	}
	if (! $best_url) {
		if ($url != '') {
			$best_url = $url;
		} elseif (strlen($item['author-link'])) {
			$best_url = $item['author-link'];
		} else {
			$best_url = $item['url'];
		}
	}

	return $best_url;
}


function item_photo_menu($item) {
	$sub_link = '';
	$poke_link = '';
	$contact_url = '';
	$pm_url = '';
	$status_link = '';
	$photos_link = '';
	$posts_link = '';

	if ((local_user()) && local_user() == $item['uid'] && $item['parent'] == $item['id'] && (! $item['self'])) {
		$sub_link = 'javascript:dosubthread(' . $item['id'] . '); return false;';
	}

	$sparkle = false;
	$profile_link = best_link_url($item, $sparkle);
	if ($profile_link === 'mailbox') {
		$profile_link = '';
	}

	$cid = 0;
	$network = '';
	$rel = 0;
	$condition = ['uid' => local_user(), 'nurl' => normalise_link($item['author-link'])];
	$contact = dba::selectFirst('contact', ['id', 'network', 'rel'], $condition);
	if (DBM::is_result($contact)) {
		$cid = $contact['id'];
		$network = $contact['network'];
		$rel = $contact['rel'];
	}

	if ($sparkle) {
		$status_link = $profile_link . '?url=status';
		$photos_link = $profile_link . '?url=photos';
		$profile_link = $profile_link . '?url=profile';
	} else {
		$profile_link = Profile::zrl($profile_link);
	}

	if ($cid && !$item['self']) {
		$poke_link = 'poke/?f=&c=' . $cid;
		$contact_url = 'contacts/' . $cid;
		$posts_link = 'contacts/' . $cid . '/posts';

		if (in_array($network, [NETWORK_DFRN, NETWORK_DIASPORA])) {
			$pm_url = 'message/new/' . $cid;
		}
	}

	if (local_user()) {
		$menu = [
			t('Follow Thread') => $sub_link,
			t('View Status') => $status_link,
			t('View Profile') => $profile_link,
			t('View Photos') => $photos_link,
			t('Network Posts') => $posts_link,
			t('View Contact') => $contact_url,
			t('Send PM') => $pm_url
		];

		if ($network == NETWORK_DFRN) {
			$menu[t("Poke")] = $poke_link;
		}

		if ((($cid == 0) || ($rel == CONTACT_IS_FOLLOWER)) &&
			in_array($item['network'], [NETWORK_DFRN, NETWORK_OSTATUS, NETWORK_DIASPORA])) {
			$menu[t('Connect/Follow')] = 'follow?url=' . urlencode($item['author-link']);
		}
	} else {
		$menu = [t('View Profile') => $item['author-link']];
	}

	$args = ['item' => $item, 'menu' => $menu];

	Addon::callHooks('item_photo_menu', $args);

	$menu = $args['menu'];

	$o = '';
	foreach ($menu as $k => $v) {
		if (strpos($v, 'javascript:') === 0) {
			$v = substr($v, 11);
			$o .= '<li role="menuitem"><a onclick="' . $v . '">' . $k . '</a></li>' . PHP_EOL;
		} elseif ($v!='') {
			$o .= '<li role="menuitem"><a href="' . $v . '">' . $k . '</a></li>' . PHP_EOL;
		}
	}
	return $o;
}

/**
 * @brief Checks item to see if it is one of the builtin activities (like/dislike, event attendance, consensus items, etc.)
 * Increments the count of each matching activity and adds a link to the author as needed.
 *
 * @param array $item
 * @param array &$conv_responses (already created with builtin activity structure)
 * @return void
 */
function builtin_activity_puller($item, &$conv_responses) {
	foreach ($conv_responses as $mode => $v) {
		$url = '';
		$sparkle = '';

		switch ($mode) {
			case 'like':
				$verb = ACTIVITY_LIKE;
				break;
			case 'dislike':
				$verb = ACTIVITY_DISLIKE;
				break;
			case 'attendyes':
				$verb = ACTIVITY_ATTEND;
				break;
			case 'attendno':
				$verb = ACTIVITY_ATTENDNO;
				break;
			case 'attendmaybe':
				$verb = ACTIVITY_ATTENDMAYBE;
				break;
			default:
				return;
		}

		if ((activity_match($item['verb'], $verb)) && ($item['id'] != $item['parent'])) {
			$url = $item['author-link'];
			if ((local_user()) && (local_user() == $item['uid']) && ($item['network'] === NETWORK_DFRN) && (! $item['self']) && (link_compare($item['author-link'], $item['url']))) {
				$url = 'redir/' . $item['contact-id'];
				$sparkle = ' class="sparkle" ';
			} else {
				$url = Profile::zrl($url);
			}

			$url = '<a href="'. $url . '"'. $sparkle .'>' . htmlentities($item['author-name']) . '</a>';

			if (! $item['thr-parent']) {
				$item['thr-parent'] = $item['parent-uri'];
			}

			if (! ((isset($conv_responses[$mode][$item['thr-parent'] . '-l']))
				&& (is_array($conv_responses[$mode][$item['thr-parent'] . '-l'])))) {
				$conv_responses[$mode][$item['thr-parent'] . '-l'] = [];
			}

			// only list each unique author once
			if (in_array($url,$conv_responses[$mode][$item['thr-parent'] . '-l'])) {
				continue;
			}

			if (! isset($conv_responses[$mode][$item['thr-parent']])) {
				$conv_responses[$mode][$item['thr-parent']] = 1;
			} else {
				$conv_responses[$mode][$item['thr-parent']] ++;
			}

			if (public_contact() == $item['author-id']) {
				$conv_responses[$mode][$item['thr-parent'] . '-self'] = 1;
			}

			$conv_responses[$mode][$item['thr-parent'] . '-l'][] = $url;

			// there can only be one activity verb per item so if we found anything, we can stop looking
			return;
		}
	}
}

/**
 * Format the vote text for a profile item
 * @param int $cnt = number of people who vote the item
 * @param array $arr = array of pre-linked names of likers/dislikers
 * @param string $type = one of 'like, 'dislike', 'attendyes', 'attendno', 'attendmaybe'
 * @param int $id  = item id
 * @return formatted text
 */
function format_like($cnt, array $arr, $type, $id) {
	$o = '';
	$expanded = '';

	if ($cnt == 1) {
		$likers = $arr[0];

		// Phrase if there is only one liker. In other cases it will be uses for the expanded
		// list which show all likers
		switch ($type) {
			case 'like' :
				$phrase = sprintf( t('%s likes this.'), $likers);
				break;
			case 'dislike' :
				$phrase = sprintf( t('%s doesn\'t like this.'), $likers);
				break;
			case 'attendyes' :
				$phrase = sprintf( t('%s attends.'), $likers);
				break;
			case 'attendno' :
				$phrase = sprintf( t('%s doesn\'t attend.'), $likers);
				break;
			case 'attendmaybe' :
				$phrase = sprintf( t('%s attends maybe.'), $likers);
				break;
		}
	}

	if ($cnt > 1) {
		$total = count($arr);
		if ($total >= MAX_LIKERS) {
			$arr = array_slice($arr, 0, MAX_LIKERS - 1);
		}
		if ($total < MAX_LIKERS) {
			$last = t('and') . ' ' . $arr[count($arr)-1];
			$arr2 = array_slice($arr, 0, -1);
			$str = implode(', ', $arr2) . ' ' . $last;
		}
		if ($total >= MAX_LIKERS) {
			$str = implode(', ', $arr);
			$str .= sprintf( t(', and %d other people'), $total - MAX_LIKERS );
		}

		$likers = $str;

		$spanatts = "class=\"fakelink\" onclick=\"openClose('{$type}list-$id');\"";

		switch ($type) {
			case 'like':
				$phrase = sprintf( t('<span  %1$s>%2$d people</span> like this'), $spanatts, $cnt);
				$explikers = sprintf( t('%s like this.'), $likers);
				break;
			case 'dislike':
				$phrase = sprintf( t('<span  %1$s>%2$d people</span> don\'t like this'), $spanatts, $cnt);
				$explikers = sprintf( t('%s don\'t like this.'), $likers);
				break;
			case 'attendyes':
				$phrase = sprintf( t('<span  %1$s>%2$d people</span> attend'), $spanatts, $cnt);
				$explikers = sprintf( t('%s attend.'), $likers);
				break;
			case 'attendno':
				$phrase = sprintf( t('<span  %1$s>%2$d people</span> don\'t attend'), $spanatts, $cnt);
				$explikers = sprintf( t('%s don\'t attend.'), $likers);
				break;
			case 'attendmaybe':
				$phrase = sprintf( t('<span  %1$s>%2$d people</span> attend maybe'), $spanatts, $cnt);
				$explikers = sprintf( t('%s anttend maybe.'), $likers);
				break;
		}

		$expanded .= "\t" . '<div class="wall-item-' . $type . '-expanded" id="' . $type . 'list-' . $id . '" style="display: none;" >' . $explikers . EOL . '</div>';
	}

	$phrase .= EOL ;
	$o .= replace_macros(get_markup_template('voting_fakelink.tpl'), [
		'$phrase' => $phrase,
		'$type' => $type,
		'$id' => $id
	]);
	$o .= $expanded;

	return $o;
}

function status_editor(App $a, $x, $notes_cid = 0, $popup = false)
{
	$o = '';

	$geotag = x($x, 'allow_location') ? replace_macros(get_markup_template('jot_geotag.tpl'), []) : '';

	$tpl = get_markup_template('jot-header.tpl');
	$a->page['htmlhead'] .= replace_macros($tpl, [
		'$newpost'   => 'true',
		'$baseurl'   => System::baseUrl(true),
		'$geotag'    => $geotag,
		'$nickname'  => $x['nickname'],
		'$ispublic'  => t('Visible to <strong>everybody</strong>'),
		'$linkurl'   => t('Please enter a link URL:'),
		'$vidurl'    => t("Please enter a video link/URL:"),
		'$audurl'    => t("Please enter an audio link/URL:"),
		'$term'      => t('Tag term:'),
		'$fileas'    => t('Save to Folder:'),
		'$whereareu' => t('Where are you right now?'),
		'$delitems'  => t('Delete item(s)?')
	]);

	$tpl = get_markup_template('jot-end.tpl');
	$a->page['end'] .= replace_macros($tpl, [
		'$newpost'   => 'true',
		'$baseurl'   => System::baseUrl(true),
		'$geotag'    => $geotag,
		'$nickname'  => $x['nickname'],
		'$ispublic'  => t('Visible to <strong>everybody</strong>'),
		'$linkurl'   => t('Please enter a link URL:'),
		'$vidurl'    => t("Please enter a video link/URL:"),
		'$audurl'    => t("Please enter an audio link/URL:"),
		'$term'      => t('Tag term:'),
		'$fileas'    => t('Save to Folder:'),
		'$whereareu' => t('Where are you right now?')
	]);

	$jotplugins = '';
	Addon::callHooks('jot_tool', $jotplugins);

	// Private/public post links for the non-JS ACL form
	$private_post = 1;
	if (x($_REQUEST, 'public')) {
		$private_post = 0;
	}

	$query_str = $a->query_string;
	if (strpos($query_str, 'public=1') !== false) {
		$query_str = str_replace(['?public=1', '&public=1'], ['', ''], $query_str);
	}

	/*
	 * I think $a->query_string may never have ? in it, but I could be wrong
	 * It looks like it's from the index.php?q=[etc] rewrite that the web
	 * server does, which converts any ? to &, e.g. suggest&ignore=61 for suggest?ignore=61
	 */
	if (strpos($query_str, '?') === false) {
		$public_post_link = '?public=1';
	} else {
		$public_post_link = '&public=1';
	}

	// $tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));
	$tpl = get_markup_template("jot.tpl");

	$o .= replace_macros($tpl,[
		'$return_path'  => $query_str,
		'$action'       => 'item',
		'$share'        => defaults($x, 'button', t('Share')),
		'$upload'       => t('Upload photo'),
		'$shortupload'  => t('upload photo'),
		'$attach'       => t('Attach file'),
		'$shortattach'  => t('attach file'),
		'$weblink'      => t('Insert web link'),
		'$shortweblink' => t('web link'),
		'$video'        => t('Insert video link'),
		'$shortvideo'   => t('video link'),
		'$audio'        => t('Insert audio link'),
		'$shortaudio'   => t('audio link'),
		'$setloc'       => t('Set your location'),
		'$shortsetloc'  => t('set location'),
		'$noloc'        => t('Clear browser location'),
		'$shortnoloc'   => t('clear location'),
		'$title'        => defaults($x, 'title', ''),
		'$placeholdertitle' => t('Set title'),
		'$category'     => defaults($x, 'category', ''),
		'$placeholdercategory' => Feature::isEnabled(local_user(), 'categories') ? t('Categories (comma-separated list)') : '',
		'$wait'         => t('Please wait'),
		'$permset'      => t('Permission settings'),
		'$shortpermset' => t('permissions'),
		'$ptyp'         => $notes_cid ? 'note' : 'wall',
		'$content'      => defaults($x, 'content', ''),
		'$post_id'      => defaults($x, 'post_id', ''),
		'$baseurl'      => System::baseUrl(true),
		'$defloc'       => $x['default_location'],
		'$visitor'      => $x['visitor'],
		'$pvisit'       => $notes_cid ? 'none' : $x['visitor'],
		'$public'       => t('Public post'),
		'$lockstate'    => $x['lockstate'],
		'$bang'         => $x['bang'],
		'$profile_uid'  => $x['profile_uid'],
		'$preview'      => Feature::isEnabled($x['profile_uid'], 'preview') ? t('Preview') : '',
		'$jotplugins'   => $jotplugins,
		'$notes_cid'    => $notes_cid,
		'$sourceapp'    => t($a->sourcename),
		'$cancel'       => t('Cancel'),
		'$rand_num'     => random_digits(12),

		// ACL permissions box
		'$acl'           => $x['acl'],
		'$group_perms'   => t('Post to Groups'),
		'$contact_perms' => t('Post to Contacts'),
		'$private'       => t('Private post'),
		'$is_private'    => $private_post,
		'$public_link'   => $public_post_link,

		//jot nav tab (used in some themes)
		'$message' => t('Message'),
		'$browser' => t('Browser'),
	]);


	if ($popup == true) {
		$o = '<div id="jot-popup" style="display: none;">' . $o . '</div>';
	}

	return $o;
}

/**
 * Plucks the children of the given parent from a given item list.
 *
 * @brief Plucks all the children in the given item list of the given parent
 *
 * @param array $item_list
 * @param array $parent
 * @param bool $recursive
 * @return type
 */
function get_item_children(array &$item_list, array $parent, $recursive = true)
{
	$children = [];
	foreach ($item_list as $i => $item) {
		if ($item['id'] != $item['parent']) {
			if ($recursive) {
				// Fallback to parent-uri if thr-parent is not set
				$thr_parent = $item['thr-parent'];
				if ($thr_parent == '') {
					$thr_parent = $item['parent-uri'];
				}

				if ($thr_parent == $parent['uri']) {
					$item['children'] = get_item_children($item_list, $item);
					$children[] = $item;
					unset($item_list[$i]);
				}
			} elseif ($item['parent'] == $parent['id']) {
				$children[] = $item;
				unset($item_list[$i]);
			}
		}
	}
	return $children;
}

/**
 * @brief Recursively sorts a tree-like item array
 *
 * @param array $items
 * @return array
 */
function sort_item_children(array $items)
{
	$result = $items;
	usort($result, 'sort_thr_created_rev');
	foreach ($result as $k => $i) {
		if (isset($result[$k]['children'])) {
			$result[$k]['children'] = sort_item_children($result[$k]['children']);
		}
	}
	return $result;
}

/**
 * @brief Recursively add all children items at the top level of a list
 *
 * @param array $children List of items to append
 * @param array $item_list
 */
function add_children_to_list(array $children, array &$item_list)
{
	foreach ($children as $child) {
		$item_list[] = $child;
		if (isset($child['children'])) {
			add_children_to_list($child['children'], $item_list);
		}
	}
}

/**
 * This recursive function takes the item tree structure created by conv_sort() and
 * flatten the extraneous depth levels when people reply sequentially, removing the
 * stairs effect in threaded conversations limiting the available content width.
 *
 * The basic principle is the following: if a post item has only one reply and is
 * the last reply of its parent, then the reply is moved to the parent.
 *
 * This process is rendered somewhat more complicated because items can be either
 * replies or likes, and these don't factor at all in the reply count/last reply.
 *
 * @brief Selectively flattens a tree-like item structure to prevent threading stairs
 *
 * @param array $parent A tree-like array of items
 * @return array
 */
function smart_flatten_conversation(array $parent)
{
	if (! isset($parent['children']) || count($parent['children']) == 0) {
		return $parent;
	}

	// We use a for loop to ensure we process the newly-moved items
	for ($i = 0; $i < count($parent['children']); $i++) {
		$child = $parent['children'][$i];

		if (isset($child['children']) && count($child['children'])) {
			// This helps counting only the regular posts
			$count_post_closure = function($var) {
				return $var['verb'] === ACTIVITY_POST;
			};

			$child_post_count = count(array_filter($child['children'], $count_post_closure));

			$remaining_post_count = count(array_filter(array_slice($parent['children'], $i), $count_post_closure));

			// If there's only one child's children post and this is the last child post
			if ($child_post_count == 1 && $remaining_post_count == 1) {

				// Searches the post item in the children
				$j = 0;
				while($child['children'][$j]['verb'] !== ACTIVITY_POST && $j < count($child['children'])) {
					$j ++;
				}

				$moved_item = $child['children'][$j];
				unset($parent['children'][$i]['children'][$j]);
				$parent['children'][] = $moved_item;
			} else {
				$parent['children'][$i] = smart_flatten_conversation($child);
			}
		}
	}

	return $parent;
}


/**
 * Expands a flat list of items into corresponding tree-like conversation structures,
 * sort the top-level posts either on "created" or "commented", and finally
 * append all the items at the top level (???)
 *
 * @brief Expands a flat item list into a conversation array for display
 *
 * @param array  $item_list A list of items belonging to one or more conversations
 * @param string $order     Either on "created" or "commented"
 * @return array
 */
function conv_sort(array $item_list, $order)
{
	$parents = [];

	if (!(is_array($item_list) && count($item_list))) {
		return $parents;
	}

	$item_array = [];

	// Dedupes the item list on the uri to prevent infinite loops
	foreach ($item_list as $item) {
		$item_array[$item['uri']] = $item;
	}

	// Extract the top level items
	foreach ($item_array as $item) {
		if ($item['id'] == $item['parent']) {
			$parents[] = $item;
		}
	}

	if (stristr($order, 'created')) {
		usort($parents, 'sort_thr_created');
	} elseif (stristr($order, 'commented')) {
		usort($parents, 'sort_thr_commented');
	}

	/*
	 * Plucks children from the item_array, second pass collects eventual orphan
	 * items and add them as children of their top-level post.
	 */
	foreach ($parents as $i => $parent) {
		$parents[$i]['children'] =
			array_merge(get_item_children($item_array, $parent, true),
				get_item_children($item_array, $parent, false));
	}

	foreach ($parents as $i => $parent) {
		$parents[$i]['children'] = sort_item_children($parents[$i]['children']);
	}

	if (PConfig::get(local_user(), 'system', 'smart_threading', 0)) {
		foreach ($parents as $i => $parent) {
			$parents[$i] = smart_flatten_conversation($parent);
		}
	}

	/// @TODO: Stop recusrsively adding all children back to the top level (!!!)
	/// However, this apparently ensures responses (likes, attendance) display (?!)
	foreach ($parents as $parent) {
		if (count($parent['children'])) {
			add_children_to_list($parent['children'], $parents);
		}
	}

	return $parents;
}

/**
 * @brief usort() callback to sort item arrays by the created key
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function sort_thr_created(array $a, array $b)
{
	return strcmp($b['created'], $a['created']);
}

/**
 * @brief usort() callback to reverse sort item arrays by the created key
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function sort_thr_created_rev(array $a, array $b)
{
	return strcmp($a['created'], $b['created']);
}

/**
 * @brief usort() callback to sort item arrays by the commented key
 *
 * @param array $a
 * @param array $b
 * @return type
 */
function sort_thr_commented(array $a, array $b)
{
	return strcmp($b['commented'], $a['commented']);
}

/// @TODO Add type-hint
function render_location_dummy($item) {
	if ($item['location'] != "") {
		return $item['location'];
	}

	if ($item['coord'] != "") {
		return $item['coord'];
	}
}

/// @TODO Add type-hint
function get_responses($conv_responses, $response_verbs, $ob, $item) {
	$ret = [];
	foreach ($response_verbs as $v) {
		$ret[$v] = [];
		$ret[$v]['count'] = defaults($conv_responses[$v], $item['uri'], '');
		$ret[$v]['list']  = defaults($conv_responses[$v], $item['uri'] . '-l', '');
		$ret[$v]['self']  = defaults($conv_responses[$v], $item['uri'] . '-self', '0');
		if (count($ret[$v]['list']) > MAX_LIKERS) {
			$ret[$v]['list_part'] = array_slice($ret[$v]['list'], 0, MAX_LIKERS);
			array_push($ret[$v]['list_part'], '<a href="#" data-toggle="modal" data-target="#' . $v . 'Modal-'
				. (($ob) ? $ob->getId() : $item['id']) . '"><b>' . t('View all') . '</b></a>');
		} else {
			$ret[$v]['list_part'] = '';
		}
		$ret[$v]['button'] = get_response_button_text($v, $ret[$v]['count']);
		$ret[$v]['title'] = $conv_responses[$v]['title'];
	}

	$count = 0;
	foreach ($ret as $key) {
		if ($key['count'] == true) {
			$count++;
		}
	}
	$ret['count'] = $count;

	return $ret;
}

function get_response_button_text($v, $count)
{
	switch ($v) {
		case 'like':
			$return = tt('Like', 'Likes', $count);
			break;
		case 'dislike':
			$return = tt('Dislike', 'Dislikes', $count);
			break;
		case 'attendyes':
			$return = tt('Attending', 'Attending', $count);
			break;
		case 'attendno':
			$return = tt('Not Attending', 'Not Attending', $count);
			break;
		case 'attendmaybe':
			$return = tt('Undecided', 'Undecided', $count);
			break;
	}

	return $return;
}
