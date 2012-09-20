<?php

require_once("include/bbcode.php");


// Note: the code in 'item_extract_images' and 'item_redir_and_replace_images'
// is identical to the code in mod/message.php for 'item_extract_images' and
// 'item_redir_and_replace_images'
if(! function_exists('item_extract_images')) {
function item_extract_images($body) {

	$saved_image = array();
	$orig_body = $body;
	$new_body = '';

	$cnt = 0;
	$img_start = strpos($orig_body, '[img');
	$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
	$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	while(($img_st_close !== false) && ($img_end !== false)) {

		$img_st_close++; // make it point to AFTER the closing bracket
		$img_end += $img_start;

		if(! strcmp(substr($orig_body, $img_start + $img_st_close, 5), 'data:')) {
			// This is an embedded image

			$saved_image[$cnt] = substr($orig_body, $img_start + $img_st_close, $img_end - ($img_start + $img_st_close));
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[!#saved_image' . $cnt . '#!]';

			$cnt++;
		}
		else
			$new_body = $new_body . substr($orig_body, 0, $img_end + strlen('[/img]'));

		$orig_body = substr($orig_body, $img_end + strlen('[/img]'));

		if($orig_body === false) // in case the body ends on a closing image tag
			$orig_body = '';

		$img_start = strpos($orig_body, '[img');
		$img_st_close = ($img_start !== false ? strpos(substr($orig_body, $img_start), ']') : false);
		$img_end = ($img_start !== false ? strpos(substr($orig_body, $img_start), '[/img]') : false);
	}

	$new_body = $new_body . $orig_body;

	return array('body' => $new_body, 'images' => $saved_image);
}}

if(! function_exists('item_redir_and_replace_images')) {
function item_redir_and_replace_images($body, $images, $cid) {

	$origbody = $body;
	$newbody = '';

	$cnt = 1;
	$pos = get_bb_tag_pos($origbody, 'url', 1);
	while($pos !== false && $cnt < 1000) {

		$search = '/\[url\=(.*?)\]\[!#saved_image([0-9]*)#!\]\[\/url\]' . '/is';
		$replace = '[url=' . z_path() . '/redir/' . $cid
				   . '?f=1&url=' . '$1' . '][!#saved_image' . '$2' .'#!][/url]';

		$newbody .= substr($origbody, 0, $pos['start']['open']);
		$subject = substr($origbody, $pos['start']['open'], $pos['end']['close'] - $pos['start']['open']);
		$origbody = substr($origbody, $pos['end']['close']);
		if($origbody === false)
			$origbody = '';

		$subject = preg_replace($search, $replace, $subject);
		$newbody .= $subject;

		$cnt++;
		$pos = get_bb_tag_pos($origbody, 'url', 1);
	}
	$newbody .= $origbody;

	$cnt = 0;
	foreach($images as $image) {
		// We're depending on the property of 'foreach' (specified on the PHP website) that
		// it loops over the array starting from the first element and going sequentially
		// to the last element
		$newbody = str_replace('[!#saved_image' . $cnt . '#!]', '[img]' . $image . '[/img]', $newbody);
		$cnt++;
	}
	return $newbody;
}}



/**
 * Render actions localized
 */
function localize_item(&$item){

	$extracted = item_extract_images($item['body']);
	if($extracted['images'])
		$item['body'] = item_redir_and_replace_images($extracted['body'], $extracted['images'], $item['contact-id']);

	$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";
	if (activity_match($item['verb'],ACTIVITY_LIKE) || activity_match($item['verb'],ACTIVITY_DISLIKE)){

		$r = q("SELECT * from `item`,`contact` WHERE
				`item`.`contact-id`=`contact`.`id` AND `item`.`uri`='%s';",
				 dbesc($item['parent-uri']));
		if(count($r)==0) return;
		$obj=$r[0];

		$author  = '[url=' . $item['author-link'] . ']' . $item['author-name'] . '[/url]';
		$objauthor =  '[url=' . $obj['author-link'] . ']' . $obj['author-name'] . '[/url]';

		switch($obj['verb']){
			case ACTIVITY_POST:
				switch ($obj['object-type']){
					case ACTIVITY_OBJ_EVENT:
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if($obj['resource-id']){
					$post_type = t('photo');
					$m=array(); preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}

		$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

		if(activity_match($item['verb'],ACTIVITY_LIKE)) {
			$bodyverb = t('%1$s likes %2$s\'s %3$s');
		}
		elseif(activity_match($item['verb'],ACTIVITY_DISLIKE)) {
			$bodyverb = t('%1$s doesn\'t like %2$s\'s %3$s');
		}
		$item['body'] = sprintf($bodyverb, $author, $objauthor, $plink);

	}
	if (activity_match($item['verb'],ACTIVITY_FRIEND)) {

		if ($item['object-type']=="" || $item['object-type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		$links = parse_xml_string($xmlhead."<links>".unxmlify($obj->link)."</links>");

		$Bname = $obj->title;
		$Blink = ""; $Bphoto = "";
		foreach ($links->link as $l){
			$atts = $l->attributes();
			switch($atts['rel']){
				case "alternate": $Blink = $atts['href'];
				case "photo": $Bphoto = $atts['href'];
			}

		}

		$A = '[url=' . zrl($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . zrl($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto!="") $Bphoto = '[url=' . zrl($Blink) . '][img]' . $Bphoto . '[/img][/url]';

		$item['body'] = sprintf( t('%1$s is now friends with %2$s'), $A, $B)."\n\n\n".$Bphoto;

	}
	if (stristr($item['verb'],ACTIVITY_POKE)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;
		if ($item['object-type']=="" || $item['object-type']!== ACTIVITY_OBJ_PERSON) return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		$links = parse_xml_string($xmlhead."<links>".unxmlify($obj->link)."</links>");

		$Bname = $obj->title;
		$Blink = ""; $Bphoto = "";
		foreach ($links->link as $l){
			$atts = $l->attributes();
			switch($atts['rel']){
				case "alternate": $Blink = $atts['href'];
				case "photo": $Bphoto = $atts['href'];
			}

		}

		$A = '[url=' . zrl($Alink) . ']' . $Aname . '[/url]';
		$B = '[url=' . zrl($Blink) . ']' . $Bname . '[/url]';
		if ($Bphoto!="") $Bphoto = '[url=' . zrl($Blink) . '][img=80x80]' . $Bphoto . '[/img][/url]';

		// we can't have a translation string with three positions but no distinguishable text
		// So here is the translate string.

		$txt = t('%1$s poked %2$s');

		// now translate the verb

		$txt = str_replace( t('poked'), t($verb), $txt);

		// then do the sprintf on the translation string

		$item['body'] = sprintf($txt, $A, $B). "\n\n\n" . $Bphoto;

	}
	if (stristr($item['verb'],ACTIVITY_MOOD)) {
		$verb = urldecode(substr($item['verb'],strpos($item['verb'],'#')+1));
		if(! $verb)
			return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];
		$A = '[url=' . zrl($Alink) . ']' . $Aname . '[/url]';

		$txt = t('%1$s is currently %2$s');

		$item['body'] = sprintf($txt, $A, t($verb));
	}

	if (activity_match($item['verb'],ACTIVITY_TAG)) {
		$r = q("SELECT * from `item`,`contact` WHERE
		`item`.`contact-id`=`contact`.`id` AND `item`.`uri`='%s';",
		 dbesc($item['parent-uri']));
		if(count($r)==0) return;
		$obj=$r[0];

		$author  = '[url=' . zrl($item['author-link']) . ']' . $item['author-name'] . '[/url]';
		$objauthor =  '[url=' . zrl($obj['author-link']) . ']' . $obj['author-name'] . '[/url]';

		switch($obj['verb']){
			case ACTIVITY_POST:
				switch ($obj['object-type']){
					case ACTIVITY_OBJ_EVENT:
						$post_type = t('event');
						break;
					default:
						$post_type = t('status');
				}
				break;
			default:
				if($obj['resource-id']){
					$post_type = t('photo');
					$m=array(); preg_match("/\[url=([^]]*)\]/", $obj['body'], $m);
					$rr['plink'] = $m[1];
				} else {
					$post_type = t('status');
				}
		}
		$plink = '[url=' . $obj['plink'] . ']' . $post_type . '[/url]';

		$parsedobj = parse_xml_string($xmlhead.$item['object']);

		$tag = sprintf('#[url=%s]%s[/url]', $parsedobj->id, $parsedobj->content);
		$item['body'] = sprintf( t('%1$s tagged %2$s\'s %3$s with %4$s'), $author, $objauthor, $plink, $tag );

	}
	if (activity_match($item['verb'],ACTIVITY_FAVORITE)){

		if ($item['object-type']== "")
			return;

		$Aname = $item['author-name'];
		$Alink = $item['author-link'];

		$xmlhead="<"."?xml version='1.0' encoding='UTF-8' ?".">";

		$obj = parse_xml_string($xmlhead.$item['object']);
		if(strlen($obj->id)) {
			$r = q("select * from item where uri = '%s' and uid = %d limit 1",
					dbesc($obj->id),
					intval($item['uid'])
			);
			if(count($r) && $r[0]['plink']) {
				$target = $r[0];
				$Bname = $target['author-name'];
				$Blink = $target['author-link'];
				$A = '[url=' . zrl($Alink) . ']' . $Aname . '[/url]';
				$B = '[url=' . zrl($Blink) . ']' . $Bname . '[/url]';
				$P = '[url=' . $target['plink'] . ']' . t('post/item') . '[/url]';
				$item['body'] = sprintf( t('%1$s marked %2$s\'s %3$s as favorite'), $A, $B, $P)."\n";

			}
		}
	}
	$matches = null;
	if(preg_match_all('/@\[url=(.*?)\]/is',$item['body'],$matches,PREG_SET_ORDER)) {
		foreach($matches as $mtch) {
			if(! strpos($mtch[1],'zrl='))
				$item['body'] = str_replace($mtch[0],'@[url=' . zrl($mtch[1]). ']',$item['body']);
		}
	}

	// add zrl's to public images
	$photo_pattern = "/\[url=(.*?)\/photos\/(.*?)\/image\/(.*?)\]\[img(.*?)\]h(.*?)\[\/img\]\[\/url\]/is";
	if(preg_match($photo_pattern,$item['body'])) {
		$photo_replace = '[url=' . zrl('$1' . '/photos/' . '$2' . '/image/' . '$3' ,true) . '][img' . '$4' . ']h' . '$5'  . '[/img][/url]';
		$item['body'] = bb_tag_preg_replace($photo_pattern, $photo_replace, 'url', $item['body']);
	}

	// add sparkle links to appropriate permalinks

	$x = stristr($item['plink'],'/display/');
	if($x) {
		$sparkle = false;
		$y = best_link_url($item,$sparkle,true);
		if(strstr($y,'/redir/'))
			$item['plink'] = $y . '?f=&url=' . $item['plink'];
	}



}

/**
 * Count the total of comments on this item and its desendants
 */
function count_descendants($item) {
	$total = count($item['children']);

	if($total > 0) {
		foreach($item['children'] as $child) {
			if(! visible_activity($child))
				$total --;
			$total += count_descendants($child);
		}
	}

	return $total;
}

function visible_activity($item) {

	if(activity_match($child['verb'],ACTIVITY_LIKE) || activity_match($child['verb'],ACTIVITY_DISLIKE))
		return false;

	if(activity_match($item['verb'],ACTIVITY_FOLLOW) && $item['object-type'] === ACTIVITY_OBJ_NOTE && $item['uid'] != local_user())
		return false;

	return true;
}

/**
 * Recursively prepare a thread for HTML
 */

function prepare_threads_body($a, $items, $cmnt_tpl, $page_writeable, $mode, $profile_owner, $alike, $dlike, $previewing, $thread_level=1) {
	$result = array();

	$wall_template = 'wall_thread.tpl';
	$wallwall_template = 'wallwall_thread.tpl';
	$items_seen = 0;
	$nb_items = count($items);

	$total_children = $nb_items;

	foreach($items as $item) {
		if($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
			// Don't count it as a visible item
			$nb_items--;
			$total_children --;
		}
		if(! visible_activity($item)) {
			$nb_items --;
			$total_children --;
		}
	}

	foreach($items as $item) {
		// prevent private email reply to public conversation from leaking.
		if($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
			continue;
		}

		if(! visible_activity($item)) {
			continue;
		}

		$items_seen++;

		$comment = '';
		$template = $wall_template;
		$commentww = '';
		$sparkle = '';
		$owner_url = $owner_photo = $owner_name = '';
		$buttons = '';
		$dropping = false;
		$star = false;
		$isstarred = "unstarred";
		$photo = $item['photo'];
		$thumb = $item['thumb'];
		$indent = '';
		$osparkle = '';
		$visiting = false;
		$lastcollapsed = false;
		$firstcollapsed = false;
		$total_children += count_descendants($item);

		$toplevelpost = (($item['id'] == $item['parent']) ? true : false);


		if($item['uid'] == local_user())
			$dropping = true;
		elseif(is_array($_SESSION['remote'])) {
			foreach($_SESSION['remote'] as $visitor) {
				if($visitor['cid'] == $item['contact-id']) {
					$dropping = true;
					$visiting = true;
					break;
				}
			}
		}

		$item_writeable = (($item['writable'] || $item['self']) ? true : false);

		// This will allow us to comment on wall-to-wall items owned by our friends
		// and community forums even if somebody else wrote the post.

		if($visiting && $mode == 'profile')
			$item_writeable = true;

		$show_comment_box = ((($page_writeable) && ($item_writeable)) ? true : false);
		$lock = ((($item['private'] == 1) || (($item['uid'] == local_user()) && (strlen($item['allow_cid']) || strlen($item['allow_gid'])
			|| strlen($item['deny_cid']) || strlen($item['deny_gid']))))
			? t('Private Message')
			: false);
		$redirect_url = $a->get_baseurl($ssl_state) . '/redir/' . $item['cid'] ;
		$shareable = ((($profile_owner == local_user()) && ($item['private'] != 1)) ? true : false);
		if(local_user() && link_compare($a->contact['url'],$item['author-link']))
			$edpost = array($a->get_baseurl($ssl_state)."/editpost/".$item['id'], t("Edit"));
		else
			$edpost = false;

		$drop = array(
			'dropping' => $dropping,
			'select' => t('Select'),
			'delete' => t('Delete'),
		);

		$filer = (($profile_owner == local_user()) ? t("save to folder") : false);

		$diff_author    = ((link_compare($item['url'],$item['author-link'])) ? false : true);
		$profile_name   = (((strlen($item['author-name']))   && $diff_author) ? $item['author-name']   : $item['name']);
		if($item['author-link'] && (! $item['author-name']))
			$profile_name = $item['author-link'];

		$sp = false;
		$profile_link = best_link_url($item,$sp);
		if($profile_link === 'mailbox')
			$profile_link = '';
		if($sp)
			$sparkle = ' sparkle';
		else
			$profile_link = zrl($profile_link);

		$normalised = normalise_link((strlen($item['author-link'])) ? $item['author-link'] : $item['url']);
		if(($normalised != 'mailbox') && (x($a->contacts,$normalised)))
			$profile_avatar = $a->contacts[$normalised]['thumb'];
		else
			$profile_avatar = (((strlen($item['author-avatar'])) && $diff_author) ? $item['author-avatar'] : $a->get_cached_avatar_image($thumb));

		$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
		call_hooks('render_location',$locate);
		$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_google($locate));

		$tags=array();
		$hashtags = array();
		$mentions = array();
		foreach(explode(',',$item['tag']) as $tag){
			$tag = trim($tag);
			if ($tag!="") {
				$t = bbcode($tag);
				$tags[] = $t;
				if($t[0] == '#')
					$hashtags[] = $t;
				elseif($t[0] == '@')
					$mentions[] = $t;
			}

		}

		$like    = ((x($alike,$item['uri'])) ? format_like($alike[$item['uri']],$alike[$item['uri'] . '-l'],'like',$item['uri']) : '');
		$dislike = ((x($dlike,$item['uri'])) ? format_like($dlike[$item['uri']],$dlike[$item['uri'] . '-l'],'dislike',$item['uri']) : '');

		if($toplevelpost) {
			if((! $item['self']) && ($mode !== 'profile')) {
				if($item['wall']) {

					// On the network page, I am the owner. On the display page it will be the profile owner.
					// This will have been stored in $a->page_contact by our calling page.
					// Put this person as the wall owner of the wall-to-wall notice.

					$owner_url = zrl($a->page_contact['url']);
					$owner_photo = $a->page_contact['thumb'];
					$owner_name = $a->page_contact['name'];
					$template = $wallwall_template;
					$commentww = 'ww';
				}
				else if($item['owner-link']) {

					$owner_linkmatch = (($item['owner-link']) && link_compare($item['owner-link'],$item['author-link']));
					$alias_linkmatch = (($item['alias']) && link_compare($item['alias'],$item['author-link']));
					$owner_namematch = (($item['owner-name']) && $item['owner-name'] == $item['author-name']);
					if((! $owner_linkmatch) && (! $alias_linkmatch) && (! $owner_namematch)) {

						// The author url doesn't match the owner (typically the contact)
						// and also doesn't match the contact alias.
						// The name match is a hack to catch several weird cases where URLs are
						// all over the park. It can be tricked, but this prevents you from
						// seeing "Bob Smith to Bob Smith via Wall-to-wall" and you know darn
						// well that it's the same Bob Smith.

						// But it could be somebody else with the same name. It just isn't highly likely.


						$owner_url = $item['owner-link'];
						$owner_photo = $item['owner-avatar'];
						$owner_name = $item['owner-name'];
						$template = $wallwall_template;
						$commentww = 'ww';
						// If it is our contact, use a friendly redirect link
						if((link_compare($item['owner-link'],$item['url']))
							&& ($item['network'] === NETWORK_DFRN)) {
							$owner_url = $redirect_url;
							$osparkle = ' sparkle';
						}
						else
							$owner_url = zrl($owner_url);
					}
				}
			}
			if($profile_owner == local_user()) {
				$isstarred = (($item['starred']) ? "starred" : "unstarred");

				$star = array(
					'do' => t("add star"),
					'undo' => t("remove star"),
					'toggle' => t("toggle star status"),
					'classdo' => (($item['starred']) ? "hidden" : ""),
					'classundo' => (($item['starred']) ? "" : "hidden"),
					'starred' =>  t('starred'),
					'tagger' => t("add tag"),
					'classtagger' => "",
				);
			}
		} else {
			$indent = 'comment';
			// Collapse comments
			if(($nb_items > 2) || ($thread_level > 2)) {
				if($items_seen == 1) {
					$firstcollapsed = true;
				}
				if($thread_level > 2) {
					if($items_seen == $nb_items)
						$lastcollapsed = true;
				}
				else if($items_seen == ($nb_items - 2)) {
					$lastcollapsed = true;
				}
			}
		}

		if(intval(get_config('system','thread_allow')) && $a->theme_thread_allow) {
			$comments_threaded = true;
		}
		else {
			$comments_threaded = false;
		}

		if($page_writeable) {
			$buttons = array(
				'like' => array( t("I like this \x28toggle\x29"), t("like")),
				'dislike' => array( t("I don't like this \x28toggle\x29"), t("dislike")),
			);
			if ($shareable) $buttons['share'] = array( t('Share this'), t('share'));


			if($show_comment_box) {
				$qc = $qcomment =  null;

				if(in_array('qcomment',$a->plugins)) {
					$qc = ((local_user()) ? get_pconfig(local_user(),'qcomment','words') : null);
					$qcomment = (($qc) ? explode("\n",$qc) : null);
				}
				$comment = replace_macros($cmnt_tpl,array(
					'$return_path' => '',
					'$threaded' => $comments_threaded,
					'$jsreload' => (($mode === 'display') ? $_SESSION['return_url'] : ''),
					'$type' => (($mode === 'profile') ? 'wall-comment' : 'net-comment'),
					'$id' => $item['item_id'],
					'$parent' => $item['item_id'],
					'$qcomment' => $qcomment,
					'$profile_uid' =>  $profile_owner,
					'$mylink' => $a->contact['url'],
					'$mytitle' => t('This is you'),
					'$myphoto' => $a->contact['thumb'],
					'$comment' => t('Comment'),
					'$submit' => t('Submit'),
					'$edbold' => t('Bold'),
					'$editalic' => t('Italic'),
					'$eduline' => t('Underline'),
					'$edquote' => t('Quote'),
					'$edcode' => t('Code'),
					'$edimg' => t('Image'),
					'$edurl' => t('Link'),
					'$edvideo' => t('Video'),
					'$preview' => t('Preview'),
					'$indent' => $indent,
					'$sourceapp' => t($a->sourcename),
					'$ww' => (($mode === 'network') ? $commentww : '')
				));
			}
		}

		if(strcmp(datetime_convert('UTC','UTC',$item['created']),datetime_convert('UTC','UTC','now - 12 hours')) > 0)
			$indent .= ' shiny';

		localize_item($item);

		$body = prepare_body($item,true);

		$tmp_item = array(
			// collapse comments in template. I don't like this much...
			'comment_firstcollapsed' => $firstcollapsed,
			'comment_lastcollapsed' => $lastcollapsed,
			// template to use to render item (wall, walltowall, search)
			'template' => $template,

			'type' => implode("",array_slice(explode("/",$item['verb']),-1)),
			'tags' => template_escape($tags),
			'hashtags' => template_escape($hashtags),
			'mentions' => template_escape($mentions),
			'body' => template_escape($body),
			'text' => strip_tags(template_escape($body)),
			'id' => $item['item_id'],
			'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
			'olinktitle' => sprintf( t('View %s\'s profile @ %s'), $owner_name, ((strlen($item['owner-link'])) ? $item['owner-link'] : $item['url'])),
			'to' => t('to'),
			'wall' => t('Wall-to-Wall'),
			'vwall' => t('via Wall-To-Wall:'),
			'profile_url' => $profile_link,
			'item_photo_menu' => item_photo_menu($item),
			'name' => template_escape($profile_name),
			'thumb' => $profile_avatar,
			'osparkle' => $osparkle,
			'sparkle' => $sparkle,
			'title' => template_escape($item['title']),
			'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),

			'ago' => (($item['app']) ? sprintf( t('%s from %s'),relative_date($item['created']),$item['app']) : relative_date($item['created'])),
			'lock' => $lock,
			'location' => template_escape($location),
			'indent' => $indent,
			'owner_url' => $owner_url,
			'owner_photo' => $owner_photo,
			'owner_name' => template_escape($owner_name),
			'plink' => get_plink($item),
			'edpost' => $edpost,
			'isstarred' => $isstarred,
			'star' => $star,
			'filer' => $filer,
			'drop' => $drop,
			'vote' => $buttons,
			'like' => $like,
			'dislike' => $dislike,
			'comment' => $comment,
			'previewing' => $previewing,
			'wait' => t('Please wait'),
			'thread_level' => $thread_level,
		);

		$arr = array('item' => $item, 'output' => $tmp_item);
		call_hooks('display_item', $arr);

		$item_result = $arr['output'];
		if($firstcollapsed) {
			$item_result['num_comments'] = sprintf( tt('%d comment','%d comments',$total_children),$total_children );
			$item_result['hidden_comments_num'] = $total_children;
			$item_result['hidden_comments_text'] = tt('comment', 'comments', $total_children);
			$item_result['hide_text'] = t('show more');
		}

		$item_result['children'] = array();
		if(count($item['children'])) {
			$item_result['children'] = prepare_threads_body($a, $item['children'], $cmnt_tpl, $page_writeable, $mode, $profile_owner, $alike, $dlike, $previewing, ($thread_level + 1));
		}
		$item_result['private'] = $item['private'];
		$item_result['toplevel'] = ($toplevelpost ? 'toplevel_item' : '');

		/*
		 * I don't like this very much...
		 */
		if(get_config('system','thread_allow') && $a->theme_thread_allow) {
			$item_result['flatten'] = false;
			$item_result['threaded'] = true;
		}
		else {
			$item_result['flatten'] = true;
			$item_result['threaded'] = false;
			if(!$toplevelpost) {
				$item_result['comment'] = false;
			}
		}

		$result[] = $item_result;
	}

	return $result;
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

if(!function_exists('conversation')) {
function conversation(&$a, $items, $mode, $update, $preview = false) {


	require_once('bbcode.php');

	$ssl_state = ((local_user()) ? true : false);

	$profile_owner = 0;
	$page_writeable      = false;

	$previewing = (($preview) ? ' preview ' : '');

	if($mode === 'network') {
		$profile_owner = local_user();
		$page_writeable = true;
	}

	if($mode === 'profile') {
		$profile_owner = $a->profile['profile_uid'];
		$page_writeable = can_write_wall($a,$profile_owner);
	}

	if($mode === 'notes') {
		$profile_owner = local_user();
		$page_writeable = true;
	}

	if($mode === 'display') {
		$profile_owner = $a->profile['uid'];
		$page_writeable = can_write_wall($a,$profile_owner);
	}

	if($mode === 'community') {
		$profile_owner = 0;
		$page_writeable = false;
	}

	$page_dropping = ((local_user() && local_user() == $profile_owner) ? true : false);


	if($update)
		$return_url = $_SESSION['return_url'];
	else
		$return_url = $_SESSION['return_url'] = $a->query_string;

	load_contact_links(local_user());

	$cb = array('items' => $items, 'mode' => $mode, 'update' => $update, 'preview' => $preview);
	call_hooks('conversation_start',$cb);

	$items = $cb['items'];

	$cmnt_tpl    = get_markup_template('comment_item.tpl');
	$tpl         = 'wall_item.tpl';
	$wallwall    = 'wallwall_item.tpl';
	$hide_comments_tpl = get_markup_template('hide_comments.tpl');

	$alike = array();
	$dlike = array();


	// array with html for each thread (parent+comments)
	$threads = array();
	$threadsid = -1;

	$page_template = get_markup_template("conversation.tpl");

	if($items && count($items)) {

		if($mode === 'network-new' || $mode === 'search' || $mode === 'community') {

			// "New Item View" on network page or search page results
			// - just loop through the items and format them minimally for display

			//$tpl = get_markup_template('search_item.tpl');
			$tpl = 'search_item.tpl';

			foreach($items as $item) {
				$threadsid++;

				$comment     = '';
				$owner_url   = '';
				$owner_photo = '';
				$owner_name  = '';
				$sparkle     = '';

				if($mode === 'search' || $mode === 'community') {
					if(((activity_match($item['verb'],ACTIVITY_LIKE)) || (activity_match($item['verb'],ACTIVITY_DISLIKE)))
						&& ($item['id'] != $item['parent']))
						continue;
					$nickname = $item['nickname'];
				}
				else
					$nickname = $a->user['nickname'];

				// prevent private email from leaking.
				if($item['network'] === NETWORK_MAIL && local_user() != $item['uid'])
						continue;

				$profile_name   = ((strlen($item['author-name']))   ? $item['author-name']   : $item['name']);
				if($item['author-link'] && (! $item['author-name']))
					$profile_name = $item['author-link'];



				$tags=array();
				$hashtags = array();
				$mentions = array();
				foreach(explode(',',$item['tag']) as $tag){
					$tag = trim($tag);
					if ($tag!="") {
						$t = bbcode($tag);
						$tags[] = $t;
						if($t[0] == '#')
							$hashtags[] = $t;
						elseif($t[0] == '@')
							$mentions[] = $t;
					}
				}

				$sp = false;
				$profile_link = best_link_url($item,$sp);
				if($profile_link === 'mailbox')
					$profile_link = '';
				if($sp)
					$sparkle = ' sparkle';
				else
					$profile_link = zrl($profile_link);

				$normalised = normalise_link((strlen($item['author-link'])) ? $item['author-link'] : $item['url']);
				if(($normalised != 'mailbox') && (x($a->contacts[$normalised])))
					$profile_avatar = $a->contacts[$normalised]['thumb'];
				else
					$profile_avatar = ((strlen($item['author-avatar'])) ? $a->get_cached_avatar_image($item['author-avatar']) : $item['thumb']);

				$locate = array('location' => $item['location'], 'coord' => $item['coord'], 'html' => '');
				call_hooks('render_location',$locate);

				$location = ((strlen($locate['html'])) ? $locate['html'] : render_location_google($locate));

				localize_item($item);
				if($mode === 'network-new')
					$dropping = true;
				else
					$dropping = false;


				$drop = array(
					'dropping' => $dropping,
					'select' => t('Select'),
					'delete' => t('Delete'),
				);

				$star = false;
				$isstarred = "unstarred";

				$lock = false;
				$likebuttons = false;
				$shareable = false;

				$body = prepare_body($item,true);

				//$tmp_item = replace_macros($tpl,array(
				$tmp_item = array(
					'template' => $tpl,
					'id' => (($preview) ? 'P0' : $item['item_id']),
					'linktitle' => sprintf( t('View %s\'s profile @ %s'), $profile_name, ((strlen($item['author-link'])) ? $item['author-link'] : $item['url'])),
					'profile_url' => $profile_link,
					'item_photo_menu' => item_photo_menu($item),
					'name' => template_escape($profile_name),
					'sparkle' => $sparkle,
					'lock' => $lock,
					'thumb' => $profile_avatar,
					'title' => template_escape($item['title']),
					'body' => template_escape($body),
					'tags' => template_escape($tags),
					'hashtags' => template_escape($hashtags),
					'mentions' => template_escape($mentions),
					'text' => strip_tags(template_escape($body)),
					'localtime' => datetime_convert('UTC', date_default_timezone_get(), $item['created'], 'r'),
					'ago' => (($item['app']) ? sprintf( t('%s from %s'),relative_date($item['created']),$item['app']) : relative_date($item['created'])),
					'location' => template_escape($location),
					'indent' => '',
					'owner_name' => template_escape($owner_name),
					'owner_url' => $owner_url,
					'owner_photo' => $owner_photo,
					'plink' => get_plink($item),
					'edpost' => false,
					'isstarred' => $isstarred,
					'star' => $star,
					'drop' => $drop,
					'vote' => $likebuttons,
					'like' => '',
					'dislike' => '',
					'comment' => '',
					'conv' => (($preview) ? '' : array('href'=> $a->get_baseurl($ssl_state) . '/display/' . $nickname . '/' . $item['id'], 'title'=> t('View in context'))),
					'previewing' => $previewing,
					'wait' => t('Please wait'),
					'thread_level' => 1,
				);

				$arr = array('item' => $item, 'output' => $tmp_item);
				call_hooks('display_item', $arr);

				$threads[$threadsid]['id'] = $item['item_id'];
				$threads[$threadsid]['items'] = array($arr['output']);

			}

		}
		else
		{
			// Normal View
			$page_template = get_markup_template("threaded_conversation.tpl");

			require_once('object/Conversation.php');
			require_once('object/Item.php');

			$conv = new Conversation($mode, $preview);

			// get all the topmost parents
			// this shouldn't be needed, as we should have only them in our array
			// But for now, this array respects the old style, just in case

			$threads = array();
			foreach($items as $item) {

				// Can we put this after the visibility check?
				like_puller($a,$item,$alike,'like');
				like_puller($a,$item,$dlike,'dislike');

				// Only add what is visible
				if($item['network'] === NETWORK_MAIL && local_user() != $item['uid']) {
					continue;
				}
				if(! visible_activity($item)) {
					continue;
				}

				if($item['id'] == $item['parent']) {
					$item_object = new Item($item);
					$conv->add_thread($item_object);
				}
			}

			$threads = $conv->get_template_data($alike, $dlike);
			if(!$threads) {
				logger('[ERROR] conversation : Failed to get template data.', LOGGER_DEBUG);
				$threads = array();
			}
		}
	}

	$o = replace_macros($page_template, array(
		'$baseurl' => $a->get_baseurl($ssl_state),
		'$mode' => $mode,
		'$user' => $a->user,
		'$threads' => $threads,
		'$dropping' => ($page_dropping?t('Delete Selected Items'):False),
	));

	return $o;
}}

function best_link_url($item,&$sparkle,$ssl_state = false) {

	$a = get_app();

	$best_url = '';
	$sparkle  = false;

	$clean_url = normalise_link($item['author-link']);

	if((local_user()) && (local_user() == $item['uid'])) {
		if(isset($a->contacts) && x($a->contacts,$clean_url)) {
			if($a->contacts[$clean_url]['network'] === NETWORK_DFRN) {
				$best_url = $a->get_baseurl($ssl_state) . '/redir/' . $a->contacts[$clean_url]['id'];
				$sparkle = true;
			}
			else
				$best_url = $a->contacts[$clean_url]['url'];
		}
	}
	if(! $best_url) {
		if(strlen($item['author-link']))
			$best_url = $item['author-link'];
		else
			$best_url = $item['url'];
	}

	return $best_url;
}


if(! function_exists('item_photo_menu')){
function item_photo_menu($item){
	$a = get_app();

	$ssl_state = false;

	if(local_user()) {
		$ssl_state = true;
		 if(! count($a->contacts))
			load_contact_links(local_user());
	}
	$poke_link="";
	$contact_url="";
	$pm_url="";
	$status_link="";
	$photos_link="";
	$posts_link="";

	$sparkle = false;
	$profile_link = best_link_url($item,$sparkle,$ssl_state);
	if($profile_link === 'mailbox')
		$profile_link = '';

	if($sparkle) {
		$cid = intval(basename($profile_link));
		$status_link = $profile_link . "?url=status";
		$photos_link = $profile_link . "?url=photos";
		$profile_link = $profile_link . "?url=profile";
		$pm_url = $a->get_baseurl($ssl_state) . '/message/new/' . $cid;
		$zurl = '';
	}
	else {
		$profile_link = zrl($profile_link);
		if(local_user() && local_user() == $item['uid'] && link_compare($item['url'],$item['author-link'])) {
			$cid = $item['contact-id'];
		}
		else {
			$cid = 0;
		}
	}
	if(($cid) && (! $item['self'])) {
		$poke_link = $a->get_baseurl($ssl_state) . '/poke/?f=&c=' . $cid;
		$contact_url = $a->get_baseurl($ssl_state) . '/contacts/' . $cid;
		$posts_link = $a->get_baseurl($ssl_state) . '/network/?cid=' . $cid;

		$clean_url = normalise_link($item['author-link']);

		if((local_user()) && (local_user() == $item['uid'])) {
			if(isset($a->contacts) && x($a->contacts,$clean_url)) {
				if($a->contacts[$clean_url]['network'] === NETWORK_DIASPORA) {
					$pm_url = $a->get_baseurl($ssl_state) . '/message/new/' . $cid;
				}
			}
		}

	}

	$menu = Array(
		t("View Status") => $status_link,
		t("View Profile") => $profile_link,
		t("View Photos") => $photos_link,
		t("Network Posts") => $posts_link,
		t("Edit Contact") => $contact_url,
		t("Send PM") => $pm_url,
		t("Poke") => $poke_link
	);


	$args = array('item' => $item, 'menu' => $menu);

	call_hooks('item_photo_menu', $args);

	$menu = $args['menu'];

	$o = "";
	foreach($menu as $k=>$v){
		if ($v!="") $o .= "<li><a href=\"$v\">$k</a></li>\n";
	}
	return $o;
}}

if(! function_exists('like_puller')) {
function like_puller($a,$item,&$arr,$mode) {

	$url = '';
	$sparkle = '';
	$verb = (($mode === 'like') ? ACTIVITY_LIKE : ACTIVITY_DISLIKE);

	if((activity_match($item['verb'],$verb)) && ($item['id'] != $item['parent'])) {
		$url = $item['author-link'];
		if((local_user()) && (local_user() == $item['uid']) && ($item['network'] === 'dfrn') && (! $item['self']) && (link_compare($item['author-link'],$item['url']))) {
			$url = $a->get_baseurl(true) . '/redir/' . $item['contact-id'];
			$sparkle = ' class="sparkle" ';
		}
		else
			$url = zrl($url);

		if(! $item['thr-parent'])
			$item['thr-parent'] = $item['parent-uri'];

		if(! ((isset($arr[$item['thr-parent'] . '-l'])) && (is_array($arr[$item['thr-parent'] . '-l']))))
			$arr[$item['thr-parent'] . '-l'] = array();
		if(! isset($arr[$item['thr-parent']]))
			$arr[$item['thr-parent']] = 1;
		else
			$arr[$item['thr-parent']] ++;
		$arr[$item['thr-parent'] . '-l'][] = '<a href="'. $url . '"'. $sparkle .'>' . $item['author-name'] . '</a>';
	}
	return;
}}

// Format the like/dislike text for a profile item
// $cnt = number of people who like/dislike the item
// $arr = array of pre-linked names of likers/dislikers
// $type = one of 'like, 'dislike'
// $id  = item id
// returns formatted text

if(! function_exists('format_like')) {
function format_like($cnt,$arr,$type,$id) {
	$o = '';
	if($cnt == 1)
		$o .= (($type === 'like') ? sprintf( t('%s likes this.'), $arr[0]) : sprintf( t('%s doesn\'t like this.'), $arr[0])) . EOL ;
	else {
		$spanatts = 'class="fakelink" onclick="openClose(\'' . $type . 'list-' . $id . '\');"';
		$o .= (($type === 'like') ?
					sprintf( t('<span  %1$s>%2$d people</span> like this.'), $spanatts, $cnt)
					 :
					sprintf( t('<span  %1$s>%2$d people</span> don\'t like this.'), $spanatts, $cnt) );
		$o .= EOL ;
		$total = count($arr);
		if($total >= MAX_LIKERS)
			$arr = array_slice($arr, 0, MAX_LIKERS - 1);
		if($total < MAX_LIKERS)
			$arr[count($arr)-1] = t('and') . ' ' . $arr[count($arr)-1];
		$str = implode(', ', $arr);
		if($total >= MAX_LIKERS)
			$str .= sprintf( t(', and %d other people'), $total - MAX_LIKERS );
		$str = (($type === 'like') ? sprintf( t('%s like this.'), $str) : sprintf( t('%s don\'t like this.'), $str));
		$o .= "\t" . '<div id="' . $type . 'list-' . $id . '" style="display: none;" >' . $str . '</div>';
	}
	return $o;
}}


function status_editor($a,$x, $notes_cid = 0, $popup=false) {

	$o = '';

	$geotag = (($x['allow_location']) ? get_markup_template('jot_geotag.tpl') : '');

	$plaintext = false;
	if(local_user() && intval(get_pconfig(local_user(),'system','plaintext')))
		$plaintext = true;

	$tpl = get_markup_template('jot-header.tpl');
	$a->page['htmlhead'] .= replace_macros($tpl, array(
		'$newpost' => 'true',
		'$baseurl' => $a->get_baseurl(true),
		'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$geotag' => $geotag,
		'$nickname' => $x['nickname'],
		'$ispublic' => t('Visible to <strong>everybody</strong>'),
		'$linkurl' => t('Please enter a link URL:'),
		'$vidurl' => t("Please enter a video link/URL:"),
		'$audurl' => t("Please enter an audio link/URL:"),
		'$term' => t('Tag term:'),
		'$fileas' => t('Save to Folder:'),
		'$whereareu' => t('Where are you right now?')
	));


	$tpl = get_markup_template('jot-end.tpl');
	$a->page['end'] .= replace_macros($tpl, array(
		'$newpost' => 'true',
		'$baseurl' => $a->get_baseurl(true),
		'$editselect' => (($plaintext) ? 'none' : '/(profile-jot-text|prvmail-text)/'),
		'$geotag' => $geotag,
		'$nickname' => $x['nickname'],
		'$ispublic' => t('Visible to <strong>everybody</strong>'),
		'$linkurl' => t('Please enter a link URL:'),
		'$vidurl' => t("Please enter a video link/URL:"),
		'$audurl' => t("Please enter an audio link/URL:"),
		'$term' => t('Tag term:'),
		'$fileas' => t('Save to Folder:'),
		'$whereareu' => t('Where are you right now?')
	));


	$tpl = get_markup_template("jot.tpl");

	$jotplugins = '';
	$jotnets = '';

	$mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);

	$mail_enabled = false;
	$pubmail_enabled = false;

	if(($x['is_owner']) && (! $mail_disabled)) {
		$r = q("SELECT * FROM `mailacct` WHERE `uid` = %d AND `server` != '' LIMIT 1",
			intval(local_user())
		);
		if(count($r)) {
			$mail_enabled = true;
			if(intval($r[0]['pubmail']))
				$pubmail_enabled = true;
		}
	}

	if($mail_enabled) {
		$selected = (($pubmail_enabled) ? ' checked="checked" ' : '');
		$jotnets .= '<div class="profile-jot-net"><input type="checkbox" name="pubmail_enable"' . $selected . ' value="1" /> ' . t("Post to Email") . '</div>';
	}

	call_hooks('jot_tool', $jotplugins);
	call_hooks('jot_networks', $jotnets);

	if($notes_cid)
		$jotnets .= '<input type="hidden" name="contact_allow[]" value="' . $notes_cid .'" />';

	$tpl = replace_macros($tpl,array('$jotplugins' => $jotplugins));

	$o .= replace_macros($tpl,array(
		'$return_path' => $a->query_string,
		'$action' =>  $a->get_baseurl(true) . '/item',
		'$share' => (x($x,'button') ? $x['button'] : t('Share')),
		'$upload' => t('Upload photo'),
		'$shortupload' => t('upload photo'),
		'$attach' => t('Attach file'),
		'$shortattach' => t('attach file'),
		'$weblink' => t('Insert web link'),
		'$shortweblink' => t('web link'),
		'$video' => t('Insert video link'),
		'$shortvideo' => t('video link'),
		'$audio' => t('Insert audio link'),
		'$shortaudio' => t('audio link'),
		'$setloc' => t('Set your location'),
		'$shortsetloc' => t('set location'),
		'$noloc' => t('Clear browser location'),
		'$shortnoloc' => t('clear location'),
		'$title' => "",
		'$placeholdertitle' => t('Set title'),
		'$category' => "",
		'$placeholdercategory' => t('Categories (comma-separated list)'),
		'$wait' => t('Please wait'),
		'$permset' => t('Permission settings'),
		'$shortpermset' => t('permissions'),
		'$ptyp' => (($notes_cid) ? 'note' : 'wall'),
		'$content' => '',
		'$post_id' => '',
		'$baseurl' => $a->get_baseurl(true),
		'$defloc' => $x['default_location'],
		'$visitor' => $x['visitor'],
		'$pvisit' => (($notes_cid) ? 'none' : $x['visitor']),
		'$emailcc' => t('CC: email addresses'),
		'$public' => t('Public post'),
		'$jotnets' => $jotnets,
		'$emtitle' => t('Example: bob@example.com, mary@example.com'),
		'$lockstate' => $x['lockstate'],
		'$acl' => $x['acl'],
		'$bang' => $x['bang'],
		'$profile_uid' => $x['profile_uid'],
		'$preview' => t('Preview'),
		'$sourceapp' => t($a->sourcename),
		'$cancel' => t('Cancel')
	));


	if ($popup==true){
		$o = '<div id="jot-popup" style="display: none;">'.$o.'</div>';

	}

	return $o;
}


function get_item_children($arr, $parent) {
	$children = array();
	foreach($arr as $item) {
		if($item['id'] != $item['parent']) {
			if(get_config('system','thread_allow')) {
				// Fallback to parent-uri if thr-parent is not set
				$thr_parent = $item['thr-parent'];
				if($thr_parent == '')
					$thr_parent = $item['parent-uri'];

				if($thr_parent == $parent['uri']) {
					$item['children'] = get_item_children($arr, $item);
					$children[] = $item;
				}
			}
			else if($item['parent'] == $parent['id']) {
				$children[] = $item;
			}
		}
	}
	return $children;
}

function sort_item_children($items) {
	$result = $items;
	usort($result,'sort_thr_created_rev');
	foreach($result as $k => $i) {
		if(count($result[$k]['children'])) {
			$result[$k]['children'] = sort_item_children($result[$k]['children']);
		}
	}
	return $result;
}

function add_children_to_list($children, &$arr) {
	foreach($children as $y) {
		$arr[] = $y;
		if(count($y['children']))
			add_children_to_list($y['children'], $arr);
	}
}

function conv_sort($arr,$order) {

	if((!(is_array($arr) && count($arr))))
		return array();

	$parents = array();
	$children = array();

	foreach($arr as $x)
		if($x['id'] == $x['parent'])
				$parents[] = $x;

	if(stristr($order,'created'))
		usort($parents,'sort_thr_created');
	elseif(stristr($order,'commented'))
		usort($parents,'sort_thr_commented');

	if(count($parents))
		foreach($parents as $i=>$_x)
			$parents[$i]['children'] = get_item_children($arr, $_x);

	/*foreach($arr as $x) {
		if($x['id'] != $x['parent']) {
			$p = find_thread_parent_index($parents,$x);
			if($p !== false)
				$parents[$p]['children'][] = $x;
		}
	}*/
	if(count($parents)) {
		foreach($parents as $k => $v) {
			if(count($parents[$k]['children'])) {
				$parents[$k]['children'] = sort_item_children($parents[$k]['children']);
				/*$y = $parents[$k]['children'];
				usort($y,'sort_thr_created_rev');
				$parents[$k]['children'] = $y;*/
			}
		}
	}

	$ret = array();
	if(count($parents)) {
		foreach($parents as $x) {
			$ret[] = $x;
			if(count($x['children']))
				add_children_to_list($x['children'], $ret);
				/*foreach($x['children'] as $y)
					$ret[] = $y;*/
		}
	}

	return $ret;
}


function sort_thr_created($a,$b) {
	return strcmp($b['created'],$a['created']);
}

function sort_thr_created_rev($a,$b) {
	return strcmp($a['created'],$b['created']);
}

function sort_thr_commented($a,$b) {
	return strcmp($b['commented'],$a['commented']);
}

function find_thread_parent_index($arr,$x) {
	foreach($arr as $k => $v)
		if($v['id'] == $x['parent'])
			return $k;
	return false;
}

function render_location_google($item) {
	$location = (($item['location']) ? '<a target="map" title="' . $item['location'] . '" href="http://maps.google.com/?q=' . urlencode($item['location']) . '">' . $item['location'] . '</a>' : '');
	$coord = (($item['coord']) ? '<a target="map" title="' . $item['coord'] . '" href="http://maps.google.com/?q=' . urlencode($item['coord']) . '">' . $item['coord'] . '</a>' : '');
	if($coord) {
		if($location)
			$location .= '<br /><span class="smalltext">(' . $coord . ')</span>';
		else
			$location = '<span class="smalltext">' . $coord . '</span>';
	}
	return $location;
}
