<?php
function oembed_replacecb($matches){
//	logger('oembedcb');
	$embedurl=$matches[1];
	$j = oembed_fetch_url($embedurl);
	$s =  oembed_format_object($j);
	return $s;//oembed_iframe($s,$j->width,$j->height);


}


function oembed_fetch_url($embedurl){

	$txt = Cache::get($embedurl);

	// These media files should now be caught in bbcode.php
	// left here as a fallback in case this is called from another source

	$noexts = array("mp3","mp4","ogg","ogv","oga","ogm","webm");
	$ext = pathinfo(strtolower($embedurl),PATHINFO_EXTENSION);
	
				
	if(is_null($txt)){
		$txt = "";
		
		if (!in_array($ext, $noexts)){
			// try oembed autodiscovery
			$redirects = 0;
			$html_text = fetch_url($embedurl, false, $redirects, 15, "text/*");
			if($html_text){
				$dom = @DOMDocument::loadHTML($html_text);
				if ($dom){
					$xpath = new DOMXPath($dom);
					$attr = "oembed";
				
					$xattr = oe_build_xpath("class","oembed");
					$entries = $xpath->query("//link[@type='application/json+oembed']");
					foreach($entries as $e){
						$href = $e->getAttributeNode("href")->nodeValue;
						$txt = fetch_url($href . '&maxwidth=425');
						break;
					}
				}
			}
		}
		
		if ($txt==false || $txt==""){
			// try oohembed service
			$ourl = "http://oohembed.com/oohembed/?url=".urlencode($embedurl).'&maxwidth=425';  
			$txt = fetch_url($ourl);
		}
		
		$txt=trim($txt);
		if ($txt[0]!="{") $txt='{"type":"error"}';
	
		//save in cache
		Cache::set($embedurl,$txt);

	}
	
	$j = json_decode($txt);
	$j->embedurl = $embedurl;
	return $j;
}
	
function oembed_format_object($j){
	$embedurl = $j->embedurl;
	$jhtml = oembed_iframe($j->embedurl,(isset($j->width) ? $j->width : null), (isset($j->height) ? $j->height : null) );
	$ret="<span class='oembed ".$j->type."'>";
	switch ($j->type) {
		case "video": {
			if (isset($j->thumbnail_url)) {
				$tw = (isset($j->thumbnail_width)) ? $j->thumbnail_width:200;
				$th = (isset($j->thumbnail_height)) ? $j->thumbnail_height:180;
				$tr = $tw/$th;
				
				$th=120; $tw = $th*$tr;
				$tpl=get_markup_template('oembed_video.tpl');
				$ret.=replace_macros($tpl, array(
					'$embedurl'=>$embedurl,
					'$escapedhtml'=>base64_encode($jhtml),
					'$tw'=>$tw,
					'$th'=>$th,
					'$turl'=>$j->thumbnail_url,
				));
				
			} else {
				$ret=$jhtml;
			}
			$ret.="<br>";
		}; break;
		case "photo": {
			$ret.= "<img width='".$j->width."' height='".$j->height."' src='".$j->url."'>";
			$ret.="<br>";
		}; break;  
		case "link": {
			//$ret = "<a href='".$embedurl."'>".$j->title."</a>";
		}; break;  
		case "rich": {
			// not so safe.. 
			$ret.= $jhtml;
		}; break;
	}

	// add link to source if not present in "rich" type
	if (  $j->type!='rich' || !strpos($j->html,$embedurl) ){
		$embedlink = (isset($j->title))?$j->title:$embedurl;
		$ret .= "<a href='$embedurl' rel='oembed'>$embedlink</a>";
		if (isset($j->author_name)) $ret.=" by ".$j->author_name;
		if (isset($j->provider_name)) $ret.=" on ".$j->provider_name;
	} else {
		// add <a> for html2bbcode conversion
		$ret .= "<a href='$embedurl' rel='oembed'/>";
	}
	$ret.="<br style='clear:left'></span>";
	return  mb_convert_encoding($ret, 'HTML-ENTITIES', mb_detect_encoding($ret));
}

function oembed_iframe($src,$width,$height) {
	if(! $width || strstr($width,'%'))
		$width = '640';
	if(! $height || strstr($height,'%'))
		$height = '300';
	// try and leave some room for the description line. 
	$height = intval($height) + 80;
	$width  = intval($width) + 40;

	$a = get_app();

	$s = $a->get_baseurl()."/oembed/".base64url_encode($src);
	return '<iframe height="' . $height . '" width="' . $width . '" src="' . $s . '" frameborder="no" >' . t('Embedded content') . '</iframe>'; 

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



