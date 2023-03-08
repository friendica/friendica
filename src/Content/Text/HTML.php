<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Content\Text;

use DOMDocument;
use DOMXPath;
use Friendica\Protocol\HTTP\MediaType;
use Friendica\Content\Widget\ContactBlock;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use League\HTMLToMarkdown\HtmlConverter;
use Psr\Http\Message\UriInterface;

class HTML
{
	/**
	 * Search all instances of a specific HTML tag node in the provided DOM document and replaces them with BBCode text nodes.
	 *
	 * @see HTML::tagToBBCodeSub()
	 */
	private static function tagToBBCode(DOMDocument $doc, string $tag, array $attributes, string $startbb, string $endbb, bool $ignoreChildren = false)
	{
		do {
			$done = self::tagToBBCodeSub($doc, $tag, $attributes, $startbb, $endbb, $ignoreChildren);
		} while ($done);
	}

	/**
	 * Search the first specific HTML tag node in the provided DOM document and replaces it with BBCode text nodes.
	 *
	 * @param DOMDocument $doc
	 * @param string      $tag            HTML tag name
	 * @param array       $attributes     Array of attributes to match and optionally use the value from
	 * @param string      $startbb        BBCode tag opening
	 * @param string      $endbb          BBCode tag closing
	 * @param bool        $ignoreChildren If set to false, the HTML tag children will be appended as text inside the BBCode tag
	 *                                    Otherwise, they will be entirely ignored. Useful for simple BBCode that draw their
	 *                                    inner value from an attribute value and disregard the tag children.
	 * @return bool Whether a replacement was done
	 */
	private static function tagToBBCodeSub(DOMDocument $doc, string $tag, array $attributes, string $startbb, string $endbb, bool $ignoreChildren = false): bool
	{
		$savestart = str_replace('$', '\x01', $startbb);
		$replace = false;

		$xpath = new DOMXPath($doc);

		/** @var \DOMNode[] $list */
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

				if (!$ignoreChildren && $node->hasChildNodes()) {
					/** @var \DOMNode $child */
					foreach ($node->childNodes as $key => $child) {
						/* Remove empty text nodes at the start or at the end of the children list */
						if ($key > 0 && $key < $node->childNodes->length - 1 || $child->nodeName != '#text' || trim($child->nodeValue) !== '') {
							$newNode = $child->cloneNode(true);
							$node->parentNode->insertBefore($newNode, $node);
						}
					}
				}

				$node->parentNode->insertBefore($EndCode, $node);
				$node->parentNode->removeChild($node);
			}
		}

		return $replace;
	}

	/**
	 * Converter for HTML to BBCode
	 *
	 * Made by: ike@piratenpartei.de
	 * Originally made for the syncom project: http://wiki.piratenpartei.de/Syncom
	 *                    https://github.com/annando/Syncom
	 *
	 * @param string $message
	 * @param string $basepath
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function toBBCode(string $message, string $basepath = ''): string
	{
		/*
		 * Check if message is empty to prevent a lot of code below from being executed
		 * for just an empty message.
		 */
		if ($message === '') {
			return '';
		}

		DI::profiler()->startRecording('rendering');
		$message = str_replace("\r", "", $message);

		$message = Strings::performWithEscapedBlocks($message, '#<pre><code.*</code></pre>#iUs', function ($message) {
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

			if (empty($message)) {
				return '';
			}

			@$doc->loadHTML($message, LIBXML_HTML_NODEFDTD);

			XML::deleteNode($doc, 'style');
			XML::deleteNode($doc, 'head');
			XML::deleteNode($doc, 'title');
			XML::deleteNode($doc, 'meta');
			XML::deleteNode($doc, 'xml');
			XML::deleteNode($doc, 'removeme');

			$xpath = new DomXPath($doc);
			$list = $xpath->query("//pre");
			foreach ($list as $node) {
				// Ensure to escape unescaped & - they will otherwise raise a warning
				$safe_value = preg_replace('/&(?!\w+;)/', '&amp;', $node->nodeValue);
				$node->nodeValue = str_replace("\n", "\r", $safe_value);
			}

			$message = $doc->saveHTML();
			$message = str_replace(["\n<", ">\n", "\r", "\n", "\xC3\x82\xC2\xA0"], ["<", ">", "<br />", " ", ""], $message);
			$message = preg_replace('= [\s]*=i', " ", $message);

			if (empty($message)) {
				return '';
			}

			@$doc->loadHTML($message, LIBXML_HTML_NODEFDTD);

			self::tagToBBCode($doc, 'html', [], "", "");
			self::tagToBBCode($doc, 'body', [], "", "");

			// Outlook-Quote - Variant 1
			self::tagToBBCode($doc, 'p', ['class' => 'MsoNormal', 'style' => 'margin-left:35.4pt'], '[quote]', '[/quote]');

			// Outlook-Quote - Variant 2
			self::tagToBBCode(
				$doc,
				'div',
				['style' => 'border:none;border-left:solid blue 1.5pt;padding:0cm 0cm 0cm 4.0pt'],
				'[quote]',
				'[/quote]'
			);

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

			self::tagToBBCode($doc, 'table', [], "[table]", "[/table]");
			self::tagToBBCode($doc, 'th', [], "[th]", "[/th]");
			self::tagToBBCode($doc, 'tr', [], "[tr]", "[/tr]");
			self::tagToBBCode($doc, 'td', [], "[td]", "[/td]");

			self::tagToBBCode($doc, 'h1', [], "[h1]", "[/h1]");
			self::tagToBBCode($doc, 'h2', [], "[h2]", "[/h2]");
			self::tagToBBCode($doc, 'h3', [], "[h3]", "[/h3]");
			self::tagToBBCode($doc, 'h4', [], "[h4]", "[/h4]");
			self::tagToBBCode($doc, 'h5', [], "[h5]", "[/h5]");
			self::tagToBBCode($doc, 'h6', [], "[h6]", "[/h6]");

			self::tagToBBCode($doc, 'a', ['href' => '/mailto:(.+)/'], '[mail=$1]', '[/mail]');
			self::tagToBBCode($doc, 'a', ['href' => '/(.+)/'], '[url=$1]', '[/url]');

			self::tagToBBCode($doc, 'img', ['src' => '/(.+)/', 'alt' => '/(.+)/'], '[img=$1]$2', '[/img]', true);
			self::tagToBBCode($doc, 'img', ['src' => '/(.+)/', 'width' => '/(\d+)/', 'height' => '/(\d+)/'], '[img=$2x$3]$1', '[/img]', true);
			self::tagToBBCode($doc, 'img', ['src' => '/(.+)/'], '[img]$1', '[/img]', true);


			self::tagToBBCode($doc, 'video', ['src' => '/(.+)/'], '[video]$1', '[/video]', true);
			self::tagToBBCode($doc, 'audio', ['src' => '/(.+)/'], '[audio]$1', '[/audio]', true);
			// Backward compatibility, [iframe] support has been removed in version 2020.12
			self::tagToBBCode($doc, 'iframe', ['src' => '/(.+)/'], '[url]$1', '[/url]', true);

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

			Hook::callAll('html2bbcode', $message);

			$message = strip_tags($message);

			$message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

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

			return $message;
		});

		$message = preg_replace_callback(
			'#<pre><code(?: class="language-([^"]*)")?>(.*)</code></pre>#iUs',
			function ($matches) {
				$prefix = '[code]';
				if ($matches[1] != '') {
					$prefix = '[code=' . $matches[1] . ']';
				}

				return $prefix . "\n" . html_entity_decode($matches[2]) . "\n" . '[/code]';
			},
			$message
		);

		$message = trim($message);

		if ($basepath != '') {
			$message = self::qualifyURLs($message, $basepath);
		}

		DI::profiler()->stopRecording();
		return $message;
	}

	/**
	 * Sub function to complete incomplete URL
	 *
	 * @param array  $matches  Result of preg_replace_callback
	 * @param string $basepath Basepath that is used to complete the URL
	 *
	 * @return string The expanded URL
	 */
	private static function qualifyURLsSub(array $matches, string $basepath): string
	{
		$base = parse_url($basepath);
		unset($base['query']);
		unset($base['fragment']);

		$link = $matches[0];
		$url = $matches[1];

		if (empty($url) || empty(parse_url($url))) {
			return $matches[0];
		}

		$parts = array_merge($base, parse_url($url));
		$url2 = Network::unparseURL($parts);

		return str_replace($url, $url2, $link);
	}

	/**
	 * Complete incomplete URLs in BBCode
	 *
	 * @param string $body     Body with URLs
	 * @param string $basepath Base path that is used to complete the URL
	 *
	 * @return string Body with expanded URLs
	 */
	private static function qualifyURLs(string $body, string $basepath): string
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
					return self::qualifyURLsSub($match, $basepath);
				},
				$body
			);
		}
		return $body;
	}

	private static function breakLines(string $line, int $level, int $wraplength = 75): string
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

		return implode("\n", $newlines);
	}

	private static function quoteLevel(string $message, int $wraplength = 75): string
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

		return implode("\n", $newlines);
	}

	private static function collectURLs(string $message): array
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

			$ignore = $ignore || strpos($treffer[1], '#') === 0;

			if (!$ignore) {
				$urls[$treffer[1]] = $treffer[1];
			}
		}

		return $urls;
	}

	/**
	 * @param string $html
	 * @param int    $wraplength Ensures individual lines aren't longer than this many characters. Doesn't break words.
	 * @param bool   $compact    True: Completely strips image tags; False: Keeps image URLs
	 * @return string
	 */
	public static function toPlaintext(string $html, int $wraplength = 75, bool $compact = false): string
	{
		DI::profiler()->startRecording('rendering');
		$message = str_replace("\r", "", $html);

		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;

		$message = mb_convert_encoding($message, 'HTML-ENTITIES', "UTF-8");

		if (empty($message)) {
			DI::profiler()->stopRecording();
			return '';
		}

		@$doc->loadHTML($message, LIBXML_HTML_NODEFDTD);

		$message = $doc->saveHTML();
		// Remove eventual UTF-8 BOM
		$message = str_replace("\xC3\x82\xC2\xA0", "", $message);

		// Collecting all links
		$urls = self::collectURLs($message);

		if (empty($message)) {
			DI::profiler()->stopRecording();
			return '';
		}

		@$doc->loadHTML($message, LIBXML_HTML_NODEFDTD);

		self::tagToBBCode($doc, 'html', [], '', '');
		self::tagToBBCode($doc, 'body', [], '', '');

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

		if (!$compact) {
			self::tagToBBCode($doc, 'img', ['src' => '/(.+)/'], ' [img]$1', '[/img] ');
		} else {
			self::tagToBBCode($doc, 'img', ['src' => '/(.+)/'], ' ', ' ');
		}

		// Backward compatibility, [iframe] support has been removed in version 2020.12
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

		DI::profiler()->stopRecording();
		return trim($message);
	}

	/**
	 * Converts provided HTML code to Markdown. The hardwrap parameter maximizes
	 * compatibility with Diaspora in spite of the Markdown standards.
	 *
	 * @param string $html
	 * @return string
	 */
	public static function toMarkdown(string $html): string
	{
		DI::profiler()->startRecording('rendering');
		$converter = new HtmlConverter(['hard_break' => true]);
		$markdown = $converter->convert($html);

		DI::profiler()->stopRecording();
		return $markdown;
	}

	/**
	 * Convert video HTML to BBCode tags
	 *
	 * @param string $s
	 * @return string
	 */
	public static function toBBCodeVideo(string $s): string
	{
		$s = preg_replace(
			'#<object[^>]+>(.*?)https?://www.youtube.com/((?:v|cp)/[A-Za-z0-9\-_=]+)(.*?)</object>#ism',
			'[youtube]$2[/youtube]',
			$s
		);

		$s = preg_replace(
			'#<iframe[^>](.*?)https?://www.youtube.com/embed/([A-Za-z0-9\-_=]+)(.*?)</iframe>#ism',
			'[youtube]$2[/youtube]',
			$s
		);

		$s = preg_replace(
			'#<iframe[^>](.*?)https?://player.vimeo.com/video/([0-9]+)(.*?)</iframe>#ism',
			'[vimeo]$2[/vimeo]',
			$s
		);

		return $s;
	}

	/**
	 * transform link href and img src from relative to absolute
	 *
	 * @param string $text
	 * @param string $base base url
	 * @return string
	 */
	public static function relToAbs(string $text, string $base): string
	{
		if (empty($base)) {
			return $text;
		}

		$base = rtrim($base, '/');

		$base2 = $base . "/";

		// Replace links
		$pattern = "/<a([^>]*) href=\"(?!http|https|\/)([^\"]*)\"/";
		$replace = "<a\${1} href=\"" . $base2 . "\${2}\"";
		$text = preg_replace($pattern, $replace, $text);

		$pattern = "/<a([^>]*) href=\"(?!http|https)([^\"]*)\"/";
		$replace = "<a\${1} href=\"" . $base . "\${2}\"";
		$text = preg_replace($pattern, $replace, $text);

		// Replace images
		$pattern = "/<img([^>]*) src=\"(?!http|https|\/)([^\"]*)\"/";
		$replace = "<img\${1} src=\"" . $base2 . "\${2}\"";
		$text = preg_replace($pattern, $replace, $text);

		$pattern = "/<img([^>]*) src=\"(?!http|https)([^\"]*)\"/";
		$replace = "<img\${1} src=\"" . $base . "\${2}\"";
		$text = preg_replace($pattern, $replace, $text);


		// Done
		return $text;
	}

	/**
	 * Loader for infinite scrolling
	 *
	 * @return string html for loader
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function scrollLoader(): string
	{
		$tpl = Renderer::getMarkupTemplate("scroll_loader.tpl");
		return Renderer::replaceMacros($tpl, [
			'wait' => DI::l10n()->t('Loading more entries...'),
			'end' => DI::l10n()->t('The end')
		]);
	}

	/**
	 * Format contacts as picture links or as text links
	 *
	 * @param array   $contact  Array with contacts which contains an array with
	 *                          int 'id' => The ID of the contact
	 *                          int 'uid' => The user ID of the user who owns this data
	 *                          string 'name' => The name of the contact
	 *                          string 'url' => The url to the profile page of the contact
	 *                          string 'addr' => The webbie of the contact (e.g.) username@friendica.com
	 *                          string 'network' => The network to which the contact belongs to
	 *                          string 'thumb' => The contact picture
	 *                          string 'click' => js code which is performed when clicking on the contact
	 * @param boolean $redirect If true try to use the redir url if it's possible
	 * @param string  $class    CSS class for the
	 * @param boolean $textmode If true display the contacts as text links
	 *                          if false display the contacts as picture links
	 * @return string Formatted html
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function micropro(array $contact, bool $redirect = false, string $class = '', bool $textmode = false): string
	{
		// Use the contact URL if no address is available
		if (empty($contact['addr'])) {
			$contact["addr"] = $contact["url"];
		}

		$url = $contact['url'];
		$sparkle = '';
		$redir = false;

		if ($redirect) {
			$url = Contact::magicLinkByContact($contact);
			if (strpos($url, 'contact/redir/') === 0) {
				$sparkle = ' sparkle';
			}
		}

		// If there is some js available we don't need the url
		if (!empty($contact['click'])) {
			$url = '';
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate($textmode ? 'micropro_txt.tpl' : 'micropro_img.tpl'), [
			'$click' => $contact['click'] ?? '',
			'$class' => $class,
			'$url' => $url,
			'$photo' => Contact::getThumb($contact),
			'$name' => $contact['name'],
			'title' => $contact['name'] . ' [' . $contact['addr'] . ']',
			'$parkle' => $sparkle,
			'$redir' => $redir
		]);
	}

	/**
	 * Search box.
	 *
	 * @param string $s     Search query.
	 * @param string $id    HTML id
	 * @param bool   $aside Display the search widgit aside.
	 *
	 * @return string Formatted HTML.
	 * @throws \Exception
	 */
	public static function search(string $s, string $id = 'search-box', bool $aside = true): string
	{
		$mode = 'text';

		if (strpos($s, '#') === 0) {
			$mode = 'tag';
		}
		$save_label = $mode === 'text' ? DI::l10n()->t('Save') : DI::l10n()->t('Follow');

		$values = [
			'$s'            => $s,
			'$q'            => urlencode($s),
			'$id'           => $id,
			'$search_label' => DI::l10n()->t('Search'),
			'$save_label'   => $save_label,
			'$search_hint'  => DI::l10n()->t('@name, !forum, #tags, content'),
			'$mode'         => $mode,
			'$return_url'   => urlencode(Search::getSearchPath($s)),
		];

		if (!$aside) {
			$values['$search_options'] = [
				'fulltext' => DI::l10n()->t('Full Text'),
				'tags'     => DI::l10n()->t('Tags'),
				'contacts' => DI::l10n()->t('Contacts')
			];

			if (DI::config()->get('system', 'poco_local_search')) {
				$values['$searchoption']['forums'] = DI::l10n()->t('Forums');
			}
		}

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('searchbox.tpl'), $values);
	}

	/**
	 * Given a HTML text and a set of filtering reasons, adds a content hiding header with the provided reasons
	 *
	 * Reasons are expected to have been translated already.
	 *
	 * @param string $html
	 * @param array  $reasons
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function applyContentFilter(string $html, array $reasons): string
	{
		if (count($reasons)) {
			$tpl = Renderer::getMarkupTemplate('wall/content_filter.tpl');
			$html = Renderer::replaceMacros($tpl, [
				'$reasons'   => $reasons,
				'$rnd'       => Strings::getRandomHex(8),
				'$openclose' => DI::l10n()->t('Click to open/close'),
				'$html'      => $html
			]);
		}

		return $html;
	}

	/**
	 * replace html amp entity with amp char
	 * @param string $s
	 * @return string
	 */
	public static function unamp(string $s): string
	{
		return str_replace('&amp;', '&', $s);
	}

	/**
	 * Clean an HTML text for potentially harmful code
	 *
	 * @param string $text
	 * @param array  $allowedIframeDomains List of allowed iframe source domains without the scheme
	 * @return string
	 */
	public static function purify(string $text, array $allowedIframeDomains = []): string
	{
		// Allows cid: URL scheme
		\HTMLPurifier_URISchemeRegistry::instance()->register('cid', new HTMLPurifier_URIScheme_cid());

		$config = \HTMLPurifier_HTML5Config::createDefault();
		$config->set('HTML.Doctype', 'HTML5');

		// Used to remove iframe with src attribute filtered out
		$config->set('AutoFormat.RemoveEmpty', true);

		$config->set('HTML.SafeIframe', true);

		array_walk($allowedIframeDomains, function (&$domain) {
			// Allow the domain and all its eventual sub-domains
			$domain = '(?:(?!-)[A-Za-z0-9-]{1,63}(?<!-)\.)*' . preg_quote(trim($domain, '/'), '%');
		});

		$config->set('URI.SafeIframeRegexp',
			'%^https://(?:
				' . implode('|', $allowedIframeDomains) . '
			)
			(?:/|$) # Prevents bogus domains like youtube.com.fake.tld
			%xi'
		);

		$config->set('Attr.AllowedRel', [
			'noreferrer' => true,
			'noopener' => true,
			'tag' => true,
		]);
		$config->set('Attr.AllowedFrameTargets', [
			'_blank' => true,
		]);

		$config->set('AutoFormat.RemoveEmpty.Predicate', [
			'colgroup' => [],        // |
			'th'       => [],        // |
			'td'       => [],        // |
			'iframe'   => ['src'],   // ↳ Default HTMLPurify values
			'i'        => ['class'], // Allows forkawesome icons
		]);

		// Uncomment to debug HTMLPurifier behavior
		//$config->set('Core.CollectErrors', true);
		//$config->set('Core.MaintainLineNumbers', true);

		$HTMLPurifier = new \HTMLPurifier($config);

		$text = $HTMLPurifier->purify($text);

		/** @var \HTMLPurifier_ErrorCollector $errorCollector */
		// Uncomment to debug HTML Purifier behavior
		//$errorCollector = $HTMLPurifier->context->get('ErrorCollector');
		//var_dump($errorCollector->getRaw());

		return $text;
	}

	/**
	 * XPath arbitrary string quoting
	 *
	 * @see https://stackoverflow.com/a/45228168
	 * @param string $value
	 * @return string
	 */
	public static function xpathQuote(string $value): string
	{
		if (false === strpos($value, '"')) {
			return '"' . $value . '"';
		}

		if (false === strpos($value, "'")) {
			return "'" . $value . "'";
		}

		// if the value contains both single and double quotes, construct an
		// expression that concatenates all non-double-quote substrings with
		// the quotes, e.g.:
		//
		//    concat("'foo'", '"', "bar")
		return 'concat(' . implode(', \'"\', ', array_map([self::class, 'xpathQuote'], explode('"', $value))) . ')';
	}

	/**
	 * Checks if the provided URL is present in the DOM document in an element with the rel="me" attribute
	 *
	 * XHTML Friends Network http://gmpg.org/xfn/
	 *
	 * @param DOMDocument  $doc
	 * @param UriInterface $meUrl
	 * @return bool
	 */
	public static function checkRelMeLink(DOMDocument $doc, UriInterface $meUrl): bool
	{
		$xpath = new \DOMXpath($doc);

		// This expression checks that "me" is among the space-delimited values of the "rel" attribute.
		// And that the href attribute contains exactly the provided URL
		$expression = "//*[contains(concat(' ', normalize-space(@rel), ' '), ' me ')][@href = " . self::xpathQuote($meUrl) . "]";

		$result = $xpath->query($expression);

		return $result !== false && $result->length > 0;
	}

	/**
	 * @param DOMDocument $doc
	 * @return string|null Lowercase charset
	 */
	public static function extractCharset(DOMDocument $doc): ?string
	{
		$xpath = new DOMXPath($doc);

		$expression = "string(//meta[@charset]/@charset)";
		if ($charset = $xpath->evaluate($expression)) {
			return strtolower($charset);
		}

		try {
			// This expression looks for a meta tag with the http-equiv attribute set to "content-type" ignoring case
			// whose content attribute contains a "charset" string and returns its value
			$expression = "string(//meta[@http-equiv][translate(@http-equiv, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = 'content-type'][contains(translate(@content, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), 'charset')]/@content)";
			$mediaType = MediaType::fromContentType($xpath->evaluate($expression));
			if (isset($mediaType->parameters['charset'])) {
				return strtolower($mediaType->parameters['charset']);
			}
		} catch(\InvalidArgumentException $e) {}

		return null;
	}
}
