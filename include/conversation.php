<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Profile;
use Friendica\Model\Tag;
use Friendica\Model\Verb;
use Friendica\Object\Post;
use Friendica\Object\Thread;
use Friendica\Protocol\Activity;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy as ProxyUtils;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Friendica\Util\XML;

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
		$replace = '[url=' . DI::baseUrl() . '/redir/' . $cid
				   . '?url=' . '$1' . '][!#saved_image' . '$2' .'#!][/url]';

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

	/// @todo The following functionality needs to be cleaned up. 
	if (!empty($item['verb'])) {
		$activity = DI::activity();

		$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";

		if (stristr($item['verb'], Activity::POKE)) {
			$verb = urldecode(substr($item['verb'], strpos($item['verb'],'#') + 1));
			if (!$verb) {
				return;
			}
			if ($item['object-type'] == "" || $item['object-type'] !== Activity\ObjectType::PERSON) {
				return;
			}

			$Aname = $item['author-name'];
			$Alink = $item['author-link'];

			$obj = XML::parseString($xmlhead . $item['object']);

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
			$txt = DI::l10n()->t('%1$s poked %2$s');

			// now translate the verb
			$poked_t = trim(sprintf($txt, '', ''));
			$txt = str_replace($poked_t, DI::l10n()->t($verb), $txt);

			// then do the sprintf on the translation string

			$item['body'] = sprintf($txt, $A, $B) . "\n\n\n" . $Bphoto;

		}

		if ($activity->match($item['verb'], Activity::TAG)) {
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
				case Activity::POST:
					switch ($obj['object-type']) {
						case Activity\ObjectType::EVENT:
							$post_type = DI::l10n()->t('event');
							break;
						default:
							$post_type = DI::l10n()->t('status');
					}
					break;
				default:
					if ($obj['resource-id']) {
						$post_type = DI::l10n()->t('photo');
						$m=[]; preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
						$rr['plink'] = $m[1];
					} else {
						$post_type = DI::l10n()->t('status');
					}
					// Let's break everthing ... ;-)
					break;
			}
			$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

			$parsedobj = XML::parseString($xmlhead . $item['object']);

			$tag = sprintf('#[url=%s]%s[/url]', $parsedobj->id, $parsedobj->content);
			$item['body'] = DI::l10n()->t('%1$s tagged %2$s\'s %3$s with %4$s', $author, $objauthor, $plink, $tag);
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
	if (!empty($item['plink']) && Session::isAuthenticated()) {
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

	$activity = DI::activity();

	if (empty($item['verb']) || $activity->isHidden($item['verb'])) {
		return false;
	}

	// @TODO below if() block can be rewritten to a single line: $isVisible = allConditionsHere;
	if ($activity->match($item['verb'], Activity::FOLLOW) &&
	    $item['object-type'] === Activity\ObjectType::NOTE &&
	    empty($item['self']) &&
	    $item['uid'] == local_user()) {
		return false;
	}

	return true;
}

function conv_get_blocklist()
{
	if (!local_user()) {
		return [];
	}

	$str_blocked = DI::pConfig()->get(local_user(), 'system', 'blocked');
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
 * @param        $mode
 * @param        $update
 * @param bool   $preview
 * @param string $order
 * @param int    $uid
 * @return string
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function conversation(App $a, array $items, $mode, $update, $preview = false, $order = 'commented', $uid = 0)
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
				. "; var netargs = '" . substr(DI::args()->getCommand(), 8)
				. '?f='
				. (!empty($_GET['contactid']) ? '&contactid=' . rawurlencode($_GET['contactid']) : '')
				. (!empty($_GET['search'])    ? '&search='    . rawurlencode($_GET['search'])    : '')
				. (!empty($_GET['star'])      ? '&star='      . rawurlencode($_GET['star'])      : '')
				. (!empty($_GET['order'])     ? '&order='     . rawurlencode($_GET['order'])     : '')
				. (!empty($_GET['bmark'])     ? '&bmark='     . rawurlencode($_GET['bmark'])     : '')
				. (!empty($_GET['liked'])     ? '&liked='     . rawurlencode($_GET['liked'])     : '')
				. (!empty($_GET['conv'])      ? '&conv='      . rawurlencode($_GET['conv'])      : '')
				. (!empty($_GET['nets'])      ? '&nets='      . rawurlencode($_GET['nets'])      : '')
				. (!empty($_GET['cmin'])      ? '&cmin='      . rawurlencode($_GET['cmin'])      : '')
				. (!empty($_GET['cmax'])      ? '&cmax='      . rawurlencode($_GET['cmax'])      : '')
				. (!empty($_GET['file'])      ? '&file='      . rawurlencode($_GET['file'])      : '')

				. "'; </script>\r\n";
		}
	} elseif ($mode === 'profile') {
		$items = conversation_add_children($items, false, $order, $uid);
		$profile_owner = $a->profile['uid'];

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
					. "<script> var profile_uid = " . $a->profile['uid']
					. "; var netargs = '?f='; </script>\r\n";
			}
		}
	} elseif ($mode === 'notes') {
		$items = conversation_add_children($items, false, $order, local_user());
		$profile_owner = local_user();

		if (!$update) {
			$live_update_div = '<div id="live-notes"></div>' . "\r\n"
				. "<script> var profile_uid = " . local_user()
				. "; var netargs = '/?f='; </script>\r\n";
		}
	} elseif ($mode === 'display') {
		$items = conversation_add_children($items, false, $order, $uid);
		$profile_owner = $a->profile['uid'];

		if (!$update) {
			$live_update_div = '<div id="live-display"></div>' . "\r\n"
				. "<script> var profile_uid = " . Session::get('uid', 0) . ";"
				. "</script>";
		}
	} elseif ($mode === 'community') {
		$items = conversation_add_children($items, true, $order, $uid);
		$profile_owner = 0;

		if (!$update) {
			$live_update_div = '<div id="live-community"></div>' . "\r\n"
				. "<script> var profile_uid = -1; var netargs = '" . substr(DI::args()->getCommand(), 10)
				."/?f='; </script>\r\n";
		}
	} elseif ($mode === 'contacts') {
		$items = conversation_add_children($items, false, $order, $uid);
		$profile_owner = 0;

		if (!$update) {
			$live_update_div = '<div id="live-contacts"></div>' . "\r\n"
				. "<script> var profile_uid = -1; var netargs = '" . substr(DI::args()->getCommand(), 9)
				."/?f='; </script>\r\n";
		}
	} elseif ($mode === 'search') {
		$live_update_div = '<div id="live-search"></div>' . "\r\n";
	}

	$page_dropping = ((local_user() && local_user() == $profile_owner) ? true : false);

	if (!$update) {
		$_SESSION['return_path'] = DI::args()->getQueryString();
	}

	$cb = ['items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview];
	Hook::callAll('conversation_start',$cb);

	$items = $cb['items'];

	$conv_responses = [
		'like'        => [],
		'dislike'     => [],
		'attendyes'   => [],
		'attendno'    => [],
		'attendmaybe' => [],
		'announce'    => [],	
	];

	if (DI::pConfig()->get(local_user(), 'system', 'hide_dislike')) {
		unset($conv_responses['dislike']);
	}

	// array with html for each thread (parent+comments)
	$threads = [];
	$threadsid = -1;

	$page_template = Renderer::getMarkupTemplate("conversation.tpl");

	if (!empty($items)) {
		if (in_array($mode, ['community', 'contacts'])) {
			$writable = true;
		} else {
			$writable = ($items[0]['uid'] == 0) && in_array($items[0]['network'], Protocol::FEDERATED);
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

				$tags = Tag::populateFromItem($item);

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
					'select' => DI::l10n()->t('Select'),
					'delete' => DI::l10n()->t('Delete'),
				];

				$star = false;
				$isstarred = "unstarred";

				$lock = false;
				$likebuttons = [
					'like'    => null,
					'dislike' => null,
					'share'   => null,
				];

				if (DI::pConfig()->get(local_user(), 'system', 'hide_dislike')) {
					unset($likebuttons['dislike']);
				}

				$body = Item::prepareBody($item, true, $preview);

				list($categories, $folders) = DI::contentItem()->determineCategoriesTerms($item);

				if (!empty($item['content-warning']) && DI::pConfig()->get(local_user(), 'system', 'disable_cw', false)) {
					$title = ucfirst($item['content-warning']);
				} else {
					$title = $item['title'];
				}

				$tmp_item = [
					'template' => $tpl,
					'id' => ($preview ? 'P0' : $item['id']),
					'guid' => ($preview ? 'Q0' : $item['guid']),
					'network' => $item['network'],
					'network_name' => ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']),
					'network_icon' => ContactSelector::networkToIcon($item['network'], $item['author-link']),
					'linktitle' => DI::l10n()->t('View %s\'s profile @ %s', $profile_name, $item['author-link']),
					'profile_url' => $profile_link,
					'item_photo_menu' => item_photo_menu($item),
					'name' => $profile_name,
					'sparkle' => $sparkle,
					'lock' => $lock,
					'thumb' => DI::baseUrl()->remove(ProxyUtils::proxifyUrl($item['author-avatar'], false, ProxyUtils::SIZE_THUMB)),
					'title' => $title,
					'body' => $body,
					'tags' => $tags['tags'],
					'hashtags' => $tags['hashtags'],
					'mentions' => $tags['mentions'],
					'implicit_mentions' => $tags['implicit_mentions'],
					'txt_cats' => DI::l10n()->t('Categories:'),
					'txt_folders' => DI::l10n()->t('Filed under:'),
					'has_cats' => ((count($categories)) ? 'true' : ''),
					'has_folders' => ((count($folders)) ? 'true' : ''),
					'categories' => $categories,
					'folders' => $folders,
					'text' => strip_tags($body),
					'localtime' => DateTimeFormat::local($item['created'], 'r'),
					'ago' => (($item['app']) ? DI::l10n()->t('%s from %s', Temporal::getRelativeDate($item['created']),$item['app']) : Temporal::getRelativeDate($item['created'])),
					'location' => $location,
					'indent' => '',
					'owner_name' => $owner_name,
					'owner_url' => $owner_url,
					'owner_photo' => DI::baseUrl()->remove(ProxyUtils::proxifyUrl($item['owner-avatar'], false, ProxyUtils::SIZE_THUMB)),
					'plink' => Item::getPlink($item),
					'edpost' => false,
					'isstarred' => $isstarred,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					'conv' => (($preview) ? '' : ['href'=> 'display/'.$item['guid'], 'title'=> DI::l10n()->t('View in context')]),
					'previewing' => $previewing,
					'wait' => DI::l10n()->t('Please wait'),
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

				if ($item['gravity'] == GRAVITY_PARENT) {
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
		'$baseurl' => DI::baseUrl()->get($ssl_state),
		'$return_path' => DI::args()->getQueryString(),
		'$live_update' => $live_update_div,
		'$remove' => DI::l10n()->t('remove'),
		'$mode' => $mode,
		'$user' => $a->user,
		'$threads' => $threads,
		'$dropping' => ($page_dropping ? DI::l10n()->t('Delete Selected Items') : False),
	]);

	return $o;
}

/**
 * Fetch all comments from a query. Additionally set the newest resharer as thread owner.
 *
 * @param mixed   $thread_items Database statement with thread posts
 * @param boolean $pinned       Is the item pinned?
 *
 * @return array items with parents and comments
 */
function conversation_fetch_comments($thread_items, $pinned) {
	$comments = [];
	$parentlines = [];
	$lineno = 0;
	$actor = [];
	$received = '';

	while ($row = Item::fetch($thread_items)) {
		if (($row['verb'] == Activity::ANNOUNCE) && !empty($row['contact-uid']) && ($row['received'] > $received) && ($row['thr-parent'] == $row['parent-uri'])) {
			$actor = ['link' => $row['author-link'], 'avatar' => $row['author-avatar'], 'name' => $row['author-name']];
			$received = $row['received'];
		}

		if ((($row['gravity'] == GRAVITY_PARENT) && !$row['origin'] && !in_array($row['network'], [Protocol::DIASPORA])) &&
			(empty($row['contact-uid']) || !in_array($row['network'], Protocol::NATIVE_SUPPORT))) {
			$parentlines[] = $lineno;
		}

		if ($row['gravity'] == GRAVITY_PARENT) {
			$row['pinned'] = $pinned;
		}

		$comments[] = $row;
		$lineno++;
	}

	DBA::close($thread_items);

	if (!empty($actor)) {
		foreach ($parentlines as $line) {
			$comments[$line]['owner-link'] = $actor['link'];
			$comments[$line]['owner-avatar'] = $actor['avatar'];
			$comments[$line]['owner-name'] = $actor['name'];
		}
	}
	return $comments;
}

/**
 * Add comments to top level entries that had been fetched before
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
	if (count($parents) > 1) {
		$max_comments = DI::config()->get('system', 'max_comments', 100);
	} else {
		$max_comments = DI::config()->get('system', 'max_display_comments', 1000);
	}

	$params = ['order' => ['uid', 'commented' => true]];

	if ($max_comments > 0) {
		$params['limit'] = $max_comments;
	}

	$items = [];

	foreach ($parents AS $parent) {
		$condition = ["`item`.`parent-uri` = ? AND `item`.`uid` IN (0, ?) AND (`vid` != ? OR `vid` IS NULL)",
			$parent['uri'], $uid, Verb::getID(Activity::FOLLOW)];
		$items = conversation_fetch_items($parent, $items, $condition, $block_authors, $params);
	}

	foreach ($items as $index => $item) {
		if ($item['uid'] == 0) {
			$items[$index]['writable'] = in_array($item['network'], Protocol::FEDERATED);
		}
	}

	$items = conv_sort($items, $order);

	return $items;
}

/**
 * Fetch conversation items
 *
 * @param array $parent
 * @param array $items
 * @param array $condition
 * @param boolean $block_authors
 * @param array $params
 * @return array
 */
function conversation_fetch_items(array $parent, array $items, array $condition, bool $block_authors, array $params) {
	if ($block_authors) {
		$condition[0] .= " AND NOT `author`.`hidden`";
	}

	$thread_items = Item::selectForUser(local_user(), array_merge(Item::DISPLAY_FIELDLIST, ['contact-uid', 'gravity']), $condition, $params);

	$comments = conversation_fetch_comments($thread_items, $parent['pinned'] ?? false);

	if (count($comments) != 0) {
		$items = array_merge($items, $comments);
	}
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

	if (local_user() && local_user() == $item['uid'] && $item['gravity'] == GRAVITY_PARENT && !$item['self']) {
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
		$status_link = $profile_link . '/status';
		$photos_link = str_replace('/profile/', '/photos/', $profile_link);
		$profile_link = $profile_link . '/profile';
	}

	if (!empty($pcid)) {
		$contact_url = 'contact/' . $pcid;
		$posts_link  = $contact_url . '/posts';
		$block_link  = $contact_url . '/block';
		$ignore_link = $contact_url . '/ignore';
	}

	if ($cid && !$item['self']) {
		$contact_url = 'contact/' . $cid;
		$poke_link   = $contact_url . '/poke';
		$posts_link  = $contact_url . '/posts';

		if (in_array($network, [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA])) {
			$pm_url = 'message/new/' . $cid;
		}
	}

	if (local_user()) {
		$menu = [
			DI::l10n()->t('Follow Thread') => $sub_link,
			DI::l10n()->t('View Status') => $status_link,
			DI::l10n()->t('View Profile') => $profile_link,
			DI::l10n()->t('View Photos') => $photos_link,
			DI::l10n()->t('Network Posts') => $posts_link,
			DI::l10n()->t('View Contact') => $contact_url,
			DI::l10n()->t('Send PM') => $pm_url,
			DI::l10n()->t('Block') => $block_link,
			DI::l10n()->t('Ignore') => $ignore_link
		];

		if ($network == Protocol::DFRN) {
			$menu[DI::l10n()->t("Poke")] = $poke_link;
		}

		if ((($cid == 0) || ($rel == Contact::FOLLOWER)) &&
			in_array($item['network'], Protocol::FEDERATED)) {
			$menu[DI::l10n()->t('Connect/Follow')] = 'follow?url=' . urlencode($item['author-link']);
		}
	} else {
		$menu = [DI::l10n()->t('View Profile') => $item['author-link']];
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
 * Checks item to see if it is one of the builtin activities (like/dislike, event attendance, consensus items, etc.)
 *
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
				$verb = Activity::LIKE;
				break;
			case 'dislike':
				$verb = Activity::DISLIKE;
				break;
			case 'attendyes':
				$verb = Activity::ATTEND;
				break;
			case 'attendno':
				$verb = Activity::ATTENDNO;
				break;
			case 'attendmaybe':
				$verb = Activity::ATTENDMAYBE;
				break;
			case 'announce':
				$verb = Activity::ANNOUNCE;
				break;
			default:
				return;
		}

		if (!empty($item['verb']) && DI::activity()->match($item['verb'], $verb) && ($item['gravity'] != GRAVITY_PARENT)) {
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
				$phrase = DI::l10n()->t('%s likes this.', $likers);
				break;
			case 'dislike' :
				$phrase = DI::l10n()->t('%s doesn\'t like this.', $likers);
				break;
			case 'attendyes' :
				$phrase = DI::l10n()->t('%s attends.', $likers);
				break;
			case 'attendno' :
				$phrase = DI::l10n()->t('%s doesn\'t attend.', $likers);
				break;
			case 'attendmaybe' :
				$phrase = DI::l10n()->t('%s attends maybe.', $likers);
				break;
			case 'announce' :
				$phrase = DI::l10n()->t('%s reshared this.', $likers);
				break;
		}
	}

	if ($cnt > 1) {
		$total = count($arr);
		if ($total < MAX_LIKERS) {
			$last = DI::l10n()->t('and') . ' ' . $arr[count($arr)-1];
			$arr2 = array_slice($arr, 0, -1);
			$likers = implode(', ', $arr2) . ' ' . $last;
		} else  {
			$arr = array_slice($arr, 0, MAX_LIKERS - 1);
			$likers = implode(', ', $arr);
			$likers .= DI::l10n()->t('and %d other people', $total - MAX_LIKERS);
		}

		$spanatts = "class=\"fakelink\" onclick=\"openClose('{$type}list-$id');\"";

		$explikers = '';
		switch ($type) {
			case 'like':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> like this', $spanatts, $cnt);
				$explikers = DI::l10n()->t('%s like this.', $likers);
				break;
			case 'dislike':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> don\'t like this', $spanatts, $cnt);
				$explikers = DI::l10n()->t('%s don\'t like this.', $likers);
				break;
			case 'attendyes':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> attend', $spanatts, $cnt);
				$explikers = DI::l10n()->t('%s attend.', $likers);
				break;
			case 'attendno':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> don\'t attend', $spanatts, $cnt);
				$explikers = DI::l10n()->t('%s don\'t attend.', $likers);
				break;
			case 'attendmaybe':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> attend maybe', $spanatts, $cnt);
				$explikers = DI::l10n()->t('%s attend maybe.', $likers);
				break;
			case 'announce':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> reshared this', $spanatts, $cnt);
				$explikers = DI::l10n()->t('%s reshared this.', $likers);
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
	DI::page()['htmlhead'] .= Renderer::replaceMacros($tpl, [
		'$newpost'   => 'true',
		'$baseurl'   => DI::baseUrl()->get(true),
		'$geotag'    => $geotag,
		'$nickname'  => $x['nickname'],
		'$ispublic'  => DI::l10n()->t('Visible to <strong>everybody</strong>'),
		'$linkurl'   => DI::l10n()->t('Please enter a image/video/audio/webpage URL:'),
		'$term'      => DI::l10n()->t('Tag term:'),
		'$fileas'    => DI::l10n()->t('Save to Folder:'),
		'$whereareu' => DI::l10n()->t('Where are you right now?'),
		'$delitems'  => DI::l10n()->t("Delete item\x28s\x29?")
	]);

	$jotplugins = '';
	Hook::callAll('jot_tool', $jotplugins);

	// Private/public post links for the non-JS ACL form
	$private_post = 1;
	if (!empty($_REQUEST['public'])) {
		$private_post = 0;
	}

	$query_str = DI::args()->getQueryString();
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
		'$new_post' => DI::l10n()->t('New Post'),
		'$return_path'  => $query_str,
		'$action'       => 'item',
		'$share'        => ($x['button'] ?? '') ?: DI::l10n()->t('Share'),
		'$loading'      => DI::l10n()->t('Loading...'),
		'$upload'       => DI::l10n()->t('Upload photo'),
		'$shortupload'  => DI::l10n()->t('upload photo'),
		'$attach'       => DI::l10n()->t('Attach file'),
		'$shortattach'  => DI::l10n()->t('attach file'),
		'$edbold'       => DI::l10n()->t('Bold'),
		'$editalic'     => DI::l10n()->t('Italic'),
		'$eduline'      => DI::l10n()->t('Underline'),
		'$edquote'      => DI::l10n()->t('Quote'),
		'$edcode'       => DI::l10n()->t('Code'),
		'$edimg'        => DI::l10n()->t('Image'),
		'$edurl'        => DI::l10n()->t('Link'),
		'$edattach'     => DI::l10n()->t('Link or Media'),
		'$setloc'       => DI::l10n()->t('Set your location'),
		'$shortsetloc'  => DI::l10n()->t('set location'),
		'$noloc'        => DI::l10n()->t('Clear browser location'),
		'$shortnoloc'   => DI::l10n()->t('clear location'),
		'$title'        => $x['title'] ?? '',
		'$placeholdertitle' => DI::l10n()->t('Set title'),
		'$category'     => $x['category'] ?? '',
		'$placeholdercategory' => Feature::isEnabled(local_user(), 'categories') ? DI::l10n()->t("Categories \x28comma-separated list\x29") : '',
		'$wait'         => DI::l10n()->t('Please wait'),
		'$permset'      => DI::l10n()->t('Permission settings'),
		'$shortpermset' => DI::l10n()->t('permissions'),
		'$wall'         => $notes_cid ? 0 : 1,
		'$posttype'     => $notes_cid ? Item::PT_PERSONAL_NOTE : Item::PT_ARTICLE,
		'$content'      => $x['content'] ?? '',
		'$post_id'      => $x['post_id'] ?? '',
		'$baseurl'      => DI::baseUrl()->get(true),
		'$defloc'       => $x['default_location'],
		'$visitor'      => $x['visitor'],
		'$pvisit'       => $notes_cid ? 'none' : $x['visitor'],
		'$public'       => DI::l10n()->t('Public post'),
		'$lockstate'    => $x['lockstate'],
		'$bang'         => $x['bang'],
		'$profile_uid'  => $x['profile_uid'],
		'$preview'      => DI::l10n()->t('Preview'),
		'$jotplugins'   => $jotplugins,
		'$notes_cid'    => $notes_cid,
		'$sourceapp'    => DI::l10n()->t($a->sourcename),
		'$cancel'       => DI::l10n()->t('Cancel'),
		'$rand_num'     => Crypto::randomDigits(12),

		// ACL permissions box
		'$acl'           => $x['acl'],
		'$group_perms'   => DI::l10n()->t('Post to Groups'),
		'$contact_perms' => DI::l10n()->t('Post to Contacts'),
		'$private'       => DI::l10n()->t('Private post'),
		'$is_private'    => $private_post,
		'$public_link'   => $public_post_link,

		//jot nav tab (used in some themes)
		'$message' => DI::l10n()->t('Message'),
		'$browser' => DI::l10n()->t('Browser'),

		'$compose_link_title' => DI::l10n()->t('Open Compose page'),
	]);


	if ($popup == true) {
		$o = '<div id="jot-popup" style="display: none;">' . $o . '</div>';
	}

	return $o;
}

/**
 * Plucks the children of the given parent from a given item list.
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
		if ($item['gravity'] != GRAVITY_PARENT) {
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
 * Recursively sorts a tree-like item array
 *
 * @param array $items
 * @return array
 */
function sort_item_children(array $items)
{
	$result = $items;
	usort($result, 'sort_thr_received_rev');
	foreach ($result as $k => $i) {
		if (isset($result[$k]['children'])) {
			$result[$k]['children'] = sort_item_children($result[$k]['children']);
		}
	}
	return $result;
}

/**
 * Recursively add all children items at the top level of a list
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
 * Selectively flattens a tree-like item structure to prevent threading stairs
 *
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
				return $var['verb'] === Activity::POST;
			};

			$child_post_count = count(array_filter($child['children'], $count_post_closure));

			$remaining_post_count = count(array_filter(array_slice($parent['children'], $i), $count_post_closure));

			// If there's only one child's children post and this is the last child post
			if ($child_post_count == 1 && $remaining_post_count == 1) {

				// Searches the post item in the children
				$j = 0;
				while($child['children'][$j]['verb'] !== Activity::POST && $j < count($child['children'])) {
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
 * Expands a flat list of items into corresponding tree-like conversation structures.
 *
 * sort the top-level posts either on "received" or "commented", and finally
 * append all the items at the top level (???)
 *
 * @param array  $item_list A list of items belonging to one or more conversations
 * @param string $order     Either on "received" or "commented"
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
		if ($item['gravity'] == GRAVITY_PARENT) {
			$parents[] = $item;
		}
	}

	if (stristr($order, 'pinned_received')) {
		usort($parents, 'sort_thr_pinned_received');
	} elseif (stristr($order, 'received')) {
		usort($parents, 'sort_thr_received');
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

	if (!DI::pConfig()->get(local_user(), 'system', 'no_smart_threading', 0)) {
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
 * usort() callback to sort item arrays by pinned and the received key
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function sort_thr_pinned_received(array $a, array $b)
{
	if ($b['pinned'] && !$a['pinned']) {
		return 1;
	} elseif (!$b['pinned'] && $a['pinned']) {
		return -1;
	}

	return strcmp($b['received'], $a['received']);
}

/**
 * usort() callback to sort item arrays by the received key
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function sort_thr_received(array $a, array $b)
{
	return strcmp($b['received'], $a['received']);
}

/**
 * usort() callback to reverse sort item arrays by the received key
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function sort_thr_received_rev(array $a, array $b)
{
	return strcmp($a['received'], $b['received']);
}

/**
 * usort() callback to sort item arrays by the commented key
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
