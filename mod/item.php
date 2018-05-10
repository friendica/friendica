<?php
/**
 * @file mod/item.php
 */

/*
 * This is the POST destination for most all locally posted
 * text stuff. This function handles status, wall-to-wall status,
 * local comments, and remote coments that are posted on this site
 * (as opposed to being delivered in a feed).
 * Also processed here are posts and comments coming through the
 * statusnet/twitter API.
 *
 * All of these become an "item" which is our basic unit of
 * information.
 */

use Friendica\App;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Addon;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBM;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Protocol\Diaspora;
use Friendica\Protocol\Email;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Emailer;

require_once 'include/enotify.php';
require_once 'include/text.php';
require_once 'include/items.php';

function item_post(App $a) {
	if (!local_user() && !remote_user()) {
		return;
	}

	require_once 'include/security.php';

	$uid = local_user();

	if (x($_REQUEST, 'dropitems')) {
		$arr_drop = explode(',', $_REQUEST['dropitems']);
		drop_items($arr_drop);
		$json = ['success' => 1];
		echo json_encode($json);
		killme();
	}

	Addon::callHooks('post_local_start', $_REQUEST);

	logger('postvars ' . print_r($_REQUEST,true), LOGGER_DATA);

	$api_source = defaults($_REQUEST, 'api_source', false);

	$message_id = ((x($_REQUEST, 'message_id') && $api_source) ? strip_tags($_REQUEST['message_id']) : '');

	$return_path = defaults($_REQUEST, 'return', '');
	$preview = intval(defaults($_REQUEST, 'preview', 0));

	/*
	 * Check for doubly-submitted posts, and reject duplicates
	 * Note that we have to ignore previews, otherwise nothing will post
	 * after it's been previewed
	 */
	if (!$preview && x($_REQUEST, 'post_id_random')) {
		if (x($_SESSION, 'post-random') && $_SESSION['post-random'] == $_REQUEST['post_id_random']) {
			logger("item post: duplicate post", LOGGER_DEBUG);
			item_post_return(System::baseUrl(), $api_source, $return_path);
		} else {
			$_SESSION['post-random'] = $_REQUEST['post_id_random'];
		}
	}

	// Is this a reply to something?
	$thr_parent = intval(defaults($_REQUEST, 'parent', 0));
	$thr_parent_uri = trim(defaults($_REQUEST, 'parent_uri', ''));

	$thr_parent_contact = null;

	$parent = 0;
	$parent_item = null;
	$parent_user = null;

	$parent_contact = null;

	$objecttype = null;
	$profile_uid = defaults($_REQUEST, 'profile_uid', local_user());

	if ($thr_parent || $thr_parent_uri) {
		if ($thr_parent) {
			$parent_item = dba::selectFirst('item', [], ['id' => $thr_parent]);
		} elseif ($thr_parent_uri) {
			$parent_item = dba::selectFirst('item', [], ['uri' => $thr_parent_uri, 'uid' => $profile_uid]);
		}

		// if this isn't the real parent of the conversation, find it
		if (DBM::is_result($parent_item)) {

			// The URI and the contact is taken from the direct parent which needn't to be the top parent
			$thr_parent_uri = $parent_item['uri'];
			$thr_parent_contact = Contact::getDetailsByURL($parent_item["author-link"]);

			if ($parent_item['id'] != $parent_item['parent']) {
				$parent_item = dba::selectFirst('item', [], ['id' => $parent_item['parent']]);
			}
		}

		if (!DBM::is_result($parent_item)) {
			notice(L10n::t('Unable to locate original post.') . EOL);
			if (x($_REQUEST, 'return')) {
				goaway($return_path);
			}
			killme();
		}

		$parent = $parent_item['id'];
		$parent_user = $parent_item['uid'];

		$parent_contact = Contact::getDetailsByURL($parent_item["author-link"]);

		$objecttype = ACTIVITY_OBJ_COMMENT;

		if (!x($_REQUEST, 'type')) {
			$_REQUEST['type'] = 'net-comment';
		}
	}

	if ($parent) {
		logger('mod_item: item_post parent=' . $parent);
	}

	$post_id     = intval(defaults($_REQUEST, 'post_id', 0));
	$app         = strip_tags(defaults($_REQUEST, 'source', ''));
	$extid       = strip_tags(defaults($_REQUEST, 'extid', ''));
	$object      = defaults($_REQUEST, 'object', '');

	// Ensure that the user id in a thread always stay the same
	if (!is_null($parent_user) && in_array($parent_user, [local_user(), 0])) {
		$profile_uid = $parent_user;
	}

	// Check for multiple posts with the same message id (when the post was created via API)
	if (($message_id != '') && ($profile_uid != 0)) {
		if (dba::exists('item', ['uri' => $message_id, 'uid' => $profile_uid])) {
			logger("Message with URI ".$message_id." already exists for user ".$profile_uid, LOGGER_DEBUG);
			return;
		}
	}

	// Allow commenting if it is an answer to a public post
	$allow_comment = local_user() && ($profile_uid == 0) && $parent && in_array($parent_item['network'], [NETWORK_OSTATUS, NETWORK_DIASPORA, NETWORK_DFRN]);

	// Now check that valid personal details have been provided
	if (!can_write_wall($profile_uid) && !$allow_comment) {
		notice(L10n::t('Permission denied.') . EOL) ;
		if (x($_REQUEST, 'return')) {
			goaway($return_path);
		}
		killme();
	}


	// is this an edited post?

	$orig_post = null;

	if ($post_id) {
		$orig_post = dba::selectFirst('item', [], ['id' => $post_id]);
	}

	$user = dba::selectFirst('user', [], ['uid' => $profile_uid]);
	if (!DBM::is_result($user) && !$parent) {
		return;
	}

	if ($orig_post) {
		$str_group_allow   = $orig_post['allow_gid'];
		$str_contact_allow = $orig_post['allow_cid'];
		$str_group_deny    = $orig_post['deny_gid'];
		$str_contact_deny  = $orig_post['deny_cid'];
		$location          = $orig_post['location'];
		$coord             = $orig_post['coord'];
		$verb              = $orig_post['verb'];
		$objecttype        = $orig_post['object-type'];
		$emailcc           = $orig_post['emailcc'];
		$app               = $orig_post['app'];
		$categories        = $orig_post['file'];
		$title             = notags(trim($_REQUEST['title']));
		$body              = escape_tags(trim($_REQUEST['body']));
		$private           = $orig_post['private'];
		$pubmail_enabled   = $orig_post['pubmail'];
		$network           = $orig_post['network'];
		$guid              = $orig_post['guid'];
		$extid             = $orig_post['extid'];

	} else {

		/*
		 * if coming from the API and no privacy settings are set,
		 * use the user default permissions - as they won't have
		 * been supplied via a form.
		 */
		/// @TODO use x($_REQUEST, 'foo') here
		if ($api_source
			&& !array_key_exists('contact_allow', $_REQUEST)
			&& !array_key_exists('group_allow', $_REQUEST)
			&& !array_key_exists('contact_deny', $_REQUEST)
			&& !array_key_exists('group_deny', $_REQUEST)) {
			$str_group_allow   = $user['allow_gid'];
			$str_contact_allow = $user['allow_cid'];
			$str_group_deny    = $user['deny_gid'];
			$str_contact_deny  = $user['deny_cid'];
		} else {
			// use the posted permissions
			$str_group_allow   = perms2str($_REQUEST['group_allow']);
			$str_contact_allow = perms2str($_REQUEST['contact_allow']);
			$str_group_deny    = perms2str($_REQUEST['group_deny']);
			$str_contact_deny  = perms2str($_REQUEST['contact_deny']);
		}

		$title             = notags(trim($_REQUEST['title']));
		$location          = notags(trim($_REQUEST['location']));
		$coord             = notags(trim($_REQUEST['coord']));
		$verb              = notags(trim($_REQUEST['verb']));
		$emailcc           = notags(trim($_REQUEST['emailcc']));
		$body              = escape_tags(trim($_REQUEST['body']));
		$network           = notags(trim(defaults($_REQUEST, 'network', NETWORK_DFRN)));
		$guid              = get_guid(32);

		$postopts = defaults($_REQUEST, 'postopts', '');

		$private = ((strlen($str_group_allow) || strlen($str_contact_allow) || strlen($str_group_deny) || strlen($str_contact_deny)) ? 1 : 0);

		if ($user['hidewall']) {
			$private = 2;
		}

		// If this is a comment, set the permissions from the parent.

		if ($parent_item) {

			// for non native networks use the network of the original post as network of the item
			if (($parent_item['network'] != NETWORK_DIASPORA)
				&& ($parent_item['network'] != NETWORK_OSTATUS)
				&& ($network == "")) {
				$network = $parent_item['network'];
			}

			$str_contact_allow = $parent_item['allow_cid'];
			$str_group_allow   = $parent_item['allow_gid'];
			$str_contact_deny  = $parent_item['deny_cid'];
			$str_group_deny    = $parent_item['deny_gid'];
			$private           = $parent_item['private'];
		}

		$pubmail_enabled = defaults($_REQUEST, 'pubmail_enable', false) && !$private;

		// if using the API, we won't see pubmail_enable - figure out if it should be set
		if ($api_source && $profile_uid && $profile_uid == local_user() && !$private) {
			if (function_exists('imap_open') && !Config::get('system', 'imap_disabled')) {
				$pubmail_enabled = dba::exists('mailacct', ["`uid` = ? AND `server` != ? AND `pubmail`", local_user(), '']);
			}
		}

		if (!strlen($body)) {
			if ($preview) {
				killme();
			}
			info(L10n::t('Empty post discarded.') . EOL);
			if (x($_REQUEST, 'return')) {
				goaway($return_path);
			}
			killme();
		}
	}

	if (strlen($categories)) {
		// get the "fileas" tags for this post
		$filedas = file_tag_file_to_list($categories, 'file');
	}
	// save old and new categories, so we can determine what needs to be deleted from pconfig
	$categories_old = $categories;
	$categories = file_tag_list_to_file(trim($_REQUEST['category']), 'category');
	$categories_new = $categories;
	if (strlen($filedas)) {
		// append the fileas stuff to the new categories list
		$categories .= file_tag_list_to_file($filedas, 'file');
	}

	// get contact info for poster

	$author = null;
	$self   = false;
	$contact_id = 0;

	if (local_user() && ((local_user() == $profile_uid) || $allow_comment)) {
		$self = true;
		$author = dba::selectFirst('contact', [], ['uid' => local_user(), 'self' => true]);
	} elseif (remote_user()) {
		if (x($_SESSION, 'remote') && is_array($_SESSION['remote'])) {
			foreach ($_SESSION['remote'] as $v) {
				if ($v['uid'] == $profile_uid) {
					$contact_id = $v['cid'];
					break;
				}
			}
		}
		if ($contact_id) {
			$author = dba::selectFirst('contact', [], ['id' => $contact_id]);
		}
	}

	if (DBM::is_result($author)) {
		$contact_id = $author['id'];
	}

	// get contact info for owner
	if ($profile_uid == local_user() || $allow_comment) {
		$contact_record = $author;
	} else {
		$contact_record = dba::selectFirst('contact', [], ['uid' => $profile_uid, 'self' => true]);
	}

	$post_type = notags(trim($_REQUEST['type']));

	if ($post_type === 'net-comment' && $parent_item !== null) {
		if ($parent_item['wall'] == 1) {
			$post_type = 'wall-comment';
		} else {
			$post_type = 'remote-comment';
		}
	}

	// Look for any tags and linkify them
	$str_tags = '';
	$inform   = '';

	$tags = get_tags($body);

	// Add a tag if the parent contact is from OStatus (This will notify them during delivery)
	if ($parent) {
		if ($thr_parent_contact['network'] == NETWORK_OSTATUS) {
			$contact = '@[url=' . $thr_parent_contact['url'] . ']' . $thr_parent_contact['nick'] . '[/url]';
			if (!stripos(implode($tags), '[url=' . $thr_parent_contact['url'] . ']')) {
				$tags[] = $contact;
			}
		}

		if ($parent_contact['network'] == NETWORK_OSTATUS) {
			$contact = '@[url=' . $parent_contact['url'] . ']' . $parent_contact['nick'] . '[/url]';
			if (!stripos(implode($tags), '[url=' . $parent_contact['url'] . ']')) {
				$tags[] = $contact;
			}
		}
	}

	$tagged = [];

	$private_forum = false;
	$only_to_forum = false;
	$forum_contact = [];

	if (count($tags)) {
		foreach ($tags as $tag) {
			$tag_type = substr($tag, 0, 1);

			if ($tag_type == '#') {
				continue;
			}

			/*
			 * If we already tagged 'Robert Johnson', don't try and tag 'Robert'.
			 * Robert Johnson should be first in the $tags array
			 */
			$fullnametagged = false;
			/// @TODO $tagged is initialized above if () block and is not filled, maybe old-lost code?
			foreach ($tagged as $nextTag) {
				if (stristr($nextTag, $tag . ' ')) {
					$fullnametagged = true;
					break;
				}
			}
			if ($fullnametagged) {
				continue;
			}

			$success = handle_tag($a, $body, $inform, $str_tags, local_user() ? local_user() : $profile_uid, $tag, $network);
			if ($success['replaced']) {
				$tagged[] = $tag;
			}
			// When the forum is private or the forum is addressed with a "!" make the post private
			if (is_array($success['contact']) && ($success['contact']['prv'] || ($tag_type == '!'))) {
				$private_forum = $success['contact']['prv'];
				$only_to_forum = ($tag_type == '!');
				$private_id = $success['contact']['id'];
				$forum_contact = $success['contact'];
			} elseif (is_array($success['contact']) && $success['contact']['forum'] &&
				($str_contact_allow == '<' . $success['contact']['id'] . '>')) {
				$private_forum = false;
				$only_to_forum = true;
				$private_id = $success['contact']['id'];
				$forum_contact = $success['contact'];
			}
		}
	}

	$original_contact_id = $contact_id;

	if (!$parent && count($forum_contact) && ($private_forum || $only_to_forum)) {
		// we tagged a forum in a top level post. Now we change the post
		$private = $private_forum;

		$str_group_allow = '';
		$str_contact_deny = '';
		$str_group_deny = '';
		if ($private_forum) {
			$str_contact_allow = '<' . $private_id . '>';
		} else {
			$str_contact_allow = '';
		}
		$contact_id = $private_id;
		$contact_record = $forum_contact;
		$_REQUEST['origin'] = false;
	}

	/*
	 * When a photo was uploaded into the message using the (profile wall) ajax
	 * uploader, The permissions are initially set to disallow anybody but the
	 * owner from seeing it. This is because the permissions may not yet have been
	 * set for the post. If it's private, the photo permissions should be set
	 * appropriately. But we didn't know the final permissions on the post until
	 * now. So now we'll look for links of uploaded messages that are in the
	 * post and set them to the same permissions as the post itself.
	 */

	$match = null;

	/// @todo these lines should be moved to Model/Photo
	if (!$preview && preg_match_all("/\[img([\=0-9x]*?)\](.*?)\[\/img\]/",$body,$match)) {
		$images = $match[2];
		if (count($images)) {

			$objecttype = ACTIVITY_OBJ_IMAGE;

			foreach ($images as $image) {
				if (!stristr($image, System::baseUrl() . '/photo/')) {
					continue;
				}
				$image_uri = substr($image,strrpos($image,'/') + 1);
				$image_uri = substr($image_uri,0, strpos($image_uri,'-'));
				if (!strlen($image_uri)) {
					continue;
				}

				// Ensure to only modify photos that you own
				$srch = '<' . intval($original_contact_id) . '>';

				$condition = ['allow_cid' => $srch, 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '',
						'resource-id' => $image_uri, 'uid' => $profile_uid];
				if (!dba::exists('photo', $condition)) {
					continue;
				}

				$fields = ['allow_cid' => $str_contact_allow, 'allow_gid' => $str_group_allow,
						'deny_cid' => $str_contact_deny, 'deny_gid' => $str_group_deny];
				$condition = ['resource-id' => $image_uri, 'uid' => $profile_uid, 'album' => L10n::t('Wall Photos')];
				dba::update('photo', $fields, $condition);
			}
		}
	}


	/*
	 * Next link in any attachment references we find in the post.
	 */
	$match = false;

	/// @todo these lines should be moved to Model/Attach (Once it exists)
	if (!$preview && preg_match_all("/\[attachment\](.*?)\[\/attachment\]/", $body, $match)) {
		$attaches = $match[1];
		if (count($attaches)) {
			foreach ($attaches as $attach) {
				// Ensure to only modify attachments that you own
				$srch = '<' . intval($original_contact_id) . '>';

				$condition = ['allow_cid' => $srch, 'allow_gid' => '', 'deny_cid' => '', 'deny_gid' => '',
						'id' => $attach];
				if (!dba::exists('attach', $condition)) {
					continue;
				}

				$fields = ['allow_cid' => $str_contact_allow, 'allow_gid' => $str_group_allow,
						'deny_cid' => $str_contact_deny, 'deny_gid' => $str_group_deny];
				$condition = ['id' => $attach];
				dba::update('attach', $fields, $condition);
			}
		}
	}

	// embedded bookmark or attachment in post? set bookmark flag

	$bookmark = 0;
	$data = BBCode::getAttachmentData($body);
	if (preg_match_all("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", $body, $match, PREG_SET_ORDER) || isset($data["type"])) {
		$objecttype = ACTIVITY_OBJ_BOOKMARK;
		$bookmark = 1;
	}

	$body = bb_translate_video($body);


	// Fold multi-line [code] sequences
	$body = preg_replace('/\[\/code\]\s*\[code\]/ism', "\n", $body);

	$body = BBCode::scaleExternalImages($body, false);

	// Setting the object type if not defined before
	if (!$objecttype) {
		$objecttype = ACTIVITY_OBJ_NOTE; // Default value
		$objectdata = BBCode::getAttachedData($body);

		if ($objectdata["type"] == "link") {
			$objecttype = ACTIVITY_OBJ_BOOKMARK;
		} elseif ($objectdata["type"] == "video") {
			$objecttype = ACTIVITY_OBJ_VIDEO;
		} elseif ($objectdata["type"] == "photo") {
			$objecttype = ACTIVITY_OBJ_IMAGE;
		}

	}

	$attachments = '';
	$match = false;

	if (preg_match_all('/(\[attachment\]([0-9]+)\[\/attachment\])/',$body,$match)) {
		foreach ($match[2] as $mtch) {
			$fields = ['id', 'filename', 'filesize', 'filetype'];
			$attachment = dba::selectFirst('attach', $fields, ['id' => $mtch]);
			if (DBM::is_result($attachment)) {
				if (strlen($attachments)) {
					$attachments .= ',';
				}
				$attachments .= '[attach]href="' . System::baseUrl() . '/attach/' . $attachment['id'] .
						'" length="' . $attachment['filesize'] . '" type="' . $attachment['filetype'] .
						'" title="' . ($attachment['filename'] ? $attachment['filename'] : '') . '"[/attach]';
			}
			$body = str_replace($match[1],'',$body);
		}
	}

	$wall = 0;

	if (($post_type === 'wall' || $post_type === 'wall-comment') && !count($forum_contact)) {
		$wall = 1;
	}

	if (!strlen($verb)) {
		$verb = ACTIVITY_POST;
	}

	if ($network == "") {
		$network = NETWORK_DFRN;
	}

	$gravity = ($parent ? 6 : 0);

	// even if the post arrived via API we are considering that it
	// originated on this site by default for determining relayability.

	$origin = intval(defaults($_REQUEST, 'origin', 1));

	$notify_type = ($parent ? 'comment-new' : 'wall-new');

	$uri = ($message_id ? $message_id : item_new_uri($a->get_hostname(), $profile_uid, $guid));

	// Fallback so that we alway have a parent uri
	if (!$thr_parent_uri || !$parent) {
		$thr_parent_uri = $uri;
	}

	$datarray = [];
	$datarray['uid']           = $profile_uid;
	$datarray['type']          = $post_type;
	$datarray['wall']          = $wall;
	$datarray['gravity']       = $gravity;
	$datarray['network']       = $network;
	$datarray['contact-id']    = $contact_id;
	$datarray['owner-name']    = $contact_record['name'];
	$datarray['owner-link']    = $contact_record['url'];
	$datarray['owner-avatar']  = $contact_record['thumb'];
	$datarray['owner-id']      = Contact::getIdForURL($datarray['owner-link']);
	$datarray['author-name']   = $author['name'];
	$datarray['author-link']   = $author['url'];
	$datarray['author-avatar'] = $author['thumb'];
	$datarray['author-id']     = Contact::getIdForURL($datarray['author-link']);
	$datarray['created']       = DateTimeFormat::utcNow();
	$datarray['edited']        = DateTimeFormat::utcNow();
	$datarray['commented']     = DateTimeFormat::utcNow();
	$datarray['received']      = DateTimeFormat::utcNow();
	$datarray['changed']       = DateTimeFormat::utcNow();
	$datarray['extid']         = $extid;
	$datarray['guid']          = $guid;
	$datarray['uri']           = $uri;
	$datarray['title']         = $title;
	$datarray['body']          = $body;
	$datarray['app']           = $app;
	$datarray['location']      = $location;
	$datarray['coord']         = $coord;
	$datarray['tag']           = $str_tags;
	$datarray['file']          = $categories;
	$datarray['inform']        = $inform;
	$datarray['verb']          = $verb;
	$datarray['object-type']   = $objecttype;
	$datarray['allow_cid']     = $str_contact_allow;
	$datarray['allow_gid']     = $str_group_allow;
	$datarray['deny_cid']      = $str_contact_deny;
	$datarray['deny_gid']      = $str_group_deny;
	$datarray['private']       = $private;
	$datarray['pubmail']       = $pubmail_enabled;
	$datarray['attach']        = $attachments;
	$datarray['bookmark']      = intval($bookmark);

	// This is not a bug. The item store function changes 'parent-uri' to 'thr-parent' and fetches 'parent-uri' new. (We should change this)
	$datarray['parent-uri']    = $thr_parent_uri;

	$datarray['postopts']      = $postopts;
	$datarray['origin']        = $origin;
	$datarray['moderated']     = false;
	$datarray['object']        = $object;

	/*
	 * These fields are for the convenience of addons...
	 * 'self' if true indicates the owner is posting on their own wall
	 * If parent is 0 it is a top-level post.
	 */
	$datarray['parent']        = $parent;
	$datarray['self']          = $self;

	// This triggers posts via API and the mirror functions
	$datarray['api_source'] = $api_source;

	// This field is for storing the raw conversation data
	$datarray['protocol'] = PROTOCOL_DFRN;

	$r = dba::fetch_first("SELECT `conversation-uri`, `conversation-href` FROM `conversation` WHERE `item-uri` = ?", $datarray['parent-uri']);
	if (DBM::is_result($r)) {
		if ($r['conversation-uri'] != '') {
			$datarray['conversation-uri'] = $r['conversation-uri'];
		}
		if ($r['conversation-href'] != '') {
			$datarray['conversation-href'] = $r['conversation-href'];
		}
	}

	if ($orig_post) {
		$datarray['edit'] = true;
	}

	// preview mode - prepare the body for display and send it via json
	if ($preview) {
		require_once 'include/conversation.php';
		// We set the datarray ID to -1 because in preview mode the dataray
		// doesn't have an ID.
		$datarray["id"] = -1;
		$o = conversation($a,[array_merge($contact_record,$datarray)],'search', false, true);
		logger('preview: ' . $o);
		echo json_encode(['preview' => $o]);
		killme();
	}

	Addon::callHooks('post_local',$datarray);

	if (x($datarray, 'cancel')) {
		logger('mod_item: post cancelled by addon.');
		if ($return_path) {
			goaway($return_path);
		}

		$json = ['cancel' => 1];
		if (x($_REQUEST, 'jsreload') && strlen($_REQUEST['jsreload'])) {
			$json['reload'] = System::baseUrl() . '/' . $_REQUEST['jsreload'];
		}

		echo json_encode($json);
		killme();
	}

	if ($orig_post) {

		// Fill the cache field
		// This could be done in Item::update as well - but we have to check for the existance of some fields.
		put_item_in_cache($datarray);

		$fields = [
			'title' => $datarray['title'],
			'body' => $datarray['body'],
			'tag' => $datarray['tag'],
			'attach' => $datarray['attach'],
			'file' => $datarray['file'],
			'rendered-html' => $datarray['rendered-html'],
			'rendered-hash' => $datarray['rendered-hash'],
			'edited' => DateTimeFormat::utcNow(),
			'changed' => DateTimeFormat::utcNow()];

		Item::update($fields, ['id' => $post_id]);

		// update filetags in pconfig
		file_tag_update_pconfig($uid,$categories_old,$categories_new,'category');

		if (x($_REQUEST, 'return') && strlen($return_path)) {
			logger('return: ' . $return_path);
			goaway($return_path);
		}
		killme();
	} else {
		$post_id = 0;
	}

	unset($datarray['edit']);
	unset($datarray['self']);
	unset($datarray['api_source']);

	$post_id = Item::insert($datarray);

	if (!$post_id) {
		logger("Item wasn't stored.");
		goaway($return_path);
	}

	$datarray = dba::selectFirst('item', [], ['id' => $post_id]);

	if (!DBM::is_result($datarray)) {
		logger("Item with id ".$post_id." couldn't be fetched.");
		goaway($return_path);
	}

	// update filetags in pconfig
	file_tag_update_pconfig($uid, $categories_old, $categories_new, 'category');

	// These notifications are sent if someone else is commenting other your wall
	if ($parent) {
		if ($contact_record != $author) {
			notification([
				'type'         => NOTIFY_COMMENT,
				'notify_flags' => $user['notify-flags'],
				'language'     => $user['language'],
				'to_name'      => $user['username'],
				'to_email'     => $user['email'],
				'uid'          => $user['uid'],
				'item'         => $datarray,
				'link'         => System::baseUrl().'/display/'.urlencode($datarray['guid']),
				'source_name'  => $datarray['author-name'],
				'source_link'  => $datarray['author-link'],
				'source_photo' => $datarray['author-avatar'],
				'verb'         => ACTIVITY_POST,
				'otype'        => 'item',
				'parent'       => $parent,
				'parent_uri'   => $parent_item['uri']
			]);
		}

		// Store the comment signature information in case we need to relay to Diaspora
		Diaspora::storeCommentSignature($datarray, $author, ($self ? $user['prvkey'] : false), $post_id);
	} else {
		if (($contact_record != $author) && !count($forum_contact)) {
			notification([
				'type'         => NOTIFY_WALL,
				'notify_flags' => $user['notify-flags'],
				'language'     => $user['language'],
				'to_name'      => $user['username'],
				'to_email'     => $user['email'],
				'uid'          => $user['uid'],
				'item'         => $datarray,
				'link'         => System::baseUrl().'/display/'.urlencode($datarray['guid']),
				'source_name'  => $datarray['author-name'],
				'source_link'  => $datarray['author-link'],
				'source_photo' => $datarray['author-avatar'],
				'verb'         => ACTIVITY_POST,
				'otype'        => 'item'
			]);
		}
	}

	Addon::callHooks('post_local_end', $datarray);

	if (strlen($emailcc) && $profile_uid == local_user()) {
		$erecips = explode(',', $emailcc);
		if (count($erecips)) {
			foreach ($erecips as $recip) {
				$addr = trim($recip);
				if (!strlen($addr)) {
					continue;
				}
				$disclaimer = '<hr />' . L10n::t('This message was sent to you by %s, a member of the Friendica social network.', $a->user['username'])
					. '<br />';
				$disclaimer .= L10n::t('You may visit them online at %s', System::baseUrl() . '/profile/' . $a->user['nickname']) . EOL;
				$disclaimer .= L10n::t('Please contact the sender by replying to this post if you do not wish to receive these messages.') . EOL;
				if (!$datarray['title']=='') {
					$subject = Email::encodeHeader($datarray['title'], 'UTF-8');
				} else {
					$subject = Email::encodeHeader('[Friendica]' . ' ' . L10n::t('%s posted an update.', $a->user['username']), 'UTF-8');
				}
				$link = '<a href="' . System::baseUrl() . '/profile/' . $a->user['nickname'] . '"><img src="' . $author['thumb'] . '" alt="' . $a->user['username'] . '" /></a><br /><br />';
				$html    = prepare_body($datarray);
				$message = '<html><body>' . $link . $html . $disclaimer . '</body></html>';
				$params =  [
					'fromName' => $a->user['username'],
					'fromEmail' => $a->user['email'],
					'toEmail' => $addr,
					'replyTo' => $a->user['email'],
					'messageSubject' => $subject,
					'htmlVersion' => $message,
					'textVersion' => Friendica\Content\Text\HTML::toPlaintext($html.$disclaimer)
				];
				Emailer::send($params);
			}
		}
	}

	// Insert an item entry for UID=0 for global entries.
	// We now do it in the background to save some time.
	// This is important in interactive environments like the frontend or the API.
	// We don't fork a new process since this is done anyway with the following command
	Worker::add(['priority' => PRIORITY_HIGH, 'dont_fork' => true], "CreateShadowEntry", $post_id);

	// Call the background process that is delivering the item to the receivers
	Worker::add(PRIORITY_HIGH, "Notifier", $notify_type, $post_id);

	logger('post_complete');

	item_post_return(System::baseUrl(), $api_source, $return_path);
	// NOTREACHED
}

function item_post_return($baseurl, $api_source, $return_path) {
	// figure out how to return, depending on from whence we came

	if ($api_source) {
		return;
	}

	if ($return_path) {
		goaway($return_path);
	}

	$json = ['success' => 1];
	if (x($_REQUEST, 'jsreload') && strlen($_REQUEST['jsreload'])) {
		$json['reload'] = $baseurl . '/' . $_REQUEST['jsreload'];
	}

	logger('post_json: ' . print_r($json,true), LOGGER_DEBUG);

	echo json_encode($json);
	killme();
}



function item_content(App $a) {

	if (!local_user() && !remote_user()) {
		return;
	}

	require_once 'include/security.php';

	$o = '';
	if (($a->argc == 3) && ($a->argv[1] === 'drop') && intval($a->argv[2])) {
		if (is_ajax()) {
			$o = Item::deleteById($a->argv[2]);
		} else {
			$o = drop_item($a->argv[2]);
		}
		if (is_ajax()) {
			// ajax return: [<item id>, 0 (no perm) | <owner id>]
			echo json_encode([intval($a->argv[2]), intval($o)]);
			killme();
		}
	}
	return $o;
}

/**
 * This function removes the tag $tag from the text $body and replaces it with
 * the appropiate link.
 *
 * @param App $a Application instance @TODO is unused in this function's scope (excluding included files)
 * @param unknown_type $body the text to replace the tag in
 * @param string $inform a comma-seperated string containing everybody to inform
 * @param string $str_tags string to add the tag to
 * @param integer $profile_uid
 * @param string $tag the tag to replace
 * @param string $network The network of the post
 *
 * @return boolean true if replaced, false if not replaced
 */
function handle_tag(App $a, &$body, &$inform, &$str_tags, $profile_uid, $tag, $network = "")
{
	$replaced = false;
	$r = null;
	$tag_type = '@';

	//is it a person tag?
	if ((strpos($tag, '@') === 0) || (strpos($tag, '!') === 0)) {
		$tag_type = substr($tag, 0, 1);
		//is it already replaced?
		if (strpos($tag, '[url=')) {
			//append tag to str_tags
			if (!stristr($str_tags, $tag)) {
				if (strlen($str_tags)) {
					$str_tags .= ',';
				}
				$str_tags .= $tag;
			}

			// Checking for the alias that is used for OStatus
			$pattern = "/[@!]\[url\=(.*?)\](.*?)\[\/url\]/ism";
			if (preg_match($pattern, $tag, $matches)) {
				$data = Contact::getDetailsByURL($matches[1]);
				if ($data["alias"] != "") {
					$newtag = '@[url=' . $data["alias"] . ']' . $data["nick"] . '[/url]';
					if (!stripos($str_tags, '[url=' . $data["alias"] . ']')) {
						if (strlen($str_tags)) {
							$str_tags .= ',';
						}
						$str_tags .= $newtag;
					}
				}
			}

			return $replaced;
		}
		$stat = false;
		//get the person's name
		$name = substr($tag, 1);

		// Sometimes the tag detection doesn't seem to work right
		// This is some workaround
		$nameparts = explode(" ", $name);
		$name = $nameparts[0];

		// Try to detect the contact in various ways
		if (strpos($name, 'http://')) {
			// At first we have to ensure that the contact exists
			Contact::getIdForURL($name);

			// Now we should have something
			$contact = Contact::getDetailsByURL($name);
		} elseif (strpos($name, '@')) {
			// This function automatically probes when no entry was found
			$contact = Contact::getDetailsByAddr($name);
		} else {
			$contact = false;
			$fields = ['id', 'url', 'nick', 'name', 'alias', 'network'];

			if (strrpos($name, '+')) {
				// Is it in format @nick+number?
				$tagcid = intval(substr($name, strrpos($name, '+') + 1));
				$contact = dba::selectFirst('contact', $fields, ['id' => $tagcid, 'uid' => $profile_uid]);
			}

			// select someone by nick or attag in the current network
			if (!DBM::is_result($contact) && ($network != "")) {
				$condition = ["(`nick` = ? OR `attag` = ?) AND `network` = ? AND `uid` = ?",
						$name, $name, $network, $profile_uid];
				$contact = dba::selectFirst('contact', $fields, $condition);
			}

			//select someone by name in the current network
			if (!DBM::is_result($contact) && ($network != "")) {
				$condition = ['name' => $name, 'network' => $network, 'uid' => $profile_uid];
				$contact = dba::selectFirst('contact', $fields, $condition);
			}

			// select someone by nick or attag in any network
			if (!DBM::is_result($contact)) {
				$condition = ["(`nick` = ? OR `attag` = ?) AND `uid` = ?", $name, $name, $profile_uid];
				$contact = dba::selectFirst('contact', $fields, $condition);
			}

			// select someone by name in any network
			if (!DBM::is_result($contact)) {
				$condition = ['name' => $name, 'uid' => $profile_uid];
				$contact = dba::selectFirst('contact', $fields, $condition);
			}
		}

		if ($contact) {
			if (strlen($inform) && (isset($contact["notify"]) || isset($contact["id"]))) {
				$inform .= ',';
			}

			if (isset($contact["id"])) {
				$inform .= 'cid:' . $contact["id"];
			} elseif (isset($contact["notify"])) {
				$inform  .= $contact["notify"];
			}

			$profile = $contact["url"];
			$alias   = $contact["alias"];
			$newname = $contact["nick"];
			if (($newname == "") || (($contact["network"] != NETWORK_OSTATUS) && ($contact["network"] != NETWORK_TWITTER)
				&& ($contact["network"] != NETWORK_STATUSNET) && ($contact["network"] != NETWORK_APPNET))) {
				$newname = $contact["name"];
			}
		}

		//if there is an url for this persons profile
		if (isset($profile) && ($newname != "")) {
			$replaced = true;
			// create profile link
			$profile = str_replace(',', '%2c', $profile);
			$newtag = $tag_type.'[url=' . $profile . ']' . $newname . '[/url]';
			$body = str_replace($tag_type . $name, $newtag, $body);
			// append tag to str_tags
			if (!stristr($str_tags, $newtag)) {
				if (strlen($str_tags)) {
					$str_tags .= ',';
				}
				$str_tags .= $newtag;
			}

			/*
			 * Status.Net seems to require the numeric ID URL in a mention if the person isn't
			 * subscribed to you. But the nickname URL is OK if they are. Grrr. We'll tag both.
			 */
			if (strlen($alias)) {
				$newtag = '@[url=' . $alias . ']' . $newname . '[/url]';
				if (!stripos($str_tags, '[url=' . $alias . ']')) {
					if (strlen($str_tags)) {
						$str_tags .= ',';
					}
					$str_tags .= $newtag;
				}
			}
		}
	}

	return ['replaced' => $replaced, 'contact' => $contact];
}
