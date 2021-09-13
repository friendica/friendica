<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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
use Friendica\BaseModule;
use Friendica\Content\ContactSelector;
use Friendica\Content\Feature;
use Friendica\Core\ACL;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session;
use Friendica\Core\Theme;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Model\Verb;
use Friendica\Object\Post as PostObject;
use Friendica\Object\Thread;
use Friendica\Protocol\Activity;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Friendica\Util\Temporal;
use Friendica\Util\XML;

/**
 * Render actions localized
 *
 * @param $item
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function localize_item(&$item)
{
	DI::profiler()->startRecording('rendering');
	/// @todo The following functionality needs to be cleaned up.
	if (!empty($item['verb'])) {
		$activity = DI::activity();

		$xmlhead = "<" . "?xml version='1.0' encoding='UTF-8' ?" . ">";

		if (stristr($item['verb'], Activity::POKE)) {
			$verb = urldecode(substr($item['verb'], strpos($item['verb'],'#') + 1));
			if (!$verb) {
				DI::profiler()->stopRecording();
				return;
			}
			if ($item['object-type'] == "" || $item['object-type'] !== Activity\ObjectType::PERSON) {
				DI::profiler()->stopRecording();
				return;
			}

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

			$author = ['uid' => 0, 'id' => $item['author-id'],
				'network' => $item['author-network'], 'url' => $item['author-link']];
			$A = '[url=' . Contact::magicLinkByContact($author) . ']' . $item['author-name'] . '[/url]';

			if (!empty($Blink)) {
				$B = '[url=' . Contact::magicLink($Blink) . ']' . $Bname . '[/url]';
			} else {
				$B = '';
			}

			if ($Bphoto != "" && !empty($Blink)) {
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
			$obj = Post::selectFirst($fields, ['uri' => $item['parent-uri']]);
			if (!DBA::isResult($obj)) {
				DI::profiler()->stopRecording();
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

	// add sparkle links to appropriate permalinks
	// Only create a redirection to a magic link when logged in
	if (!empty($item['plink']) && Session::isAuthenticated()) {
		$author = ['uid' => 0, 'id' => $item['author-id'],
			'network' => $item['author-network'], 'url' => $item['author-link']];
		$item['plink'] = Contact::magicLinkByContact($author, $item['plink']);
	}
	DI::profiler()->stopRecording();
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

	$str_blocked = str_replace(["\n", "\r"], ",", DI::pConfig()->get(local_user(), 'system', 'blocked'));
	if (empty($str_blocked)) {
		return [];
	}

	$blocklist = [];

	foreach (explode(',', $str_blocked) as $entry) {
		$cid = Contact::getIdForURL(trim($entry), 0, false);
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
	DI::profiler()->startRecording('rendering');
	$page = DI::page();

	$page->registerFooterScript(Theme::getPathForFile('asset/typeahead.js/dist/typeahead.bundle.js'));
	$page->registerFooterScript(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.js'));
	$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput.css'));
	$page->registerStylesheet(Theme::getPathForFile('js/friendica-tagsinput/friendica-tagsinput-typeahead.css'));

	$ssl_state = (local_user() ? true : false);

	$live_update_div = '';

	$blocklist = conv_get_blocklist();

	$previewing = (($preview) ? ' preview ' : '');

	if ($mode === 'network') {
		$items = conversation_add_children($items, false, $order, $uid);
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
					. "<script> var profile_uid = " . $uid
					. "; var netargs = '?f='; </script>\r\n";
			}
		}
	} elseif ($mode === 'notes') {
		$items = conversation_add_children($items, false, $order, local_user());

		if (!$update) {
			$live_update_div = '<div id="live-notes"></div>' . "\r\n"
				. "<script> var profile_uid = " . local_user()
				. "; var netargs = '/?f='; </script>\r\n";
		}
	} elseif ($mode === 'display') {
		$items = conversation_add_children($items, false, $order, $uid);

		if (!$update) {
			$live_update_div = '<div id="live-display"></div>' . "\r\n"
				. "<script> var profile_uid = " . Session::get('uid', 0) . ";"
				. "</script>";
		}
	} elseif ($mode === 'community') {
		$items = conversation_add_children($items, true, $order, $uid);

		if (!$update) {
			$live_update_div = '<div id="live-community"></div>' . "\r\n"
				. "<script> var profile_uid = -1; var netargs = '" . substr(DI::args()->getCommand(), 10)
				. '?f='
				. (!empty($_GET['no_sharer']) ? '&no_sharer=' . rawurlencode($_GET['no_sharer']) : '')
				. "'; </script>\r\n";
		}
	} elseif ($mode === 'contacts') {
		$items = conversation_add_children($items, false, $order, $uid);

		if (!$update) {
			$live_update_div = '<div id="live-contact"></div>' . "\r\n"
				. "<script> var profile_uid = -1; var netargs = '" . substr(DI::args()->getCommand(), 8)
				."/?f='; </script>\r\n";
		}
	} elseif ($mode === 'search') {
		$live_update_div = '<div id="live-search"></div>' . "\r\n";
	}

	$page_dropping = ((local_user() && local_user() == $uid) ? true : false);

	if (!$update) {
		$_SESSION['return_path'] = DI::args()->getQueryString();
	}

	$cb = ['items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview];
	Hook::callAll('conversation_start', $cb);

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
	$formSecurityToken = BaseModule::getFormSecurityToken('contact_action');

	if (!empty($items)) {
		if (in_array($mode, ['community', 'contacts'])) {
			$writable = true;
		} else {
			$writable = ($items[0]['uid'] == 0) && in_array($items[0]['network'], Protocol::FEDERATED);
		}

		if (!local_user()) {
			$writable = false;
		}

		if (in_array($mode, ['filed', 'search', 'contact-posts'])) {

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

				$sparkle = '';
				if (strpos($profile_link, 'redir/') === 0) {
					$sparkle = ' sparkle';
				}

				$locate = ['location' => $item['location'], 'coord' => $item['coord'], 'html' => ''];
				Hook::callAll('render_location', $locate);
				$location_html = $locate['html'] ?: Strings::escapeHtml($locate['location'] ?: $locate['coord'] ?: '');

				localize_item($item);
				if ($mode === 'filed') {
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

				$likebuttons = [
					'like'     => null,
					'dislike'  => null,
					'share'    => null,
					'announce' => null,
				];

				if (DI::pConfig()->get(local_user(), 'system', 'hide_dislike')) {
					unset($likebuttons['dislike']);
				}

				$body_html = Item::prepareBody($item, true, $preview);

				list($categories, $folders) = DI::contentItem()->determineCategoriesTerms($item, local_user());

				if (!empty($item['content-warning']) && DI::pConfig()->get(local_user(), 'system', 'disable_cw', false)) {
					$title = ucfirst($item['content-warning']);
				} else {
					$title = $item['title'];
				}

				$tmp_item = [
					'template' => $tpl,
					'id' => ($preview ? 'P0' : $item['id']),
					'guid' => ($preview ? 'Q0' : $item['guid']),
					'commented' => $item['commented'],
					'received' => $item['received'],
					'created_date' => $item['created'],
					'uriid' => $item['uri-id'],
					'network' => $item['network'],
					'network_name' => ContactSelector::networkToName($item['author-network'], $item['author-link'], $item['network']),
					'network_icon' => ContactSelector::networkToIcon($item['network'], $item['author-link']),
					'linktitle' => DI::l10n()->t('View %s\'s profile @ %s', $profile_name, $item['author-link']),
					'profile_url' => $profile_link,
					'item_photo_menu_html' => item_photo_menu($item, $formSecurityToken),
					'name' => $profile_name,
					'sparkle' => $sparkle,
					'lock' => false,
					'thumb' => DI::baseUrl()->remove(Contact::getAvatarUrlForUrl($item['author-link'], $item['uid'], Proxy::SIZE_THUMB)),
					'title' => $title,
					'body_html' => $body_html,
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
					'text' => strip_tags($body_html),
					'localtime' => DateTimeFormat::local($item['created'], 'r'),
					'ago' => (($item['app']) ? DI::l10n()->t('%s from %s', Temporal::getRelativeDate($item['created']), $item['app']) : Temporal::getRelativeDate($item['created'])),
					'location_html' => $location_html,
					'indent' => '',
					'owner_name' => '',
					'owner_url' => '',
					'owner_photo' => DI::baseUrl()->remove(Contact::getAvatarUrlForUrl($item['owner-link'], $item['uid'], Proxy::SIZE_THUMB)),
					'plink' => Item::getPlink($item),
					'edpost' => false,
					'isstarred' => 'unstarred',
					'star' => false,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like_html' => '',
					'dislike_html' => '',
					'comment_html' => '',
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
					$item_object = new PostObject($item);
					$conv->addParent($item_object);
				}
			}

			$threads = $conv->getTemplateData($conv_responses, $formSecurityToken);
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
		'$update' => $update,
		'$threads' => $threads,
		'$dropping' => ($page_dropping ? DI::l10n()->t('Delete Selected Items') : False),
	]);

	DI::profiler()->stopRecording();
	return $o;
}

/**
 * Adds some information (Causer, post reason, direction) to the fetched post row.
 *
 * @param array   $row      Post row
 * @param array   $activity Contact data of the resharer
 *
 * @return array items with parents and comments
 */
function conversation_add_row_information(array $row, array $activity) {
	DI::profiler()->startRecording('rendering');

	if ($row['uid'] == 0) {
		$row['writable'] = in_array($row['network'], Protocol::FEDERATED);
	}

	if (!empty($activity)) {
		if (($row['gravity'] == GRAVITY_PARENT)) {
			$row['post-reason'] = Item::PR_ANNOUNCEMENT;
			$row = array_merge($row, $activity);
			$contact = Contact::getById($activity['causer-id'], ['url', 'name', 'thumb']);
			$row['causer-link'] = $contact['url'];
			$row['causer-avatar'] = $contact['thumb'];
			$row['causer-name'] = $contact['name'];
		} elseif (($row['gravity'] == GRAVITY_ACTIVITY) && ($row['verb'] == Activity::ANNOUNCE) &&
			($row['author-id'] == $activity['causer-id'])) {
			return $row;
		}
	}

	switch ($row['post-reason']) {
		case Item::PR_TO:
			$row['direction'] = ['direction' => 7, 'title' => DI::l10n()->t('You had been addressed (%s).', 'to')];
			break;
		case Item::PR_CC:
			$row['direction'] = ['direction' => 7, 'title' => DI::l10n()->t('You had been addressed (%s).', 'cc')];
			break;
		case Item::PR_BTO:
			$row['direction'] = ['direction' => 7, 'title' => DI::l10n()->t('You had been addressed (%s).', 'bto')];
			break;
		case Item::PR_BCC:
			$row['direction'] = ['direction' => 7, 'title' => DI::l10n()->t('You had been addressed (%s).', 'bcc')];
			break;
		case Item::PR_FOLLOWER:
			$row['direction'] = ['direction' => 6, 'title' => DI::l10n()->t('You are following %s.', $row['author-name'])];
			break;
		case Item::PR_TAG:
			$row['direction'] = ['direction' => 4, 'title' => DI::l10n()->t('Tagged')];
			break;
		case Item::PR_ANNOUNCEMENT:
			if (!empty($row['causer-id']) && DI::pConfig()->get(local_user(), 'system', 'display_resharer')) {
				$row['owner-id'] = $row['causer-id'];
				$row['owner-link'] = $row['causer-link'];
				$row['owner-avatar'] = $row['causer-avatar'];
				$row['owner-name'] = $row['causer-name'];
			}

			if (($row['gravity'] == GRAVITY_PARENT) && !empty($row['causer-id'])) {
				$causer = ['uid' => 0, 'id' => $row['causer-id'],
					'network' => $row['causer-network'], 'url' => $row['causer-link']];
				$row['reshared'] = DI::l10n()->t('%s reshared this.', '<a href="'. htmlentities(Contact::magicLinkByContact($causer)) .'">' . htmlentities($row['causer-name']) . '</a>');
			}
			$row['direction'] = ['direction' => 3, 'title' => (empty($row['causer-id']) ? DI::l10n()->t('Reshared') : DI::l10n()->t('Reshared by %s <%s>', $row['causer-name'], $row['causer-link']))];
			break;
		case Item::PR_COMMENT:
			$row['direction'] = ['direction' => 5, 'title' => DI::l10n()->t('%s is participating in this thread.', $row['author-name'])];
			break;
		case Item::PR_STORED:
			$row['direction'] = ['direction' => 8, 'title' => DI::l10n()->t('Stored')];
			break;
		case Item::PR_GLOBAL:
			$row['direction'] = ['direction' => 9, 'title' => DI::l10n()->t('Global')];
			break;
		case Item::PR_RELAY:
			$row['direction'] = ['direction' => 10, 'title' => (empty($row['causer-id']) ? DI::l10n()->t('Relayed') : DI::l10n()->t('Relayed by %s <%s>', $row['causer-name'], $row['causer-link']))];
			break;
		case Item::PR_FETCHED:
			$row['direction'] = ['direction' => 2, 'title' => (empty($row['causer-id']) ? DI::l10n()->t('Fetched') : DI::l10n()->t('Fetched because of %s <%s>', $row['causer-name'], $row['causer-link']))];
			break;
	}

	DI::profiler()->stopRecording();
	return $row;
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
	DI::profiler()->startRecording('rendering');
	if (count($parents) > 1) {
		$max_comments = DI::config()->get('system', 'max_comments', 100);
	} else {
		$max_comments = DI::config()->get('system', 'max_display_comments', 1000);
	}

	$params = ['order' => ['uri-id' => true, 'uid' => true]];

	$activities      = [];
	$uriids          = [];
	$commentcounter  = [];
	$activitycounter = [];

	foreach ($parents AS $parent) {
		if (!empty($parent['thr-parent-id']) && !empty($parent['gravity']) && ($parent['gravity'] == GRAVITY_ACTIVITY)) {
			$uriid = $parent['thr-parent-id'];
			if (!empty($parent['author-id'])) {
				$activities[$uriid] = ['causer-id' => $parent['author-id']];
				foreach (['commented', 'received', 'created'] as $orderfields) {
					if (!empty($parent[$orderfields])) {
						$activities[$uriid][$orderfields] = $parent[$orderfields];
					}
				}
			}
		} else {
			$uriid = $parent['uri-id'];
		}
		$uriids[] = $uriid;

		$commentcounter[$uriid]  = 0;
		$activitycounter[$uriid] = 0;
	}

	$condition = ['parent-uri-id' => $uriids];
	if ($block_authors) {
		$condition['author-hidden'] = false;
	}

	$condition = DBA::mergeConditions($condition,
		["`uid` IN (0, ?) AND (`vid` != ? OR `vid` IS NULL)", $uid, Verb::getID(Activity::FOLLOW)]);

	$thread_items = Post::selectForUser(local_user(), array_merge(Item::DISPLAY_FIELDLIST, ['pinned', 'contact-uid', 'gravity', 'post-type', 'post-reason']), $condition, $params);

	$items = [];

	while ($row = Post::fetch($thread_items)) {
		if (!empty($items[$row['uri-id']]) && ($row['uid'] == 0)) {
			continue;
		}

		if ($max_comments > 0) {
			if (($row['gravity'] == GRAVITY_COMMENT) && (++$commentcounter[$row['parent-uri-id']] > $max_comments)) {
				continue;
			}
			if (($row['gravity'] == GRAVITY_ACTIVITY) && (++$activitycounter[$row['parent-uri-id']] > $max_comments)) {
				continue;
			}
		}
		$items[$row['uri-id']] = conversation_add_row_information($row, $activities[$row['uri-id']] ?? []);
	}

	DBA::close($thread_items);

	$items = conv_sort($items, $order);

	DI::profiler()->stopRecording();
	return $items;
}

function item_photo_menu($item, string $formSecurityToken)
{
	DI::profiler()->startRecording('rendering');
	$sub_link = '';
	$poke_link = '';
	$contact_url = '';
	$pm_url = '';
	$status_link = '';
	$photos_link = '';
	$posts_link = '';
	$block_link = '';
	$ignore_link = '';

	if (local_user() && local_user() == $item['uid'] && $item['gravity'] == GRAVITY_PARENT && !$item['self'] && !$item['mention']) {
		$sub_link = 'javascript:doFollowThread(' . $item['id'] . '); return false;';
	}

	$author = ['uid' => 0, 'id' => $item['author-id'],
		'network' => $item['author-network'], 'url' => $item['author-link']];
	$profile_link = Contact::magicLinkByContact($author, $item['author-link']);
	$sparkle = (strpos($profile_link, 'redir/') === 0);

	$cid = 0;
	$pcid = $item['author-id'];
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
		$block_link  = $item['self'] ? '' : $contact_url . '/block?t=' . $formSecurityToken;
		$ignore_link = $item['self'] ? '' : $contact_url . '/ignore?t=' . $formSecurityToken;
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

		if (!empty($item['language'])) {
			$menu[DI::l10n()->t('Languages')] = 'javascript:alert(\'' . Item::getLanguageMessage($item) . '\');';
		}

		if ($network == Protocol::DFRN) {
			$menu[DI::l10n()->t("Poke")] = $poke_link;
		}

		if ((($cid == 0) || ($rel == Contact::FOLLOWER)) &&
			in_array($item['network'], Protocol::FEDERATED)) {
			$menu[DI::l10n()->t('Connect/Follow')] = 'follow?url=' . urlencode($item['author-link']) . '&auto=1';
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
		} elseif ($v) {
			$o .= '<li role="menuitem"><a href="' . $v . '">' . $k . '</a></li>' . PHP_EOL;
		}
	}
	DI::profiler()->stopRecording();
	return $o;
}

/**
 * Checks item to see if it is one of the builtin activities (like/dislike, event attendance, consensus items, etc.)
 *
 * Increments the count of each matching activity and adds a link to the author as needed.
 *
 * @param array  $activity
 * @param array &$conv_responses (already created with builtin activity structure)
 * @return void
 * @throws ImagickException
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function builtin_activity_puller(array $activity, array &$conv_responses)
{
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

		if (!empty($activity['verb']) && DI::activity()->match($activity['verb'], $verb) && ($activity['gravity'] != GRAVITY_PARENT)) {
			$author = [
				'uid' => 0,
				'id' => $activity['author-id'],
				'network' => $activity['author-network'],
				'url' => $activity['author-link']
			];
			$url = Contact::magicLinkByContact($author);
			if (strpos($url, 'redir/') === 0) {
				$sparkle = ' class="sparkle" ';
			}

			$link = '<a href="' . $url . '"' . $sparkle . '>' . htmlentities($activity['author-name']) . '</a>';

			if (empty($activity['thr-parent-id'])) {
				$activity['thr-parent-id'] = $activity['parent-uri-id'];
			}

			// Skip when the causer of the parent is the same than the author of the announce
			if (($verb == Activity::ANNOUNCE) && Post::exists(['uri-id' => $activity['thr-parent-id'],
				'uid' => $activity['uid'], 'causer-id' => $activity['author-id'], 'gravity' => GRAVITY_PARENT])) {
				continue;
			}

			if (!isset($conv_responses[$mode][$activity['thr-parent-id']])) {
				$conv_responses[$mode][$activity['thr-parent-id']] = [
					'links' => [],
					'self' => 0,
				];
			} elseif (in_array($link, $conv_responses[$mode][$activity['thr-parent-id']]['links'])) {
				// only list each unique author once
				continue;
			}

			if (public_contact() == $activity['author-id']) {
				$conv_responses[$mode][$activity['thr-parent-id']]['self'] = 1;
			}

			$conv_responses[$mode][$activity['thr-parent-id']]['links'][] = $link;

			// there can only be one activity verb per item so if we found anything, we can stop looking
			return;
		}
	}
}

/**
 * Format the activity text for an item/photo/video
 *
 * @param array  $links = array of pre-linked names of actors
 * @param string $verb  = one of 'like, 'dislike', 'attendyes', 'attendno', 'attendmaybe'
 * @param int    $id    = item id
 * @return string formatted text
 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
 */
function format_activity(array $links, $verb, $id) {
	DI::profiler()->startRecording('rendering');
	$o = '';
	$expanded = '';
	$phrase = '';

	$total = count($links);
	if ($total == 1) {
		$likers = $links[0];

		// Phrase if there is only one liker. In other cases it will be uses for the expanded
		// list which show all likers
		switch ($verb) {
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
	} elseif ($total > 1) {
		if ($total < MAX_LIKERS) {
			$likers = implode(', ', array_slice($links, 0, -1));
			$likers .= ' ' . DI::l10n()->t('and') . ' ' . $links[count($links)-1];
		} else  {
			$likers = implode(', ', array_slice($links, 0, MAX_LIKERS - 1));
			$likers .= ' ' . DI::l10n()->t('and %d other people', $total - MAX_LIKERS);
		}

		$spanatts = "class=\"fakelink\" onclick=\"openClose('{$verb}list-$id');\"";

		$explikers = '';
		switch ($verb) {
			case 'like':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> like this', $spanatts, $total);
				$explikers = DI::l10n()->t('%s like this.', $likers);
				break;
			case 'dislike':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> don\'t like this', $spanatts, $total);
				$explikers = DI::l10n()->t('%s don\'t like this.', $likers);
				break;
			case 'attendyes':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> attend', $spanatts, $total);
				$explikers = DI::l10n()->t('%s attend.', $likers);
				break;
			case 'attendno':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> don\'t attend', $spanatts, $total);
				$explikers = DI::l10n()->t('%s don\'t attend.', $likers);
				break;
			case 'attendmaybe':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> attend maybe', $spanatts, $total);
				$explikers = DI::l10n()->t('%s attend maybe.', $likers);
				break;
			case 'announce':
				$phrase = DI::l10n()->t('<span  %1$s>%2$d people</span> reshared this', $spanatts, $total);
				$explikers = DI::l10n()->t('%s reshared this.', $likers);
				break;
		}

		$expanded .= "\t" . '<p class="wall-item-' . $verb . '-expanded" id="' . $verb . 'list-' . $id . '" style="display: none;" >' . $explikers . EOL . '</p>';
	}

	$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('voting_fakelink.tpl'), [
		'$phrase' => $phrase,
		'$type' => $verb,
		'$id' => $id
	]);
	$o .= $expanded;

	DI::profiler()->stopRecording();
	return $o;
}

function status_editor(App $a, array $x = [], $notes_cid = 0, $popup = false)
{
	$user = User::getById($a->getLoggedInUserId(), ['uid', 'nickname', 'allow_location', 'default-location']);
	if (empty($user['uid'])) {
		return '';
	}

	DI::profiler()->startRecording('rendering');
	$o = '';

	$x['allow_location']   = $x['allow_location']   ?? $user['allow_location'];
	$x['default_location'] = $x['default_location'] ?? $user['default-location'];
	$x['nickname']         = $x['nickname']         ?? $user['nickname'];
	$x['lockstate']        = $x['lockstate']        ?? ACL::getLockstateForUserId($user['uid']) ? 'lock' : 'unlock';
	$x['acl']              = $x['acl']              ?? ACL::getFullSelectorHTML(DI::page(), $user['uid'], true);
	$x['bang']             = $x['bang']             ?? '';
	$x['visitor']          = $x['visitor']          ?? 'block';
	$x['is_owner']         = $x['is_owner']         ?? true;
	$x['profile_uid']      = $x['profile_uid']      ?? local_user();


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
		'$delitems'  => DI::l10n()->t("Delete item\x28s\x29?"),
		'$is_mobile' => DI::mode()->isMobile(),
	]);

	$jotplugins = '';
	Hook::callAll('jot_tool', $jotplugins);

	$tpl = Renderer::getMarkupTemplate("jot.tpl");

	$o .= Renderer::replaceMacros($tpl, [
		'$new_post' => DI::l10n()->t('New Post'),
		'$return_path'  => DI::args()->getQueryString(),
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
		'$edvideo'      => DI::l10n()->t('Video'),
		'$setloc'       => DI::l10n()->t('Set your location'),
		'$shortsetloc'  => DI::l10n()->t('set location'),
		'$noloc'        => DI::l10n()->t('Clear browser location'),
		'$shortnoloc'   => DI::l10n()->t('clear location'),
		'$title'        => $x['title'] ?? '',
		'$placeholdertitle' => DI::l10n()->t('Set title'),
		'$category'     => $x['category'] ?? '',
		'$placeholdercategory' => Feature::isEnabled(local_user(), 'categories') ? DI::l10n()->t("Categories \x28comma-separated list\x29") : '',
		'$scheduled_at' => Temporal::getDateTimeField(
			new DateTime(),
			DateTime::createFromFormat(DateTimeFormat::MYSQL, DateTimeFormat::local('now + 6 months')),
			null,
			DI::l10n()->t('Scheduled at'),
			'scheduled_at',
		),
		'$wait'         => DI::l10n()->t('Please wait'),
		'$permset'      => DI::l10n()->t('Permission settings'),
		'$shortpermset' => DI::l10n()->t('Permissions'),
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
		'$cancel'       => DI::l10n()->t('Cancel'),
		'$rand_num'     => Crypto::randomDigits(12),

		// ACL permissions box
		'$acl'           => $x['acl'],

		//jot nav tab (used in some themes)
		'$message' => DI::l10n()->t('Message'),
		'$browser' => DI::l10n()->t('Browser'),

		'$compose_link_title' => DI::l10n()->t('Open Compose page'),
	]);


	if ($popup == true) {
		$o = '<div id="jot-popup" style="display: none;">' . $o . '</div>';
	}

	DI::profiler()->stopRecording();
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
	DI::profiler()->startRecording('rendering');
	$children = [];
	foreach ($item_list as $i => $item) {
		if ($item['gravity'] != GRAVITY_PARENT) {
			if ($recursive) {
				// Fallback to parent-uri if thr-parent is not set
				$thr_parent = $item['thr-parent-id'];
				if ($thr_parent == '') {
					$thr_parent = $item['parent-uri-id'];
				}

				if ($thr_parent == $parent['uri-id']) {
					$item['children'] = get_item_children($item_list, $item);
					$children[] = $item;
					unset($item_list[$i]);
				}
			} elseif ($item['parent-uri-id'] == $parent['uri-id']) {
				$children[] = $item;
				unset($item_list[$i]);
			}
		}
	}
	DI::profiler()->stopRecording();
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
	DI::profiler()->startRecording('rendering');
	$result = $items;
	usort($result, 'sort_thr_received_rev');
	foreach ($result as $k => $i) {
		if (isset($result[$k]['children'])) {
			$result[$k]['children'] = sort_item_children($result[$k]['children']);
		}
	}
	DI::profiler()->stopRecording();
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
	DI::profiler()->startRecording('rendering');
	if (!isset($parent['children']) || count($parent['children']) == 0) {
		DI::profiler()->stopRecording();
		return $parent;
	}

	// We use a for loop to ensure we process the newly-moved items
	for ($i = 0; $i < count($parent['children']); $i++) {
		$child = $parent['children'][$i];

		if (isset($child['children']) && count($child['children'])) {
			// This helps counting only the regular posts
			$count_post_closure = function($var) {
				DI::profiler()->stopRecording();
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

	DI::profiler()->stopRecording();
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
	DI::profiler()->startRecording('rendering');
	$parents = [];

	if (!(is_array($item_list) && count($item_list))) {
		DI::profiler()->stopRecording();
		return $parents;
	}

	$blocklist = conv_get_blocklist();

	$item_array = [];

	// Dedupes the item list on the uri to prevent infinite loops
	foreach ($item_list as $item) {
		if (in_array($item['author-id'], $blocklist)) {
			continue;
		}

		$item_array[$item['uri-id']] = $item;
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

	DI::profiler()->stopRecording();
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
