<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text;

use Friendica\DI;
use Friendica\Model\Contact;

/**
 * Friendica-specific usage of Markdown
 */
class Markdown
{
	/**
	 * Converts a Markdown string into HTML. The hardwrap parameter maximizes
	 * compatibility with Diaspora in spite of the Markdown standard.
	 *
	 * @param string $text
	 * @param bool   $hardwrap Enables line breaks on \n without two trailing spaces
	 * @param string $baseuri  Optional. Prepend anchor links with this URL
	 * @return string
	 */
	public static function convert($text, $hardwrap = true, $baseuri = null)
	{
		DI::profiler()->startRecording('rendering');

		$MarkdownParser                     = new MarkdownParser();
		$MarkdownParser->code_class_prefix  = 'language-';
		$MarkdownParser->hard_wrap          = $hardwrap;
		$MarkdownParser->hashtag_protection = true;
		$MarkdownParser->url_filter_func    = function ($url) use ($baseuri) {
			if (!empty($baseuri) && strpos($url, '#') === 0) {
				$url = ltrim($baseuri, '/') . $url;
			}
			return  $url;
		};

		$text = self::convertDiasporaMentionsToHtml($text);

		$html = $MarkdownParser->transform($text);

		DI::profiler()->stopRecording();

		return $html;
	}

	/**
	 * Replace Diaspora-style mentions in a text since they trip the Markdown parser autolinker.
	 *
	 * @param string $text
	 * @return string
	 */
	private static function convertDiasporaMentionsToHtml(string $text)
	{
		return preg_replace_callback(
			'/([@!]){(?:([^}]+?); ?)?([^} ]+)}/',
			/*
			 * Matching values for the callback
			 * [1] = mention type (@ or !)
			 * [2] = name (optional)
			 * [3] = profile URL
			 */
			function ($matches) {
				if ($matches[3] == '') {
					return '';
				}

				$data = Contact::getByURL($matches[3]);

				if (empty($data)) {
					return '';
				}

				$name = $matches[2];

				if ($name == '') {
					$name = $data['name'];
				}

				return $matches[1] . '<a href="' . $data['url'] . '">' . $name . '</a>';
			},
			$text
		);
	}

	/*
	 * we don't want to support a bbcode specific markdown interpreter
	 * and the markdown library we have is pretty good, but provides HTML output.
	 * So we'll use that to convert to HTML, then convert the HTML back to bbcode,
	 * and then clean up a few Diaspora specific constructs.
	 */
	public static function toBBCode($s): string
	{
		// @TODO Temporary until we find the source of the null value to finally set the correct type-hint
		if (is_null($s)) {
			DI::logger()->warning('Received null value');
			return '';
		}

		if (!$s) {
			return $s;
		}

		DI::profiler()->startRecording('rendering');

		// The parser cannot handle paragraphs correctly
		$s = str_replace(['</p>', '<p>', '<p dir="ltr">'], ['<br>', '<br>', '<br>'], $s);

		// Escaping hashtags that could be titles
		$s = preg_replace('/^\#([^\s\#])/im', '\#$1', $s);

		$s = self::convert($s);

		$s = HTML::toBBCode($s);

		// protect the recycle symbol from turning into a tag, but without unescaping angles and naked ampersands
		$s = str_replace('&#x2672;', html_entity_decode('&#x2672;', ENT_QUOTES, 'UTF-8'), $s);

		//$s = preg_replace("/([^\]\=]|^)(https?\:\/\/)(vimeo|youtu|www\.youtube|soundcloud)([a-zA-Z0-9\:\/\-\?\&\;\.\=\_\~\#\%\$\!\+\,]+)/ism", '$1[url=$2$3$4]$2$3$4[/url]',$s);
		$s = BBCode::pregReplaceInTag('/\[url\=?(.*?)\]https?:\/\/www.youtube.com\/watch\?v\=(.*?)\[\/url\]/ism', '[youtube]$2[/youtube]', 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=https?:\/\/www.youtube.com\/watch\?v\=(.*?)\].*?\[\/url\]/ism', '[youtube]$1[/youtube]', 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=https?:\/\/www.youtube.com\/shorts\/(.*?)\].*?\[\/url\]/ism', '[youtube]$1[/youtube]', 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=?(.*?)\]https?:\/\/vimeo.com\/([0-9]+)(.*?)\[\/url\]/ism', '[vimeo]$2[/vimeo]', 'url', $s);
		$s = BBCode::pregReplaceInTag('/\[url\=https?:\/\/vimeo.com\/([0-9]+)\](.*?)\[\/url\]/ism', '[vimeo]$1[/vimeo]', 'url', $s);

		// remove duplicate adjacent code tags
		$s = preg_replace('/(\[code\])+(.*?)(\[\/code\])+/ism', '[code]$2[/code]', $s);

		DI::profiler()->stopRecording();
		return $s;
	}
}
