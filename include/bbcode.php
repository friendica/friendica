<?php
require_once("include/oembed.php");
require_once('include/event.php');
require_once('include/map.php');
require_once('mod/proxy.php');

function bb_PictureCacheExt($matches) {
	if (strpos($matches[3], "data:image/") === 0)
		return ($matches[0]);

	$matches[3] = proxy_url($matches[3]);
	return "[img=".$matches[1]."x".$matches[2]."]".$matches[3]."[/img]";
}

function bb_PictureCache($matches) {
	if (strpos($matches[1], "data:image/") === 0)
		return ($matches[0]);

	$matches[1] = proxy_url($matches[1]);
	return "[img]".$matches[1]."[/img]";
}

function bb_map_coords($match) {
	// the extra space in the following line is intentional
	return str_replace($match[0],'<div class="map"  >' . generate_map(str_replace('/',' ',$match[1])) . '</div>', $match[0]);
}
function bb_map_location($match) {
	// the extra space in the following line is intentional
	return str_replace($match[0],'<div class="map"  >' . generate_named_map($match[1]) . '</div>', $match[0]);
}

function bb_attachment($Text, $simplehtml = false, $tryoembed = true) {
	$Text = preg_replace_callback("/(.*?)\[attachment(.*?)\](.*?)\[\/attachment\]/ism",
		function ($match) use ($simplehtml, $tryoembed){

			$attributes = $match[2];

			$type = "";
			preg_match("/type='(.*?)'/ism", $attributes, $matches);
			if ($matches[1] != "")
				$type = strtolower($matches[1]);

			preg_match('/type="(.*?)"/ism', $attributes, $matches);
			if ($matches[1] != "")
				$type = strtolower($matches[1]);

			if ($type == "")
				return($match[0]);

			if (!in_array($type, array("link", "audio", "video")))
				return($match[0]);

			$url = "";
			preg_match("/url='(.*?)'/ism", $attributes, $matches);
			if ($matches[1] != "")
				$url = $matches[1];

			preg_match('/url="(.*?)"/ism', $attributes, $matches);
			if ($matches[1] != "")
				$url = $matches[1];

			$title = "";
			preg_match("/title='(.*?)'/ism", $attributes, $matches);
			if ($matches[1] != "")
				$title = $matches[1];

			preg_match('/title="(.*?)"/ism', $attributes, $matches);
			if ($matches[1] != "")
				$title = $matches[1];

			//$title = htmlentities($title, ENT_QUOTES, 'UTF-8', false);
			$title = bbcode(html_entity_decode($title, ENT_QUOTES, 'UTF-8'), false, false, true);
			$title = str_replace(array("[", "]"), array("&#91;", "&#93;"), $title);

			$image = "";
			if ($type != "video") {
				preg_match("/image='(.*?)'/ism", $attributes, $matches);
				if ($matches[1] != "")
					$image = $matches[1];

				preg_match('/image="(.*?)"/ism', $attributes, $matches);
				if ($matches[1] != "")
					$image = $matches[1];
			}

			$preview = "";
			if ($type != "video") {
				preg_match("/preview='(.*?)'/ism", $attributes, $matches);
				if ($matches[1] != "")
					$preview = $matches[1];

				preg_match('/preview="(.*?)"/ism', $attributes, $matches);
				if ($matches[1] != "")
					$preview = $matches[1];
			}

			if (((strpos($match[1], "[img=") !== false) OR (strpos($match[1], "[img]") !== false)) AND ($image != "")) {
				$preview = $image;
				$image = "";
			}

			if ($simplehtml == 7)
				$text = sprintf('<a href="%s" title="%s" class="attachment thumbnail" rel="nofollow external">%s</a>',
						$url, $title, $title);
			elseif (($simplehtml != 4) AND ($simplehtml != 0))
				$text = sprintf('<a href="%s" target="_blank">%s</a><br>', $url, $title);
			else {
				$text = sprintf('<span class="type-%s">', $type);

				$bookmark = array(sprintf('[bookmark=%s]%s[/bookmark]', $url, $title), $url, $title);
				if ($tryoembed)
					$oembed = tryoembed($bookmark);
				else
					$oembed = $bookmark[0];

				if (strstr(strtolower($oembed), "<iframe "))
					$text = $oembed;
				else {
					if (($image != "") AND !strstr(strtolower($oembed), "<img "))
						$text .= sprintf('<a href="%s" target="_blank"><img src="%s" alt="" title="%s" class="attachment-image" /></a><br />', $url, proxy_url($image), $title);
					elseif (($preview != "") AND !strstr(strtolower($oembed), "<img "))
						$text .= sprintf('<a href="%s" target="_blank"><img src="%s" alt="" title="%s" class="attachment-preview" /></a><br />', $url, proxy_url($preview), $title);

					$text .= $oembed;

					$text .= sprintf('<blockquote>%s</blockquote></span>', trim($match[3]));
				}
			}

			return($match[1].$text);
		},$Text);

	return($Text);
}

function bb_rearrange_share($shared) {
	if (!in_array(strtolower($shared[2]), array("type-link", "type-audio", "type-video")))
		return($shared[0]);

	if (!preg_match_all("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism",$shared[3], $bookmark))
		return($shared[0]);

	$type = substr(trim(strtolower($shared[2])), 5);

	$title = "";
	$url = "";
	$preview = "";
	$description = "";

	if (isset($bookmark[2][0]))
		$title = $bookmark[2][0];

	if (isset($bookmark[1][0]))
		$url = $bookmark[1][0];

	$cleanedshare = trim($shared[3]);
	$cleanedshare = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $cleanedshare);
	preg_match("/\[img\](.*?)\[\/img\]/ism", $cleanedshare, $matches);

	if ($matches)
		$preview = trim($matches[1]);

	preg_match("/\[quote\](.*?)\[\/quote\]/ism", $cleanedshare, $matches);
	if ($matches)
		$description = trim($matches[1]);

	$url = str_replace(array("[", "]"), array("&#91;", "&#93;"), htmlentities($url, ENT_QUOTES, 'UTF-8', false));
	$title = str_replace(array("[", "]"), array("&#91;", "&#93;"), htmlentities($title, ENT_QUOTES, 'UTF-8', false));
	$preview = str_replace(array("[", "]"), array("&#91;", "&#93;"), htmlentities($preview, ENT_QUOTES, 'UTF-8', false));

	$Text = trim($shared[1])."\n[attachment type='".$type."'";

	if ($url != "")
		$Text .= " url='".$url."'";
	if ($title != "")
		$Text .= " title='".$title."'";
	if ($preview != "") {
		require_once("include/Photo.php");
		$picturedata = get_photo_info($preview);

		if (count($picturedata) > 0) {
			// if the preview picture is larger than 500 pixels then show it in a larger mode
			// But only, if the picture isn't higher than large (To prevent huge posts)
			if (($picturedata[0] >= 500) AND ($picturedata[0] >= $picturedata[1]))
				$Text .= " image='".$preview."'";
			else
				$Text .= " preview='".$preview."'";
		} else
			$Text .= " preview='".$preview."'";
	}
	$Text .= "]".$description."[/attachment]";

	return($Text);
}

function bb_remove_share_information($Text, $plaintext = false, $nolink = false) {
	$Text = preg_replace_callback("((.*?)\[class=(.*?)\](.*?)\[\/class\])ism",
		function ($match) use ($plaintext, $nolink){
			return(bb_cleanup_share($match, $plaintext, $nolink));
		},$Text);
	return($Text);
}

function bb_cleanup_share($shared, $plaintext, $nolink) {
	$shared[1] = trim($shared[1]);

	if (!in_array($shared[2], array("type-link", "type-video")))
		return($shared[0]);

	if ($plaintext)
		$shared[3] = preg_replace("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism","[bookmark=$1]$1[/bookmark]", $shared[3]);

	if (!preg_match_all("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism",$shared[3], $bookmark))
		return($shared[0]);

	if ($nolink)
		return($shared[1]);

	$title = "";
	$link = "";

	if (isset($bookmark[2][0]))
		$title = $bookmark[2][0];

	if (isset($bookmark[1][0]))
		$link = $bookmark[1][0];

	if (($shared[1] != "") AND (strpos($title, $shared[1]) !== false))
		$shared[1] = $title;

	if (($title != "") AND ((strpos($shared[1],$title) !== false) OR
		(similar_text($shared[1],$title) / strlen($title)) > 0.9))
		$title = "";

//        if (strpos($shared[1],$link) !== false)
//                $link = "";

	$text = trim($shared[1]);

	if (($text == "") AND ($title != "") AND ($link == ""))
		$text .= "\n\n".trim($title);

	// If the link already is included in the post, don't add it again
	if (($link != "") AND strpos($text, $link))
		return(trim($text));

	if (($link != "") AND ($title != ""))
		$text .= "\n[url=".trim($link)."]".trim($title)."[/url]";
	elseif (($link != ""))
		$text .= "\n".trim($link);

	return(trim($text));
}


function bb_cleanstyle($st) {
  return "<span style=\"".cleancss($st[1]).";\">".$st[2]."</span>";
}

function bb_cleanclass($st) {
  return "<span class=\"".cleancss($st[1])."\">".$st[2]."</span>";
}

function cleancss($input) {

	$cleaned = "";

	$input = strtolower($input);

	for ($i = 0; $i < strlen($input); $i++) {
		$char = substr($input, $i, 1);

		if (($char >= "a") and ($char <= "z"))
			$cleaned .= $char;

		if (!(strpos(" #;:0123456789-_", $char) === false))
			$cleaned .= $char;
	}

	return($cleaned);
}

function stripcode_br_cb($s) {
	return '[code]' . str_replace('<br />', '', $s[1]) . '[/code]';
}

function bb_onelinecode_cb($match) {
	if (strpos($match[1],"<br>")===false){
		return "<key>".$match[1]."</key>";
	}
	return "<code>".$match[1]."</code>";
}

function tryoembed($match){
	//$url = ((count($match)==2)?$match[1]:$match[2]);
	$url = $match[1];

	// Always embed the SSL version
	$url = str_replace(array("http://www.youtube.com/", "http://player.vimeo.com/"),
				array("https://www.youtube.com/", "https://player.vimeo.com/"), $url);

	//logger("tryoembed: $url");

	$o = oembed_fetch_url($url);

	if (isset($match[2]))
		$o->title = $match[2];

	if ($o->type=="error") return $match[0];

	$html = oembed_format_object($o);
	return $html; //oembed_iframe($html,$o->width,$o->height);

}

// [noparse][i]italic[/i][/noparse] turns into
// [noparse][ i ]italic[ /i ][/noparse],
// to hide them from parser.

function bb_spacefy($st) {
  $whole_match = $st[0];
  $captured = $st[1];
  $spacefied = preg_replace("/\[(.*?)\]/", "[ $1 ]", $captured);
  $new_str = str_replace($captured, $spacefied, $whole_match);
  return $new_str;
}

// The previously spacefied [noparse][ i ]italic[ /i ][/noparse],
// now turns back and the [noparse] tags are trimed
// returning [i]italic[/i]

function bb_unspacefy_and_trim($st) {
  $whole_match = $st[0];
  $captured = $st[1];
  $unspacefied = preg_replace("/\[ (.*?)\ ]/", "[$1]", $captured);
  return $unspacefied;
}

function bb_find_open_close($s, $open, $close, $occurance = 1) {

	if($occurance < 1)
		$occurance = 1;

	$start_pos = -1;
	for($i = 1; $i <= $occurance; $i++) {
		if( $start_pos !== false)
			$start_pos = strpos($s, $open, $start_pos + 1);
	}

	if( $start_pos === false)
		return false;

	$end_pos = strpos($s, $close, $start_pos);

	if( $end_pos === false)
		return false;

	$res = array( 'start' => $start_pos, 'end' => $end_pos );

	return $res;
}

function get_bb_tag_pos($s, $name, $occurance = 1) {

	if($occurance < 1)
		$occurance = 1;

	$start_open = -1;
	for($i = 1; $i <= $occurance; $i++) {
		if( $start_open !== false)
			$start_open = strpos($s, '[' . $name, $start_open + 1); // allow [name= type tags
	}

	if( $start_open === false)
		return false;

	$start_equal = strpos($s, '=', $start_open);
	$start_close = strpos($s, ']', $start_open);

	if( $start_close === false)
		return false;

	$start_close++;

	$end_open = strpos($s, '[/' . $name . ']', $start_close);

	if( $end_open === false)
		return false;

	$res = array( 'start' => array('open' => $start_open, 'close' => $start_close),
		      'end' => array('open' => $end_open, 'close' => $end_open + strlen('[/' . $name . ']')) );
	if( $start_equal !== false)
		$res['start']['equal'] = $start_equal + 1;

	return $res;
}

function bb_tag_preg_replace($pattern, $replace, $name, $s) {

	$string = $s;

	$occurance = 1;
	$pos = get_bb_tag_pos($string, $name, $occurance);
	while($pos !== false && $occurance < 1000) {

		$start = substr($string, 0, $pos['start']['open']);
		$subject = substr($string, $pos['start']['open'], $pos['end']['close'] - $pos['start']['open']);
		$end = substr($string, $pos['end']['close']);
		if($end === false)
			$end = '';

		$subject = preg_replace($pattern, $replace, $subject);
		$string = $start . $subject . $end;

		$occurance++;
		$pos = get_bb_tag_pos($string, $name, $occurance);
	}

	return $string;
}

if(! function_exists('bb_extract_images')) {
function bb_extract_images($body) {

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
			$new_body = $new_body . substr($orig_body, 0, $img_start) . '[$#saved_image' . $cnt . '#$]';

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

if(! function_exists('bb_replace_images')) {
function bb_replace_images($body, $images) {

	$newbody = $body;

	$cnt = 0;
	foreach($images as $image) {
		// We're depending on the property of 'foreach' (specified on the PHP website) that
		// it loops over the array starting from the first element and going sequentially
		// to the last element
		$newbody = str_replace('[$#saved_image' . $cnt . '#$]', '<img src="' . proxy_url($image) .'" alt="' . t('Image/photo') . '" />', $newbody);
		$cnt++;
	}

	return $newbody;
}}

function bb_ShareAttributes($share, $simplehtml) {
	$attributes = $share[2];

	$author = "";
	preg_match("/author='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$author = html_entity_decode($matches[1],ENT_QUOTES,'UTF-8');

	preg_match('/author="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$author = $matches[1];

	$profile = "";
	preg_match("/profile='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	preg_match('/profile="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$profile = $matches[1];

	$avatar = "";
	preg_match("/avatar='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$avatar = $matches[1];

	preg_match('/avatar="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$avatar = $matches[1];

	$link = "";
	preg_match("/link='(.*?)'/ism", $attributes, $matches);
	if ($matches[1] != "")
		$link = $matches[1];

	preg_match('/link="(.*?)"/ism', $attributes, $matches);
	if ($matches[1] != "")
		$link = $matches[1];

	$posted = "";

	$itemcache = get_itemcachepath();

	// relative dates only make sense when they aren't cached
	if ($itemcache == "") {
		preg_match("/posted='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "")
			$posted = $matches[1];

		preg_match('/posted="(.*?)"/ism', $attributes, $matches);
		if ($matches[1] != "")
			$posted = $matches[1];

		$reldate = (($posted) ? " " . relative_date($posted) : '');
	}

	$userid = GetProfileUsername($profile,$author, false);
	$userid_compact = GetProfileUsername($profile,$author, true);

	$preshare = trim($share[1]);

	if ($preshare != "")
		$preshare .= "<br /><br />";

	switch ($simplehtml) {
		case 1:
			$text = $preshare.html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8').' <a href="'.$profile.'">'.$userid."</a>: <br />»".$share[3]."«";
			break;
		case 2:
			$text = $preshare.html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8').' '.$userid_compact.": <br />".$share[3];
			break;
		case 3: // Diaspora
			$headline .= '<b>'.html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8').$userid.':</b><br />';

			$text = trim($share[1]);

			if ($text != "")
				$text .= "<hr />";

			if (substr(normalise_link($link), 0, 19) != "http://twitter.com/") {
				$text .= $headline.'<blockquote>'.trim($share[3])."</blockquote><br />";

				if ($link != "")
					$text .= '<br /><a href="'.$link.'">[l]</a>';
			} else
				$text .= '<br /><a href="'.$link.'">'.$link.'</a>';

			break;
		case 4:
			$headline = '<div class="shared_header">';
			$headline .= '<span><b>'.html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8');
			$headline .= sprintf(t('<a href="%1$s" target="_blank">%2$s</a> %3$s'), $link, $userid, $posted);
			$headline .= ":</b></span></div>";

			$text = trim($share[1]);

			if ($text != "")
				$text .= "<hr />";

			$text .= $headline.'<blockquote class="shared_content">'.trim($share[3])."</blockquote><br />";

			break;
		case 5:
			$text = $preshare.html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8').' '.$userid_compact.": <br />".$share[3];
			break;
		case 6: // app.net
			$text = $preshare."&gt;&gt; @".$userid_compact.": <br />".$share[3];
			break;
		case 7: // statusnet/GNU Social
			$text = $preshare.html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8')." @".$userid_compact.": ".$share[3];
			break;
		case 8: // twitter
			$text = $preshare."RT @".$userid_compact.": ".$share[3];
			break;
		case 9: // Google+/Facebook
			$text = $preshare.html_entity_decode("&#x2672; ", ENT_QUOTES, 'UTF-8').' '.$userid_compact.": <br />".$share[3];

			if ($link != "")
				$text .= "<br /><br />".$link;
			break;
		default:
			$headline = trim($share[1]).'<div class="shared_header">';
			if ($avatar != "")
				$headline .= '<img src="'.proxy_url($avatar, false, PROXY_SIZE_MICRO).'" height="32" width="32" >';

			$headline .= sprintf(t('<span><a href="%s" target="_blank">%s</a> wrote the following <a href="%s" target="_blank">post</a>'.$reldate.':</span>'), $profile, $author, $link);
			$headline .= "</div>";
			$text = $headline.'<blockquote class="shared_content">'.trim($share[3])."</blockquote>";
			break;
	}
	return($text);
}

function GetProfileUsername($profile, $username, $compact = false, $getnetwork = false) {

	$twitter = preg_replace("=https?://twitter.com/(.*)=ism", "$1@twitter.com", $profile);
	if ($twitter != $profile) {
		if ($getnetwork)
			return(NETWORK_TWITTER);
		elseif ($compact)
			return($twitter);
		else
			return($username." (".$twitter.")");
	}

	$appnet = preg_replace("=https?://alpha.app.net/(.*)=ism", "$1@alpha.app.net", $profile);
	if ($appnet != $profile) {
		if ($getnetwork)
			return(NETWORK_APPNET);
		elseif ($compact)
			return($appnet);
		else
			return($username." (".$appnet.")");
	}

	$gplus = preg_replace("=https?://plus.google.com/(.*)=ism", "$1@plus.google.com", $profile);
	if ($gplus != $profile) {
		if ($getnetwork)
			return(NETWORK_GPLUS);
		elseif ($compact)
			return($gplususername." (".$username.")");
		else
			return($username." (".$gplus.")");
	}

	$friendica = preg_replace("=https?://(.*)/profile/(.*)=ism", "$2@$1", $profile);
	if ($friendica != $profile) {
		if ($getnetwork)
			return(NETWORK_DFRN);
		elseif ($compact)
			return($friendica);
		else
			return($username." (".$friendica.")");
	}

	$diaspora = preg_replace("=https?://(.*)/u/(.*)=ism", "$2@$1", $profile);
	if ($diaspora != $profile) {
		if ($getnetwork)
			return(NETWORK_DIASPORA);
		elseif ($compact)
			return($diaspora);
		else
			return($username." (".$diaspora.")");
	}

	$red = preg_replace("=https?://(.*)/channel/(.*)=ism", "$2@$1", $profile);
	if ($red != $profile) {
		if ($getnetwork)
			// red is identified as Diaspora - friendica can't connect directly to it
			return(NETWORK_DIASPORA);
		elseif ($compact)
			return($red);
		else
			return($username." (".$red.")");
	}

	$StatusnetHost = preg_replace("=https?://(.*)/user/(.*)=ism", "$1", $profile);
	if ($StatusnetHost != $profile) {
		$StatusnetUser = preg_replace("=https?://(.*)/user/(.*)=ism", "$2", $profile);
		if ($StatusnetUser != $profile) {
			$UserData = fetch_url("http://".$StatusnetHost."/api/users/show.json?user_id=".$StatusnetUser);
			$user = json_decode($UserData);
			if ($user) {
				if ($getnetwork)
					return(NETWORK_STATUSNET);
				elseif ($compact)
					return($user->screen_name."@".$StatusnetHost);
				else
					return($username." (".$user->screen_name."@".$StatusnetHost.")");
			}
		}
	}

	// pumpio (http://host.name/user)
	$rest = preg_replace("=https?://([\.\w]+)/([\.\w]+)(.*)=ism", "$3", $profile);
	if ($rest == "") {
		$pumpio = preg_replace("=https?://([\.\w]+)/([\.\w]+)(.*)=ism", "$2@$1", $profile);
		if ($pumpio != $profile) {
			if ($getnetwork)
				return(NETWORK_PUMPIO);
			elseif ($compact)
				return($pumpio);
			else
				return($username." (".$pumpio.")");
		}
	}

	return($username);
}

function bb_DiasporaLinks($match) {
	$a = get_app();

	return "[url=".$a->get_baseurl()."/display/".$match[1]."]".$match[2]."[/url]";
}

function bb_RemovePictureLinks($match) {
	$text = Cache::get($match[1]);

	if(is_null($text)){
		$a = get_app();

		$stamp1 = microtime(true);

		$ch = @curl_init($match[1]);
		@curl_setopt($ch, CURLOPT_NOBODY, true);
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());
		@curl_exec($ch);
		$curl_info = @curl_getinfo($ch);

		$a->save_timestamp($stamp1, "network");

		if (substr($curl_info["content_type"], 0, 6) == "image/")
			$text = "[url=".$match[1]."]".$match[1]."[/url]";
		else {
			$text = "[url=".$match[2]."]".$match[2]."[/url]";

			// if its not a picture then look if its a page that contains a picture link
			require_once("include/network.php");

			$body = fetch_url($match[1]);

			$doc = new DOMDocument();
			@$doc->loadHTML($body);
			$xpath = new DomXPath($doc);
			$list = $xpath->query("//meta[@name]");
			foreach ($list as $node) {
				$attr = array();

				if ($node->attributes->length)
					foreach ($node->attributes as $attribute)
						$attr[$attribute->name] = $attribute->value;

				if (strtolower($attr["name"]) == "twitter:image")
					$text = "[url=".$attr["content"]."]".$attr["content"]."[/url]";
			}
		}
		Cache::set($match[1],$text);
	}
	return($text);
}

function bb_expand_links($match) {
	if (($match[3] == "") OR ($match[2] == $match[3]) OR stristr($match[2], $match[3]))
		return ($match[1]."[url]".$match[2]."[/url]");
	else
		return ($match[1].$match[3]." [url]".$match[2]."[/url]");
}

function bb_CleanPictureLinksSub($match) {
	$text = Cache::get($match[1]);

	if(is_null($text)){
		$a = get_app();

		$stamp1 = microtime(true);

		$ch = @curl_init($match[1]);
		@curl_setopt($ch, CURLOPT_NOBODY, true);
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		@curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());
		@curl_exec($ch);
		$curl_info = @curl_getinfo($ch);

		$a->save_timestamp($stamp1, "network");

		// if its a link to a picture then embed this picture
		if (substr($curl_info["content_type"], 0, 6) == "image/")
			$text = "[img]".$match[1]."[/img]";
		else {
			$text = "[img]".$match[2]."[/img]";

			// if its not a picture then look if its a page that contains a picture link
			require_once("include/network.php");

			$body = fetch_url($match[1]);

			$doc = new DOMDocument();
			@$doc->loadHTML($body);
			$xpath = new DomXPath($doc);
			$list = $xpath->query("//meta[@name]");
			foreach ($list as $node) {
				$attr = array();

				if ($node->attributes->length)
					foreach ($node->attributes as $attribute)
						$attr[$attribute->name] = $attribute->value;

				if (strtolower($attr["name"]) == "twitter:image")
					$text = "[img]".$attr["content"]."[/img]";
			}
		}
		Cache::set($match[1],$text);
	}
	return($text);
}

function bb_CleanPictureLinks($text) {
	$text = preg_replace_callback("&\[url=([^\[\]]*)\]\[img\](.*)\[\/img\]\[\/url\]&Usi", 'bb_CleanPictureLinksSub', $text);
	return ($text);
}

	// BBcode 2 HTML was written by WAY2WEB.net
	// extended to work with Mistpark/Friendica - Mike Macgirvin

function bbcode($Text,$preserve_nl = false, $tryoembed = true, $simplehtml = false, $forplaintext = false) {

	$a = get_app();

	// Hide all [noparse] contained bbtags by spacefying them
	// POSSIBLE BUG --> Will the 'preg' functions crash if there's an embedded image?

	$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_spacefy',$Text);
	$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_spacefy',$Text);
	$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_spacefy',$Text);


	// Move all spaces out of the tags
	$Text = preg_replace("/\[(\w*)\](\s*)/ism", '$2[$1]', $Text);
	$Text = preg_replace("/(\s*)\[\/(\w*)\]/ism", '[/$2]$1', $Text);

	// Extract the private images which use data urls since preg has issues with
	// large data sizes. Stash them away while we do bbcode conversion, and then put them back
	// in after we've done all the regex matching. We cannot use any preg functions to do this.

	$extracted = bb_extract_images($Text);
	$Text = $extracted['body'];
	$saved_image = $extracted['images'];

	// If we find any event code, turn it into an event.
	// After we're finished processing the bbcode we'll
	// replace all of the event code with a reformatted version.

	$ev = bbtoevent($Text);

	// Replace any html brackets with HTML Entities to prevent executing HTML or script
	// Don't use strip_tags here because it breaks [url] search by replacing & with amp

	$Text = str_replace("<", "&lt;", $Text);
	$Text = str_replace(">", "&gt;", $Text);

	// remove some newlines before the general conversion
	$Text = preg_replace("/\s?\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism","[share$1]$2[/share]",$Text);
	$Text = preg_replace("/\s?\[quote(.*?)\]\s?(.*?)\s?\[\/quote\]\s?/ism","[quote$1]$2[/quote]",$Text);

	$Text = preg_replace("/\n\[code\]/ism", "[code]", $Text);
	$Text = preg_replace("/\[\/code\]\n/ism", "[/code]", $Text);

	// Rearrange shares to attachments
	$Text = preg_replace_callback("((.*?)\[class=(.*?)\](.*?)\[\/class\])ism", "bb_rearrange_share",$Text);

	// when the content is meant exporting to other systems then remove the avatar picture since this doesn't really look good on these systems
	if (!$tryoembed)
		$Text = preg_replace("/\[share(.*?)avatar\s?=\s?'.*?'\s?(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism","\n[share$1$2]$3[/share]",$Text);

	// Convert new line chars to html <br /> tags

	// nlbr seems to be hopelessly messed up
	//	$Text = nl2br($Text);

	// We'll emulate it.

	$Text = trim($Text);
	$Text = str_replace("\r\n","\n", $Text);

	// removing multiplicated newlines
	if (get_config("system", "remove_multiplicated_lines")) {
		$search = array("\n\n\n", "\n ", " \n", "[/quote]\n\n", "\n[/quote]", "[/li]\n", "\n[li]", "\n[ul]", "[/ul]\n", "\n\n[share ", "[/attachment]\n",
				"\n[h1]", "[/h1]\n", "\n[h2]", "[/h2]\n", "\n[h3]", "[/h3]\n", "\n[h4]", "[/h4]\n", "\n[h5]", "[/h5]\n", "\n[h6]", "[/h6]\n");
		$replace = array("\n\n", "\n", "\n", "[/quote]\n", "[/quote]", "[/li]", "[li]", "[ul]", "[/ul]", "\n[share ", "[/attachment]",
				"[h1]", "[/h1]", "[h2]", "[/h2]", "[h3]", "[/h3]", "[h4]", "[/h4]", "[h5]", "[/h5]", "[h6]", "[/h6]");
		do {
			$oldtext = $Text;
			$Text = str_replace($search, $replace, $Text);
		} while ($oldtext != $Text);
	}

	// Handle attached links or videos
	$Text = bb_attachment($Text, $simplehtml, $tryoembed);

	$Text = str_replace(array("\r","\n"), array('<br />','<br />'), $Text);

	if($preserve_nl)
		$Text = str_replace(array("\n","\r"), array('',''),$Text);

	// Set up the parameters for a URL search string
	$URLSearchString = "^\[\]";
	// Set up the parameters for a MAIL search string
	$MAILSearchString = $URLSearchString;

	// Remove all hashtag addresses
	if ((!$tryoembed OR $simplehtml) AND !in_array($simplehtml, array(3, 7)))
		$Text = preg_replace("/([#@])\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '$1$3', $Text);
	elseif ($simplehtml == 3)
		$Text = preg_replace("/([@])\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
			'$1<a href="$2">$3</a>',
			$Text);
	elseif ($simplehtml == 7)
		$Text = preg_replace("/([@])\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",
			'$1<span class="vcard"><a href="$2" class="url" title="$3"><span class="fn nickname mention">$3</span></a></span>',
			$Text);


	// Bookmarks in red - will be converted to bookmarks in friendica
	$Text = preg_replace("/#\^\[url\]([$URLSearchString]*)\[\/url\]/ism", '[bookmark=$1]$1[/bookmark]', $Text);
	$Text = preg_replace("/#\^\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '[bookmark=$1]$2[/bookmark]', $Text);
	$Text = preg_replace("/#\[url\=[$URLSearchString]*\]\^\[\/url\]\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/i",
				"[bookmark=$1]$2[/bookmark]", $Text);

	if (in_array($simplehtml, array(2, 6, 7, 8, 9))) {
		$Text = preg_replace_callback("/([^#@])\[url\=([^\]]*)\](.*?)\[\/url\]/ism","bb_expand_links",$Text);
		//$Text = preg_replace("/[^#@]\[url\=([^\]]*)\](.*?)\[\/url\]/ism",' $2 [url]$1[/url]',$Text);
		$Text = preg_replace("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism",' $2 [url]$1[/url]',$Text);
	}

	if ($simplehtml == 5)
		$Text = preg_replace("/[^#@]\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '[url]$1[/url]', $Text);

	// Perform URL Search
	if ($tryoembed)
		$Text = preg_replace_callback("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism",'tryoembed',$Text);

	if ($simplehtml == 5)
		$Text = preg_replace("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism",'[url]$1[/url]',$Text);
	else
		$Text = preg_replace("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism",'[url=$1]$2[/url]',$Text);

	// Handle Diaspora posts
	$Text = preg_replace_callback("&\[url=/posts/([^\[\]]*)\](.*)\[\/url\]&Usi", 'bb_DiasporaLinks', $Text);

	// if the HTML is used to generate plain text, then don't do this search, but replace all URL of that kind to text
	if (!$forplaintext)
		$Text = preg_replace("/([^\]\='".'"'."]|^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1<a href="$2" target="_blank">$2</a>', $Text);
	else {
		$Text = preg_replace("(\[url\]([$URLSearchString]*)\[\/url\])ism"," $1 ",$Text);
		$Text = preg_replace_callback("&\[url=([^\[\]]*)\]\[img\](.*)\[\/img\]\[\/url\]&Usi", 'bb_RemovePictureLinks', $Text);
	}

	if ($tryoembed)
		$Text = preg_replace_callback("/\[url\]([$URLSearchString]*)\[\/url\]/ism",'tryoembed',$Text);

	$Text = preg_replace("/\[url\]([$URLSearchString]*)\[\/url\]/ism", '<a href="$1" target="_blank">$1</a>', $Text);
	$Text = preg_replace("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '<a href="$1" target="_blank">$2</a>', $Text);
	//$Text = preg_replace("/\[url\=([$URLSearchString]*)\]([$URLSearchString]*)\[\/url\]/ism", '<a href="$1" target="_blank">$2</a>', $Text);

	// Red compatibility, though the link can't be authenticated on Friendica
	$Text = preg_replace("/\[zrl\=([$URLSearchString]*)\](.*?)\[\/zrl\]/ism", '<a href="$1" target="_blank">$2</a>', $Text);


	// we may need to restrict this further if it picks up too many strays
	// link acct:user@host to a webfinger profile redirector

	$Text = preg_replace('/acct:(.*?)@(.*?)([ ,])/', '<a href="' . $a->get_baseurl() . '/acctlink?addr=' . "$1@$2"
		. '" target="extlink" >acct:' . "$1@$2$3" . '</a>',$Text);

	// Perform MAIL Search
	$Text = preg_replace("/\[mail\]([$MAILSearchString]*)\[\/mail\]/", '<a href="mailto:$1">$1</a>', $Text);
	$Text = preg_replace("/\[mail\=([$MAILSearchString]*)\](.*?)\[\/mail\]/", '<a href="mailto:$1">$2</a>', $Text);

	// leave open the posibility of [map=something]
	// this is replaced in prepare_body() which has knowledge of the item location

	if (strpos($Text,'[/map]') !== false) {
		$Text = preg_replace_callback("/\[map\](.*?)\[\/map\]/ism", 'bb_map_location', $Text);
	}
	if (strpos($Text,'[map=') !== false) {
		$Text = preg_replace_callback("/\[map=(.*?)\]/ism", 'bb_map_coords', $Text);
	}
	if (strpos($Text,'[map]') !== false) {
		$Text = preg_replace("/\[map\]/", '<div class="map"></div>', $Text);
	}

	// Check for headers
	$Text = preg_replace("(\[h1\](.*?)\[\/h1\])ism",'<h1>$1</h1>',$Text);
	$Text = preg_replace("(\[h2\](.*?)\[\/h2\])ism",'<h2>$1</h2>',$Text);
	$Text = preg_replace("(\[h3\](.*?)\[\/h3\])ism",'<h3>$1</h3>',$Text);
	$Text = preg_replace("(\[h4\](.*?)\[\/h4\])ism",'<h4>$1</h4>',$Text);
	$Text = preg_replace("(\[h5\](.*?)\[\/h5\])ism",'<h5>$1</h5>',$Text);
	$Text = preg_replace("(\[h6\](.*?)\[\/h6\])ism",'<h6>$1</h6>',$Text);

	// Check for bold text
	$Text = preg_replace("(\[b\](.*?)\[\/b\])ism",'<strong>$1</strong>',$Text);

	// Check for Italics text
	$Text = preg_replace("(\[i\](.*?)\[\/i\])ism",'<em>$1</em>',$Text);

	// Check for Underline text
	$Text = preg_replace("(\[u\](.*?)\[\/u\])ism",'<u>$1</u>',$Text);

	// Check for strike-through text
	$Text = preg_replace("(\[s\](.*?)\[\/s\])ism",'<strike>$1</strike>',$Text);

	// Check for over-line text
	$Text = preg_replace("(\[o\](.*?)\[\/o\])ism",'<span class="overline">$1</span>',$Text);

	// Check for colored text
	$Text = preg_replace("(\[color=(.*?)\](.*?)\[\/color\])ism","<span style=\"color: $1;\">$2</span>",$Text);

	// Check for sized text
	// [size=50] --> font-size: 50px (with the unit).
	$Text = preg_replace("(\[size=(\d*?)\](.*?)\[\/size\])ism","<span style=\"font-size: $1px; line-height: initial;\">$2</span>",$Text);
	$Text = preg_replace("(\[size=(.*?)\](.*?)\[\/size\])ism","<span style=\"font-size: $1; line-height: initial;\">$2</span>",$Text);

	// Check for centered text
	$Text = preg_replace("(\[center\](.*?)\[\/center\])ism","<div style=\"text-align:center;\">$1</div>",$Text);

	// Check for list text
	$Text = str_replace("[*]", "<li>", $Text);

	// Check for style sheet commands
	$Text = preg_replace_callback("(\[style=(.*?)\](.*?)\[\/style\])ism","bb_cleanstyle",$Text);

	// Check for CSS classes
	$Text = preg_replace_callback("(\[class=(.*?)\](.*?)\[\/class\])ism","bb_cleanclass",$Text);

	// handle nested lists
	$endlessloop = 0;

	while ((((strpos($Text, "[/list]") !== false) && (strpos($Text, "[list") !== false)) ||
	       ((strpos($Text, "[/ol]") !== false) && (strpos($Text, "[ol]") !== false)) ||
	       ((strpos($Text, "[/ul]") !== false) && (strpos($Text, "[ul]") !== false)) ||
	       ((strpos($Text, "[/li]") !== false) && (strpos($Text, "[li]") !== false))) && (++$endlessloop < 20)) {
		$Text = preg_replace("/\[list\](.*?)\[\/list\]/ism", '<ul class="listbullet" style="list-style-type: circle;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[list=\](.*?)\[\/list\]/ism", '<ul class="listnone" style="list-style-type: none;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[list=1\](.*?)\[\/list\]/ism", '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[list=((?-i)i)\](.*?)\[\/list\]/ism",'<ul class="listlowerroman" style="list-style-type: lower-roman;">$2</ul>' ,$Text);
		$Text = preg_replace("/\[list=((?-i)I)\](.*?)\[\/list\]/ism", '<ul class="listupperroman" style="list-style-type: upper-roman;">$2</ul>' ,$Text);
		$Text = preg_replace("/\[list=((?-i)a)\](.*?)\[\/list\]/ism", '<ul class="listloweralpha" style="list-style-type: lower-alpha;">$2</ul>' ,$Text);
		$Text = preg_replace("/\[list=((?-i)A)\](.*?)\[\/list\]/ism", '<ul class="listupperalpha" style="list-style-type: upper-alpha;">$2</ul>' ,$Text);
		$Text = preg_replace("/\[ul\](.*?)\[\/ul\]/ism", '<ul class="listbullet" style="list-style-type: circle;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[ol\](.*?)\[\/ol\]/ism", '<ul class="listdecimal" style="list-style-type: decimal;">$1</ul>' ,$Text);
		$Text = preg_replace("/\[li\](.*?)\[\/li\]/ism", '<li>$1</li>' ,$Text);
	}

	$Text = preg_replace("/\[th\](.*?)\[\/th\]/sm", '<th>$1</th>' ,$Text);
	$Text = preg_replace("/\[td\](.*?)\[\/td\]/sm", '<td>$1</td>' ,$Text);
	$Text = preg_replace("/\[tr\](.*?)\[\/tr\]/sm", '<tr>$1</tr>' ,$Text);
	$Text = preg_replace("/\[table\](.*?)\[\/table\]/sm", '<table>$1</table>' ,$Text);

	$Text = preg_replace("/\[table border=1\](.*?)\[\/table\]/sm", '<table border="1" >$1</table>' ,$Text);
	$Text = preg_replace("/\[table border=0\](.*?)\[\/table\]/sm", '<table border="0" >$1</table>' ,$Text);

	$Text = str_replace('[hr]','<hr />', $Text);

	// This is actually executed in prepare_body()

	$Text = str_replace('[nosmile]','',$Text);

	// Check for font change text
	$Text = preg_replace("/\[font=(.*?)\](.*?)\[\/font\]/sm","<span style=\"font-family: $1;\">$2</span>",$Text);

	// Declare the format for [code] layout

//	$Text = preg_replace_callback("/\[code\](.*?)\[\/code\]/ism",'stripcode_br_cb',$Text);

	$CodeLayout = '<code>$1</code>';
	// Check for [code] text
	$Text = preg_replace("/\[code\](.*?)\[\/code\]/ism","$CodeLayout", $Text);

	// Declare the format for [spoiler] layout
	$SpoilerLayout = '<blockquote class="spoiler">$1</blockquote>';

	// Check for [spoiler] text
	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/spoiler]") !== false) and (strpos($Text, "[spoiler]") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[spoiler\](.*?)\[\/spoiler\]/ism","$SpoilerLayout", $Text);

	// Check for [spoiler=Author] text

	$t_wrote = t('$1 wrote:');

	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/spoiler]")!== false)  and (strpos($Text, "[spoiler=") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[spoiler=[\"\']*(.*?)[\"\']*\](.*?)\[\/spoiler\]/ism",
				     "<br /><strong class=".'"spoiler"'.">" . $t_wrote . "</strong><blockquote class=".'"spoiler"'.">$2</blockquote>",
				     $Text);

	// Declare the format for [quote] layout
	$QuoteLayout = '<blockquote>$1</blockquote>';

	// Check for [quote] text
	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/quote]") !== false) and (strpos($Text, "[quote]") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[quote\](.*?)\[\/quote\]/ism","$QuoteLayout", $Text);

	// Check for [quote=Author] text

	$t_wrote = t('$1 wrote:');

	// handle nested quotes
	$endlessloop = 0;
	while ((strpos($Text, "[/quote]")!== false)  and (strpos($Text, "[quote=") !== false) and (++$endlessloop < 20))
		$Text = preg_replace("/\[quote=[\"\']*(.*?)[\"\']*\](.*?)\[\/quote\]/ism",
				     "<br /><strong class=".'"author"'.">" . $t_wrote . "</strong><blockquote>$2</blockquote>",
				     $Text);


	// [img=widthxheight]image source[/img]
	$Text = preg_replace_callback("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", 'bb_PictureCacheExt', $Text);

	$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '<img src="$3" style="width: $1px;" >', $Text);
	$Text = preg_replace("/\[zmg\=([0-9]*)x([0-9]*)\](.*?)\[\/zmg\]/ism", '<img class="zrl" src="$3" style="width: $1px;" >', $Text);

	// Images
	// [img]pathtoimage[/img]
	$Text = preg_replace_callback("/\[img\](.*?)\[\/img\]/ism", 'bb_PictureCache', $Text);

	$Text = preg_replace("/\[img\](.*?)\[\/img\]/ism", '<img src="$1" alt="' . t('Image/photo') . '" />', $Text);
	$Text = preg_replace("/\[zmg\](.*?)\[\/zmg\]/ism", '<img src="$1" alt="' . t('Image/photo') . '" />', $Text);

	// Shared content
	$Text = preg_replace_callback("/(.*?)\[share(.*?)\](.*?)\[\/share\]/ism",
		function ($match) use ($simplehtml){
			return(bb_ShareAttributes($match, $simplehtml));
		},$Text);

	$Text = preg_replace("/\[crypt\](.*?)\[\/crypt\]/ism",'<br/><img src="' .$a->get_baseurl() . '/images/lock_icon.gif" alt="' . t('Encrypted content') . '" title="' . t('Encrypted content') . '" /><br />', $Text);
	$Text = preg_replace("/\[crypt(.*?)\](.*?)\[\/crypt\]/ism",'<br/><img src="' .$a->get_baseurl() . '/images/lock_icon.gif" alt="' . t('Encrypted content') . '" title="' . '$1' . ' ' . t('Encrypted content') . '" /><br />', $Text);
	//$Text = preg_replace("/\[crypt=(.*?)\](.*?)\[\/crypt\]/ism",'<br/><img src="' .$a->get_baseurl() . '/images/lock_icon.gif" alt="' . t('Encrypted content') . '" title="' . '$1' . ' ' . t('Encrypted content') . '" /><br />', $Text);


	// Try to Oembed
	if ($tryoembed) {
		$Text = preg_replace("/\[video\](.*?\.(ogg|ogv|oga|ogm|webm|mp4))\[\/video\]/ism", '<video src="$1" controls="controls" width="' . $a->videowidth . '" height="' . $a->videoheight . '"><a href="$1">$1</a></video>', $Text);
		$Text = preg_replace("/\[audio\](.*?\.(ogg|ogv|oga|ogm|webm|mp4|mp3))\[\/audio\]/ism", '<audio src="$1" controls="controls"><a href="$1">$1</a></audio>', $Text);

		$Text = preg_replace_callback("/\[video\](.*?)\[\/video\]/ism", 'tryoembed', $Text);
		$Text = preg_replace_callback("/\[audio\](.*?)\[\/audio\]/ism", 'tryoembed', $Text);
	} else {
		$Text = preg_replace("/\[video\](.*?)\[\/video\]/",
					'<a href="$1" target="_blank">$1</a>', $Text);
		$Text = preg_replace("/\[audio\](.*?)\[\/audio\]/",
					'<a href="$1" target="_blank">$1</a>', $Text);
	}

	// html5 video and audio


	if ($tryoembed)
		$Text = preg_replace("/\[iframe\](.*?)\[\/iframe\]/ism", '<iframe src="$1" width="' . $a->videowidth . '" height="' . $a->videoheight . '"><a href="$1">$1</a></iframe>', $Text);
	else
		$Text = preg_replace("/\[iframe\](.*?)\[\/iframe\]/ism", '<a href="$1">$1</a>', $Text);

	// Youtube extensions
	if ($tryoembed) {
		$Text = preg_replace_callback("/\[youtube\](https?:\/\/www.youtube.com\/watch\?v\=.*?)\[\/youtube\]/ism", 'tryoembed', $Text);
		$Text = preg_replace_callback("/\[youtube\](www.youtube.com\/watch\?v\=.*?)\[\/youtube\]/ism", 'tryoembed', $Text);
		$Text = preg_replace_callback("/\[youtube\](https?:\/\/youtu.be\/.*?)\[\/youtube\]/ism",'tryoembed',$Text);
	}

	$Text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/youtube\]/ism",'[youtube]$1[/youtube]',$Text);
	$Text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/embed\/(.*?)\[\/youtube\]/ism",'[youtube]$1[/youtube]',$Text);
	$Text = preg_replace("/\[youtube\]https?:\/\/youtu.be\/(.*?)\[\/youtube\]/ism",'[youtube]$1[/youtube]',$Text);

	if ($tryoembed)
		$Text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", '<iframe width="' . $a->videowidth . '" height="' . $a->videoheight . '" src="https://www.youtube.com/embed/$1" frameborder="0" ></iframe>', $Text);
	else
		$Text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism",
					'<a href="https://www.youtube.com/watch?v=$1" target="_blank">https://www.youtube.com/watch?v=$1</a>', $Text);

	if ($tryoembed) {
		$Text = preg_replace_callback("/\[vimeo\](https?:\/\/player.vimeo.com\/video\/[0-9]+).*?\[\/vimeo\]/ism",'tryoembed',$Text);
		$Text = preg_replace_callback("/\[vimeo\](https?:\/\/vimeo.com\/[0-9]+).*?\[\/vimeo\]/ism",'tryoembed',$Text);
	}

	$Text = preg_replace("/\[vimeo\]https?:\/\/player.vimeo.com\/video\/([0-9]+)(.*?)\[\/vimeo\]/ism",'[vimeo]$1[/vimeo]',$Text);
	$Text = preg_replace("/\[vimeo\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/vimeo\]/ism",'[vimeo]$1[/vimeo]',$Text);

	if ($tryoembed)
		$Text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", '<iframe width="' . $a->videowidth . '" height="' . $a->videoheight . '" src="https://player.vimeo.com/video/$1" frameborder="0" ></iframe>', $Text);
	else
		$Text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism",
					'<a href="https://vimeo.com/$1" target="_blank">https://vimeo.com/$1</a>', $Text);

//	$Text = preg_replace("/\[youtube\](.*?)\[\/youtube\]/", '<object width="425" height="350" type="application/x-shockwave-flash" data="http://www.youtube.com/v/$1" ><param name="movie" value="http://www.youtube.com/v/$1"></param><!--[if IE]><embed src="http://www.youtube.com/v/$1" type="application/x-shockwave-flash" width="425" height="350" /><![endif]--></object>', $Text);

	// oembed tag
	$Text = oembed_bbcode2html($Text);

	// Avoid triple linefeeds through oembed
	$Text = str_replace("<br style='clear:left'></span><br /><br />", "<br style='clear:left'></span><br />", $Text);

	// If we found an event earlier, strip out all the event code and replace with a reformatted version.
	// Replace the event-start section with the entire formatted event. The other bbcode is stripped.
	// Summary (e.g. title) is required, earlier revisions only required description (in addition to
	// start which is always required). Allow desc with a missing summary for compatibility.

	if((x($ev,'desc') || x($ev,'summary')) && x($ev,'start')) {
		$sub = format_event_html($ev, $simplehtml);

		$Text = preg_replace("/\[event\-summary\](.*?)\[\/event\-summary\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-description\](.*?)\[\/event\-description\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-start\](.*?)\[\/event\-start\]/ism",$sub,$Text);
		$Text = preg_replace("/\[event\-finish\](.*?)\[\/event\-finish\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-location\](.*?)\[\/event\-location\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-adjust\](.*?)\[\/event\-adjust\]/ism",'',$Text);
	}


	//replace oneliner <code> with <key>
	$Text = preg_replace_callback("|(?!<br[^>]*>)<code>([^<]*)</code>(?!<br[^>]*>)|ism", 'bb_onelinecode_cb', $Text);

	// Unhide all [noparse] contained bbtags unspacefying them
	// and triming the [noparse] tag.

	$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_unspacefy_and_trim',$Text);
	$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_unspacefy_and_trim',$Text);
	$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_unspacefy_and_trim',$Text);


	$Text = preg_replace('/\[\&amp\;([#a-z0-9]+)\;\]/','&$1;',$Text);
	$Text = preg_replace('/\&\#039\;/','\'',$Text);
	$Text = preg_replace('/\&quot\;/','"',$Text);

	// fix any escaped ampersands that may have been converted into links
	$Text = preg_replace("/\<([^>]*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism",'<$1$2=$3&$4>',$Text);
	$Text = preg_replace("/\<([^>]*?)(src|href)=\"(?!http|ftp|mailto|gopher|cid)(.*?)\>/ism",'<$1$2="">',$Text);

	if($saved_image)
		$Text = bb_replace_images($Text, $saved_image);

	// Clean up the HTML by loading and saving the HTML with the DOM.
	// Bad structured html can break a whole page.
	// For performance reasons do it only with ativated item cache or at export.
	if (!$tryoembed OR (get_itemcachepath() != "")) {
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;

		$Text = mb_convert_encoding($Text, 'HTML-ENTITIES', "UTF-8");

		$doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">';
		$encoding = '<?xml encoding="UTF-8">';
		@$doc->loadHTML($encoding.$doctype."<html><body>".$Text."</body></html>");
		$doc->encoding = 'UTF-8';
		$Text = $doc->saveHTML();
		$Text = str_replace(array("<html><body>", "</body></html>", $doctype, $encoding), array("", "", "", ""), $Text);

		$Text = str_replace('<br></li>','</li>', $Text);

		//$Text = mb_convert_encoding($Text, "UTF-8", 'HTML-ENTITIES');
	}

	// Clean up some useless linebreaks in lists
	//$Text = str_replace('<br /><ul','<ul ', $Text);
	//$Text = str_replace('</ul><br />','</ul>', $Text);
	//$Text = str_replace('</li><br />','</li>', $Text);
	//$Text = str_replace('<br /><li>','<li>', $Text);
	//	$Text = str_replace('<br /><ul','<ul ', $Text);

	call_hooks('bbcode',$Text);

	return trim($Text);
}
?>
