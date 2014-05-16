<?php
/* To-Do
https://developers.google.com/+/plugins/snippet/

<meta itemprop="name" content="Toller Titel">
<meta itemprop="description" content="Eine tolle Beschreibung">
<meta itemprop="image" content="http://maple.libertreeproject.org/images/tree-icon.png">

<body itemscope itemtype="http://schema.org/Product">
  <h1 itemprop="name">Shiny Trinket</h1>
  <img itemprop="image" src="{image-url}" />
  <p itemprop="description">Shiny trinkets are shiny.</p>
</body>
*/

if(!function_exists('deletenode')) {
	function deletenode(&$doc, $node)
	{
		$xpath = new DomXPath($doc);
		$list = $xpath->query("//".$node);
		foreach ($list as $child)
			$child->parentNode->removeChild($child);
	}
}

function completeurl($url, $scheme) {
        $urlarr = parse_url($url);

        if (isset($urlarr["scheme"]))
                return($url);

        $schemearr = parse_url($scheme);

        $complete = $schemearr["scheme"]."://".$schemearr["host"];

        if (@$schemearr["port"] != "")
                $complete .= ":".$schemearr["port"];

		if(strpos($urlarr['path'],'/') !== 0)
			$complete .= '/';

        $complete .= $urlarr["path"];

        if (@$urlarr["query"] != "")
                $complete .= "?".$urlarr["query"];

        if (@$urlarr["fragment"] != "")
                $complete .= "#".$urlarr["fragment"];

        return($complete);
}

function parseurl_getsiteinfo($url, $no_guessing = false) {

	$siteinfo = array();

	$url = trim($url, "'");
	$url = trim($url, '"');
	$siteinfo["url"] = $url;
	$siteinfo["type"] = "link";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 0);
	curl_setopt($ch, CURLOPT_TIMEOUT, 3);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch,CURLOPT_USERAGENT,' Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:24.0) Gecko/20100101 Firefox/24.0');

	$header = curl_exec($ch);
	$curl_info = @curl_getinfo($ch);
        $http_code = $curl_info['http_code'];
	curl_close($ch);

	if ((($curl_info['http_code'] == "301") OR ($curl_info['http_code'] == "302") OR ($curl_info['http_code'] == "303") OR ($curl_info['http_code'] == "307"))
		AND (($curl_info['redirect_url'] != "") OR ($curl_info['location'] != ""))) {
		if ($curl_info['redirect_url'] != "")
			$siteinfo = parseurl_getsiteinfo($curl_info['redirect_url']);
		else
			$siteinfo = parseurl_getsiteinfo($curl_info['location']);
		return($siteinfo);
	}

	require_once("include/oembed.php");

	$oembed_data = oembed_fetch_url($url);

	if ($oembed_data->type != "error")
		$siteinfo["type"] = $oembed_data->type;

	// Fetch the first mentioned charset. Can be in body or header
	$charset = "";
	if (preg_match('/charset=(.*?)['."'".'"\s\n]/', $header, $matches))
		$charset = trim(array_pop($matches));

	if ($charset == "")
		$charset = "utf-8";

	$pos = strpos($header, "\r\n\r\n");

	if ($pos)
		$body = trim(substr($header, $pos));
	else
		$body = $header;

	$body = mb_convert_encoding($body, "UTF-8", $charset);
	$body = mb_convert_encoding($body, 'HTML-ENTITIES', "UTF-8");

	$doc = new DOMDocument();
	@$doc->loadHTML($body);

	deletenode($doc, 'style');
	deletenode($doc, 'script');
	deletenode($doc, 'option');
	deletenode($doc, 'h1');
	deletenode($doc, 'h2');
	deletenode($doc, 'h3');
	deletenode($doc, 'h4');
	deletenode($doc, 'h5');
	deletenode($doc, 'h6');
	deletenode($doc, 'ol');
	deletenode($doc, 'ul');

	$xpath = new DomXPath($doc);

	$list = $xpath->query("//meta[@content]");
        foreach ($list as $node) {
                $attr = array();
                if ($node->attributes->length)
                        foreach ($node->attributes as $attribute)
                                $attr[$attribute->name] = $attribute->value;

                if (@$attr["http-equiv"] == 'refresh') {
                        $path = $attr["content"];
                        $pathinfo = explode(";", $path);
                        $content = "";
                        foreach ($pathinfo AS $value) {
                                if (substr(strtolower($value), 0, 4) == "url=")
                                        $content = substr($value, 4);
                        }
                        if ($content != "") {
                                $siteinfo = parseurl_getsiteinfo($content);
                                return($siteinfo);
                        }
                }
	}

	//$list = $xpath->query("head/title");
	$list = $xpath->query("//title");
	foreach ($list as $node)
		$siteinfo["title"] =  html_entity_decode($node->nodeValue, ENT_QUOTES, "UTF-8");

	//$list = $xpath->query("head/meta[@name]");
	$list = $xpath->query("//meta[@name]");
	foreach ($list as $node) {
		$attr = array();
		if ($node->attributes->length)
                        foreach ($node->attributes as $attribute)
                                $attr[$attribute->name] = $attribute->value;

		$attr["content"] = trim(html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8"));

		if ($attr["content"] != "")
			switch (strtolower($attr["name"])) {
				case "fulltitle":
					$siteinfo["title"] = $attr["content"];
					break;
				case "description":
					$siteinfo["text"] = $attr["content"];
					break;
				case "twitter:image":
					$siteinfo["image"] = $attr["content"];
					break;
				case "twitter:card":
					if (($siteinfo["type"] == "") OR ($attr["content"] == "photo"))
						$siteinfo["type"] = $attr["content"];
					break;
				case "twitter:description":
					$siteinfo["text"] = $attr["content"];
					break;
				case "twitter:title":
					$siteinfo["title"] = $attr["content"];
					break;
				case "dc.title":
					$siteinfo["title"] = $attr["content"];
					break;
				case "dc.description":
					$siteinfo["text"] = $attr["content"];
					break;
			}
		if ($siteinfo["type"] == "summary")
			$siteinfo["type"] = "link";
	}

	//$list = $xpath->query("head/meta[@property]");
	$list = $xpath->query("//meta[@property]");
	foreach ($list as $node) {
		$attr = array();
		if ($node->attributes->length)
                        foreach ($node->attributes as $attribute)
                                $attr[$attribute->name] = $attribute->value;

		$attr["content"] = trim(html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8"));

		if ($attr["content"] != "")
			switch (strtolower($attr["property"])) {
				case "og:image":
					$siteinfo["image"] = $attr["content"];
					break;
				case "og:title":
					$siteinfo["title"] = $attr["content"];
					break;
				case "og:description":
					$siteinfo["text"] = $attr["content"];
					break;
			}
	}

	if ($oembed_data->type == "link") {
		if (isset($oembed_data->title) AND (trim($oembed_data->title) != ""))
			$siteinfo["title"] = $oembed_data->title;
		if (isset($oembed_data->description) AND (trim($oembed_data->description) != ""))
			$siteinfo["text"] = trim($oembed_data->description);
		if (isset($oembed_data->thumbnail_url) AND (trim($oembed_data->thumbnail_url) != ""))
			$siteinfo["image"] = $oembed_data->thumbnail_url;
	}

	if ((@$siteinfo["image"] == "") AND !$no_guessing) {
            $list = $xpath->query("//img[@src]");
            foreach ($list as $node) {
                $attr = array();
                if ($node->attributes->length)
                    foreach ($node->attributes as $attribute)
                        $attr[$attribute->name] = $attribute->value;

			$src = completeurl($attr["src"], $url);
			$photodata = @getimagesize($src);

			if (($photodata) && ($photodata[0] > 150) and ($photodata[1] > 150)) {
				if ($photodata[0] > 300) {
					$photodata[1] = round($photodata[1] * (300 / $photodata[0]));
					$photodata[0] = 300;
				}
				if ($photodata[1] > 300) {
					$photodata[0] = round($photodata[0] * (300 / $photodata[1]));
					$photodata[1] = 300;
				}
				$siteinfo["images"][] = array("src"=>$src,
								"width"=>$photodata[0],
								"height"=>$photodata[1]);
			}

 		}
    } else {
		$src = completeurl($siteinfo["image"], $url);

		unset($siteinfo["image"]);

		$photodata = @getimagesize($src);

		if (($photodata) && ($photodata[0] > 10) and ($photodata[1] > 10))
			$siteinfo["images"][] = array("src"=>$src,
							"width"=>$photodata[0],
							"height"=>$photodata[1]);
	}

	if ((@$siteinfo["text"] == "") AND (@$siteinfo["title"] != "") AND !$no_guessing) {
		$text = "";

		$list = $xpath->query("//div[@class='article']");
		foreach ($list as $node)
			if (strlen($node->nodeValue) > 40)
				$text .= " ".trim($node->nodeValue);

		if ($text == "") {
			$list = $xpath->query("//div[@class='content']");
			foreach ($list as $node)
				if (strlen($node->nodeValue) > 40)
					$text .= " ".trim($node->nodeValue);
		}

		// If none text was found then take the paragraph content
		if ($text == "") {
			$list = $xpath->query("//p");
			foreach ($list as $node)
				if (strlen($node->nodeValue) > 40)
					$text .= " ".trim($node->nodeValue);
		}

		if ($text != "") {
			$text = trim(str_replace(array("\n", "\r"), array(" ", " "), $text));

			while (strpos($text, "  "))
				$text = trim(str_replace("  ", " ", $text));

			$siteinfo["text"] = trim(html_entity_decode(substr($text,0,350), ENT_QUOTES, "UTF-8").'...');
		}
	}

	logger("parseurl_getsiteinfo: Siteinfo for ".$url." ".print_r($siteinfo, true), LOGGER_DEBUG);

	return($siteinfo);
}

function arr_add_hashes(&$item,$k) {
	$item = '#' . $item;
}

function parse_url_content(&$a) {

	$text = null;
	$str_tags = '';

	$textmode = false;

	if(local_user() && (! feature_enabled(local_user(),'richtext')))
		$textmode = true;

	//if($textmode)
	$br = (($textmode) ? "\n" : '<br />');

	if(x($_GET,'binurl'))
		$url = trim(hex2bin($_GET['binurl']));
	else
		$url = trim($_GET['url']);

	if($_GET['title'])
		$title = strip_tags(trim($_GET['title']));

	if($_GET['description'])
		$text = strip_tags(trim($_GET['description']));

	if($_GET['tags']) {
		$arr_tags = str_getcsv($_GET['tags']);
		if(count($arr_tags)) {
			array_walk($arr_tags,'arr_add_hashes');
			$str_tags = $br . implode(' ',$arr_tags) . $br;
		}
	}

	logger('parse_url: ' . $url);

	if($textmode)
		$template = '[bookmark=%s]%s[/bookmark]%s';
	else
		$template = "<a class=\"bookmark\" href=\"%s\" >%s</a>%s";

	$arr = array('url' => $url, 'text' => '');

	call_hooks('parse_link', $arr);

	if(strlen($arr['text'])) {
		echo $arr['text'];
		killme();
	}


	if($url && $title && $text) {

		$title = str_replace(array("\r","\n"),array('',''),$title);

		if($textmode)
			$text = '[quote]' . trim($text) . '[/quote]' . $br;
		else {
			$text = '<blockquote>' . htmlspecialchars(trim($text)) . '</blockquote><br />';
			$title = htmlspecialchars($title);
		}

		$result = sprintf($template,$url,($title) ? $title : $url,$text) . $str_tags;

		logger('parse_url (unparsed): returns: ' . $result);

		echo $result;
		killme();
	}

	$siteinfo = parseurl_getsiteinfo($url);

	$url= $siteinfo["url"];

	$sitedata = "";

	if($siteinfo["title"] == "") {
		$sitedata .= sprintf($template,$url,$url,'') . $str_tags;
		killme();
	} else {
		$text = $siteinfo["text"];
		$title = $siteinfo["title"];
	}

	$image = "";

	if (($siteinfo["type"] != "video") AND (sizeof($siteinfo["images"]) > 0)){
		/* Execute below code only if image is present in siteinfo */

		$total_images = 0;
		$max_images = get_config('system','max_bookmark_images');
		if($max_images === false)
			$max_images = 2;
		else
			$max_images = intval($max_images);

		foreach ($siteinfo["images"] as $imagedata) {
			if($textmode)
				$image .= '[img='.$imagedata["width"].'x'.$imagedata["height"].']'.$imagedata["src"].'[/img]' . "\n";
			else
				$image .= '<img height="'.$imagedata["height"].'" width="'.$imagedata["width"].'" src="'.$imagedata["src"].'" alt="photo" /><br />';
			$total_images ++;
			if($max_images && $max_images >= $total_images)
				break;
		}
	}

	if(strlen($text)) {
		if($textmode)
			$text = '[quote]'.trim($text).'[/quote]';
		else
			$text = '<blockquote>'.htmlspecialchars(trim($text)).'</blockquote>';
	}

	if($image)
		$text = $br.$br.$image.$text;
	else
		$text = $br.$text;

	$title = str_replace(array("\r","\n"),array('',''),$title);

	$result = sprintf($template,$url,($title) ? $title : $url,$text) . $str_tags;

	logger('parse_url: returns: ' . $result);

	$sitedata .=  trim($result);

	if (($siteinfo["type"] == "video") AND ($url != ""))
		echo "[class=type-video]".$sitedata."[/class]";
	elseif (($siteinfo["type"] != "photo"))
		echo "[class=type-link]".$sitedata."[/class]";
	else
		echo "[class=type-photo]".$title.$br.$image."[/class]";

	killme();
}
?>
