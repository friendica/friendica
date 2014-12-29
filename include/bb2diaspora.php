<?php

require_once("include/oembed.php");
require_once("include/event.php");
require_once("library/markdown.php");
require_once("include/html2bbcode.php");
require_once("include/bbcode.php");
require_once("include/markdownify/markdownify.php");


// we don't want to support a bbcode specific markdown interpreter
// and the markdown library we have is pretty good, but provides HTML output.
// So we'll use that to convert to HTML, then convert the HTML back to bbcode,
// and then clean up a few Diaspora specific constructs.

function diaspora2bb($s) {

	$s = html_entity_decode($s,ENT_COMPAT,'UTF-8');

	// Simply remove cr.
	$s = str_replace("\r","",$s);

	// <br/> is invalid. Replace it with the valid expression
	$s = str_replace(array("<br/>", "</p>", "<p>", '<p dir="ltr">'),array("<br />", "<br />", "<br />", "<br />"),$s);

	$s = preg_replace('/\@\{(.+?)\; (.+?)\@(.+?)\}/','@[url=https://$3/u/$2]$1[/url]',$s);

	// Escaping the hash tags
	$s = preg_replace('/\#([^\s\#])/','&#35;$1',$s);

	$s = Markdown($s);

	$s = str_replace('&#35;','#',$s);

	$s = html2bbcode($s);

	// protect the recycle symbol from turning into a tag, but without unescaping angles and naked ampersands
	$s = str_replace('&#x2672;',html_entity_decode('&#x2672;',ENT_QUOTES,'UTF-8'),$s);

	// Convert everything that looks like a link to a link
	$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2$3]$2$3[/url]',$s);

	//$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)(vimeo|youtu|www\.youtube|soundcloud)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2$3$4]$2$3$4[/url]',$s);
	$s = bb_tag_preg_replace("/\[url\=?(.*?)\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/url\]/ism",'[youtube]$2[/youtube]','url',$s);
	$s = bb_tag_preg_replace("/\[url\=https?:\/\/www.youtube.com\/watch\?v\=(.*?)\].*?\[\/url\]/ism",'[youtube]$1[/youtube]','url',$s);
	$s = bb_tag_preg_replace("/\[url\=?(.*?)\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/url\]/ism",'[vimeo]$2[/vimeo]','url',$s);
	$s = bb_tag_preg_replace("/\[url\=https?:\/\/vimeo.com\/([0-9]+)\](.*?)\[\/url\]/ism",'[vimeo]$1[/vimeo]','url',$s);
	// remove duplicate adjacent code tags
	$s = preg_replace("/(\[code\])+(.*?)(\[\/code\])+/ism","[code]$2[/code]", $s);

	// Don't show link to full picture (until it is fixed)
	$s = scale_external_images($s, false);

	return $s;
}

function bb2diaspora($Text,$preserve_nl = false, $fordiaspora = true) {

	$OriginalText = $Text;

	// Since Diaspora is creating a summary for links, this function removes them before posting
	if ($fordiaspora)
		$Text = bb_remove_share_information($Text);

	/**
	 * Transform #tags, strip off the [url] and replace spaces with underscore
	 */
	$URLSearchString = "^\[\]";
	$Text = preg_replace_callback("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/i", create_function('$match',
		'return \'#\'. str_replace(\' \', \'_\', $match[2]);'
	), $Text);


	// Converting images with size parameters to simple images. Markdown doesn't know it.
	$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $Text);

	// Convert it to HTML - don't try oembed
	if ($fordiaspora) {
		$Text = bbcode($Text, $preserve_nl, false, 3);

		// Add all tags that maybe were removed
		if (preg_match_all("/#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism",$OriginalText, $tags)) {
			$tagline = "";
			foreach($tags[2] as $tag)
				if (!strpos($Text, "#".$tag))
					$tagline .= "#".$tag." ";

			$Text = $Text."<br />".$tagline;
		}

	} else {
		$Text = bbcode($Text, $preserve_nl, false, 4);

		// Libertree doesn't convert a harizontal rule if there isn't a linefeed
		$Text = str_replace(array("<hr />", "<hr>"), array("<br /><hr />", "<br><hr>"), $Text);
	}

	// Now convert HTML to Markdown
	$md = new Markdownify(false, false, false);
	$Text = $md->parseString($Text);

	// The Markdownify converter converts underscores '_' in URLs to '\_', which
	// messes up the URL. Manually fix these
	$count = 1;
	$pos = bb_find_open_close($Text, '[', ']', $count);
	while($pos !== false) {
		$start = substr($Text, 0, $pos['start']);
		$subject = substr($Text, $pos['start'], $pos['end'] - $pos['start'] + 1);
		$end = substr($Text, $pos['end'] + 1);

		$subject = str_replace('\_', '_', $subject);
		$Text = $start . $subject . $end;

		$count++;
		$pos = bb_find_open_close($Text, '[', ']', $count);
	}

	// If the text going into bbcode() has a plain URL in it, i.e.
	// with no [url] tags around it, it will come out of parseString()
	// looking like: <http://url.com>, which gets removed by strip_tags().
	// So take off the angle brackets of any such URL
	$Text = preg_replace("/<http(.*?)>/is", "http$1", $Text);

	// Remove all unconverted tags
	$Text = strip_tags($Text);

	// Remove any leading or trailing whitespace, as this will mess up
	// the Diaspora signature verification and cause the item to disappear
	$Text = trim($Text);

	call_hooks('bb2diaspora',$Text);

	return $Text;
}

function unescape_underscores_in_links($m) {
	$y = str_replace('\\_','_', $m[2]);
	return('[' . $m[1] . '](' . $y . ')');
}

function format_event_diaspora($ev) {

	$a = get_app();

	if(! ((is_array($ev)) && count($ev)))
		return '';

	$bd_format = t('l F d, Y \@ g:i A') ; // Friday January 18, 2011 @ 8 AM

	$o = 'Friendica event notification:' . "\n";

	$o .= '**' . (($ev['summary']) ? bb2diaspora($ev['summary']) : bb2diaspora($ev['desc'])) .  '**' . "\n";

	$o .= t('Starts:') . ' ' . '['
		. (($ev['adjust']) ? day_translate(datetime_convert('UTC', 'UTC', 
			$ev['start'] , $bd_format ))
			:  day_translate(datetime_convert('UTC', 'UTC', 
			$ev['start'] , $bd_format)))
		.  '](' . $a->get_baseurl() . '/localtime/?f=&time=' . urlencode(datetime_convert('UTC','UTC',$ev['start'])) . ")\n";

	if(! $ev['nofinish'])
		$o .= t('Finishes:') . ' ' . '[' 
			. (($ev['adjust']) ? day_translate(datetime_convert('UTC', 'UTC', 
				$ev['finish'] , $bd_format ))
				:  day_translate(datetime_convert('UTC', 'UTC', 
				$ev['finish'] , $bd_format )))
			. '](' . $a->get_baseurl() . '/localtime/?f=&time=' . urlencode(datetime_convert('UTC','UTC',$ev['finish'])) . ")\n";

	if(strlen($ev['location']))
		$o .= t('Location:') . bb2diaspora($ev['location']) 
			. "\n";

	$o .= "\n";
	return $o;
}
