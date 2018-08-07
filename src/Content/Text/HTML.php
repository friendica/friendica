<?php

/**
 * @file src/Content/Text/HTML.php
 */

namespace Friendica\Content\Text;

use DOMDocument;
use DOMXPath;
use Friendica\Core\Addon;
use Friendica\Util\Network;
use Friendica\Util\XML;

class HTML
{
	public static function sanitizeCSS($input)
	{
		$cleaned = "";

		$input = strtolower($input);

		for ($i = 0; $i < strlen($input); $i++) {
			$char = substr($input, $i, 1);

			if (($char >= "a") && ($char <= "z")) {
				$cleaned .= $char;
			}

			if (!(strpos(" #;:0123456789-_.%", $char) === false)) {
				$cleaned .= $char;
			}
		}

		return $cleaned;
	}

	private static function tagToBBCode(DOMDocument $doc, $tag, $attributes, $startbb, $endbb)
	{
		do {
			$done = self::tagToBBCodeSub($doc, $tag, $attributes, $startbb, $endbb);
		} while ($done);
	}

	private static function tagToBBCodeSub(DOMDocument $doc, $tag, $attributes, $startbb, $endbb)
	{
		$savestart = str_replace('$', '\x01', $startbb);
		$replace = false;

		$xpath = new DOMXPath($doc);

		$list = $xpath->query("//" . $tag);
		foreach ($list as $node) {
			$attr = [];
			if ($node->attributes->length) {
				foreach ($node->attributes as $attribute) {
					$attr[$attribute->name] = $attribute->value;
				}
			}

			$replace = true;

			$startbb = $savestart;

			$i = 0;

			foreach ($attributes as $attribute => $value) {
				$startbb = str_replace('\x01' . ++$i, '$1', $startbb);
				if (strpos('*' . $startbb, '$1') > 0) {
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
				$StartCode = $doc->createTextNode($startbb);
				$EndCode = $doc->createTextNode($endbb);

				$node->parentNode->insertBefore($StartCode, $node);

				if ($node->hasChildNodes()) {
					foreach ($node->childNodes as $child) {
						$newNode = $child->cloneNode(true);
						$node->parentNode->insertBefore($newNode, $node);
					}
				}

				$node->parentNode->insertBefore($EndCode, $node);
				$node->parentNode->removeChild($node);
			}
		}

		return $replace;
	}

	/**
	 * Made by: ike@piratenpartei.de
	 * Originally made for the syncom project: http://wiki.piratenpartei.de/Syncom
	 * 					https://github.com/annando/Syncom
	 *
	 * @brief Converter for HTML to BBCode
	 * @param string $message
	 * @param string $basepath
	 * @return string
	 */
	public static function toBBCode($message, $basepath = '')
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
			], [
			"<li>",
			"</li>",
			], $message
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

		self::tagToBBCode($doc, 'html', [], "", "");
		self::tagToBBCode($doc, 'body', [], "", "");

		// Outlook-Quote - Variant 1
		self::tagToBBCode($doc, 'p', ['class' => 'MsoNormal', 'style' => 'margin-left:35.4pt'], '[quote]', '[/quote]');

		// Outlook-Quote - Variant 2
		self::tagToBBCode($doc, 'div', ['style' => 'border:none;border-left:solid blue 1.5pt;padding:0cm 0cm 0cm 4.0pt'],
			'[quote]', '[/quote]');

		// MyBB-Stuff
		self::tagToBBCode($doc, 'span', ['style' => 'text-decoration: underline;'], '[u]', '[/u]');
		self::tagToBBCode($doc, 'span', ['style' => 'font-style: italic;'], '[i]', '[/i]');
		self::tagToBBCode($doc, 'span', ['style' => 'font-weight: bold;'], '[b]', '[/b]');

		/* self::node2BBCode($doc, 'font', array('face'=>'/([\w ]+)/', 'size'=>'/(\d+)/', 'color'=>'/(.+)/'), '[font=$1][size=$2][color=$3]', '[/color][/size][/font]');
		  self::node2BBCode($doc, 'font', array('size'=>'/(\d+)/', 'color'=>'/(.+)/'), '[size=$1][color=$2]', '[/color][/size]');
		  self::node2BBCode($doc, 'font', array('face'=>'/([\w ]+)/', 'size'=>'/(.+)/'), '[font=$1][size=$2]', '[/size][/font]');
		  self::node2BBCode($doc, 'font', array('face'=>'/([\w ]+)/', 'color'=>'/(.+)/'), '[font=$1][color=$3]', '[/color][/font]');
		  self::node2BBCode($doc, 'font', array('face'=>'/([\w ]+)/'), '[font=$1]', '[/font]');
		  self::node2BBCode($doc, 'font', array('size'=>'/(\d+)/'), '[size=$1]', '[/size]');
		  self::node2BBCode($doc, 'font', array('color'=>'/(.+)/'), '[color=$1]', '[/color]');
		 */
		// Untested
		//self::node2BBCode($doc, 'span', array('style'=>'/.*font-size:\s*(.+?)[,;].*font-family:\s*(.+?)[,;].*color:\s*(.+?)[,;].*/'), '[size=$1][font=$2][color=$3]', '[/color][/font][/size]');
		//self::node2BBCode($doc, 'span', array('style'=>'/.*font-size:\s*(\d+)[,;].*/'), '[size=$1]', '[/size]');
		//self::node2BBCode($doc, 'span', array('style'=>'/.*font-size:\s*(.+?)[,;].*/'), '[size=$1]', '[/size]');

		self::tagToBBCode($doc, 'span', ['style' => '/.*color:\s*(.+?)[,;].*/'], '[color="$1"]', '[/color]');

		//self::node2BBCode($doc, 'span', array('style'=>'/.*font-family:\s*(.+?)[,;].*/'), '[font=$1]', '[/font]');
		//self::node2BBCode($doc, 'div', array('style'=>'/.*font-family:\s*(.+?)[,;].*font-size:\s*(\d+?)pt.*/'), '[font=$1][size=$2]', '[/size][/font]');
		//self::node2BBCode($doc, 'div', array('style'=>'/.*font-family:\s*(.+?)[,;].*font-size:\s*(\d+?)px.*/'), '[font=$1][size=$2]', '[/size][/font]');
		//self::node2BBCode($doc, 'div', array('style'=>'/.*font-family:\s*(.+?)[,;].*/'), '[font=$1]', '[/font]');
		// Importing the classes - interesting for importing of posts from third party networks that were exported from friendica
		// Test
		//self::node2BBCode($doc, 'span', array('class'=>'/([\w ]+)/'), '[class=$1]', '[/class]');
		self::tagToBBCode($doc, 'span', ['class' => 'type-link'], '[class=type-link]', '[/class]');
		self::tagToBBCode($doc, 'span', ['class' => 'type-video'], '[class=type-video]', '[/class]');

		self::tagToBBCode($doc, 'strong', [], '[b]', '[/b]');
		self::tagToBBCode($doc, 'em', [], '[i]', '[/i]');
		self::tagToBBCode($doc, 'b', [], '[b]', '[/b]');
		self::tagToBBCode($doc, 'i', [], '[i]', '[/i]');
		self::tagToBBCode($doc, 'u', [], '[u]', '[/u]');
		self::tagToBBCode($doc, 's', [], '[s]', '[/s]');
		self::tagToBBCode($doc, 'del', [], '[s]', '[/s]');
		self::tagToBBCode($doc, 'strike', [], '[s]', '[/s]');

		self::tagToBBCode($doc, 'big', [], "[size=large]", "[/size]");
		self::tagToBBCode($doc, 'small', [], "[size=small]", "[/size]");

		self::tagToBBCode($doc, 'blockquote', [], '[quote]', '[/quote]');

		self::tagToBBCode($doc, 'br', [], "\n", '');

		self::tagToBBCode($doc, 'p', ['class' => 'MsoNormal'], "\n", "");
		self::tagToBBCode($doc, 'div', ['class' => 'MsoNormal'], "\r", "");

		self::tagToBBCode($doc, 'span', [], "", "");

		self::tagToBBCode($doc, 'span', [], "", "");
		self::tagToBBCode($doc, 'pre', [], "", "");

		self::tagToBBCode($doc, 'div', [], "\r", "\r");
		self::tagToBBCode($doc, 'p', [], "\n", "\n");

		self::tagToBBCode($doc, 'ul', [], "[list]", "[/list]");
		self::tagToBBCode($doc, 'ol', [], "[list=1]", "[/list]");
		self::tagToBBCode($doc, 'li', [], "[*]", "");

		self::tagToBBCode($doc, 'hr', [], "[hr]", "");

		self::tagToBBCode($doc, 'table', [], "", "");
		self::tagToBBCode($doc, 'tr', [], "\n", "");
		self::tagToBBCode($doc, 'td', [], "\t", "");
		//self::node2BBCode($doc, 'table', array(), "[table]", "[/table]");
		//self::node2BBCode($doc, 'th', array(), "[th]", "[/th]");
		//self::node2BBCode($doc, 'tr', array(), "[tr]", "[/tr]");
		//self::node2BBCode($doc, 'td', array(), "[td]", "[/td]");
		//self::node2BBCode($doc, 'h1', array(), "\n\n[size=xx-large][b]", "[/b][/size]\n");
		//self::node2BBCode($doc, 'h2', array(), "\n\n[size=x-large][b]", "[/b][/size]\n");
		//self::node2BBCode($doc, 'h3', array(), "\n\n[size=large][b]", "[/b][/size]\n");
		//self::node2BBCode($doc, 'h4', array(), "\n\n[size=medium][b]", "[/b][/size]\n");
		//self::node2BBCode($doc, 'h5', array(), "\n\n[size=small][b]", "[/b][/size]\n");
		//self::node2BBCode($doc, 'h6', array(), "\n\n[size=x-small][b]", "[/b][/size]\n");

		self::tagToBBCode($doc, 'h1', [], "[h1]", "[/h1]");
		self::tagToBBCode($doc, 'h2', [], "[h2]", "[/h2]");
		self::tagToBBCode($doc, 'h3', [], "[h3]", "[/h3]");
		self::tagToBBCode($doc, 'h4', [], "[h4]", "[/h4]");
		self::tagToBBCode($doc, 'h5', [], "[h5]", "[/h5]");
		self::tagToBBCode($doc, 'h6', [], "[h6]", "[/h6]");

		self::tagToBBCode($doc, 'a', ['href' => '/mailto:(.+)/'], '[mail=$1]', '[/mail]');
		self::tagToBBCode($doc, 'a', ['href' => '/(.+)/'], '[url=$1]', '[/url]');

		self::tagToBBCode($doc, 'img', ['src' => '/(.+)/', 'width' => '/(\d+)/', 'height' => '/(\d+)/'], '[img=$2x$3]$1',
			'[/img]');
		self::tagToBBCode($doc, 'img', ['src' => '/(.+)/'], '[img]$1', '[/img]');


		self::tagToBBCode($doc, 'video', ['src' => '/(.+)/'], '[video]$1', '[/video]');
		self::tagToBBCode($doc, 'audio', ['src' => '/(.+)/'], '[audio]$1', '[/audio]');
		self::tagToBBCode($doc, 'iframe', ['src' => '/(.+)/'], '[iframe]$1', '[/iframe]');

		self::tagToBBCode($doc, 'key', [], '[code]', '[/code]');
		self::tagToBBCode($doc, 'code', [], '[code]', '[/code]');

		$message = $doc->saveHTML();

		// I'm removing something really disturbing
		// Don't know exactly what it is
		$message = str_replace(chr(194) . chr(160), ' ', $message);

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
				"[*]"], $message
			);
		} while ($message != $oldmessage);

		$message = str_replace(
			['[b][b]', '[/b][/b]', '[i][i]', '[/i][/i]'], ['[b]', '[/b]', '[i]', '[/i]'], $message
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
			$message = self::qualifyURLs($message, $basepath);
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
	private static function qualifyURLsSub($matches, $basepath)
	{
		$base = parse_url($basepath);
		unset($base['query']);
		unset($base['fragment']);

		$link = $matches[0];
		$url = $matches[1];

		$parts = array_merge($base, parse_url($url));
		$url2 = Network::unparseURL($parts);

		return str_replace($url, $url2, $link);
	}

	/**
	 * @brief Complete incomplete URLs in BBCode
	 *
	 * @param string $body     Body with URLs
	 * @param string $basepath Base path that is used to complete the URL
	 *
	 * @return string Body with expanded URLs
	 */
	private static function qualifyURLs($body, $basepath)
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
				$match, function ($match) use ($basepath) {
					return self::qualifyURLsSub($match, $basepath);
				},
				$body
			);
		}
		return $body;
	}

	private static function breakLines($line, $level, $wraplength = 75)
	{
		if ($wraplength == 0) {
			$wraplength = 2000000;
		}

		$wraplen = $wraplength - $level;

		$newlines = [];

		do {
			$oldline = $line;

			$subline = substr($line, 0, $wraplen);

			$pos = strrpos($subline, ' ');

			if ($pos == 0) {
				$pos = strpos($line, ' ');
			}

			if (($pos > 0) && strlen($line) > $wraplen) {
				$newline = trim(substr($line, 0, $pos));
				if ($level > 0) {
					$newline = str_repeat(">", $level) . ' ' . $newline;
				}

				$newlines[] = $newline . " ";
				$line = substr($line, $pos + 1);
			}
		} while ((strlen($line) > $wraplen) && !($oldline == $line));

		if ($level > 0) {
			$line = str_repeat(">", $level) . ' ' . $line;
		}

		$newlines[] = $line;

		return implode($newlines, "\n");
	}

	private static function quoteLevel($message, $wraplength = 75)
	{
		$lines = explode("\n", $message);

		$newlines = [];
		$level = 0;
		foreach ($lines as $line) {
			$line = trim($line);
			$startquote = false;
			while (strpos("*" . $line, '[quote]') > 0) {
				$level++;
				$pos = strpos($line, '[quote]');
				$line = substr($line, 0, $pos) . substr($line, $pos + 7);
				$startquote = true;
			}

			$currlevel = $level;

			while (strpos("*" . $line, '[/quote]') > 0) {
				$level--;
				if ($level < 0) {
					$level = 0;
				}

				$pos = strpos($line, '[/quote]');
				$line = substr($line, 0, $pos) . substr($line, $pos + 8);
			}

			if (!$startquote || ($line != '')) {
				$newlines[] = self::breakLines($line, $currlevel, $wraplength);
			}
		}

		return implode($newlines, "\n");
	}

	private static function collectURLs($message)
	{
		$pattern = '/<a.*?href="(.*?)".*?>(.*?)<\/a>/is';
		preg_match_all($pattern, $message, $result, PREG_SET_ORDER);

		$urls = [];
		foreach ($result as $treffer) {
			$ignore = false;

			// A list of some links that should be ignored
			$list = ["/user/", "/tag/", "/group/", "/profile/", "/search?search=", "/search?tag=", "mailto:", "/u/", "/node/",
				"//plus.google.com/", "//twitter.com/"];
			foreach ($list as $listitem) {
				if (strpos($treffer[1], $listitem) !== false) {
					$ignore = true;
				}
			}

			if ((strpos($treffer[1], "//twitter.com/") !== false) && (strpos($treffer[1], "/status/") !== false)) {
				$ignore = false;
			}

			if ((strpos($treffer[1], "//plus.google.com/") !== false) && (strpos($treffer[1], "/posts") !== false)) {
				$ignore = false;
			}

			if ((strpos($treffer[1], "//plus.google.com/") !== false) && (strpos($treffer[1], "/photos") !== false)) {
				$ignore = false;
			}

			if (!$ignore) {
				$urls[$treffer[1]] = $treffer[1];
			}
		}

		return $urls;
	}

	public static function toPlaintext($html, $wraplength = 75, $compact = false)
	{
		$message = str_replace("\r", "", $html);

		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;

		$message = mb_convert_encoding($message, 'HTML-ENTITIES', "UTF-8");

		@$doc->loadHTML($message);

		$xpath = new DOMXPath($doc);
		$list = $xpath->query("//pre");
		foreach ($list as $node) {
			$node->nodeValue = str_replace("\n", "\r", $node->nodeValue);
		}

		$message = $doc->saveHTML();
		$message = str_replace(["\n<", ">\n", "\r", "\n", "\xC3\x82\xC2\xA0"], ["<", ">", "<br>", " ", ""], $message);
		$message = preg_replace('= [\s]*=i', " ", $message);

		// Collecting all links
		$urls = self::collectURLs($message);

		@$doc->loadHTML($message);

		self::tagToBBCode($doc, 'html', [], '', '');
		self::tagToBBCode($doc, 'body', [], '', '');

		// MyBB-Auszeichnungen
		/*
		  self::node2BBCode($doc, 'span', array('style'=>'text-decoration: underline;'), '_', '_');
		  self::node2BBCode($doc, 'span', array('style'=>'font-style: italic;'), '/', '/');
		  self::node2BBCode($doc, 'span', array('style'=>'font-weight: bold;'), '*', '*');

		  self::node2BBCode($doc, 'strong', array(), '*', '*');
		  self::node2BBCode($doc, 'b', array(), '*', '*');
		  self::node2BBCode($doc, 'i', array(), '/', '/');
		  self::node2BBCode($doc, 'u', array(), '_', '_');
		 */

		if ($compact) {
			self::tagToBBCode($doc, 'blockquote', [], "»", "«");
		} else {
			self::tagToBBCode($doc, 'blockquote', [], '[quote]', "[/quote]\n");
		}

		self::tagToBBCode($doc, 'br', [], "\n", '');

		self::tagToBBCode($doc, 'span', [], "", "");
		self::tagToBBCode($doc, 'pre', [], "", "");
		self::tagToBBCode($doc, 'div', [], "\r", "\r");
		self::tagToBBCode($doc, 'p', [], "\n", "\n");

		//self::node2BBCode($doc, 'ul', array(), "\n[list]", "[/list]\n");
		//self::node2BBCode($doc, 'ol', array(), "\n[list=1]", "[/list]\n");
		self::tagToBBCode($doc, 'li', [], "\n* ", "\n");

		self::tagToBBCode($doc, 'hr', [], "\n" . str_repeat("-", 70) . "\n", "");

		self::tagToBBCode($doc, 'tr', [], "\n", "");
		self::tagToBBCode($doc, 'td', [], "\t", "");

		self::tagToBBCode($doc, 'h1', [], "\n\n*", "*\n");
		self::tagToBBCode($doc, 'h2', [], "\n\n*", "*\n");
		self::tagToBBCode($doc, 'h3', [], "\n\n*", "*\n");
		self::tagToBBCode($doc, 'h4', [], "\n\n*", "*\n");
		self::tagToBBCode($doc, 'h5', [], "\n\n*", "*\n");
		self::tagToBBCode($doc, 'h6', [], "\n\n*", "*\n");

		// Problem: there is no reliable way to detect if it is a link to a tag or profile
		//self::node2BBCode($doc, 'a', array('href'=>'/(.+)/'), ' $1 ', ' ', true);
		//self::node2BBCode($doc, 'a', array('href'=>'/(.+)/', 'rel'=>'oembed'), ' $1 ', '', true);
		//self::node2BBCode($doc, 'img', array('alt'=>'/(.+)/'), '$1', '');
		//self::node2BBCode($doc, 'img', array('title'=>'/(.+)/'), '$1', '');
		//self::node2BBCode($doc, 'img', array(), '', '');
		if (!$compact) {
			self::tagToBBCode($doc, 'img', ['src' => '/(.+)/'], ' [img]$1', '[/img] ');
		} else {
			self::tagToBBCode($doc, 'img', ['src' => '/(.+)/'], ' ', ' ');
		}

		self::tagToBBCode($doc, 'iframe', ['src' => '/(.+)/'], ' $1 ', '');

		$message = $doc->saveHTML();

		if (!$compact) {
			$message = str_replace("[img]", "", $message);
			$message = str_replace("[/img]", "", $message);
		}

		// was ersetze ich da?
		// Irgendein stoerrisches UTF-Zeug
		$message = str_replace(chr(194) . chr(160), ' ', $message);

		$message = str_replace("&nbsp;", " ", $message);

		// Aufeinanderfolgende DIVs
		$message = preg_replace('=\r *\r=i', "\n", $message);
		$message = str_replace("\r", "\n", $message);

		$message = strip_tags($message);

		$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

		if (!$compact && ($message != '')) {
			foreach ($urls as $id => $url) {
				if ($url != '' && strpos($message, $url) === false) {
					$message .= "\n" . $url . ' ';
				}
			}
		}

		$message = str_replace("\n«", "«\n", $message);
		$message = str_replace("»\n", "\n»", $message);

		do {
			$oldmessage = $message;
			$message = str_replace("\n\n\n", "\n\n", $message);
		} while ($oldmessage != $message);

		$message = self::quoteLevel(trim($message), $wraplength);

		return trim($message);
	}
}
