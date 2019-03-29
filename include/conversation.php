<?php
/**
 * @file include/conversation.php
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\PConfig;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Model\Term;
use Friendica\Object\Post;
use Friendica\Object\Thread;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Temporal;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use Friendica\Util\Crypto;

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

		if (!strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
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
	$pos = BBCode::getTagPosition($origbody, 'url', 0);
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
		// Isn't this supposed to use $cnt value for $occurrences? - @MrPetovan
		$pos = BBCode::getTagPosition($origbody, 'url', 0);
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
 *
 * @param $item
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function localize_item(&$item)
{
	$extracted = item_extract_images($item['body']);
	if ($extracted['images']) {
		$item['body'] = item_redir_and_replace_images($extracted['body'], $extracted['images'], $item['contact-id']);
	}

	/*
	heluecht 2018-06-19: from my point of view this whole code part is useless.
	It just renders the body message of technical posts (Like, dislike, ...).
	But: The body isn't visible at all. So we do this stuff just because we can.
	Even if these messages were visible, this would only mean that something went wrong.
	During the further steps of the database restructuring I would like to address this issue.
	*/

	$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";
	if (activity_match($item['verb'], ACTIVITY_LIKE)
		|| activity_match($item['verb'], ACTIVITY_DISLIKE)
		|| activity_match($item['verb'], ACTIVITY_ATTEND)
		|| activity_match($item['verb'], ACTIVITY_ATTENDNO)
		|| activity_match($item['verb'], ACTIVITY_ATTENDMAYBE)) {

		$fields = ['author-link', 'author-name', 'verb', 'object-type', 'resource-id', 'body', 'plink'];
		$obj = Item::selectFirst($fields, ['uri' => $item['parent-uri']]);
		if (!DBA::isResult($obj)) {
			return;
		}

		$author  = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
		$objauthor =  '[url=' . $obj['author-link'] . ']' . $obj['author-name'] . '[/url]';

		switch ($obj['verb']) {
			case ACTIVITY_POST:
				switch ($obj['object-type']) {
					case ACTIVITY_OBJ_EVENT:
						$post_type = L10n::t('event');
						break;
					default:
						$post_type = L10n::t('status');
				}
				break;
			default:
				if ($obj['resource-id']) {
					$post_type = L10n::t('photo');
					$m = [];
					preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = L10n::t('status');
				}
		}

		$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

		$bodyverb = '';
		if (activity_match($item['verb'], ACTIVITY_LIKE)) {
			$bodyverb = L10n::t('%1$s likes %2$s\'s %3$s');
		} elseif (activity_match($item['verb'], ACTIVITY_DISLIKE)) {
			$bodyverb = L10n::t('%1$s doesn\'t like %2$s\'s %3$s');
		} elseif (activity_match($item['verb'], ACTIVITY_ATTEND)) {
			$bodyverb = L10n::t('%1$s attends %2$s\'s %3$s');
		} elseif (activity_match($item['verb'], ACTIVITY_ATTENDNO)) {
			$bodyverb = L10n::t('%1$s doesn\'t attend %2$s\'s %3$s');
		} elseif (activity_match($item['verb'], ACTIVITY_ATTENDMAYBE)) {
			$bodyverb = L10n::t('%1$s attends maybe %2$s\'s %3$s');
		}

		$item['body'] = sprintf($bodyverb, $author, $objauthor, $plink);
	}

	if (activity_match($item['verb'], ACTIVITY_FRIEND)) {

		if ($item['object-type']=="" || $item['object-type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = XML::parseString($xmlhead.$item['object']);
		$links = XML::parseString($xmlhead."<links>".XML::unescape($obj->link)."</links>");

		$Bname = $obj->title;
		$Blink = "";
		$Bphoto = "";
		foreach ($links->link as $l) {
			$atts = $l->attributes();
			switch ($atts['rel']) {
				case "alternate": $Blink = $atts['href']; break;
				case "photo": $Bphoto = $atts['href']; break;
			}
		}

		$A = '[url=' . Contact::magicLink($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . Contact::magicLink($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto != "") {
			$Bphoto = '[url=' . Contact::magicLink($Blink) . '][img]' . $Bphoto . '[/img][/url]';
		}

		$item['body'] = L10n::t('%1$s is now friends with %2$s', $A, $B)."\n\n\n".$Bphoto;

	}
	if (stristr($item['verb'], ACTIVITY_POKE)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if (!$verb) {
			return;
		}
		if ($item['object-type']=="" || $item['object-type']!== ACTIVITY_OBJ_PERSON) {
			return;
		}

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";

		$obj = XML::parseString($xmlhead.$item['object']);

		$Bname = $obj->title;
		$Blink = $obj->id;
		$Bphoto = "";

		foreach ($obj->link as $l) {
			$atts = $l->attributes();
			switch ($atts['rel']) {
				case "alternate": $Blink = $atts['href'];
				case "photo": $Bphoto = $atts['href'];
			}
		}

		$A = '[url=' . Contact::magicLink($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . Contact::magicLink($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto != "") {
			$Bphoto = '[url=' . Contact::magicLink($Blink) . '][img=80x80]' . $Bphoto . '[/img][/url]';
		}

		/*
		 * we can't have a translation string with three positions but no distinguishable text
		 * So here is the translate string.
		 */
		$txt = L10n::t('%1$s poked %2$s');

		// now translate the verb
		$poked_t = trim(sprintf($txt, "", ""));
		$txt = str_replace($poked_t, L10n::t($verb), $txt);

		// then do the sprintf on the translation string

		$item['body'] = sprintf($txt, $A, $B). "\n\n\n" . $Bphoto;

	}

	if (activity_match($item['verb'], ACTIVITY_TAG)) {
		$fields = ['author-id', 'author-link', 'author-name', 'author-network',
			'verb', 'object-type', 'resource-id', 'body', 'plink'];
		$obj = Item::selectFirst($fields, ['uri' => $item['parent-uri']]);
		if (!DBA::isResult($obj)) {
			return;
		}

		$author_arr = ['uid' => 0, 'id' => $item['author-id'],
			'network' => $item['author-network'], 'url' => $item['author-link']];
		$author  = '[url=' . Contact::magicLinkByContact($author_arr) . ']' . $item['author-name'] . '[/url]';

		$author_arr = ['uid' => 0, 'id' => $obj['author-id'],
			'network' => $obj['author-network'], 'url' => $obj['author-link']];
		$objauthor  = '[url=' . Contact::magicLinkByContact($author_arr) . ']' . $obj['author-name'] . '[/url]';

		switch ($obj['verb']) {
			case ACTIVITY_POST:
				switch ($obj['object-type']) {
					case ACTIVITY_OBJ_EVENT:
						$post_type = L10n::t('event');
						break;
					default:
						$post_type = L10n::t('status');
				}
				break;
			default:
				if ($obj['resource-id']) {
					$post_type = L10n::t('photo');
					$m=[]; preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = L10n::t('status');
				}
				// Let's break everthing ... ;-)
				break;
		}
		$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

		$parsedobj = XML::parseString($xmlhead.$item['object']);

		$tag = sprintf('#[url=%s]%s[/url]', $parsedobj->id, $parsedobj->content);
		$item['body'] = L10n::t('%1$s tagged %2$s\'s %3$s with %4$s', $author, $objauthor, $plink, $tag);
	}

	if (activity_match($item['verb'], ACTIVITY_FAVORITE)) {
		if ($item['object-type'] == "") {
			return;
		}

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";

		$obj = XML::parseString($xmlhead.$item['object']);
		if (strlen($obj->id)) {
			$fields = ['author-link', 'author-name', 'plink'];
			$target = Item::selectFirst($fields, ['uri' => $obj->id, 'uid' => $item['uid']]);
			if (DBA::isResult($target) && $target['plink']) {
				$Bname = $target['author-name'];
				$Blink = $target['author-link'];
				$A = '[url=' . Contact::magicLink($Alink) . ']' . $Aname . '[/url]';
				$B = '[url=' . Contact::magicLink($Blink) . ']' . $Bname . '[/url]';
				$P = '[url=' . $target['plink'] . ']' . L10n::t('post/item') . '[/url]';
				$item['body'] = L10n::t('%1$s marked %2$s\'s %3$s as favorite', $A, $B, $P)."\n";
			}
		}
	}
	$matches = null;
	if (preg_match_all('/@\[url=(.*?)\]/is', $item['body'], $matches, PREG_SET_ORDER)) {
		foreach ($matches as $mtch) {
			if (!strpos($mtch[1], 'zrl=')) {
				$item['body'] = str_replace($mtch[0], '@[url=' . Contact::magicLink($mtch[1]) . ']', $item['body']);
			}
		}
	}

	// add zrl's to public images
	$photo_pattern = "/\[url=(.*?)\/photos\/(.*?)\/image\/(.*?)\]\[img(.*?)\]h(.*?)\[\/img\]\[\/url\]/is";
	if (preg_match($photo_pattern, $item['body'])) {
		$photo_replace = '[url=' . Profile::zrl('$1' . '/photos/' . '$2' . '/image/' . '$3' ,true) . '][img' . '$4' . ']h' . '$5'  . '[/img][/url]';
		$item['body'] = BBCode::pregReplaceInTag($photo_pattern, $photo_replace, 'url', $item['body']);
	}

	// add sparkle links to appropriate permalinks
	$author = ['uid' => 0, 'id' => $item['author-id'],
		'network' => $item['author-network'], 'url' => $item['author-link']];

	// Only create a redirection to a magic link when logged in
	if (!empty($item['plink']) && (local_user() || remote_user())) {
		$item['plink'] = Contact::magicLinkByContact($author, $item['plink']);
	}
}

/**
 * Count the total of comments on this item and its desendants
 * @TODO proper type-hint + doc-tag
 * @param $item
 * @return int
 */
function count_descendants($item) {
	$total = count($item['children']);

	if ($total > 0) {
		foreach ($item['children'] as $child) {
			if (!visible_activity($child)) {
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
	$hidden_activities = [ACTIVITY_LIKE, ACTIVITY_DISLIKE, ACTIVITY_ATTEND, ACTIVITY_ATTENDNO, ACTIVITY_ATTENDMAYBE, ACTIVITY_FOLLOW, ACTIVITY2_ANNOUNCE];
	foreach ($hidden_activities as $act) {
		if (activity_match($item['verb'], $act)) {
			return false;
		}
	}

	// @TODO below if() block can be rewritten to a single line: $isVisible = allConditionsHere;
	if (activity_match($item['verb'], ACTIVITY_FOLLOW) && $item['object-type'] === ACTIVITY_OBJ_NOTE && empty($item['self']) && $item['uid'] == local_user()) {
		return false;
	}

	return true;
}

function conv_get_blocklist()
{
	if (!local_user()) {
		return [];
	}

	$str_blocked = PConfig::get(local_user(), 'system', 'blocked');
	if (empty($str_blocked)) {
		return [];
	}

	$blocklist = [];

	foreach (explode(',', $str_blocked) as $entry) {
		// The 4th parameter guarantees that there always will be a public contact entry
		$cid = Contact::getIdForURL(trim($entry), 0, true, ['url' => trim($entry)]);
		if (!empty($cid)) {
			$blocklist[] = $cid;
		}
	}

	return $blocklist;
}

/**
 * "Render" a conversation or list of items for HTML display.
 * There are two major forms of display:
 *      - Sequential or unthreaded ("New Item View" or search results)
 *      - conversation view
 * The $mode parameter decides between the various renderings and also
 * figures out how to determine page owner and other contextual items
 * that are based on unique features of the calling module.
 * @param App    $a
 * @param array  $items
 * @param Pager  $pager
 * @param        $mode
 * @param        $update
 * @param bool   $preview
 * @param string $order
 * @param int    $uid
 * @return string
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function conversation(App $a, array $items, Pager $pager, $mode, $update, $preview = false, $order = 'commented', $uid = 0)
{
	$ssl_state = (local_user() ? true : false);

	$profile_owner = 0;
	$live_update_div = '';

	$blocklist = conv_get_blocklist();

	$previewing = (($preview) ? ' preview ' : '');

	if ($mode === 'network') {
		$items = conversation_add_children($items, false, $order, $uid);
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
				. (!empty($_GET['cid'])    ? '&cid='    . rawurlencode($_GET['cid'])    : '')
				. (!empty($_GET['search']) ? '&search=' . rawurlencode($_GET['search']) : '')
				. (!empty($_GET['star'])   ? '&star='   . rawurlencode($_GET['star'])   : '')
				. (!empty($_GET['order'])  ? '&order='  . rawurlencode($_GET['order'])  : '')
				. (!empty($_GET['bmark'])  ? '&bmark='  . rawurlencode($_GET['bmark'])  : '')
				. (!empty($_GET['liked'])  ? '&liked='  . rawurlencode($_GET['liked'])  : '')
				. (!empty($_GET['conv'])   ? '&conv='   . rawurlencode($_GET['conv'])   : '')
				. (!empty($_GET['nets'])   ? '&nets='   . rawurlencode($_GET['nets'])   : '')
				. (!empty($_GET['cmin'])   ? '&cmin='   . rawurlencode($_GET['cmin'])   : '')
				. (!empty($_GET['cmax'])   ? '&cmax='   . rawurlencode($_GET['cmax'])   : '')
				. (!empty($_GET['file'])   ? '&file='   . rawurlencode($_GET['file'])   : '')

				. "'; var profile_page = " . $pager->getPage() . "; </script>\r\n";
		}
	} elseif ($mode === 'profile') {
		$items = conversation_add_children($items, false, $order, $uid);
		$profile_owner = $a->profile['profile_uid'];

		if (!$update) {
			$tab = 'posts';
			if (!empty($_GET['tab'])) {
				$tab = Strings::escapeTags(trim($_GET['tab']));
			}
			if ($tab === 'posts') {
				/*
				 * This is ugly, but we can't pass the profile_uid through the session to the ajax updater,
				 * because browser prefetching might change it on us. We have to deliver it with the page.
				 */

				$live_update_div = '<div id="live-profile"></div>' . "\r\n"
					. "<script> var profile_uid = " . $a->profile['profile_uid']
					. "; var netargs = '?f='; var profile_page = " . $pager->getPage() . "; </script>\r\n";
			}
		}
	} elseif ($mode === 'notes') {
		$items = conversation_add_children($items, false, $order, local_user());
		$profile_owner = local_user();

		if (!$update) {
			$live_update_div = '<div id="live-notes"></div>' . "\r\n"
				. "<script> var profile_uid = " . local_user()
				. "; var netargs = '/?f='; var profile_page = " . $pager->getPage() . "; </script>\r\n";
		}
	} elseif ($mode === 'display') {
		$items = conversation_add_children($items, false, $order, $uid);
		$profile_owner = $a->profile['uid'];

		if (!$update) {
			$live_update_div = '<div id="live-display"></div>' . "\r\n"
				. "<script> var profile_uid = " . defaults($_SESSION, 'uid', 0) . ";"
				. " var profile_page = 1; </script>";
		}
	} elseif ($mode === 'community') {
		$items = conversation_add_children($items, true, $order, $uid);
		$profile_owner = 0;

		if (!$update) {
			$live_update_div = '<div id="live-community"></div>' . "\r\n"
				. "<script> var profile_uid = -1; var netargs = '" . substr($a->cmd, 10)
				."/?f='; var profile_page = " . $pager->getPage() . "; </script>\r\n";
		}
	} elseif ($mode === 'contacts') {
		$items = conversation_add_children($items, false, $order, $uid);
		$profile_owner = 0;

		if (!$update) {
			$live_update_div = '<div id="live-contacts"></div>' . "\r\n"
				. "<script> var profile_uid = -1; var netargs = '" . substr($a->cmd, 9)
				."/?f='; var profile_page = " . $pager->getPage() . "; </script>\r\n";
		}
	} elseif ($mode === 'search') {
		$live_update_div = '<div id="live-search"></div>' . "\r\n";
	}

	$page_dropping = ((local_user() && local_user() == $profile_owner) ? true : false);

	if (!$update) {
		$_SESSION['return_path'] = $a->query_string;
	}

	$cb = ['items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview];
	Hook::callAll('conversation_start',$cb);

	$items = $cb['items'];

	$conv_responses = [
		'like' => ['title' => L10n::t('Likes','title')], 'dislike' => ['title' => L10n::t('Dislikes','title')],
		'attendyes' => ['title' => L10n::t('Attending','title')], 'attendno' => ['title' => L10n::t('Not attending','title')], 'attendmaybe' => ['title' => L10n::t('Might attend','title')]
	];

	// array with html for each thread (parent+comments)
	$threads = [];
	$threadsid = -1;

	$page_template = Renderer::getMarkupTemplate("conversation.tpl");

	if (!empty($items)) {
		if (in_array($mode, ['community', 'contacts'])) {
			$writable = true;
		} else {
			$writable = ($items[0]['uid'] == 0) && in_array($items[0]['network'], [Protocol::ACTIVITYPUB, Protocol::OSTATUS, Protocol::DIASPORA, Protocol::DFRN]);
		}

		if (!local_user()) {
			$writable = false;
		}

		if (in_array($mode, ['network-new', 'search', 'contact-posts'])) {

			/*
			 * "New Item View" on network page or search page results
			 * - just loop through the items and format them minimally for display
			 */

			$tpl = 'search_item.tpl';

			foreach ($items as $item) {

				if (!visible_activity($item)) {
					continue;
				}

				if (in_array($item['author-id'], $blocklist)) {
					continue;
				}

				$threadsid++;

				$owner_url   = '';
				$owner_name  = '';
				$sparkle     = '';

				// prevent private email from leaking.
				if ($item['network'] === Protocol::MAIL && local_user() != $item['uid']) {
					continue;
				}

				$profile_name = $item['author-name'];
				if (!empty($item['author-link']) && empty($item['author-name'])) {
					$profile_name = $item['author-link'];
				}

				$tags = Term::populateTagsFromItem($item);

				$author = ['uid' => 0, 'id' => $item['author-id'],
					'network' => $item['author-network'], 'url' => $item['author-link']];
				$profile_link = Contact::magicLinkByContact($author);

				if (strpos($profile_link, 'redir/') === 0) {
					$sparkle = ' sparkle';
				}

				$locate = ['location' => $item['location'], 'coord' => $item['coord'], 'html' => ''];
				Hook::callAll('render_location',$locate);

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
					'select' => L10n::t('Select'),
					'delete' => L10n::t('Delete'),
				];

				$star = false;
				$isstarred = "unstarred";

				$lock = false;
				$likebuttons = false;

				$body = Item::prepareBody($item, true, $preview);

				list($categories, $folders) = get_cats_and_terms($item);

				if (!empty($item['content-warning']) && PConfig::get(local_user(), 'system', 'disable_cw', false)) {
					$title = ucfirst($item['content-warning']);
				} else {
					$title = $item['title'];
				}

				$tmp_item = [
					'template' => $tpl,
					'id' => ($preview ? 'P0' : $item['id']),
					'guid' => ($preview ? 'Q0' : $item['guid']),
					'network' => $item['network'],
					'network_name' => ContactSelector::networkToName($item['network'], $item['author-link']),
					'linktitle' => L10n::t('View %s\'s profile @ %s', $profile_name, $item['author-link']),
					'profile_url' => $profile_link,
					'item_photo_menu' => item_photo_menu($item),
					'name' => $profile_name,
					'sparkle' => $sparkle,
					'lock' => $lock,
					'thumb' => System::removedBaseUrl(ProxyUtils::proxifyUrl($item['author-avatar'], false, ProxyUtils::SIZE_THUMB)),
					'title' => $title,
					'body' => $body,
					'tags' => $tags['tags'],
					'hashtags' => $tags['hashtags'],
					'mentions' => $tags['mentions'],
					'implicit_mentions' => $tags['implicit_mentions'],
					'txt_cats' => L10n::t('Categories:'),
					'txt_folders' => L10n::t('Filed under:'),
					'has_cats' => ((count($categories)) ? 'true' : ''),
					'has_folders' => ((count($folders)) ? 'true' : ''),
					'categories' => $categories,
					'folders' => $folders,
					'text' => strip_tags($body),
					'localtime' => DateTimeFormat::local($item['created'], 'r'),
					'ago' => (($item['app']) ? L10n::t('%s from %s', Temporal::getRelativeDate($item['created']),$item['app']) : Temporal::getRelativeDate($item['created'])),
					'location' => $location,
					'indent' => '',
					'owner_name' => $owner_name,
					'owner_url' => $owner_url,
					'owner_photo' => System::removedBaseUrl(ProxyUtils::proxifyUrl($item['owner-avatar'], false, ProxyUtils::SIZE_THUMB)),
					'plink' => Item::getPlink($item),
					'edpost' => false,
					'isstarred' => $isstarred,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					'conv' => (($preview) ? '' : ['href'=> 'display/'.$item['guid'], 'title'=> L10n::t('View in context')]),
					'previewing' => $previewing,
					'wait' => L10n::t('Please wait'),
					'thread_level' => 1,
				];

				$arr = ['item' => $item, 'output' => $tmp_item];
				Hook::callAll('display_item', $arr);

				$threads[$threadsid]['id'] = $item['id'];
				$threads[$threadsid]['network'] = $item['network'];
				$threads[$threadsid]['items'] = [$arr['output']];

			}
		} else {
			// Normal View
			$page_template = Renderer::getMarkupTemplate("threaded_conversation.tpl");

			$conv = new Thread($mode, $preview, $writable);

			/*
			 * get all the topmost parents
			 * this shouldn't be needed, as we should have only them in our array
			 * But for now, this array respects the old style, just in case
			 */
			foreach ($items as $item) {
				if (in_array($item['author-id'], $blocklist)) {
					continue;
				}

				// Can we put this after the visibility check?
				builtin_activity_puller($item, $conv_responses);

				// Only add what is visible
				if ($item['network'] === Protocol::MAIL && local_user() != $item['uid']) {
					continue;
				}

				if (!visible_activity($item)) {
					continue;
				}

				/// @todo Check if this call is needed or not
				$arr = ['item' => $item];
				Hook::callAll('display_item', $arr);

				$item['pagedrop'] = $page_dropping;

				if ($item['id'] == $item['parent']) {
					$item_object = new Post($item);
					$conv->addParent($item_object);
				}
			}

			$threads = $conv->getTemplateData($conv_responses);
			if (!$threads) {
				Logger::log('[ERROR] conversation : Failed to get template data.', Logger::DEBUG);
				$threads = [];
			}
		}
	}

	$o = Renderer::replaceMacros($page_template, [
		'$baseurl' => System::baseUrl($ssl_state),
		'$return_path' => $a->query_string,
		'$live_update' => $live_update_div,
		'$remove' => L10n::t('remove'),
		'$mode' => $mode,
		'$user' => $a->user,
		'$threads' => $threads,
		'$dropping' => ($page_dropping ? L10n::t('Delete Selected Items') : False),
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
 * @param       $block_authors
 * @param       $order
 * @param       $uid
 * @return array items with parents and comments
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function conversation_add_children(array $parents, $block_authors, $order, $uid) {
	$max_comments = Config::get('system', 'max_comments', 100);

	$params = ['order' => ['uid', 'commented' => true]];

	if ($max_comments > 0) {
		$params['limit'] = $max_comments;
	}

	$items = [];

	foreach ($parents AS $parent) {
		$condition = ["`item`.`parent-uri` = ? AND `item`.`uid` IN (0, ?) ",
			$parent['uri'], $uid];
		if ($block_authors) {
			$condition[0] .= "AND NOT `author`.`hidden`";
		}
		$thread_items = Item::selectForUser(local_user(), [], $condition, $params);

		$comments = Item::inArray($thread_items);

		if (count($comments) != 0) {
			$items = array_merge($items, $comments);
		}
	}

	foreach ($items as $index => $item) {
		if ($item['uid'] == 0) {
			$items[$index]['writable'] = in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::OSTATUS, Protocol::DIASPORA, Protocol::DFRN]);
		}
	}

	$items = conv_sort($items, $order);

	return $items;
}

function item_photo_menu($item) {
	$sub_link = '';
	$poke_link = '';
	$contact_url = '';
	$pm_url = '';
	$status_link = '';
	$photos_link = '';
	$posts_link = '';
	$block_link = '';
	$ignore_link = '';

	if (local_user() && local_user() == $item['uid'] && $item['parent'] == $item['id'] && !$item['self']) {
		$sub_link = 'javascript:dosubthread(' . $item['id'] . '); return false;';
	}

	$author = ['uid' => 0, 'id' => $item['author-id'],
		'network' => $item['author-network'], 'url' => $item['author-link']];
	$profile_link = Contact::magicLinkByContact($author, $item['author-link']);
	$sparkle = (strpos($profile_link, 'redir/') === 0);

	$cid = 0;
	$pcid = Contact::getIdForURL($item['author-link'], 0, true);
	$network = '';
	$rel = 0;
	$condition = ['uid' => local_user(), 'nurl' => Strings::normaliseLink($item['author-link'])];
	$contact = DBA::selectFirst('contact', ['id', 'network', 'rel'], $condition);
	if (DBA::isResult($contact)) {
		$cid = $contact['id'];
		$network = $contact['network'];
		$rel = $contact['rel'];
	}

	if ($sparkle) {
		$status_link = $profile_link . '?tab=status';
		$photos_link = str_replace('/profile/', '/photos/', $profile_link);
		$profile_link = $profile_link . '?=profile';
	}

	if (!empty($pcid)) {
		$contact_url = 'contact/' . $pcid;
		$posts_link = 'contact/' . $pcid . '/posts';
		$block_link = 'contact/' . $pcid . '/block';
		$ignore_link = 'contact/' . $pcid . '/ignore';
	}

	if ($cid && !$item['self']) {
		$poke_link = 'poke/?f=&c=' . $cid;
		$contact_url = 'contact/' . $cid;
		$posts_link = 'contact/' . $cid . '/posts';

		if (in_array($network, [Protocol::DFRN, Protocol::DIASPORA])) {
			$pm_url = 'message/new/' . $cid;
		}
	}

	if (local_user()) {
		$menu = [
			L10n::t('Follow Thread') => $sub_link,
			L10n::t('View Status') => $status_link,
			L10n::t('View Profile') => $profile_link,
			L10n::t('View Photos') => $photos_link,
			L10n::t('Network Posts') => $posts_link,
			L10n::t('View Contact') => $contact_url,
			L10n::t('Send PM') => $pm_url,
			L10n::t('Block') => $block_link,
			L10n::t('Ignore') => $ignore_link
		];

		if ($network == Protocol::DFRN) {
			$menu[L10n::t("Poke")] = $poke_link;
		}

		if ((($cid == 0) || ($rel == Contact::FOLLOWER)) &&
			in_array($item['network'], [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::OSTATUS, Protocol::DIASPORA])) {
			$menu[L10n::t('Connect/Follow')] = 'follow?url=' . urlencode($item['author-link']);
		}
	} else {
		$menu = [L10n::t('View Profile') => $item['author-link']];
	}

	$args = ['item' => $item, 'menu' => $menu];

	Hook::callAll('item_photo_menu', $args);

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
 * @param array  $item
 * @param array &$conv_responses (already created with builtin activity structure)
 * @return void
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function builtin_activity_puller($item, &$conv_responses) {
	foreach ($conv_responses as $mode => $v) {
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

		if (activity_match($item['verb'], $verb) && ($item['id'] != $item['parent'])) {
			$author = ['uid' => 0, 'id' => $item['author-id'],
				'network' => $item['author-network'], 'url' => $item['author-link']];
			$url = Contact::magicLinkByContact($author);
			if (strpos($url, 'redir/') === 0) {
				$sparkle = ' class="sparkle" ';
			}

			$url = '<a href="'. $url . '"'. $sparkle .'>' . htmlentities($item['author-name']) . '</a>';

			if (empty($item['thr-parent'])) {
				$item['thr-parent'] = $item['parent-uri'];
			}

			if (!(isset($conv_responses[$mode][$item['thr-parent'] . '-l'])
				&& is_array($conv_responses[$mode][$item['thr-parent'] . '-l']))) {
				$conv_responses[$mode][$item['thr-parent'] . '-l'] = [];
			}

			// only list each unique author once
			if (in_array($url,$conv_responses[$mode][$item['thr-parent'] . '-l'])) {
				continue;
			}

			if (!isset($conv_responses[$mode][$item['thr-parent']])) {
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
 *
 * @param int    $cnt  = number of people who vote the item
 * @param array  $arr  = array of pre-linked names of likers/dislikers
 * @param string $type = one of 'like, 'dislike', 'attendyes', 'attendno', 'attendmaybe'
 * @param int    $id   = item id
 * @return string formatted text
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function format_like($cnt, array $arr, $type, $id) {
	$o = '';
	$expanded = '';
	$phrase = '';

	if ($cnt == 1) {
		$likers = $arr[0];

		// Phrase if there is only one liker. In other cases it will be uses for the expanded
		// list which show all likers
		switch ($type) {
			case 'like' :
				$phrase = L10n::t('%s likes this.', $likers);
				break;
			case 'dislike' :
				$phrase = L10n::t('%s doesn\'t like this.', $likers);
				break;
			case 'attendyes' :
				$phrase = L10n::t('%s attends.', $likers);
				break;
			case 'attendno' :
				$phrase = L10n::t('%s doesn\'t attend.', $likers);
				break;
			case 'attendmaybe' :
				$phrase = L10n::t('%s attends maybe.', $likers);
				break;
		}
	}

	if ($cnt > 1) {
		$total = count($arr);
		if ($total < MAX_LIKERS) {
			$last = L10n::t('and') . ' ' . $arr[count($arr)-1];
			$arr2 = array_slice($arr, 0, -1);
			$likers = implode(', ', $arr2) . ' ' . $last;
		} else  {
			$arr = array_slice($arr, 0, MAX_LIKERS - 1);
			$likers = implode(', ', $arr);
			$likers .= L10n::t('and %d other people', $total - MAX_LIKERS);
		}

		$spanatts = "class=\"fakelink\" onclick=\"openClose('{$type}list-$id');\"";

		$explikers = '';
		switch ($type) {
			case 'like':
				$phrase = L10n::t('<span  %1$s>%2$d people</span> like this', $spanatts, $cnt);
				$explikers = L10n::t('%s like this.', $likers);
				break;
			case 'dislike':
				$phrase = L10n::t('<span  %1$s>%2$d people</span> don\'t like this', $spanatts, $cnt);
				$explikers = L10n::t('%s don\'t like this.', $likers);
				break;
			case 'attendyes':
				$phrase = L10n::t('<span  %1$s>%2$d people</span> attend', $spanatts, $cnt);
				$explikers = L10n::t('%s attend.', $likers);
				break;
			case 'attendno':
				$phrase = L10n::t('<span  %1$s>%2$d people</span> don\'t attend', $spanatts, $cnt);
				$explikers = L10n::t('%s don\'t attend.', $likers);
				break;
			case 'attendmaybe':
				$phrase = L10n::t('<span  %1$s>%2$d people</span> attend maybe', $spanatts, $cnt);
				$explikers = L10n::t('%s attend maybe.', $likers);
				break;
		}

		$expanded .= "\t" . '<p class="wall-item-' . $type . '-expanded" id="' . $type . 'list-' . $id . '" style="display: none;" >' . $explikers . EOL . '</p>';
	}

	$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('voting_fakelink.tpl'), [
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

	$geotag = !empty($x['allow_location']) ? Renderer::replaceMacros(Renderer::getMarkupTemplate('jot_geotag.tpl'), []) : '';

	$tpl = Renderer::getMarkupTemplate('jot-header.tpl');
	$a->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
		'$newpost'   => 'true',
		'$baseurl'   => System::baseUrl(true),
		'$geotag'    => $geotag,
		'$nickname'  => $x['nickname'],
		'$ispublic'  => L10n::t('Visible to <strong>everybody</strong>'),
		'$linkurl'   => L10n::t('Please enter a image/video/audio/webpage URL:'),
		'$term'      => L10n::t('Tag term:'),
		'$fileas'    => L10n::t('Save to Folder:'),
		'$whereareu' => L10n::t('Where are you right now?'),
		'$delitems'  => L10n::t("Delete item\x28s\x29?")
	]);

	$jotplugins = '';
	Hook::callAll('jot_tool', $jotplugins);

	// Private/public post links for the non-JS ACL form
	$private_post = 1;
	if (!empty($_REQUEST['public'])) {
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

	// $tpl = Renderer::replaceMacros($tpl,array('$jotplugins' => $jotplugins));
	$tpl = Renderer::getMarkupTemplate("jot.tpl");

	$o .= Renderer::replaceMacros($tpl,[
		'$new_post' => L10n::t('New Post'),
		'$return_path'  => $query_str,
		'$action'       => 'item',
		'$share'        => defaults($x, 'button', L10n::t('Share')),
		'$upload'       => L10n::t('Upload photo'),
		'$shortupload'  => L10n::t('upload photo'),
		'$attach'       => L10n::t('Attach file'),
		'$shortattach'  => L10n::t('attach file'),
		'$edbold'       => L10n::t('Bold'),
		'$editalic'     => L10n::t('Italic'),
		'$eduline'      => L10n::t('Underline'),
		'$edquote'      => L10n::t('Quote'),
		'$edcode'       => L10n::t('Code'),
		'$edimg'        => L10n::t('Image'),
		'$edurl'        => L10n::t('Link'),
		'$edattach'     => L10n::t('Link or Media'),
		'$setloc'       => L10n::t('Set your location'),
		'$shortsetloc'  => L10n::t('set location'),
		'$noloc'        => L10n::t('Clear browser location'),
		'$shortnoloc'   => L10n::t('clear location'),
		'$title'        => defaults($x, 'title', ''),
		'$placeholdertitle' => L10n::t('Set title'),
		'$category'     => defaults($x, 'category', ''),
		'$placeholdercategory' => Feature::isEnabled(local_user(), 'categories') ? L10n::t("Categories \x28comma-separated list\x29") : '',
		'$wait'         => L10n::t('Please wait'),
		'$permset'      => L10n::t('Permission settings'),
		'$shortpermset' => L10n::t('permissions'),
		'$wall'         => $notes_cid ? 0 : 1,
		'$posttype'     => $notes_cid ? Item::PT_PERSONAL_NOTE : Item::PT_ARTICLE,
		'$content'      => defaults($x, 'content', ''),
		'$post_id'      => defaults($x, 'post_id', ''),
		'$baseurl'      => System::baseUrl(true),
		'$defloc'       => $x['default_location'],
		'$visitor'      => $x['visitor'],
		'$pvisit'       => $notes_cid ? 'none' : $x['visitor'],
		'$public'       => L10n::t('Public post'),
		'$lockstate'    => $x['lockstate'],
		'$bang'         => $x['bang'],
		'$profile_uid'  => $x['profile_uid'],
		'$preview'      => L10n::t('Preview'),
		'$jotplugins'   => $jotplugins,
		'$notes_cid'    => $notes_cid,
		'$sourceapp'    => L10n::t($a->sourcename),
		'$cancel'       => L10n::t('Cancel'),
		'$rand_num'     => Crypto::randomDigits(12),

		// ACL permissions box
		'$acl'           => $x['acl'],
		'$group_perms'   => L10n::t('Post to Groups'),
		'$contact_perms' => L10n::t('Post to Contacts'),
		'$private'       => L10n::t('Private post'),
		'$is_private'    => $private_post,
		'$public_link'   => $public_post_link,

		//jot nav tab (used in some themes)
		'$message' => L10n::t('Message'),
		'$browser' => L10n::t('Browser'),
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
 * @param bool  $recursive
 * @return array
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
	if (!isset($parent['children']) || count($parent['children']) == 0) {
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
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function conv_sort(array $item_list, $order)
{
	$parents = [];

	if (!(is_array($item_list) && count($item_list))) {
		return $parents;
	}

	$blocklist = conv_get_blocklist();

	$item_array = [];

	// Dedupes the item list on the uri to prevent infinite loops
	foreach ($item_list as $item) {
		if (in_array($item['author-id'], $blocklist)) {
			continue;
		}

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
 * @return int
 */
function sort_thr_commented(array $a, array $b)
{
	return strcmp($b['commented'], $a['commented']);
}

function render_location_dummy(array $item) {
	if (!empty($item['location']) && !empty($item['location'])) {
		return $item['location'];
	}

	if (!empty($item['coord']) && !empty($item['coord'])) {
		return $item['coord'];
	}
}

function get_responses(array $conv_responses, array $response_verbs, array $item, Post $ob = null) {
	$ret = [];
	foreach ($response_verbs as $v) {
		$ret[$v] = [];
		$ret[$v]['count'] = defaults($conv_responses[$v], $item['uri'], 0);
		$ret[$v]['list']  = defaults($conv_responses[$v], $item['uri'] . '-l', []);
		$ret[$v]['self']  = defaults($conv_responses[$v], $item['uri'] . '-self', '0');
		if (count($ret[$v]['list']) > MAX_LIKERS) {
			$ret[$v]['list_part'] = array_slice($ret[$v]['list'], 0, MAX_LIKERS);
			array_push($ret[$v]['list_part'], '<a href="#" data-toggle="modal" data-target="#' . $v . 'Modal-'
				. (($ob) ? $ob->getId() : $item['id']) . '"><b>' . L10n::t('View all') . '</b></a>');
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
	$return = '';
	switch ($v) {
		case 'like':
			$return = L10n::tt('Like', 'Likes', $count);
			break;
		case 'dislike':
			$return = L10n::tt('Dislike', 'Dislikes', $count);
			break;
		case 'attendyes':
			$return = L10n::tt('Attending', 'Attending', $count);
			break;
		case 'attendno':
			$return = L10n::tt('Not Attending', 'Not Attending', $count);
			break;
		case 'attendmaybe':
			$return = L10n::tt('Undecided', 'Undecided', $count);
			break;
	}

	return $return;
}
