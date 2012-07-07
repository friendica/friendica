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

	// for testing purposes: Collect raw markdown articles
	// $file = tempnam("/tmp/friendica/", "markdown");
	// file_put_contents($file, $s);

	$s = html_entity_decode($s,ENT_COMPAT,'UTF-8');

	// Too many new lines. So deactivated the following line
	// $s = str_replace("\r","\n",$s);
	// Simply remove cr.
	$s = str_replace("\r","",$s);

	// <br/> is invalid. Replace it with the valid expression
	$s = str_replace("<br/>","<br />",$s);

	$s = preg_replace('/\@\{(.+?)\; (.+?)\@(.+?)\}/','@[url=https://$3/u/$2]$1[/url]',$s);

	// Escaping the hash tags - doesn't always seem to work
	// $s = preg_replace('/\#([^\s\#])/','\\#$1',$s);
	// This seems to work
	$s = preg_replace('/\#([^\s\#])/','&#35;$1',$s);

	$s = Markdown($s);

	$s = str_replace('&#35;','#',$s);
// we seem to have double linebreaks
//	$s = str_replace("\n",'<br />',$s);

	$s = html2bbcode($s);
//	$s = str_replace('&#42;','*',$s);

	// protect the recycle symbol from turning into a tag, but without unescaping angles and naked ampersands
	$s = str_replace('&#x2672;',html_entity_decode('&#x2672;',ENT_QUOTES,'UTF-8'),$s);

	// Convert everything that looks like a link to a link
	$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2$3]$2$3[/url]',$s);

	//$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)(vimeo|youtu|www\.youtube|soundcloud)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2$3$4]$2$3$4[/url]',$s);
	$s = preg_replace("/\[url\=?(.*?)\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/url\]/ism",'[youtube]$2[/youtube]',$s);
	$s = preg_replace("/\[url\=https?:\/\/www.youtube.com\/watch\?v\=(.*?)\].*?\[\/url\]/ism",'[youtube]$1[/youtube]',$s);
	$s = preg_replace("/\[url\=?(.*?)\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/url\]/ism",'[vimeo]$2[/vimeo]',$s);
	$s = preg_replace("/\[url\=https?:\/\/vimeo.com\/([0-9]+)\](.*?)\[\/url\]/ism",'[vimeo]$1[/vimeo]',$s);
	// remove duplicate adjacent code tags
	$s = preg_replace("/(\[code\])+(.*?)(\[\/code\])+/ism","[code]$2[/code]", $s);

	// Don't show link to full picture (until it is fixed)
	$s = scale_external_images($s, false);

	return $s;
}


function stripdcode_br_cb($s) {
	return '[code]' . str_replace('<br />', "\n\t", $s[1]) . '[/code]';
}


//////////////////////
// The following "diaspora_ul" and "diaspora_ol" are only appropriate for the
// pre-Markdownify conversion. If Markdownify isn't used, use the non-Markdownify
// versions below
//////////////////////
/*
function diaspora_ul($s) {
	// Replace "[*]" followed by any number (including zero) of
	// spaces by "* " to match Diaspora's list format
	if( strpos($s[0], "[list]") === 0 )
		return '<ul class="listbullet" style="list-style-type: circle;">' . preg_replace("/\[\*\]( *)/", "* ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[ul]") === 0 )
		return '<ul class="listbullet" style="list-style-type: circle;">' . preg_replace("/\[\*\]( *)/", "* ", $s[1]) . '</ul>';
	else
		return $s[0];
}


function diaspora_ol($s) {
	// A hack: Diaspora will create a properly-numbered ordered list even
	// if you use '1.' for each element of the list, like:
	//		1. First element
	//		1. Second element
	//		1. Third element
	if( strpos($s[0], "[list=1]") === 0 )
		return '<ul class="listdecimal" style="list-style-type: decimal;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[list=i]") === 0 )
		return '<ul class="listlowerroman" style="list-style-type: lower-roman;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[list=I]") === 0 )
		return '<ul class="listupperroman" style="list-style-type: upper-roman;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[list=a]") === 0 )
		return '<ul class="listloweralpha" style="list-style-type: lower-alpha;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[list=A]") === 0 )
		return '<ul class="listupperalpha" style="list-style-type: upper-alpha;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	elseif( strpos($s[0], "[ol]") === 0 )
		return '<ul class="listdecimal" style="list-style-type: decimal;">' . preg_replace("/\[\*\]( *)/", "1. ", $s[1]) . '</ul>';
	else
		return $s[0];
}
*/

//////////////////////
// Non-Markdownify versions of "diaspora_ol" and "diaspora_ul"
//////////////////////
function diaspora_ul($s) {
	// Replace "[\\*]" followed by any number (including zero) of
	// spaces by "* " to match Diaspora's list format
	return preg_replace("/\[\\\\\*\]( *)/", "* ", $s[1]);
}

function diaspora_ol($s) {
	// A hack: Diaspora will create a properly-numbered ordered list even
	// if you use '1.' for each element of the list, like:
	// 1. First element
	// 1. Second element
	// 1. Third element
	return preg_replace("/\[\\\\\*\]( *)/", "1. ", $s[1]);
}


function bb2diaspora($Text,$preserve_nl = false) {

//////////////////////
// An attempt was made to convert bbcode to html and then to markdown
// consisting of the following lines.
// I'm undoing this as we have a lot of bbcode constructs which
// were simply getting lost, for instance bookmark, vimeo, video, youtube, events, etc.
// We can try this again, but need a very good test sequence to verify
// all the major bbcode constructs that we use are getting through.
//////////////////////
/*
	// bbcode() will convert "[*]" into "<li>" with no closing "</li>"
	// Markdownify() is unable to handle these, as it makes each new
	// "<li>" into a deeper nested element until it crashes. So pre-format
	// the lists as Diaspora lists before sending the $Text to bbcode()
	//
	// Note that to get nested lists to work for Diaspora, we would need
	// to define the closing tag for the list elements. So nested lists
	// are going to be flattened out in Diaspora for now

	$endlessloop = 0;
	while ((((strpos($Text, "[/list]") !== false) && (strpos($Text, "[list") !== false)) ||
	       ((strpos($Text, "[/ol]") !== false) && (strpos($Text, "[ol]") !== false)) || 
	       ((strpos($Text, "[/ul]") !== false) && (strpos($Text, "[ul]") !== false))) && (++$endlessloop < 20)) {
		$Text = preg_replace_callback("/\[list\](.*?)\[\/list\]/is", 'diaspora_ul', $Text);
		$Text = preg_replace_callback("/\[list=1\](.*?)\[\/list\]/is", 'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[list=i\](.*?)\[\/list\]/s",'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[list=I\](.*?)\[\/list\]/s", 'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[list=a\](.*?)\[\/list\]/s", 'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[list=A\](.*?)\[\/list\]/s", 'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[ul\](.*?)\[\/ul\]/is", 'diaspora_ul', $Text);
		$Text = preg_replace_callback("/\[ol\](.*?)\[\/ol\]/is", 'diaspora_ol', $Text);
	}

*/

	// Convert it to HTML - don't try oembed
//	$Text = bbcode($Text, $preserve_nl, false);

	// Now convert HTML to Markdown
//	$md = new Markdownify(false, false, false);
//	$Text = $md->parseString($Text);

	// If the text going into bbcode() has a plain URL in it, i.e.
	// with no [url] tags around it, it will come out of parseString()
	// looking like: <http://url.com>, which gets removed by strip_tags().
	// So take off the angle brackets of any such URL
//	$Text = preg_replace("/<http(.*?)>/is", "http$1", $Text);

	// Remove all unconverted tags
//	$Text = strip_tags($Text);

////// 
// end of bb->html->md conversion attempt
//////



	$ev = bbtoevent($Text);

	// Replace any html brackets with HTML Entities to prevent executing HTML or script
	// Don't use strip_tags here because it breaks [url] search by replacing & with amp

	$Text = str_replace("<", "&lt;", $Text);
	$Text = str_replace(">", "&gt;", $Text);

	// If we find any event code, turn it into an event.
	// After we're finished processing the bbcode we'll
	// replace all of the event code with a reformatted version.

	if($preserve_nl)
		$Text = str_replace(array("\n","\r"), array('',''),$Text);
	else
		// Remove the "return" character, as Diaspora uses only the "newline"
		// character, so having the "return" character can cause signature
		// failures
		$Text = str_replace("\r", "", $Text);


	// Set up the parameters for a URL search string
	$URLSearchString = "^\[\]";
	// Set up the parameters for a MAIL search string
	$MAILSearchString = $URLSearchString;

	// Perform URL Search

	// [img]pathtoimage[/img]

	// the following was added on 10-January-2012 due to an inability of Diaspora's
	// new javascript markdown processor to handle links with images as the link "text"
	// It is not optimal and may be removed if this ability is restored in the future

	$Text = preg_replace("/\[url\=([$URLSearchString]*)\]\[img\](.*?)\[\/img\]\[\/url\]/ism",
		'![' . t('image/photo') . '](' . '$2' . ')' . "\n" . '[' . t('link') . '](' . '$1' . ')', $Text);

	$Text = preg_replace("/\[bookmark\]([$URLSearchString]*)\[\/bookmark\]/ism", '[$1]($1)', $Text);
	$Text = preg_replace("/\[bookmark\=([$URLSearchString]*)\](.*?)\[\/bookmark\]/ism", '[$2]($1)', $Text);

	$Text = preg_replace("/\[url\]([$URLSearchString]*)\[\/url\]/ism", '[$1]($1)', $Text);
	$Text = preg_replace("/\#\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '[#$2]($1)', $Text);
	$Text = preg_replace("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '[$2]($1)', $Text);


	$Text = preg_replace("/\[img\](.*?)\[\/img\]/", '![' . t('image/photo') . '](' . '$1' . ')', $Text);
	$Text = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/", '![' . t('image/photo') . '](' . '$2' . ')', $Text);

	// Perform MAIL Search
	$Text = preg_replace("(\[mail\]([$MAILSearchString]*)\[/mail\])", '[$1](mailto:$1)', $Text);
	$Text = preg_replace("/\[mail\=([$MAILSearchString]*)\](.*?)\[\/mail\]/", '[$2](mailto:$1)', $Text);

	$Text = str_replace('*', '\\*', $Text);
	$Text = str_replace('_', '\\_', $Text);

	$Text = str_replace('`','\\`', $Text);

	// Check for bold text
	$Text = preg_replace("(\[b\](.*?)\[\/b\])is",'**$1**',$Text);

	// Check for italics text
	$Text = preg_replace("(\[i\](.*?)\[\/i\])is",'_$1_',$Text);

	// Check for underline text
	// Replace with italics since Diaspora doesn't have underline
	$Text = preg_replace("(\[u\](.*?)\[\/u\])is",'_$1_',$Text);

	// Check for strike-through text
	$Text = preg_replace("(\[s\](.*?)\[\/s\])is",'**[strike]**$1**[/strike]**',$Text);

	// Check for over-line text
//	$Text = preg_replace("(\[o\](.*?)\[\/o\])is",'<span class="overline">$1</span>',$Text);

	// Check for colored text
	// Remove color since Diaspora doesn't support it
	$Text = preg_replace("(\[color=(.*?)\](.*?)\[\/color\])is","$2",$Text);

	// Check for sized text
	// Remove it since Diaspora doesn't support sizes very well
	$Text = preg_replace("(\[size=(.*?)\](.*?)\[\/size\])is","$2",$Text);

	// Check for list text
	$endlessloop = 0;
	while ((((strpos($Text, "[/list]") !== false) && (strpos($Text, "[list") !== false)) ||
	       ((strpos($Text, "[/ol]") !== false) && (strpos($Text, "[ol]") !== false)) || 
	       ((strpos($Text, "[/ul]") !== false) && (strpos($Text, "[ul]") !== false)) || 
	       ((strpos($Text, "[/li]") !== false) && (strpos($Text, "[li]") !== false))) && (++$endlessloop < 20)) {
		$Text = preg_replace_callback("/\[list\](.*?)\[\/list\]/is", 'diaspora_ul', $Text);
		$Text = preg_replace_callback("/\[list=1\](.*?)\[\/list\]/is", 'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[list=i\](.*?)\[\/list\]/s",'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[list=I\](.*?)\[\/list\]/s", 'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[list=a\](.*?)\[\/list\]/s", 'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[list=A\](.*?)\[\/list\]/s", 'diaspora_ol', $Text);
		$Text = preg_replace_callback("/\[ul\](.*?)\[\/ul\]/is", 'diaspora_ul', $Text);
		$Text = preg_replace_callback("/\[ol\](.*?)\[\/ol\]/is", 'diaspora_ol', $Text);
		$Text = preg_replace("/\[li\]( *)(.*?)\[\/li\]/s", '* $2' ,$Text);
	}

	// Just get rid of table tags since Diaspora doesn't support tables
	$Text = preg_replace("/\[th\](.*?)\[\/th\]/s", '$1' ,$Text);
	$Text = preg_replace("/\[td\](.*?)\[\/td\]/s", '$1' ,$Text);
	$Text = preg_replace("/\[tr\](.*?)\[\/tr\]/s", '$1' ,$Text);
	$Text = preg_replace("/\[table\](.*?)\[\/table\]/s", '$1' ,$Text);

	$Text = preg_replace("/\[table border=(.*?)\](.*?)\[\/table\]/s", '$2' ,$Text);
//	$Text = preg_replace("/\[table border=0\](.*?)\[\/table\]/s", '<table border="0" >$1</table>' ,$Text);


//	$Text = str_replace("[*]", "<li>", $Text);

	// Check for font change text
//	$Text = preg_replace("(\[font=(.*?)\](.*?)\[\/font\])","<span style=\"font-family: $1;\">$2</span>",$Text);


	$Text = preg_replace_callback("/\[code\](.*?)\[\/code\]/is",'stripdcode_br_cb',$Text);

	// Check for [code] text
	$Text = preg_replace("/(\[code\])+(.*?)(\[\/code\])+/is","\t$2\n", $Text);




	// Declare the format for [quote] layout
	//	$QuoteLayout = '<blockquote>$1</blockquote>';
	// Check for [quote] text
	$Text = preg_replace("/\[quote\](.*?)\[\/quote\]/is",">$1\n\n", $Text);
	$Text = preg_replace("/\[quote=(.*?)\](.*?)\[\/quote\]/is",">$2\n\n", $Text);

	// Images

	// html5 video and audio

	$Text = preg_replace("/\[video\](.*?)\[\/video\]/", '$1', $Text);

	$Text = preg_replace("/\[audio\](.*?)\[\/audio\]/", '$1', $Text);

//	$Text = preg_replace("/\[iframe\](.*?)\[\/iframe\]/", '<iframe src="$1" width="425" height="350"><a href="$1">$1</a></iframe>', $Text);

	// [img=widthxheight]image source[/img]
//	$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/", '<img src="$3" style="height:{$2}px; width:{$1}px;" >', $Text);

	$Text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/youtube\]/ism",'http://www.youtube.com/watch?v=$1',$Text); 
	$Text = preg_replace("/\[youtube\]https?:\/\/www.youtube.com\/embed\/(.*?)\[\/youtube\]/ism",'http://www.youtube.com/watch?v=$1',$Text); 
	$Text = preg_replace("/\[youtube\]https?:\/\/youtu.be\/(.*?)\[\/youtube\]/ism",'http://www.youtube.com/watch?v=$1',$Text); 
	$Text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", 'http://www.youtube.com/watch?v=$1', $Text);

	$Text = preg_replace("/\[vimeo\]https?:\/\/player.vimeo.com\/video\/([0-9]+)(.*?)\[\/vimeo\]/ism",'http://vimeo.com/$1',$Text); 
	$Text = preg_replace("/\[vimeo\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/vimeo\]/ism",'http://vimeo.com/$1',$Text); 
	$Text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", 'http://vimeo.com/$1',$Text);


	$Text = str_replace('[nosmile]','',$Text);

	// oembed tag
	//	$Text = oembed_bbcode2html($Text);

	// If we found an event earlier, strip out all the event code and replace with a reformatted version.

	if(x($ev,'start')) {

		$sub = format_event_diaspora($ev);

		$Text = preg_replace("/\[event\-summary\](.*?)\[\/event\-summary\]/is",'',$Text);
		$Text = preg_replace("/\[event\-description\](.*?)\[\/event\-description\]/is",'',$Text);
		$Text = preg_replace("/\[event\-start\](.*?)\[\/event\-start\]/is",$sub,$Text);
		$Text = preg_replace("/\[event\-finish\](.*?)\[\/event\-finish\]/is",'',$Text);
		$Text = preg_replace("/\[event\-location\](.*?)\[\/event\-location\]/is",'',$Text);
		$Text = preg_replace("/\[event\-adjust\](.*?)\[\/event\-adjust\]/is",'',$Text);
	}

	$Text = preg_replace("/\<(.*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism",'<$1$2=$3&$4>',$Text);

	$Text = preg_replace_callback('/\[(.*?)\]\((.*?)\)/ism','unescape_underscores_in_links',$Text);


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
