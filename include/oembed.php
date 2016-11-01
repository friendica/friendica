<?php
function oembed_replacecb($matches){
	$embedurl=$matches[1];
	$j = oembed_fetch_url($embedurl);
	$s =  oembed_format_object($j);
	return $s;
}


function oembed_fetch_url($embedurl, $no_rich_type = false){
	$embedurl = trim($embedurl, "'");
	$embedurl = trim($embedurl, '"');

	$a = get_app();

	$r = q("SELECT * FROM `oembed` WHERE `url` = '%s'",
		dbesc(normalise_link($embedurl)));

	if ($r)
		$txt = $r[0]["content"];
	else
		$txt = Cache::get($a->videowidth . $embedurl);

	// These media files should now be caught in bbcode.php
	// left here as a fallback in case this is called from another source

	$noexts = array("mp3","mp4","ogg","ogv","oga","ogm","webm");
	$ext = pathinfo(strtolower($embedurl),PATHINFO_EXTENSION);


	if(is_null($txt)){
		$txt = "";

		if (!in_array($ext, $noexts)){
			// try oembed autodiscovery
			$redirects = 0;
			$html_text = fetch_url($embedurl, false, $redirects, 15, "text/*"); /**/
			if($html_text){
				$dom = @DOMDocument::loadHTML($html_text);
				if ($dom){
					$xpath = new DOMXPath($dom);
					$attr = "oembed";
					$xattr = oe_build_xpath("class","oembed");
					$entries = $xpath->query("//link[@type='application/json+oembed']");
					foreach($entries as $e){
						$href = $e->getAttributeNode("href")->nodeValue;
						$txt = fetch_url($href . '&maxwidth=' . $a->videowidth);
						break;
					}
					$entries = $xpath->query("//link[@type='text/json+oembed']");
					foreach($entries as $e){
						$href = $e->getAttributeNode("href")->nodeValue;
						$txt = fetch_url($href . '&maxwidth=' . $a->videowidth);
						break;
					}
				}
			}
		}

		if ($txt==false || $txt==""){
			$embedly = get_config("system", "embedly");
			if ($embedly != "") {
				// try embedly service
				$ourl = "https://api.embed.ly/1/oembed?key=".$embedly."&url=".urlencode($embedurl);
				$txt = fetch_url($ourl);

				logger("oembed_fetch_url: ".$txt, LOGGER_DEBUG);
			}
		}

		$txt=trim($txt);

		if ($txt[0]!="{")
			$txt='{"type":"error"}';
		else {	//save in cache
			$j = json_decode($txt);
			if ($j->type != "error")
				q("INSERT INTO `oembed` (`url`, `content`, `created`) VALUES ('%s', '%s', '%s')
					ON DUPLICATE KEY UPDATE `content` = '%s', `created` = '%s'",
					dbesc(normalise_link($embedurl)),
					dbesc($txt), dbesc(datetime_convert()),
					dbesc($txt), dbesc(datetime_convert()));

			Cache::set($a->videowidth.$embedurl,$txt, CACHE_DAY);
		}
	}

	$j = json_decode($txt);

	if (!is_object($j))
		return false;

	// Always embed the SSL version
	if (isset($j->html))
		$j->html = str_replace(array("http://www.youtube.com/", "http://player.vimeo.com/"),
			array("https://www.youtube.com/", "https://player.vimeo.com/"), $j->html);

	$j->embedurl = $embedurl;

	// If fetching information doesn't work, then improve via internal functions
	if (($j->type == "error") OR ($no_rich_type AND ($j->type == "rich"))) {
		require_once("mod/parse_url.php");
		$data = parseurl_getsiteinfo_cached($embedurl, true, false);
		$j->type = $data["type"];

		if ($j->type == "photo") {
			$j->url = $data["url"];
			//$j->width = $data["images"][0]["width"];
			//$j->height = $data["images"][0]["height"];
		}

		if (isset($data["title"]))
		        $j->title = $data["title"];

		if (isset($data["text"]))
	        	$j->description = $data["text"];

		if (is_array($data["images"])) {
		        $j->thumbnail_url = $data["images"][0]["src"];
		        $j->thumbnail_width = $data["images"][0]["width"];
	        	$j->thumbnail_height = $data["images"][0]["height"];
		}
	}

	call_hooks('oembed_fetch_url', $embedurl, $j);

	return $j;
}

function oembed_format_object($j){
	require_once("mod/proxy.php");

	$a = get_app();
	$embedurl = $j->embedurl;
	$jhtml = oembed_iframe($j->embedurl,(isset($j->width) ? $j->width : null), (isset($j->height) ? $j->height : null) );
	$ret="<span class='oembed ".$j->type."'>";
	switch ($j->type) {
		case "video": {
			if (isset($j->thumbnail_url)) {
				$tw = (isset($j->thumbnail_width) && intval($j->thumbnail_width)) ? $j->thumbnail_width:200;
				$th = (isset($j->thumbnail_height) && intval($j->thumbnail_height)) ? $j->thumbnail_height:180;
				// make sure we don't attempt divide by zero, fallback is a 1:1 ratio
				$tr = (($th) ? $tw/$th : 1);

				$th=120; $tw = $th*$tr;
				$tpl=get_markup_template('oembed_video.tpl');
				$ret.=replace_macros($tpl, array(
				'$baseurl' => $a->get_baseurl(),
					'$embedurl'=>$embedurl,
					'$escapedhtml'=>base64_encode($jhtml),
					'$tw'=>$tw,
					'$th'=>$th,
					'$turl'=>$j->thumbnail_url,
				));

			} else {
				$ret=$jhtml;
			}
			//$ret.="<br>";
		}; break;
		case "photo": {
			$ret.= "<img width='".$j->width."' src='".proxy_url($j->url)."'>";
		}; break;
		case "link": {
		}; break;
		case "rich": {
			// not so safe..
			if (!get_config("system","no_oembed_rich_content"))
				$ret.= proxy_parse_html($jhtml);
		}; break;
	}

	// add link to source if not present in "rich" type
	if ($j->type!='rich' || !strpos($j->html,$embedurl) ){
		$ret .= "<h4>";
		if (isset($j->title)) {
			if (isset($j->provider_name))
				$ret .= $j->provider_name.": ";

			$embedlink = (isset($j->title))?$j->title:$embedurl;
			$ret .= "<a href='$embedurl' rel='oembed'>$embedlink</a>";
			if (isset($j->author_name))
				$ret.=" (".$j->author_name.")";
		} elseif (isset($j->provider_name) OR isset($j->author_name)) {
			$embedlink = "";
			if (isset($j->provider_name))
				$embedlink .= $j->provider_name;

			if (isset($j->author_name)) {
				if ($embedlink != "")
					$embedlink .= ": ";

				$embedlink .= $j->author_name;
			}
			if (trim($embedlink) == "")
				$embedlink = $embedurl;

			$ret .= "<a href='$embedurl' rel='oembed'>$embedlink</a>";
		}
		//if (isset($j->author_name)) $ret.=" by ".$j->author_name;
		//if (isset($j->provider_name)) $ret.=" on ".$j->provider_name;
		$ret .= "</h4>";
	} else {
		// add <a> for html2bbcode conversion
		$ret .= "<a href='$embedurl' rel='oembed'>$embedurl</a>";
	}
	$ret.="</span>";
	$ret = str_replace("\n","",$ret);
	return mb_convert_encoding($ret, 'HTML-ENTITIES', mb_detect_encoding($ret));
}

/**
 * @brief Generates the iframe HTML for an oembed attachment.
 *
 * Width and height are given by the remote, and are regularly too small for
 * the generated iframe.
 *
 * The width is entirely discarded for the actual width of the post, while fixed
 * height is used as a starting point before the inevitable resizing.
 *
 * Since the iframe is automatically resized on load, there are no need for ugly
 * and impractical scrollbars.
 *
 * @param string $src Original remote URL to embed
 * @param string $width
 * @param string $height
 * @return string formatted HTML
 *
 * @see oembed_format_object()
 */
function oembed_iframe($src, $width, $height) {
	if (!$height || strstr($height,'%')) {
		$height = '200';
	}
	$width = '100%';

	$a = get_app();
	$s = $a->get_baseurl() . '/oembed/'.base64url_encode($src);
	return '<iframe onload="resizeIframe(this);" class="embed_rich" height="' . $height . '" width="' . $width . '" src="' . $s . '" scrolling="no" frameborder="no">' . t('Embedded content') . '</iframe>';
}



function oembed_bbcode2html($text){
	$stopoembed = get_config("system","no_oembed");
	if ($stopoembed == true){
		return preg_replace("/\[embed\](.+?)\[\/embed\]/is", "<!-- oembed $1 --><i>". t('Embedding disabled') ." : $1</i><!-- /oembed $1 -->" ,$text);
	}
	return preg_replace_callback("/\[embed\](.+?)\[\/embed\]/is", 'oembed_replacecb' ,$text);
}


function oe_build_xpath($attr, $value){
	// http://westhoffswelt.de/blog/0036_xpath_to_select_html_by_class.html
	return "contains( normalize-space( @$attr ), ' $value ' ) or substring( normalize-space( @$attr ), 1, string-length( '$value' ) + 1 ) = '$value ' or substring( normalize-space( @$attr ), string-length( @$attr ) - string-length( '$value' ) ) = ' $value' or @$attr = '$value'";
}

function oe_get_inner_html( $node ) {
    $innerHTML= '';
    $children = $node->childNodes;
    foreach ($children as $child) {
        $innerHTML .= $child->ownerDocument->saveXML( $child );
    }
    return $innerHTML;
}

/**
 * Find <span class='oembed'>..<a href='url' rel='oembed'>..</a></span>
 * and replace it with [embed]url[/embed]
 */
function oembed_html2bbcode($text) {
	// start parser only if 'oembed' is in text
	if (strpos($text, "oembed")){

		// convert non ascii chars to html entities
		$html_text = mb_convert_encoding($text, 'HTML-ENTITIES', mb_detect_encoding($text));

		// If it doesn't parse at all, just return the text.
		$dom = @DOMDocument::loadHTML($html_text);
		if(! $dom)
			return $text;
		$xpath = new DOMXPath($dom);
		$attr = "oembed";

		$xattr = oe_build_xpath("class","oembed");
		$entries = $xpath->query("//span[$xattr]");

		$xattr = "@rel='oembed'";//oe_build_xpath("rel","oembed");
		foreach($entries as $e) {
			$href = $xpath->evaluate("a[$xattr]/@href", $e)->item(0)->nodeValue;
			if(!is_null($href)) $e->parentNode->replaceChild(new DOMText("[embed]".$href."[/embed]"), $e);
		}
		return oe_get_inner_html( $dom->getElementsByTagName("body")->item(0) );
	} else {
		return $text;
	}
}
