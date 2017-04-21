<?php

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
 *
 * Posts that originate externally or do not fall into the above
 * posting categories go through item_store() instead of this function.
 */

require_once 'include/crypto.php';
require_once 'include/enotify.php';
require_once 'include/email.php';
require_once 'include/tags.php';
require_once 'include/files.php';
require_once 'include/threads.php';
require_once 'include/text.php';
require_once 'include/items.php';
require_once 'include/Scrape.php';
require_once 'include/diaspora.php';
require_once 'include/Contact.php';

function item_post(App $a) {

	if ((! local_user()) && (! remote_user()) && (! x($_REQUEST, 'commenter'))) {
		return;
	}

	require_once 'include/security.php';

	$uid = local_user();

	if (x($_REQUEST, 'dropitems')) {
		$arr_drop = explode(',', $_REQUEST['dropitems']);
		drop_items($arr_drop);
		$json = array('success' => 1);
		echo json_encode($json);
		killme();
	}

	call_hooks('post_local_start', $_REQUEST);
	// logger('postinput ' . file_get_contents('php://input'));
	logger('postvars ' . print_r($_REQUEST,true), LOGGER_DATA);

	$api_source = ((x($_REQUEST, 'api_source') && $_REQUEST['api_source']) ? true : false);

	$message_id = ((x($_REQUEST, 'message_id') && $api_source) ? strip_tags($_REQUEST['message_id']) : '');

	$return_path = ((x($_REQUEST, 'return')) ? $_REQUEST['return'] : '');
	$preview = ((x($_REQUEST, 'preview')) ? intval($_REQUEST['preview']) : 0);

	/*
	 * Check for doubly-submitted posts, and reject duplicates
	 * Note that we have to ignore previews, otherwise nothing will post
	 * after it's been previewed
	 */
	if (!$preview && x($_REQUEST, 'post_id_random')) {
		if (x($_SESSION, 'post-random') && $_SESSION['post-random'] == $_REQUEST['post_id_random']) {
			logger("item post: duplicate post", LOGGER_DEBUG);
			item_post_return(App::get_baseurl(), $api_source, $return_path);
		} else {
			$_SESSION['post-random'] = $_REQUEST['post_id_random'];
		}
	}

	// Is this a reply to something?
	$parent = ((x($_REQUEST, 'parent')) ? intval($_REQUEST['parent']) : 0);
	$parent_uri = ((x($_REQUEST, 'parent_uri')) ? trim($_REQUEST['parent_uri']) : '');

	$parent_item = null;
	$parent_contact = null;
	$thr_parent = '';
	$parid = 0;
	$r = false;
	$objecttype = null;

	if ($parent || $parent_uri) {

		$objecttype = ACTIVITY_OBJ_COMMENT;

		if (! x($_REQUEST, 'type')) {
			$_REQUEST['type'] = 'net-comment';
		}

		if ($parent) {
			$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1",
				intval($parent)
			);
		} elseif ($parent_uri && local_user()) {
			// This is coming from an API source, and we are logged in
			$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
				dbesc($parent_uri),
				intval(local_user())
			);
		}

		// if this isn't the real parent of the conversation, find it
		if (dbm::is_result($r)) {
			$parid = $r[0]['parent'];
			$parent_uri = $r[0]['uri'];
			if ($r[0]['id'] != $r[0]['parent']) {
				$r = q("SELECT * FROM `item` WHERE `id` = `parent` AND `parent` = %d LIMIT 1",
					intval($parid)
				);
			}
		}

		if (! dbm::is_result($r)) {
			notice( t('Unable to locate original post.') . EOL);
			if (x($_REQUEST, 'return')) {
				goaway($return_path);
			}
			killme();
		}
		$parent_item = $r[0];
		$parent = $r[0]['id'];

		// multi-level threading - preserve the info but re-parent to our single level threading
		//if(($parid) && ($parid != $parent))
		$thr_parent = $parent_uri;

		if ($parent_item['contact-id'] && $uid) {
			$r = q("SELECT * FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
				intval($parent_item['contact-id']),
				intval($uid)
			);
			if (dbm::is_result($r)) {
				$parent_contact = $r[0];
			}

			// If the contact id doesn't fit with the contact, then set the contact to null
			$thrparent = q("SELECT `author-link`, `network` FROM `item` WHERE `uri` = '%s' LIMIT 1", dbesc($thr_parent));
			if (dbm::is_result($thrparent) AND ($thrparent[0]["network"] === NETWORK_OSTATUS)
				AND (normalise_link($parent_contact["url"]) != normalise_link($thrparent[0]["author-link"]))) {
				$parent_contact = get_contact_details_by_url($thrparent[0]["author-link"]);

				if (!isset($parent_contact["nick"])) {
					require_once 'include/Scrape.php';
					$probed_contact = probe_url($thrparent[0]["author-link"]);
					if ($probed_contact["network"] != NETWORK_FEED) {
						$parent_contact = $probed_contact;
						$parent_contact["nurl"] = normalise_link($probed_contact["url"]);
						$parent_contact["thumb"] = $probed_contact["photo"];
						$parent_contact["micro"] = $probed_contact["photo"];
						$parent_contact["addr"] = $probed_contact["addr"];
					}
				}
				logger('no contact found: ' . print_r($thrparent, true), LOGGER_DEBUG);
			} else {
				logger('parent contact: ' . print_r($parent_contact, true), LOGGER_DEBUG);
			}

			if ($parent_contact["nick"] == "") {
				$parent_contact["nick"] = $parent_contact["name"];
			}
		}
	}

	if ($parent) {
		logger('mod_item: item_post parent=' . $parent);
	}

	$profile_uid = ((x($_REQUEST, 'profile_uid')) ? intval($_REQUEST['profile_uid']) : 0);
	$post_id     = ((x($_REQUEST, 'post_id'))     ? intval($_REQUEST['post_id'])     : 0);
	$app         = ((x($_REQUEST, 'source'))      ? strip_tags($_REQUEST['source'])  : '');
	$extid       = ((x($_REQUEST, 'extid'))       ? strip_tags($_REQUEST['extid'])   : '');
	$object      = ((x($_REQUEST, 'object'))      ? $_REQUEST['object']              : '');

	// Check for multiple posts with the same message id (when the post was created via API)
	if (($message_id != '') AND ($profile_uid != 0)) {
		$r = q("SELECT * FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($message_id),
			intval($profile_uid)
		);

		if (dbm::is_result($r)) {
			logger("Message with URI ".$message_id." already exists for user ".$profile_uid, LOGGER_DEBUG);
			return;
		}
	}

	$allow_moderated = false;

	// here is where we are going to check for permission to post a moderated comment.

	// First check that the parent exists and it is a wall item.

	if ((x($_REQUEST, 'commenter')) && ((! $parent) || (! $parent_item['wall']))) {
		notice(t('Permission denied.') . EOL) ;
		if (x($_REQUEST, 'return')) {
			goaway($return_path);
		}
		killme();
	}

	/*
	 * Now check that it is a page_type of PAGE_BLOG, and that valid personal details
	 * have been provided, and run any anti-spam plugins
	 */
	if ((! can_write_wall($a, $profile_uid)) && (! $allow_moderated)) {
		notice(t('Permission denied.') . EOL) ;
		if (x($_REQUEST, 'return')) {
			goaway($return_path);
		}
		killme();
	}


	// is this an edited post?

	$orig_post = null;

	if ($post_id) {
		$i = q("SELECT * FROM `item` WHERE `uid` = %d AND `id` = %d LIMIT 1",
			intval($profile_uid),
			intval($post_id)
		);
		if (! dbm::is_result($i)) {
			killme();
		}
		$orig_post = $i[0];
	}

	$user = null;

	$r = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1",
		intval($profile_uid)
	);
	if (dbm::is_result($r)) {
		$user = $r[0];
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
		$pubmail_enable    = $orig_post['pubmail'];
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
		if (($api_source)
			&& (! array_key_exists('contact_allow', $_REQUEST))
			&& (! array_key_exists('group_allow', $_REQUEST))
			&& (! array_key_exists('contact_deny', $_REQUEST))
			&& (! array_key_exists('group_deny', $_REQUEST))) {
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
		$network           = notags(trim($_REQUEST['network']));
		$guid              = get_guid(32);

		item_add_language_opt($_REQUEST);
		$postopts = $_REQUEST['postopts'] ? $_REQUEST['postopts'] : "";

		$private = ((strlen($str_group_allow) || strlen($str_contact_allow) || strlen($str_group_deny) || strlen($str_contact_deny)) ? 1 : 0);

		if ($user['hidewall']) {
			$private = 2;
		}

		// If this is a comment, set the permissions from the parent.

		if ($parent_item) {

			// for non native networks use the network of the original post as network of the item
			if (($parent_item['network'] != NETWORK_DIASPORA)
				AND ($parent_item['network'] != NETWORK_OSTATUS)
				AND ($network == "")) {
				$network = $parent_item['network'];
			}

			$str_contact_allow = $parent_item['allow_cid'];
			$str_group_allow   = $parent_item['allow_gid'];
			$str_contact_deny  = $parent_item['deny_cid'];
			$str_group_deny    = $parent_item['deny_gid'];
			$private           = $parent_item['private'];
		}

		$pubmail_enable    = ((x($_REQUEST, 'pubmail_enable') && intval($_REQUEST['pubmail_enable']) && (! $private)) ? 1 : 0);

		// if using the API, we won't see pubmail_enable - figure out if it should be set

		if ($api_source && $profile_uid && $profile_uid == local_user() && (! $private)) {
			$mail_disabled = ((function_exists('imap_open') && (! get_config('system', 'imap_disabled'))) ? 0 : 1);
			if (! $mail_disabled) {
				/// @TODO Check if only pubmail is loaded, * loads all columns
				$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d AND `server` != '' LIMIT 1",
					intval(local_user())
				);
				if (dbm::is_result($r) && intval($r[0]['pubmail'])) {
					$pubmail_enabled = true;
				}
			}
		}

		if (! strlen($body)) {
			if ($preview) {
				killme();
			}
			info(t('Empty post discarded.') . EOL );
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

	if ((local_user()) && (local_user() == $profile_uid)) {
		$self = true;
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($_SESSION['uid']));
	} elseif(remote_user()) {
		if (x($_SESSION, 'remote') && is_array($_SESSION['remote'])) {
			foreach ($_SESSION['remote'] as $v) {
				if ($v['uid'] == $profile_uid) {
					$contact_id = $v['cid'];
					break;
				}
			}
		}
		if ($contact_id) {
			$r = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1",
				intval($contact_id)
			);
		}
	}

	if (dbm::is_result($r)) {
		$author = $r[0];
		$contact_id = $author['id'];
	}

	// get contact info for owner

	if ($profile_uid == local_user()) {
		$contact_record = $author;
	} else {
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `self` = 1 LIMIT 1",
			intval($profile_uid)
		);
		if (dbm::is_result($r)) {
			$contact_record = $r[0];
		}
	}

	$post_type = notags(trim($_REQUEST['type']));

	if ($post_type === 'net-comment' && $parent_item !== null) {
		if ($parent_item['wall'] == 1) {
			$post_type = 'wall-comment';
		} else {
			$post_type = 'remote-comment';
		}
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

	if ((! $preview) && preg_match_all("/\[img([\=0-9x]*?)\](.*?)\[\/img\]/",$body,$match)) {
		$images = $match[2];
		if (count($images)) {

			$objecttype = ACTIVITY_OBJ_IMAGE;

			foreach ($images as $image) {
				if (! stristr($image,App::get_baseurl() . '/photo/')) {
					continue;
				}
				$image_uri = substr($image,strrpos($image,'/') + 1);
				$image_uri = substr($image_uri,0, strpos($image_uri,'-'));
				if (! strlen($image_uri)) {
					continue;
				}
				$srch = '<' . intval($contact_id) . '>';

				$r = q("SELECT `id` FROM `photo` WHERE `allow_cid` = '%s' AND `allow_gid` = '' AND `deny_cid` = '' AND `deny_gid` = ''
					AND `resource-id` = '%s' AND `uid` = %d LIMIT 1",
					dbesc($srch),
					dbesc($image_uri),
					intval($profile_uid)
				);

				if (! dbm::is_result($r)) {
					continue;
				}

				$r = q("UPDATE `photo` SET `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s'
					WHERE `resource-id` = '%s' AND `uid` = %d AND `album` = '%s' ",
					dbesc($str_contact_allow),
					dbesc($str_group_allow),
					dbesc($str_contact_deny),
					dbesc($str_group_deny),
					dbesc($image_uri),
					intval($profile_uid),
					dbesc( t('Wall Photos'))
				);
			}
		}
	}


	/*
	 * Next link in any attachment references we find in the post.
	 */
	$match = false;

	if ((! $preview) && preg_match_all("/\[attachment\](.*?)\[\/attachment\]/", $body, $match)) {
		$attaches = $match[1];
		if (count($attaches)) {
			foreach ($attaches as $attach) {
				$r = q("SELECT * FROM `attach` WHERE `uid` = %d AND `id` = %d LIMIT 1",
					intval($profile_uid),
					intval($attach)
				);
				if (dbm::is_result($r)) {
					$r = q("UPDATE `attach` SET `allow_cid` = '%s', `allow_gid` = '%s', `deny_cid` = '%s', `deny_gid` = '%s'
						WHERE `uid` = %d AND `id` = %d",
						dbesc($str_contact_allow),
						dbesc($str_group_allow),
						dbesc($str_contact_deny),
						dbesc($str_group_deny),
						intval($profile_uid),
						intval($attach)
					);
				}
			}
		}
	}

	// embedded bookmark or attachment in post? set bookmark flag

	$bookmark = 0;
	$data = get_attachment_data($body);
	if (preg_match_all("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism", $body, $match, PREG_SET_ORDER) OR isset($data["type"])) {
		$objecttype = ACTIVITY_OBJ_BOOKMARK;
		$bookmark = 1;
	}

	$body = bb_translate_video($body);


	// Fold multi-line [code] sequences
	$body = preg_replace('/\[\/code\]\s*\[code\]/ism', "\n", $body);

	$body = scale_external_images($body, false);

	// Setting the object type if not defined before
	if (!$objecttype) {
		$objecttype = ACTIVITY_OBJ_NOTE; // Default value
		require_once 'include/plaintext.php';
		$objectdata = get_attached_data($body);

		if ($post["type"] == "link") {
			$objecttype = ACTIVITY_OBJ_BOOKMARK;
		} elseif ($post["type"] == "video") {
			$objecttype = ACTIVITY_OBJ_VIDEO;
		} elseif ($post["type"] == "photo") {
			$objecttype = ACTIVITY_OBJ_IMAGE;
		}

	}

	// Look for any tags and linkify them
	$str_tags = '';
	$inform   = '';

	$tags = get_tags($body);

	/*
	 * add a statusnet style reply tag if the original post was from there
	 * and we are replying, and there isn't one already
	 */
	if ($parent AND ($parent_contact['network'] == NETWORK_OSTATUS)) {
		$contact = '@[url=' . $parent_contact['url'] . ']' . $parent_contact['nick'] . '[/url]';

		if (!in_array($contact, $tags)) {
			$body = $contact . ' ' . $body;
			$tags[] = $contact;
		}

		$toplevel_contact = "";
		$toplevel_parent = q("SELECT `contact`.* FROM `contact`
						INNER JOIN `item` ON `item`.`contact-id` = `contact`.`id` AND `contact`.`url` = `item`.`author-link`
						WHERE `item`.`id` = `item`.`parent` AND `item`.`parent` = %d", intval($parent));
		if (dbm::is_result($toplevel_parent)) {
			$toplevel_contact = '@' . $toplevel_parent[0]['nick'] . '+' . $toplevel_parent[0]['id'];
		} else {
			$toplevel_parent = q("SELECT `author-link`, `author-name` FROM `item` WHERE `id` = `parent` AND `parent` = %d", intval($parent));
			$toplevel_contact = '@[url=' . $toplevel_parent[0]['author-link'] . ']' . $toplevel_parent[0]['author-name'] . '[/url]';
		}

		if (!in_array($toplevel_contact, $tags)) {
			$tags[] = $toplevel_contact;
		}
	}

	$tagged = array();

	$private_forum = false;

	if (count($tags)) {
		foreach ($tags as $tag) {

			if (strpos($tag, '#') === 0) {
				continue;
			}

			/*
			 * If we already tagged 'Robert Johnson', don't try and tag 'Robert'.
			 * Robert Johnson should be first in the $tags array
			 */
			$fullnametagged = false;
			/// @TODO $tagged is initialized above if() block and is not filled, maybe old-lost code?
			foreach ($tagged as $nextTag) {
				if (stristr($nextTag, $tag . ' ')) {
					$fullnametagged = true;
					break;
				}
			}
			if ($fullnametagged) {
				continue;
			}

			$success = handle_tag($a, $body, $inform, $str_tags, (local_user()) ? local_user() : $profile_uid , $tag, $network);
			if ($success['replaced']) {
				$tagged[] = $tag;
			}
			if (is_array($success['contact']) && intval($success['contact']['prv'])) {
				$private_forum = true;
				$private_id = $success['contact']['id'];
			}
		}
	}

	if (($private_forum) && (! $parent) && (! $private)) {
		// we tagged a private forum in a top level post and the message was public.
		// Restrict it.
		$private = 1;
		$str_contact_allow = '<' . $private_id . '>';
	}

	$attachments = '';
	$match = false;

	if (preg_match_all('/(\[attachment\]([0-9]+)\[\/attachment\])/',$body,$match)) {
		foreach ($match[2] as $mtch) {
			$r = q("SELECT `id`,`filename`,`filesize`,`filetype` FROM `attach` WHERE `uid` = %d AND `id` = %d LIMIT 1",
				intval($profile_uid),
				intval($mtch)
			);
			if (dbm::is_result($r)) {
				if (strlen($attachments)) {
					$attachments .= ',';
				}
				$attachments .= '[attach]href="' . App::get_baseurl() . '/attach/' . $r[0]['id'] . '" length="' . $r[0]['filesize'] . '" type="' . $r[0]['filetype'] . '" title="' . (($r[0]['filename']) ? $r[0]['filename'] : '') . '"[/attach]';
			}
			$body = str_replace($match[1],'',$body);
		}
	}

	$wall = 0;

	if ($post_type === 'wall' || $post_type === 'wall-comment') {
		$wall = 1;
	}

	if (! strlen($verb)) {
		$verb = ACTIVITY_POST ;
	}

	if ($network == "") {
		$network = NETWORK_DFRN;
	}

	$gravity = (($parent) ? 6 : 0 );

	// even if the post arrived via API we are considering that it
	// originated on this site by default for determining relayability.

	$origin = ((x($_REQUEST, 'origin')) ? intval($_REQUEST['origin']) : 1);

	$notify_type = (($parent) ? 'comment-new' : 'wall-new' );

	$uri = (($message_id) ? $message_id : item_new_uri($a->get_hostname(),$profile_uid, $guid));

	// Fallback so that we alway have a thr-parent
	if (!$thr_parent) {
		$thr_parent = $uri;
	}

	$datarray = array();
	$datarray['uid']           = $profile_uid;
	$datarray['type']          = $post_type;
	$datarray['wall']          = $wall;
	$datarray['gravity']       = $gravity;
	$datarray['network']       = $network;
	$datarray['contact-id']    = $contact_id;
	$datarray['owner-name']    = $contact_record['name'];
	$datarray['owner-link']    = $contact_record['url'];
	$datarray['owner-avatar']  = $contact_record['thumb'];
	$datarray['owner-id']      = get_contact($datarray['owner-link'], 0);
	$datarray['author-name']   = $author['name'];
	$datarray['author-link']   = $author['url'];
	$datarray['author-avatar'] = $author['thumb'];
	$datarray['author-id']     = get_contact($datarray['author-link'], 0);
	$datarray['created']       = datetime_convert();
	$datarray['edited']        = datetime_convert();
	$datarray['commented']     = datetime_convert();
	$datarray['received']      = datetime_convert();
	$datarray['changed']       = datetime_convert();
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
	$datarray['pubmail']       = $pubmail_enable;
	$datarray['attach']        = $attachments;
	$datarray['bookmark']      = intval($bookmark);
	$datarray['thr-parent']    = $thr_parent;
	$datarray['postopts']      = $postopts;
	$datarray['origin']        = $origin;
	$datarray['moderated']     = $allow_moderated;
	$datarray['gcontact-id']   = get_gcontact_id(array("url" => $datarray['author-link'], "network" => $datarray['network'],
							"photo" => $datarray['author-avatar'], "name" => $datarray['author-name']));
	$datarray['object']        = $object;

	/*
	 * These fields are for the convenience of plugins...
	 * 'self' if true indicates the owner is posting on their own wall
	 * If parent is 0 it is a top-level post.
	 */
	$datarray['parent']        = $parent;
	$datarray['self']          = $self;
//	$datarray['prvnets']       = $user['prvnets'];

	$datarray['parent-uri'] = ($parent == 0) ? $uri : $parent_item['uri'];
	$datarray['plink'] = App::get_baseurl() . '/display/' . urlencode($datarray['guid']);
	$datarray['last-child'] = 1;
	$datarray['visible'] = 1;

	if ($orig_post) {
		$datarray['edit'] = true;
	}

	// Search for hashtags
	item_body_set_hashtags($datarray);

	// preview mode - prepare the body for display and send it via json
	if ($preview) {
		require_once 'include/conversation.php';
		// We set the datarray ID to -1 because in preview mode the dataray
		// doesn't have an ID.
		$datarray["id"] = -1;
		$o = conversation($a,array(array_merge($contact_record,$datarray)),'search', false, true);
		logger('preview: ' . $o);
		echo json_encode(array('preview' => $o));
		killme();
	}

	call_hooks('post_local',$datarray);

	if (x($datarray, 'cancel')) {
		logger('mod_item: post cancelled by plugin.');
		if ($return_path) {
			goaway($return_path);
		}

		$json = array('cancel' => 1);
		if (x($_REQUEST, 'jsreload') && strlen($_REQUEST['jsreload'])) {
			$json['reload'] = App::get_baseurl() . '/' . $_REQUEST['jsreload'];
		}

		echo json_encode($json);
		killme();
	}

	// Fill the cache field
	put_item_in_cache($datarray);

	if ($orig_post) {
		$r = q("UPDATE `item` SET `title` = '%s', `body` = '%s', `tag` = '%s', `attach` = '%s', `file` = '%s', `rendered-html` = '%s', `rendered-hash` = '%s', `edited` = '%s', `changed` = '%s' WHERE `id` = %d AND `uid` = %d",
			dbesc($datarray['title']),
			dbesc($datarray['body']),
			dbesc($datarray['tag']),
			dbesc($datarray['attach']),
			dbesc($datarray['file']),
			dbesc($datarray['rendered-html']),
			dbesc($datarray['rendered-hash']),
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($post_id),
			intval($profile_uid)
		);

		create_tags_from_item($post_id);
		create_files_from_item($post_id);
		update_thread($post_id);

		// update filetags in pconfig
		file_tag_update_pconfig($uid,$categories_old,$categories_new,'category');

		proc_run(PRIORITY_HIGH, "include/notifier.php", 'edit_post', $post_id);
		if ((x($_REQUEST, 'return')) && strlen($return_path)) {
			logger('return: ' . $return_path);
			goaway($return_path);
		}
		killme();
	} else {
		$post_id = 0;
	}

	q("COMMIT");
	q("START TRANSACTION;");

	$r = q("INSERT INTO `item` (`guid`, `extid`, `uid`,`type`,`wall`,`gravity`, `network`, `contact-id`,
					`owner-name`,`owner-link`,`owner-avatar`, `owner-id`,
					`author-name`, `author-link`, `author-avatar`, `author-id`,
					`created`, `edited`, `commented`, `received`, `changed`,
					`uri`, `thr-parent`, `title`, `body`, `app`, `location`, `coord`,
					`tag`, `inform`, `verb`, `object-type`, `postopts`,
					`allow_cid`, `allow_gid`, `deny_cid`, `deny_gid`, `private`,
					`pubmail`, `attach`, `bookmark`,`origin`, `moderated`, `file`,
					`rendered-html`, `rendered-hash`, `gcontact-id`, `object`,
					`parent`, `parent-uri`, `plink`, `last-child`, `visible`)
		VALUES('%s', '%s', %d, '%s', %d, %d, '%s', %d,
			'%s', '%s', '%s', %d,
			'%s', '%s', '%s', %d,
			'%s', '%s', '%s', '%s', '%s',
			'%s', '%s', '%s', '%s', '%s', '%s', '%s',
			'%s', '%s', '%s', '%s', '%s',
			'%s', '%s', '%s', '%s', %d,
			%d, '%s', %d, %d, %d, '%s',
			'%s', '%s', %d, '%s',
			%d, '%s', '%s', %d, %d)",
		dbesc($datarray['guid']),
		dbesc($datarray['extid']),
		intval($datarray['uid']),
		dbesc($datarray['type']),
		intval($datarray['wall']),
		intval($datarray['gravity']),
		dbesc($datarray['network']),
		intval($datarray['contact-id']),
		dbesc($datarray['owner-name']),
		dbesc($datarray['owner-link']),
		dbesc($datarray['owner-avatar']),
		intval($datarray['owner-id']),
		dbesc($datarray['author-name']),
		dbesc($datarray['author-link']),
		dbesc($datarray['author-avatar']),
		intval($datarray['author-id']),
		dbesc($datarray['created']),
		dbesc($datarray['edited']),
		dbesc($datarray['commented']),
		dbesc($datarray['received']),
		dbesc($datarray['changed']),
		dbesc($datarray['uri']),
		dbesc($datarray['thr-parent']),
		dbesc($datarray['title']),
		dbesc($datarray['body']),
		dbesc($datarray['app']),
		dbesc($datarray['location']),
		dbesc($datarray['coord']),
		dbesc($datarray['tag']),
		dbesc($datarray['inform']),
		dbesc($datarray['verb']),
		dbesc($datarray['object-type']),
		dbesc($datarray['postopts']),
		dbesc($datarray['allow_cid']),
		dbesc($datarray['allow_gid']),
		dbesc($datarray['deny_cid']),
		dbesc($datarray['deny_gid']),
		intval($datarray['private']),
		intval($datarray['pubmail']),
		dbesc($datarray['attach']),
		intval($datarray['bookmark']),
		intval($datarray['origin']),
		intval($datarray['moderated']),
		dbesc($datarray['file']),
		dbesc($datarray['rendered-html']),
		dbesc($datarray['rendered-hash']),
		intval($datarray['gcontact-id']),
		dbesc($datarray['object']),
		intval($datarray['parent']),
		dbesc($datarray['parent-uri']),
		dbesc($datarray['plink']),
		intval($datarray['last-child']),
		intval($datarray['visible'])
	);

	if (dbm::is_result($r)) {
		$r = q("SELECT LAST_INSERT_ID() AS `item-id`");
		if (dbm::is_result($r)) {
			$post_id = $r[0]['item-id'];
		} else {
			$post_id = 0;
		}
	} else {
		logger('mod_item: unable to create post.');
		$post_id = 0;
	}

	if ($post_id == 0) {
		q("COMMIT");
		logger('mod_item: unable to retrieve post that was just stored.');
		notice(t('System error. Post not saved.') . EOL);
		goaway($return_path);
		// NOTREACHED
	}

	logger('mod_item: saved item ' . $post_id);

	$datarray["id"] = $post_id;

	item_set_last_item($datarray);

	// update filetags in pconfig
	file_tag_update_pconfig($uid,$categories_old,$categories_new,'category');

	if ($parent) {

		// This item is the last leaf and gets the comment box, clear any ancestors
		$r = q("UPDATE `item` SET `last-child` = 0, `changed` = '%s' WHERE `parent` = %d AND `last-child` AND `id` != %d",
			dbesc(datetime_convert()),
			intval($parent),
			intval($post_id)
		);

		// update the commented timestamp on the parent
		q("UPDATE `item` SET `visible` = 1, `commented` = '%s', `changed` = '%s' WHERE `id` = %d",
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($parent)
		);

		if ($contact_record != $author) {
			notification(array(
				'type'         => NOTIFY_COMMENT,
				'notify_flags' => $user['notify-flags'],
				'language'     => $user['language'],
				'to_name'      => $user['username'],
				'to_email'     => $user['email'],
				'uid'          => $user['uid'],
				'item'         => $datarray,
				'link'         => App::get_baseurl().'/display/'.urlencode($datarray['guid']),
				'source_name'  => $datarray['author-name'],
				'source_link'  => $datarray['author-link'],
				'source_photo' => $datarray['author-avatar'],
				'verb'         => ACTIVITY_POST,
				'otype'        => 'item',
				'parent'       => $parent,
				'parent_uri'   => $parent_item['uri']
			));

		}


		// Store the comment signature information in case we need to relay to Diaspora
		Diaspora::store_comment_signature($datarray, $author, ($self ? $user['prvkey'] : false), $post_id);

	} else {
		$parent = $post_id;

		$r = q("UPDATE `item` SET `parent` = %d WHERE `id` = %d",
			intval($parent),
			intval($post_id));

		if ($contact_record != $author) {
			notification(array(
				'type'         => NOTIFY_WALL,
				'notify_flags' => $user['notify-flags'],
				'language'     => $user['language'],
				'to_name'      => $user['username'],
				'to_email'     => $user['email'],
				'uid'          => $user['uid'],
				'item'         => $datarray,
				'link'         => App::get_baseurl().'/display/'.urlencode($datarray['guid']),
				'source_name'  => $datarray['author-name'],
				'source_link'  => $datarray['author-link'],
				'source_photo' => $datarray['author-avatar'],
				'verb'         => ACTIVITY_POST,
				'otype'        => 'item'
			));
		}
	}

	call_hooks('post_local_end', $datarray);

	if (strlen($emailcc) && $profile_uid == local_user()) {
		$erecips = explode(',', $emailcc);
		if (count($erecips)) {
			foreach ($erecips as $recip) {
				$addr = trim($recip);
				if (! strlen($addr)) {
					continue;
				}
				$disclaimer = '<hr />' . sprintf( t('This message was sent to you by %s, a member of the Friendica social network.'), $a->user['username'])
					. '<br />';
				$disclaimer .= sprintf( t('You may visit them online at %s'), App::get_baseurl() . '/profile/' . $a->user['nickname']) . EOL;
				$disclaimer .= t('Please contact the sender by replying to this post if you do not wish to receive these messages.') . EOL;
				if (!$datarray['title']=='') {
					$subject = email_header_encode($datarray['title'], 'UTF-8');
				} else {
					$subject = email_header_encode('[Friendica]' . ' ' . sprintf( t('%s posted an update.'), $a->user['username']), 'UTF-8');
				}
				$link = '<a href="' . App::get_baseurl() . '/profile/' . $a->user['nickname'] . '"><img src="' . $author['thumb'] . '" alt="' . $a->user['username'] . '" /></a><br /><br />';
				$html    = prepare_body($datarray);
				$message = '<html><body>' . $link . $html . $disclaimer . '</body></html>';
				include_once 'include/html2plain.php';
				$params = array (
				    'fromName' => $a->user['username'],
				    'fromEmail' => $a->user['email'],
				    'toEmail' => $addr,
				    'replyTo' => $a->user['email'],
				    'messageSubject' => $subject,
				    'htmlVersion' => $message,
				    'textVersion' => html2plain($html.$disclaimer),
				);
				Emailer::send($params);
			}
		}
	}

	if ($post_id == $parent) {
		add_thread($post_id);
	} else {
		update_thread($parent, true);
	}

	q("COMMIT");

	create_tags_from_item($post_id);
	create_files_from_item($post_id);

	// Insert an item entry for UID=0 for global entries.
	// We now do it in the background to save some time.
	// This is important in interactive environments like the frontend or the API.
	// We don't fork a new process since this is done anyway with the following command
	proc_run(array('priority' => PRIORITY_HIGH, 'dont_fork' => true), "include/create_shadowentry.php", $post_id);

	// Call the background process that is delivering the item to the receivers
	proc_run(PRIORITY_HIGH, "include/notifier.php", $notify_type, $post_id);

	logger('post_complete');

	item_post_return(App::get_baseurl(), $api_source, $return_path);
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

	$json = array('success' => 1);
	if (x($_REQUEST, 'jsreload') && strlen($_REQUEST['jsreload'])) {
		$json['reload'] = $baseurl . '/' . $_REQUEST['jsreload'];
	}

	logger('post_json: ' . print_r($json,true), LOGGER_DEBUG);

	echo json_encode($json);
	killme();
}



function item_content(App $a) {

	if ((! local_user()) && (! remote_user())) {
		return;
	}

	require_once 'include/security.php';

	$o = '';
	if (($a->argc == 3) && ($a->argv[1] === 'drop') && intval($a->argv[2])) {
		$o = drop_item($a->argv[2], !is_ajax());
		if (is_ajax()) {
			// ajax return: [<item id>, 0 (no perm) | <owner id>]
			echo json_encode(array(intval($a->argv[2]), intval($o)));
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
function handle_tag(App $a, &$body, &$inform, &$str_tags, $profile_uid, $tag, $network = "") {
	require_once 'include/Scrape.php';
	require_once 'include/socgraph.php';

	$replaced = false;
	$r = null;

	//is it a person tag?
	if (strpos($tag, '@') === 0) {
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
			$pattern = "/@\[url\=(.*?)\](.*?)\[\/url\]/ism";
			if (preg_match($pattern, $tag, $matches)) {

				$r = q("SELECT `alias`, `name` FROM `contact` WHERE `nurl` = '%s' AND `alias` != '' AND `uid` = 0",
					normalise_link($matches[1]));
				if (!dbm::is_result($r)) {
					$r = q("SELECT `alias`, `name` FROM `gcontact` WHERE `nurl` = '%s' AND `alias` != ''",
						normalise_link($matches[1]));
				}
				if (dbm::is_result($r)) {
					$data = $r[0];
				} else {
					$data = probe_url($matches[1]);
				}

				if ($data["alias"] != "") {
					$newtag = '@[url=' . $data["alias"] . ']' . $data["name"] . '[/url]';
					if (!stristr($str_tags, $newtag)) {
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
		if ((strpos($name, '@')) || (strpos($name, 'http://'))) {
			// Is it in format @user@domain.tld or @http://domain.tld/...?

			// First check the contact table for the address
			$r = q("SELECT `id`, `url`, `nick`, `name`, `alias`, `network`, `notify` FROM `contact`
				WHERE `addr` = '%s' AND `uid` = %d AND
					(`network` != '%s' OR (`notify` != '' AND `alias` != ''))
				LIMIT 1",
					dbesc($name),
					intval($profile_uid),
					dbesc(NETWORK_OSTATUS)
			);

			// Then check in the contact table for the url
			if (!dbm::is_result($r)) {
				$r = q("SELECT `id`, `url`, `nick`, `name`, `alias`, `network`, `notify` FROM `contact`
					WHERE `nurl` = '%s' AND `uid` = %d AND
						(`network` != '%s' OR (`notify` != '' AND `alias` != ''))
					LIMIT 1",
						dbesc(normalise_link($name)),
						intval($profile_uid),
						dbesc(NETWORK_OSTATUS)
				);
			}

			// Then check in the global contacts for the address
			if (!dbm::is_result($r)) {
				$r = q("SELECT `url`, `nick`, `name`, `alias`, `network`, `notify` FROM `gcontact`
					WHERE `addr` = '%s' AND (`network` != '%s' OR (`notify` != '' AND `alias` != ''))
					LIMIT 1",
						dbesc($name),
						dbesc(NETWORK_OSTATUS)
				);
			}

			// Then check in the global contacts for the url
			if (!dbm::is_result($r)) {
				$r = q("SELECT `url`, `nick`, `name`, `alias`, `network`, `notify` FROM `gcontact`
					WHERE `nurl` = '%s' AND (`network` != '%s' OR (`notify` != '' AND `alias` != ''))
					LIMIT 1",
						dbesc(normalise_link($name)),
						dbesc(NETWORK_OSTATUS)
				);
			}

			if (!dbm::is_result($r)) {
				$probed = probe_url($name);
				if ($result['network'] != NETWORK_PHANTOM) {
					update_gcontact($probed);
					$r = q("SELECT `url`, `name`, `nick`, `network`, `alias`, `notify` FROM `gcontact` WHERE `nurl` = '%s' LIMIT 1",
						dbesc(normalise_link($probed["url"])));
				}
			}
		} else {
			$r = false;
			if (strrpos($name, '+')) {
				// Is it in format @nick+number?
				$tagcid = intval(substr($name, strrpos($name, '+') + 1));

				$r = q("SELECT `id`, `url`, `nick`, `name`, `alias`, `network` FROM `contact` WHERE `id` = %d AND `uid` = %d LIMIT 1",
						intval($tagcid),
						intval($profile_uid)
				);
			}

			// select someone by attag or nick and the name passed in the current network
			if(!dbm::is_result($r) AND ($network != ""))
				$r = q("SELECT `id`, `url`, `nick`, `name`, `alias`, `network` FROM `contact` WHERE `attag` = '%s' OR `nick` = '%s' AND `network` = '%s' AND `uid` = %d ORDER BY `attag` DESC LIMIT 1",
						dbesc($name),
						dbesc($name),
						dbesc($network),
						intval($profile_uid)
				);

			//select someone from this user's contacts by name in the current network
			if (!dbm::is_result($r) AND ($network != "")) {
				$r = q("SELECT `id`, `url`, `nick`, `name`, `alias`, `network` FROM `contact` WHERE `name` = '%s' AND `network` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($name),
						dbesc($network),
						intval($profile_uid)
				);
			}

			// select someone by attag or nick and the name passed in
			if(!dbm::is_result($r)) {
				$r = q("SELECT `id`, `url`, `nick`, `name`, `alias`, `network` FROM `contact` WHERE `attag` = '%s' OR `nick` = '%s' AND `uid` = %d ORDER BY `attag` DESC LIMIT 1",
						dbesc($name),
						dbesc($name),
						intval($profile_uid)
				);
			}

			// select someone from this user's contacts by name
			if(!dbm::is_result($r)) {
				$r = q("SELECT `id`, `url`, `nick`, `name`, `alias`, `network` FROM `contact` WHERE `name` = '%s' AND `uid` = %d LIMIT 1",
						dbesc($name),
						intval($profile_uid)
				);
			}
		}

		if (dbm::is_result($r)) {
			if (strlen($inform) AND (isset($r[0]["notify"]) OR isset($r[0]["id"]))) {
				$inform .= ',';
			}

			if (isset($r[0]["id"])) {
				$inform .= 'cid:' . $r[0]["id"];
			} elseif (isset($r[0]["notify"])) {
				$inform  .= $r[0]["notify"];
			}

			$profile = $r[0]["url"];
			$alias   = $r[0]["alias"];
			$newname = $r[0]["nick"];
			if (($newname == "") OR (($r[0]["network"] != NETWORK_OSTATUS) AND ($r[0]["network"] != NETWORK_TWITTER)
				AND ($r[0]["network"] != NETWORK_STATUSNET) AND ($r[0]["network"] != NETWORK_APPNET))) {
				$newname = $r[0]["name"];
			}
		}

		//if there is an url for this persons profile
		if (isset($profile) AND ($newname != "")) {

			$replaced = true;
			// create profile link
			$profile = str_replace(',', '%2c', $profile);
			$newtag = '@[url=' . $profile . ']' . $newname . '[/url]';
			$body = str_replace('@' . $name, $newtag, $body);
			// append tag to str_tags
			if (! stristr($str_tags, $newtag)) {
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
				if (! stristr($str_tags, $newtag)) {
					if (strlen($str_tags)) {
						$str_tags .= ',';
					}
					$str_tags .= $newtag;
				}
			}
		}
	}

	return array('replaced' => $replaced, 'contact' => $r[0]);
}
