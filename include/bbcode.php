<?php

require_once("include/oembed.php");
require_once('include/event.php');

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

		if (!(strpos(" #;:0123456789", $char) === false))
			$cleaned .= $char;
	}

	return($cleaned);
}

function stripcode_br_cb($s) {
	return '[code]' . str_replace('<br />', '', $s[1]) . '[/code]';
}

function tryoembed($match){
	$url = ((count($match)==2)?$match[1]:$match[2]);
//	logger("tryoembed: $url");

	$o = oembed_fetch_url($url);

	//echo "<pre>"; var_dump($match, $url, $o); killme();

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
		$newbody = str_replace('[$#saved_image' . $cnt . '#$]', '<img src="' . $image .'" alt="' . t('Image/photo') . '" />', $newbody);
		$cnt++;
	}

	return $newbody;
}}

function bb_ShareAttributes($match) {

        $attributes = $match[1];

        $author = "";
        preg_match("/author='(.*?)'/ism", $attributes, $matches);
        if ($matches[1] != "")
                $author = html_entity_decode($matches[1],ENT_QUOTES,'UTF-8');

        preg_match('/author="(.*?)"/ism', $attributes, $matches);
        if ($matches[1] != "")
                $author = $matches[1];

        $link = "";
        preg_match("/link='(.*?)'/ism", $attributes, $matches);
        if ($matches[1] != "")
                $link = $matches[1];

        preg_match('/link="(.*?)"/ism', $attributes, $matches);
        if ($matches[1] != "")
                $link = $matches[1];

        $avatar = "";
        preg_match("/avatar='(.*?)'/ism", $attributes, $matches);
        if ($matches[1] != "")
                $avatar = $matches[1];

        preg_match('/avatar="(.*?)"/ism', $attributes, $matches);
        if ($matches[1] != "")
                $avatar = $matches[1];

        $profile = "";
        preg_match("/profile='(.*?)'/ism", $attributes, $matches);
        if ($matches[1] != "")
                $profile = $matches[1];

        preg_match('/profile="(.*?)"/ism', $attributes, $matches);
        if ($matches[1] != "")
                $profile = $matches[1];

        $headline = '<div class="shared_header">';

	if ($avatar != "")
		$headline .= '<img src="'.$avatar.'" height="32" width="32" >';

	$headline .= sprintf(t('<span><a href="%s" target="external-link">%s</a> wrote the following <a href="%s" target="external-link">post</a>:</span>'), $profile, $author, $link);

        $headline .= "</div>";

        $text = $headline.'<blockquote class="shared_content">'.trim($match[2])."</blockquote>";

        return($text);
}

	// BBcode 2 HTML was written by WAY2WEB.net
	// extended to work with Mistpark/Friendica - Mike Macgirvin

function bbcode($Text,$preserve_nl = false, $tryoembed = true) {

	$a = get_app();

	// Hide all [noparse] contained bbtags by spacefying them
	// POSSIBLE BUG --> Will the 'preg' functions crash if there's an embedded image?

	$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_spacefy',$Text);
	$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_spacefy',$Text);
	$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_spacefy',$Text);


	// Move all spaces out of the tags
	$Text = preg_replace("/\[(\w*)\](\s*)/ism", '$2[$1]', $Text);
	$Text = preg_replace("/(\s*)\[\/(\w*)\]/ism", '[/$2]$1', $Text);

	// Extract the private images which use data url's since preg has issues with
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
	$search = array("\n\n\n", "\n ", " \n", "[/quote]\n\n", "\n[/quote]");
	$replace = array("\n\n", "\n", "\n", "[/quote]\n", "[/quote]");
	do {
		$oldtext = $Text;
		$Text = str_replace($search, $replace, $Text);
	} while ($oldtext != $Text);

	$Text = str_replace(array("\r","\n"), array('<br />','<br />'), $Text);

	if($preserve_nl)
		$Text = str_replace(array("\n","\r"), array('',''),$Text);



	// Set up the parameters for a URL search string
	$URLSearchString = "^\[\]";
	// Set up the parameters for a MAIL search string
	$MAILSearchString = $URLSearchString;


	// Perform URL Search

	$Text = preg_replace("/([^\]\='".'"'."]|^)(https?\:\/\/[a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1<a href="$2" target="external-link">$2</a>', $Text);

	if ($tryoembed)
		$Text = preg_replace_callback("/\[bookmark\=([^\]]*)\].*?\[\/bookmark\]/ism",'tryoembed',$Text);

	$Text = preg_replace("/\[bookmark\=([^\]]*)\](.*?)\[\/bookmark\]/ism",'[url=$1]$2[/url]',$Text);

	if ($tryoembed)
		$Text = preg_replace_callback("/\[url\]([$URLSearchString]*)\[\/url\]/ism",'tryoembed',$Text);

	$Text = preg_replace("/\[url\]([$URLSearchString]*)\[\/url\]/ism", '<a href="$1" target="external-link">$1</a>', $Text);
	$Text = preg_replace("/\[url\=([$URLSearchString]*)\](.*?)\[\/url\]/ism", '<a href="$1" target="external-link">$2</a>', $Text);
	//$Text = preg_replace("/\[url\=([$URLSearchString]*)\]([$URLSearchString]*)\[\/url\]/ism", '<a href="$1" target="_blank">$2</a>', $Text);

	// we may need to restrict this further if it picks up too many strays
	// link acct:user@host to a webfinger profile redirector

	$Text = preg_replace('/acct:(.*?)@(.*?)([ ,])/', '<a href="' . $a->get_baseurl() . '/acctlink?addr=' . "$1@$2" 
		. '" target="extlink" >acct:' . "$1@$2$3" . '</a>',$Text);

	// Perform MAIL Search
	$Text = preg_replace("/\[mail\]([$MAILSearchString]*)\[\/mail\]/", '<a href="mailto:$1">$1</a>', $Text);
	$Text = preg_replace("/\[mail\=([$MAILSearchString]*)\](.*?)\[\/mail\]/", '<a href="mailto:$1">$2</a>', $Text);

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
	$Text = preg_replace("(\[size=(\d*?)\](.*?)\[\/size\])ism","<span style=\"font-size: $1px;\">$2</span>",$Text);
	$Text = preg_replace("(\[size=(.*?)\](.*?)\[\/size\])ism","<span style=\"font-size: $1;\">$2</span>",$Text);

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
	//$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '<img src="$3" style="height: $2px; width: $1px;" >', $Text);
	$Text = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '<img src="$3" style="width: $1px;" >', $Text);

	// Images
	// [img]pathtoimage[/img]
	$Text = preg_replace("/\[img\](.*?)\[\/img\]/ism", '<img src="$1" alt="' . t('Image/photo') . '" />', $Text);

	// Shared content
	$Text = preg_replace_callback("/\[share(.*?)\](.*?)\[\/share\]/ism","bb_ShareAttributes",$Text);

	$Text = preg_replace("/\[crypt\](.*?)\[\/crypt\]/ism",'<br/><img src="' .$a->get_baseurl() . '/images/lock_icon.gif" alt="' . t('Encrypted content') . '" title="' . t('Encrypted content') . '" /><br />', $Text);
	$Text = preg_replace("/\[crypt=(.*?)\](.*?)\[\/crypt\]/ism",'<br/><img src="' .$a->get_baseurl() . '/images/lock_icon.gif" alt="' . t('Encrypted content') . '" title="' . '$1' . ' ' . t('Encrypted content') . '" /><br />', $Text);


	// Try to Oembed
	if ($tryoembed) {
		$Text = preg_replace("/\[video\](.*?\.(ogg|ogv|oga|ogm|webm|mp4))\[\/video\]/ism", '<video src="$1" controls="controls" width="' . $a->videowidth . '" height="' . $a->videoheight . '"><a href="$1">$1</a></video>', $Text);
		$Text = preg_replace("/\[audio\](.*?\.(ogg|ogv|oga|ogm|webm|mp4|mp3))\[\/audio\]/ism", '<audio src="$1" controls="controls"><a href="$1">$1</a></audio>', $Text);

		$Text = preg_replace_callback("/\[video\](.*?)\[\/video\]/ism", 'tryoembed', $Text);
		$Text = preg_replace_callback("/\[audio\](.*?)\[\/audio\]/ism", 'tryoembed', $Text);
	} else {
		$Text = preg_replace("/\[video\](.*?)\[\/video\]/", '$1', $Text);
		$Text = preg_replace("/\[audio\](.*?)\[\/audio\]/", '$1', $Text);
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
		$Text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", '<iframe width="' . $a->videowidth . '" height="' . $a->videoheight . '" src="http://www.youtube.com/embed/$1" frameborder="0" ></iframe>', $Text);
	else
		$Text = preg_replace("/\[youtube\]([A-Za-z0-9\-_=]+)(.*?)\[\/youtube\]/ism", "http://www.youtube.com/watch?v=$1", $Text);


	if ($tryoembed) {
		$Text = preg_replace_callback("/\[vimeo\](https?:\/\/player.vimeo.com\/video\/[0-9]+).*?\[\/vimeo\]/ism",'tryoembed',$Text); 
		$Text = preg_replace_callback("/\[vimeo\](https?:\/\/vimeo.com\/[0-9]+).*?\[\/vimeo\]/ism",'tryoembed',$Text); 
	}

	$Text = preg_replace("/\[vimeo\]https?:\/\/player.vimeo.com\/video\/([0-9]+)(.*?)\[\/vimeo\]/ism",'[vimeo]$1[/vimeo]',$Text); 
	$Text = preg_replace("/\[vimeo\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/vimeo\]/ism",'[vimeo]$1[/vimeo]',$Text);

	if ($tryoembed)
		$Text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", '<iframe width="' . $a->videowidth . '" height="' . $a->videoheight . '" src="http://player.vimeo.com/video/$1" frameborder="0" ></iframe>', $Text);
	else
		$Text = preg_replace("/\[vimeo\]([0-9]+)(.*?)\[\/vimeo\]/ism", "http://vimeo.com/$1", $Text);

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
		$sub = format_event_html($ev);

		$Text = preg_replace("/\[event\-summary\](.*?)\[\/event\-summary\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-description\](.*?)\[\/event\-description\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-start\](.*?)\[\/event\-start\]/ism",$sub,$Text); 
		$Text = preg_replace("/\[event\-finish\](.*?)\[\/event\-finish\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-location\](.*?)\[\/event\-location\]/ism",'',$Text);
		$Text = preg_replace("/\[event\-adjust\](.*?)\[\/event\-adjust\]/ism",'',$Text);
	}

	// Unhide all [noparse] contained bbtags unspacefying them 
	// and triming the [noparse] tag.

	$Text = preg_replace_callback("/\[noparse\](.*?)\[\/noparse\]/ism", 'bb_unspacefy_and_trim',$Text);
	$Text = preg_replace_callback("/\[nobb\](.*?)\[\/nobb\]/ism", 'bb_unspacefy_and_trim',$Text);
	$Text = preg_replace_callback("/\[pre\](.*?)\[\/pre\]/ism", 'bb_unspacefy_and_trim',$Text);


	$Text = preg_replace('/\[\&amp\;([#a-z0-9]+)\;\]/','&$1;',$Text);
	$Text = preg_replace('/\&\#039\;/','\'',$Text);
	$Text = preg_replace('/\&quot\;/','"',$Text);

	// fix any escaped ampersands that may have been converted into links
	$Text = preg_replace("/\<(.*?)(src|href)=(.*?)\&amp\;(.*?)\>/ism",'<$1$2=$3&$4>',$Text);
	$Text = preg_replace("/\<(.*?)(src|href)=\"[^hfm](.*?)\>/ism",'<$1$2="">',$Text);

	if($saved_image)
		$Text = bb_replace_images($Text, $saved_image);

	// Clean up the HTML by loading and saving the HTML with the DOM
	// Only do it when it has to be done - for performance reasons
	if (!$tryoembed) {
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;

		$Text = mb_convert_encoding($Text, 'HTML-ENTITIES', "UTF-8");

		$doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/REC-html40/loose.dtd">';
		@$doc->loadHTML($doctype."<html><body>".$Text."</body></html>");

		$Text = $doc->saveHTML();
		$Text = str_replace(array("<html><body>", "</body></html>", $doctype), array("", "", ""), $Text);

		$Text = str_replace('<br></li>','</li>', $Text);

		$Text = mb_convert_encoding($Text, "UTF-8", 'HTML-ENTITIES');
	}

	call_hooks('bbcode',$Text);

	return $Text;
}

