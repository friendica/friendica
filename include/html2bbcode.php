<?php
/**
 * @file include/html2bbcode.php
 * @brief Converter for HTML to BBCode
 *
 * Made by: ike@piratenpartei.de
 * Originally made for the syncom project: http://wiki.piratenpartei.de/Syncom
 * 					https://github.com/annando/Syncom
 */

use Friendica\Core\Addon;
use Friendica\Util\XML;

function node2bbcode(&$doc, $oldnode, $attributes, $startbb, $endbb)
{
	do {
		$done = node2bbcodesub($doc, $oldnode, $attributes, $startbb, $endbb);
	} while ($done);
}

function node2bbcodesub(&$doc, $oldnode, $attributes, $startbb, $endbb)
{
	$savestart = str_replace('$', '\x01', $startbb);
	$replace = false;

	$xpath = new DomXPath($doc);

	$list = $xpath->query("//".$oldnode);
	foreach ($list as $oldNode) {
		$attr = [];
		if ($oldNode->attributes->length) {
			foreach ($oldNode->attributes as $attribute) {
				$attr[$attribute->name] = $attribute->value;
			}
		}

		$replace = true;

		$startbb = $savestart;

		$i = 0;

		foreach ($attributes as $attribute => $value) {
			$startbb = str_replace('\x01'.++$i, '$1', $startbb);
			if (strpos('*'.$startbb, '$1') > 0) {
				if ($replace && (@$attr[$attribute] != '')) {
					$startbb = preg_replace($value, $startbb, $attr[$attribute], -1, $count);

					// If nothing could be changed
					if ($count == 0) {
						$replace = false;
					}
				} else {
					$replace = false;
				}
			} else {
				if (@$attr[$attribute] != $value) {
					$replace = false;
				}
			}
		}

		if ($replace) {
			$StartCode = $oldNode->ownerDocument->createTextNode($startbb);
			$EndCode = $oldNode->ownerDocument->createTextNode($endbb);

			$oldNode->parentNode->insertBefore($StartCode, $oldNode);

			if ($oldNode->hasChildNodes()) {
				foreach ($oldNode->childNodes as $child) {
					$newNode = $child->cloneNode(true);
					$oldNode->parentNode->insertBefore($newNode, $oldNode);
				}
			}

			$oldNode->parentNode->insertBefore($EndCode, $oldNode);
			$oldNode->parentNode->removeChild($oldNode);
		}
	}
	return($replace);
}

function html2bbcode($message, $basepath = '')
{

	$message = str_replace("\r", "", $message);

	// Removing code blocks before the whitespace removal processing below
	$codeblocks = [];
	$message = preg_replace_callback(
		'#<pre><code(?: class="([^"]*)")?>(.*)</code></pre>#iUs',
		function ($matches) use (&$codeblocks) {
			$return = '[codeblock-' . count($codeblocks) . ']';

			$prefix = '[code]';
			if ($matches[1] != '') {
				$prefix = '[code=' . $matches[1] . ']';
			}
			$codeblocks[] = $prefix . trim($matches[2]) . '[/code]';
			return $return;
		},
		$message
	);

	$message = str_replace(
		[
			"<li><p>",
			"</p></li>",
		],
		[
			"<li>",
			"</li>",
		],
		$message
	);

	// remove namespaces
	$message = preg_replace('=<(\w+):(.+?)>=', '<removeme>', $message);
	$message = preg_replace('=</(\w+):(.+?)>=', '</removeme>', $message);

	$doc = new DOMDocument();
	$doc->preserveWhiteSpace = false;

	$message = mb_convert_encoding($message, 'HTML-ENTITIES', "UTF-8");

	@$doc->loadHTML($message);

	XML::deleteNode($doc, 'style');
	XML::deleteNode($doc, 'head');
	XML::deleteNode($doc, 'title');
	XML::deleteNode($doc, 'meta');
	XML::deleteNode($doc, 'xml');
	XML::deleteNode($doc, 'removeme');

	$xpath = new DomXPath($doc);
	$list = $xpath->query("//pre");
	foreach ($list as $node) {
		$node->nodeValue = str_replace("\n", "\r", $node->nodeValue);
	}

	$message = $doc->saveHTML();
	$message = str_replace(["\n<", ">\n", "\r", "\n", "\xC3\x82\xC2\xA0"], ["<", ">", "<br />", " ", ""], $message);
	$message = preg_replace('= [\s]*=i', " ", $message);
	@$doc->loadHTML($message);

	node2bbcode($doc, 'html', [], "", "");
	node2bbcode($doc, 'body', [], "", "");

	// Outlook-Quote - Variant 1
	node2bbcode($doc, 'p', ['class'=>'MsoNormal', 'style'=>'margin-left:35.4pt'], '[quote]', '[/quote]');

	// Outlook-Quote - Variant 2
	node2bbcode($doc, 'div', ['style'=>'border:none;border-left:solid blue 1.5pt;padding:0cm 0cm 0cm 4.0pt'], '[quote]', '[/quote]');

	// MyBB-Stuff
	node2bbcode($doc, 'span', ['style'=>'text-decoration: underline;'], '[u]', '[/u]');
	node2bbcode($doc, 'span', ['style'=>'font-style: italic;'], '[i]', '[/i]');
	node2bbcode($doc, 'span', ['style'=>'font-weight: bold;'], '[b]', '[/b]');

	/*node2bbcode($doc, 'font', array('face'=>'/([\w ]+)/', 'size'=>'/(\d+)/', 'color'=>'/(.+)/'), '[font=$1][size=$2][color=$3]', '[/color][/size][/font]');
	node2bbcode($doc, 'font', array('size'=>'/(\d+)/', 'color'=>'/(.+)/'), '[size=$1][color=$2]', '[/color][/size]');
	node2bbcode($doc, 'font', array('face'=>'/([\w ]+)/', 'size'=>'/(.+)/'), '[font=$1][size=$2]', '[/size][/font]');
	node2bbcode($doc, 'font', array('face'=>'/([\w ]+)/', 'color'=>'/(.+)/'), '[font=$1][color=$3]', '[/color][/font]');
	node2bbcode($doc, 'font', array('face'=>'/([\w ]+)/'), '[font=$1]', '[/font]');
	node2bbcode($doc, 'font', array('size'=>'/(\d+)/'), '[size=$1]', '[/size]');
	node2bbcode($doc, 'font', array('color'=>'/(.+)/'), '[color=$1]', '[/color]');
	*/
	// Untested
	//node2bbcode($doc, 'span', array('style'=>'/.*font-size:\s*(.+?)[,;].*font-family:\s*(.+?)[,;].*color:\s*(.+?)[,;].*/'), '[size=$1][font=$2][color=$3]', '[/color][/font][/size]');
	//node2bbcode($doc, 'span', array('style'=>'/.*font-size:\s*(\d+)[,;].*/'), '[size=$1]', '[/size]');
	//node2bbcode($doc, 'span', array('style'=>'/.*font-size:\s*(.+?)[,;].*/'), '[size=$1]', '[/size]');

	node2bbcode($doc, 'span', ['style'=>'/.*color:\s*(.+?)[,;].*/'], '[color="$1"]', '[/color]');

	//node2bbcode($doc, 'span', array('style'=>'/.*font-family:\s*(.+?)[,;].*/'), '[font=$1]', '[/font]');

	//node2bbcode($doc, 'div', array('style'=>'/.*font-family:\s*(.+?)[,;].*font-size:\s*(\d+?)pt.*/'), '[font=$1][size=$2]', '[/size][/font]');
	//node2bbcode($doc, 'div', array('style'=>'/.*font-family:\s*(.+?)[,;].*font-size:\s*(\d+?)px.*/'), '[font=$1][size=$2]', '[/size][/font]');
	//node2bbcode($doc, 'div', array('style'=>'/.*font-family:\s*(.+?)[,;].*/'), '[font=$1]', '[/font]');

	// Importing the classes - interesting for importing of posts from third party networks that were exported from friendica
	// Test
	//node2bbcode($doc, 'span', array('class'=>'/([\w ]+)/'), '[class=$1]', '[/class]');
	node2bbcode($doc, 'span', ['class'=>'type-link'], '[class=type-link]', '[/class]');
	node2bbcode($doc, 'span', ['class'=>'type-video'], '[class=type-video]', '[/class]');

	node2bbcode($doc, 'strong', [], '[b]', '[/b]');
	node2bbcode($doc, 'em', [], '[i]', '[/i]');
	node2bbcode($doc, 'b', [], '[b]', '[/b]');
	node2bbcode($doc, 'i', [], '[i]', '[/i]');
	node2bbcode($doc, 'u', [], '[u]', '[/u]');

	node2bbcode($doc, 'big', [], "[size=large]", "[/size]");
	node2bbcode($doc, 'small', [], "[size=small]", "[/size]");

	node2bbcode($doc, 'blockquote', [], '[quote]', '[/quote]');

	node2bbcode($doc, 'br', [], "\n", '');

	node2bbcode($doc, 'p', ['class'=>'MsoNormal'], "\n", "");
	node2bbcode($doc, 'div', ['class'=>'MsoNormal'], "\r", "");

	node2bbcode($doc, 'span', [], "", "");

	node2bbcode($doc, 'span', [], "", "");
	node2bbcode($doc, 'pre', [], "", "");

	node2bbcode($doc, 'div', [], "\r", "\r");
	node2bbcode($doc, 'p', [], "\n", "\n");

	node2bbcode($doc, 'ul', [], "[list]", "[/list]");
	node2bbcode($doc, 'ol', [], "[list=1]", "[/list]");
	node2bbcode($doc, 'li', [], "[*]", "");

	node2bbcode($doc, 'hr', [], "[hr]", "");

	node2bbcode($doc, 'table', [], "", "");
	node2bbcode($doc, 'tr', [], "\n", "");
	node2bbcode($doc, 'td', [], "\t", "");
	//node2bbcode($doc, 'table', array(), "[table]", "[/table]");
	//node2bbcode($doc, 'th', array(), "[th]", "[/th]");
	//node2bbcode($doc, 'tr', array(), "[tr]", "[/tr]");
	//node2bbcode($doc, 'td', array(), "[td]", "[/td]");

	//node2bbcode($doc, 'h1', array(), "\n\n[size=xx-large][b]", "[/b][/size]\n");
	//node2bbcode($doc, 'h2', array(), "\n\n[size=x-large][b]", "[/b][/size]\n");
	//node2bbcode($doc, 'h3', array(), "\n\n[size=large][b]", "[/b][/size]\n");
	//node2bbcode($doc, 'h4', array(), "\n\n[size=medium][b]", "[/b][/size]\n");
	//node2bbcode($doc, 'h5', array(), "\n\n[size=small][b]", "[/b][/size]\n");
	//node2bbcode($doc, 'h6', array(), "\n\n[size=x-small][b]", "[/b][/size]\n");

	node2bbcode($doc, 'h1', [], "\n\n[h1]", "[/h1]\n");
	node2bbcode($doc, 'h2', [], "\n\n[h2]", "[/h2]\n");
	node2bbcode($doc, 'h3', [], "\n\n[h3]", "[/h3]\n");
	node2bbcode($doc, 'h4', [], "\n\n[h4]", "[/h4]\n");
	node2bbcode($doc, 'h5', [], "\n\n[h5]", "[/h5]\n");
	node2bbcode($doc, 'h6', [], "\n\n[h6]", "[/h6]\n");

	node2bbcode($doc, 'a', ['href'=>'/mailto:(.+)/'], '[mail=$1]', '[/mail]');
	node2bbcode($doc, 'a', ['href'=>'/(.+)/'], '[url=$1]', '[/url]');

	node2bbcode($doc, 'img', ['src'=>'/(.+)/', 'width'=>'/(\d+)/', 'height'=>'/(\d+)/'], '[img=$2x$3]$1', '[/img]');
	node2bbcode($doc, 'img', ['src'=>'/(.+)/'], '[img]$1', '[/img]');


	node2bbcode($doc, 'video', ['src'=>'/(.+)/'], '[video]$1', '[/video]');
	node2bbcode($doc, 'audio', ['src'=>'/(.+)/'], '[audio]$1', '[/audio]');
	node2bbcode($doc, 'iframe', ['src'=>'/(.+)/'], '[iframe]$1', '[/iframe]');

	node2bbcode($doc, 'key', [], '[code]', '[/code]');
	node2bbcode($doc, 'code', [], '[code]', '[/code]');

	$message = $doc->saveHTML();

	// I'm removing something really disturbing
	// Don't know exactly what it is
	$message = str_replace(chr(194).chr(160), ' ', $message);

	$message = str_replace("&nbsp;", " ", $message);

	// removing multiple DIVs
	$message = preg_replace('=\r *\r=i', "\n", $message);
	$message = str_replace("\r", "\n", $message);

	Addon::callHooks('html2bbcode', $message);

	$message = strip_tags($message);

	$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

	$message = str_replace(["<"], ["&lt;"], $message);

	// remove quotes if they don't make sense
	$message = preg_replace('=\[/quote\][\s]*\[quote\]=i', "\n", $message);

	$message = preg_replace('=\[quote\]\s*=i', "[quote]", $message);
	$message = preg_replace('=\s*\[/quote\]=i', "[/quote]", $message);

	do {
		$oldmessage = $message;
		$message = str_replace("\n \n", "\n\n", $message);
	} while ($oldmessage != $message);

	do {
		$oldmessage = $message;
		$message = str_replace("\n\n\n", "\n\n", $message);
	} while ($oldmessage != $message);

	do {
		$oldmessage = $message;
		$message = str_replace(
			[
				"[/size]\n\n",
				"\n[hr]",
				"[hr]\n",
				"\n[list",
				"[/list]\n",
				"\n[/",
				"[list]\n",
				"[list=1]\n",
				"\n[*]"],
			[
				"[/size]\n",
				"[hr]",
				"[hr]",
				"[list",
				"[/list]",
				"[/",
				"[list]",
				"[list=1]",
				"[*]"],
			$message
		);
	} while ($message != $oldmessage);

	$message = str_replace(
		['[b][b]', '[/b][/b]', '[i][i]', '[/i][/i]'],
		['[b]', '[/b]', '[i]', '[/i]'],
		$message
	);

	// Handling Yahoo style of mails
	$message = str_replace('[hr][b]From:[/b]', '[quote][b]From:[/b]', $message);

	// Restore code blocks
	$message = preg_replace_callback(
		'#\[codeblock-([0-9]+)\]#iU',
		function ($matches) use ($codeblocks) {
			$return = '';
			if (isset($codeblocks[intval($matches[1])])) {
				$return = $codeblocks[$matches[1]];
			}
			return $return;
		},
		$message
	);

	$message = trim($message);

	if ($basepath != '') {
		$message = addHostname($message, $basepath);
	}

	return $message;
}

/**
 * @brief Sub function to complete incomplete URL
 *
 * @param array  $matches  Result of preg_replace_callback
 * @param string $basepath Basepath that is used to complete the URL
 *
 * @return string The expanded URL
 */
function addHostnameSub($matches, $basepath)
{
	$base = parse_url($basepath);
	unset($base['query']);
	unset($base['fragment']);

	$link = $matches[0];
	$url = $matches[1];

	$parts = array_merge($base, parse_url($url));
	$url2 = unParseUrl($parts);

	return str_replace($url, $url2, $link);
}

/**
 * @brief Complete incomplete URLs in BBCode
 *
 * @param string $body     Body with URLs
 * @param string $basepath Basepath that is used to complete the URL
 *
 * @return string Body with expanded URLs
 */
function addHostname($body, $basepath)
{
	$URLSearchString = "^\[\]";

	$matches = ["/\[url\=([$URLSearchString]*)\].*?\[\/url\]/ism",
			"/\[url\]([$URLSearchString]*)\[\/url\]/ism",
			"/\[img\=[0-9]*x[0-9]*\](.*?)\[\/img\]/ism",
			"/\[img\](.*?)\[\/img\]/ism",
			"/\[zmg\=[0-9]*x[0-9]*\](.*?)\[\/img\]/ism",
			"/\[zmg\](.*?)\[\/zmg\]/ism",
			"/\[video\](.*?)\[\/video\]/ism",
			"/\[audio\](.*?)\[\/audio\]/ism",
			];

	foreach ($matches as $match) {
		$body = preg_replace_callback(
			$match,
			function ($match) use ($basepath) {
				return addHostnameSub($match, $basepath);
			},
			$body
		);
	}
	return $body;
}
