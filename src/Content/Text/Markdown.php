<?php

/**
 * @file src/Content/Text/Markdown.php
 */

namespace Friendica\Content\Text;

use Friendica\BaseObject;
use Friendica\Model\Contact;
use Michelf\MarkdownExtra;
use Friendica\Content\Text\HTML;

require_once 'include/text.php';

/**
 * Friendica-specific usage of Markdown
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Markdown extends BaseObject
{
	/**
	 * Converts a Markdown string into HTML. The hardwrap parameter maximizes
	 * compatibility with Diaspora in spite of the Markdown standard.
	 *
	 * @brief Converts a Markdown string into HTML
	 * @param string $text
	 * @param bool   $hardwrap
	 * @return string
	 */
	public static function convert($text, $hardwrap = true) {
		$stamp1 = microtime(true);

		$MarkdownParser = new MarkdownExtra();
		$MarkdownParser->hard_wrap = $hardwrap;
		$MarkdownParser->code_block_content_func = function ($string, $type) {
			if ($type) {
				return text_highlight($string, $type);
			} else {
				return $string;
			}
		};

		$html = $MarkdownParser->transform($text);

		self::getApp()->save_timestamp($stamp1, "parser");

		return $html;
	}

	/**
	 * @brief Callback function to replace a Diaspora style mention in a mention for Friendica
	 *
	 * @param array $match Matching values for the callback
	 * @return string Replaced mention
	 */
	private static function diasporaMention2BBCodeCallback($match)
	{
		if ($match[2] == '') {
			return;
		}

		$data = Contact::getDetailsByAddr($match[2]);

		$name = $match[1];

		if ($name == '') {
			$name = $data['name'];
		}

		return '@[url=' . $data['url'] . ']' . $name . '[/url]';
	}

	/*
	 * we don't want to support a bbcode specific markdown interpreter
	 * and the markdown library we have is pretty good, but provides HTML output.
	 * So we'll use that to convert to HTML, then convert the HTML back to bbcode,
	 * and then clean up a few Diaspora specific constructs.
	 */
	public static function toBBCode($s)
	{
		$s = html_entity_decode($s, ENT_COMPAT, 'UTF-8');

		// Handles single newlines
		$s = str_replace("\r\n", "\n", $s);
		$s = str_replace("\n", " \n", $s);
		$s = str_replace("\r", " \n", $s);

		// Replace lonely stars in lines not starting with it with literal stars
		$s = preg_replace('/^([^\*]+)\*([^\*]*)$/im', '$1\*$2', $s);

		// The parser cannot handle paragraphs correctly
		$s = str_replace(['</p>', '<p>', '<p dir="ltr">'], ['<br>', '<br>', '<br>'], $s);

		// Escaping the hash tags
		$s = preg_replace('/\#([^\s\#])/', '&#35;$1', $s);

		$s = self::convert($s);

		$regexp = "/@\{(?:([^\}]+?); )?([^\} ]+)\}/";
		$s = preg_replace_callback($regexp, ['self', 'diasporaMention2BBCodeCallback'], $s);

		$s = str_replace('&#35;', '#', $s);

		$s = HTML::toBBCode($s);

		// protect the recycle symbol from turning into a tag, but without unescaping angles and naked ampersands
		$s = str_replace('&#x2672;', html_entity_decode('&#x2672;', ENT_QUOTES, 'UTF-8'), $s);

		// Convert everything that looks like a link to a link
		$s = preg_replace('/([^\]=]|^)(https?\:\/\/)([a-zA-Z0-9:\/\-?&;.=_~#%$!+,@]+(?<!,))/ism', '$1[url=$2$3]$2$3[/url]', $s);

		//$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)(vimeo|youtu|www\.youtube|soundcloud)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2$3$4]$2$3$4[/url]',$s);
		$s = BBCode::pregReplaceInTag('/\[url\=?(.*?)\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/url\]/ism', '[youtube]$2[/youtube]', 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=https?:\/\/www.youtube.com\/watch\?v\=(.*?)\].*?\[\/url\]/ism'   , '[youtube]$1[/youtube]', 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=?(.*?)\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/url\]/ism'        , '[vimeo]$2[/vimeo]'    , 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=https?:\/\/vimeo.com\/([0-9]+)\](.*?)\[\/url\]/ism'              , '[vimeo]$1[/vimeo]'    , 'url', $s);

		// remove duplicate adjacent code tags
		$s = preg_replace('/(\[code\])+(.*?)(\[\/code\])+/ism', '[code]$2[/code]', $s);

		// Don't show link to full picture (until it is fixed)
		$s = BBCode::scaleExternalImages($s, false);

		return $s;
	}
}
