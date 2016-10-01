<?php
/**
 * @file mod/parse_url.php
 *
 * @todo https://developers.google.com/+/plugins/snippet/
 *
 * @verbatim
 * <meta itemprop="name" content="Toller Titel">
 * <meta itemprop="description" content="Eine tolle Beschreibung">
 * <meta itemprop="image" content="http://maple.libertreeproject.org/images/tree-icon.png">
 *
 * <body itemscope itemtype="http://schema.org/Product">
 *   <h1 itemprop="name">Shiny Trinket</h1>
 *   <img itemprop="image" src="{image-url}" />
 *   <p itemprop="description">Shiny trinkets are shiny.</p>
 * </body>
 * @endverbatim
*/

if (!function_exists('deletenode')) {
    function deletenode(&$doc, $node)
    {
        $xpath = new DomXPath($doc);
        $list = $xpath->query("//".$node);
        foreach ($list as $child) {
            $child->parentNode->removeChild($child);
        }
    }
}

function completeurl($url, $scheme)
{
    $urlarr = parse_url($url);

    if (isset($urlarr["scheme"])) {
        return($url);
    }

    $schemearr = parse_url($scheme);

    $complete = $schemearr["scheme"]."://".$schemearr["host"];

    if (@$schemearr["port"] != "") {
        $complete .= ":".$schemearr["port"];
    }

    if (strpos($urlarr['path'], '/') !== 0) {
        $complete .= '/';
    }

    $complete .= $urlarr["path"];

    if (@$urlarr["query"] != "") {
        $complete .= "?".$urlarr["query"];
    }

    if (@$urlarr["fragment"] != "") {
        $complete .= "#".$urlarr["fragment"];
    }

    return($complete);
}

function parseurl_getsiteinfo_cached($url, $no_guessing = false, $do_oembed = true)
{
    if ($url == "") {
        return false;
    }

    $r = q("SELECT * FROM `parsed_url` WHERE `url` = '%s' AND `guessing` = %d AND `oembed` = %d",
        dbesc(normalise_link($url)), intval(!$no_guessing), intval($do_oembed));

    if ($r) {
        $data = $r[0]["content"];
    }

    if (!is_null($data)) {
        $data = unserialize($data);
        return $data;
    }

    $data = parseurl_getsiteinfo($url, $no_guessing, $do_oembed);

    q("INSERT INTO `parsed_url` (`url`, `guessing`, `oembed`, `content`, `created`) VALUES ('%s', %d, %d, '%s', '%s')
		 ON DUPLICATE KEY UPDATE `content` = '%s', `created` = '%s'",
        dbesc(normalise_link($url)), intval(!$no_guessing), intval($do_oembed),
        dbesc(serialize($data)), dbesc(datetime_convert()),
        dbesc(serialize($data)), dbesc(datetime_convert()));

    return $data;
}

function parseurl_getsiteinfo($url, $no_guessing = false, $do_oembed = true, $count = 1)
{
    require_once("include/network.php");
    require_once("include/Photo.php");

    $a = get_app();

    $siteinfo = array();

    if ($count > 10) {
        logger("parseurl_getsiteinfo: Endless loop detected for ".$url, LOGGER_DEBUG);
        return($siteinfo);
    }

    $url = trim($url, "'");
    $url = trim($url, '"');

    $url = original_url($url);

    $siteinfo["url"] = $url;
    $siteinfo["type"] = "link";

    $stamp1 = microtime(true);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());

    $header = curl_exec($ch);
    $curl_info = @curl_getinfo($ch);
    $http_code = $curl_info['http_code'];
    curl_close($ch);

    $a->save_timestamp($stamp1, "network");

    if ((($curl_info['http_code'] == "301") or ($curl_info['http_code'] == "302") or ($curl_info['http_code'] == "303") or ($curl_info['http_code'] == "307"))
        and (($curl_info['redirect_url'] != "") or ($curl_info['location'] != ""))) {
        if ($curl_info['redirect_url'] != "") {
            $siteinfo = parseurl_getsiteinfo($curl_info['redirect_url'], $no_guessing, $do_oembed, ++$count);
        } else {
            $siteinfo = parseurl_getsiteinfo($curl_info['location'], $no_guessing, $do_oembed, ++$count);
        }
        return($siteinfo);
    }

    // if the file is too large then exit
    if ($curl_info["download_content_length"] > 1000000) {
        return($siteinfo);
    }

    // if it isn't a HTML file then exit
    if (($curl_info["content_type"] != "") and !strstr(strtolower($curl_info["content_type"]), "html")) {
        return($siteinfo);
    }

    if ($do_oembed) {
        require_once("include/oembed.php");

        $oembed_data = oembed_fetch_url($url);

        if ($oembed_data->type != "error") {
            $siteinfo["type"] = $oembed_data->type;
        }

        if (($oembed_data->type == "link") and ($siteinfo["type"] != "photo")) {
            if (isset($oembed_data->title)) {
                $siteinfo["title"] = $oembed_data->title;
            }
            if (isset($oembed_data->description)) {
                $siteinfo["text"] = trim($oembed_data->description);
            }
            if (isset($oembed_data->thumbnail_url)) {
                $siteinfo["image"] = $oembed_data->thumbnail_url;
            }
        }
    }

    $stamp1 = microtime(true);

    // Now fetch the body as well
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $a->get_useragent());

    $header = curl_exec($ch);
    $curl_info = @curl_getinfo($ch);
    $http_code = $curl_info['http_code'];
    curl_close($ch);

    $a->save_timestamp($stamp1, "network");

    // Fetch the first mentioned charset. Can be in body or header
    $charset = "";
    if (preg_match('/charset=(.*?)['."'".'"\s\n]/', $header, $matches)) {
        $charset = trim(trim(trim(array_pop($matches)), ';,'));
    }

    if ($charset == "") {
        $charset = "utf-8";
    }

    $pos = strpos($header, "\r\n\r\n");

    if ($pos) {
        $body = trim(substr($header, $pos));
    } else {
        $body = $header;
    }

    if (($charset != '') and (strtoupper($charset) != "UTF-8")) {
        logger("parseurl_getsiteinfo: detected charset ".$charset, LOGGER_DEBUG);
        //$body = mb_convert_encoding($body, "UTF-8", $charset);
        $body = iconv($charset, "UTF-8//TRANSLIT", $body);
    }

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
        if ($node->attributes->length) {
            foreach ($node->attributes as $attribute) {
                $attr[$attribute->name] = $attribute->value;
            }
        }

        if (@$attr["http-equiv"] == 'refresh') {
            $path = $attr["content"];
            $pathinfo = explode(";", $path);
            $content = "";
            foreach ($pathinfo as $value) {
                if (substr(strtolower($value), 0, 4) == "url=") {
                    $content = substr($value, 4);
                }
            }
            if ($content != "") {
                $siteinfo = parseurl_getsiteinfo($content, $no_guessing, $do_oembed, ++$count);
                return($siteinfo);
            }
        }
    }

    $list = $xpath->query("//title");
    if ($list->length > 0) {
        $siteinfo["title"] = $list->item(0)->nodeValue;
    }

    //$list = $xpath->query("head/meta[@name]");
    $list = $xpath->query("//meta[@name]");
    foreach ($list as $node) {
        $attr = array();
        if ($node->attributes->length) {
            foreach ($node->attributes as $attribute) {
                $attr[$attribute->name] = $attribute->value;
            }
        }

        $attr["content"] = trim(html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8"));

        if ($attr["content"] != "") {
            switch (strtolower($attr["name"])) {
                case "fulltitle":
                    $siteinfo["title"] = $attr["content"];
                    break;
                case "description":
                    $siteinfo["text"] = $attr["content"];
                    break;
                case "thumbnail":
                    $siteinfo["image"] = $attr["content"];
                    break;
                case "twitter:image":
                    $siteinfo["image"] = $attr["content"];
                    break;
                case "twitter:image:src":
                    $siteinfo["image"] = $attr["content"];
                    break;
                case "twitter:card":
                    if (($siteinfo["type"] == "") or ($attr["content"] == "photo")) {
                        $siteinfo["type"] = $attr["content"];
                    }
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
                case "keywords":
                    $keywords = explode(",", $attr["content"]);
                    break;
                case "news_keywords":
                    $keywords = explode(",", $attr["content"]);
                    break;
            }
        }
        if ($siteinfo["type"] == "summary") {
            $siteinfo["type"] = "link";
        }
    }

    if (isset($keywords)) {
        $siteinfo["keywords"] = array();
        foreach ($keywords as $keyword) {
            if (!in_array(trim($keyword), $siteinfo["keywords"])) {
                $siteinfo["keywords"][] = trim($keyword);
            }
        }
    }

    //$list = $xpath->query("head/meta[@property]");
    $list = $xpath->query("//meta[@property]");
    foreach ($list as $node) {
        $attr = array();
        if ($node->attributes->length) {
            foreach ($node->attributes as $attribute) {
                $attr[$attribute->name] = $attribute->value;
            }
        }

        $attr["content"] = trim(html_entity_decode($attr["content"], ENT_QUOTES, "UTF-8"));

        if ($attr["content"] != "") {
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
    }

    if ((@$siteinfo["image"] == "") and !$no_guessing) {
        $list = $xpath->query("//img[@src]");
        foreach ($list as $node) {
            $attr = array();
            if ($node->attributes->length) {
                foreach ($node->attributes as $attribute) {
                    $attr[$attribute->name] = $attribute->value;
                }
            }

            $src = completeurl($attr["src"], $url);
            $photodata = get_photo_info($src);

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
    } elseif ($siteinfo["image"] != "") {
        $src = completeurl($siteinfo["image"], $url);

        unset($siteinfo["image"]);

        $photodata = get_photo_info($src);

        if (($photodata) && ($photodata[0] > 10) and ($photodata[1] > 10)) {
            $siteinfo["images"][] = array("src"=>$src,
                            "width"=>$photodata[0],
                            "height"=>$photodata[1]);
        }
    }

    if ((@$siteinfo["text"] == "") and (@$siteinfo["title"] != "") and !$no_guessing) {
        $text = "";

        $list = $xpath->query("//div[@class='article']");
        foreach ($list as $node) {
            if (strlen($node->nodeValue) > 40) {
                $text .= " ".trim($node->nodeValue);
            }
        }

        if ($text == "") {
            $list = $xpath->query("//div[@class='content']");
            foreach ($list as $node) {
                if (strlen($node->nodeValue) > 40) {
                    $text .= " ".trim($node->nodeValue);
                }
            }
        }

        // If none text was found then take the paragraph content
        if ($text == "") {
            $list = $xpath->query("//p");
            foreach ($list as $node) {
                if (strlen($node->nodeValue) > 40) {
                    $text .= " ".trim($node->nodeValue);
                }
            }
        }

        if ($text != "") {
            $text = trim(str_replace(array("\n", "\r"), array(" ", " "), $text));

            while (strpos($text, "  ")) {
                $text = trim(str_replace("  ", " ", $text));
            }

            $siteinfo["text"] = trim(html_entity_decode(substr($text, 0, 350), ENT_QUOTES, "UTF-8").'...');
        }
    }

    logger("parseurl_getsiteinfo: Siteinfo for ".$url." ".print_r($siteinfo, true), LOGGER_DEBUG);

    call_hooks('getsiteinfo', $siteinfo);

    return($siteinfo);
}

function arr_add_hashes(&$item, $k)
{
    $item = '#' . $item;
}

function parse_url_content(&$a)
{
    require_once("include/items.php");

    $text = null;
    $str_tags = '';

    $textmode = false;

    if (local_user() && (! feature_enabled(local_user(), 'richtext'))) {
        $textmode = true;
    }

    //if($textmode)
    $br = (($textmode) ? "\n" : '<br />');

    if (x($_GET, 'binurl')) {
        $url = trim(hex2bin($_GET['binurl']));
    } else {
        $url = trim($_GET['url']);
    }

    if ($_GET['title']) {
        $title = strip_tags(trim($_GET['title']));
    }

    if ($_GET['description']) {
        $text = strip_tags(trim($_GET['description']));
    }

    if ($_GET['tags']) {
        $arr_tags = str_getcsv($_GET['tags']);
        if (count($arr_tags)) {
            array_walk($arr_tags, 'arr_add_hashes');
            $str_tags = $br . implode(' ', $arr_tags) . $br;
        }
    }

    // add url scheme if missing
    $arrurl = parse_url($url);
    if (!x($arrurl, 'scheme')) {
        if (x($arrurl, 'host')) {
            $url = "http:".$url;
        } else {
            $url = "http://".$url;
        }
    }

    logger('parse_url: ' . $url);

    if ($textmode) {
        $template = '[bookmark=%s]%s[/bookmark]%s';
    } else {
        $template = "<a class=\"bookmark\" href=\"%s\" >%s</a>%s";
    }

    $arr = array('url' => $url, 'text' => '');

    call_hooks('parse_link', $arr);

    if (strlen($arr['text'])) {
        echo $arr['text'];
        killme();
    }


    if ($url && $title && $text) {
        $title = str_replace(array("\r", "\n"), array('', ''), $title);

        if ($textmode) {
            $text = '[quote]' . trim($text) . '[/quote]' . $br;
        } else {
            $text = '<blockquote>' . htmlspecialchars(trim($text)) . '</blockquote><br />';
            $title = htmlspecialchars($title);
        }

        $result = sprintf($template, $url, ($title) ? $title : $url, $text) . $str_tags;

        logger('parse_url (unparsed): returns: ' . $result);

        echo $result;
        killme();
    }

    $siteinfo = parseurl_getsiteinfo($url);

    unset($siteinfo["keywords"]);

    $info = add_page_info_data($siteinfo);

    if (!$textmode) {
        // Replace ' with ’ - not perfect - but the richtext editor has problems otherwise
        $info = str_replace(array("&#039;"), array("&#8217;"), $info);
    }

    echo $info;

    killme();
}
