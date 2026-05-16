<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Text;

use DOMDocument;
use DOMElement;
use Friendica\Model\Photo;
use Friendica\Model\Post;

/**
 * Tumblr Neue Post Format
 * @see https://www.tumblr.com/docs/npf
 */
class NPF
{
	private static $heading_subtype = [];

	/**
	 * Convert BBCode into NPF (Tumblr Neue Post Format)
	 *
	 * @param string $bbcode
	 * @param integer $uri_id
	 * @return array NPF
	 */
	public static function fromBBCode(string $bbcode, int $uri_id): array
	{
		$bbcode = self::prepareBody($bbcode);

		$html = BBCode::convertForUriId($uri_id, $bbcode, BBCode::NPF);
		if (empty($html)) {
			return [];
		}

		$doc = new DOMDocument();

		$doc->formatOutput = true;
		if (!@$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'))) {
			return [];
		}

		self::setHeadingSubStyles($doc);

		$element = $doc->getElementsByTagName('body')->item(0);

		[$npf, $text, $formatting] = self::routeChildren($element, $uri_id, true, []);

		return self::addLinkBlockForUriId($uri_id, 0, $npf);
	}

	/**
	 * Fetch the heading types
	 *
	 * @param DOMDocument $doc
	 * @return void
	 */
	private static function setHeadingSubStyles(DOMDocument $doc)
	{
		self::$heading_subtype = [];
		foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $element) {
			if ($doc->getElementsByTagName($element)->count() > 0) {
				if (empty(self::$heading_subtype)) {
					self::$heading_subtype[$element] = 'heading1';
				} else {
					self::$heading_subtype[$element] = 'heading2';
				}
			}
		}
	}

	/**
	 * Prepare the BBCode for the NPF conversion
	 *
	 * @param string $bbcode
	 * @return string
	 */
	private static function prepareBody(string $bbcode): string
	{
		$shared = BBCode::fetchShareAttributes($bbcode);
		if (!empty($shared)) {
			$bbcode = $shared['shared'];
		}

		$bbcode = preg_replace("/\[img\=([0-9]*)x([0-9]*)\](.*?)\[\/img\]/ism", '[img]$3[/img]', $bbcode);

		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img=([^\[\]]*)\]([^\[\]]*)\[\/img\]\s*\[/url\]#ism", $bbcode, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (preg_match('#/photo/.*-[01]\.#ism', $picture[2]) && (preg_match('#/photo/.*-0\.#ism', $picture[1]) || preg_match('#/photos/.*/image/#ism', $picture[1]))) {
					$bbcode = str_replace($picture[0], "\n\n[img=" . str_replace('-1.', '-0.', $picture[2]) . "]" . $picture[3] . "[/img]\n\n", $bbcode);
				}
			}
		}

		$bbcode = preg_replace("/\[img\=(.*?)\](.*?)\[\/img\]/ism", "\n\n[img=$1]$2[/img]\n\n", $bbcode);

		if (preg_match_all("#\[url=([^\]]+?)\]\s*\[img\]([^\[]+?)\[/img\]\s*\[/url\]#ism", $bbcode, $pictures, PREG_SET_ORDER)) {
			foreach ($pictures as $picture) {
				if (preg_match('#/photo/.*-[01]\.#ism', $picture[2]) && (preg_match('#/photo/.*-0\.#ism', $picture[1]) || preg_match('#/photos/.*/image/#ism', $picture[1]))) {
					$bbcode = str_replace($picture[0], "\n\n[img]" . str_replace('-1.', '-0.', $picture[2]) . "[/img]\n\n", $bbcode);
				}
			}
		}

		$bbcode = preg_replace("/\[img\](.*?)\[\/img\]/ism", "\n\n[img]$1[/img]\n\n", $bbcode);

		do {
			$oldbbcode = $bbcode;
			$bbcode    = str_replace(["\n\n\n"], ["\n\n"], $bbcode);
		} while ($oldbbcode != $bbcode);

		return trim($bbcode);
	}

	/**
	 * Walk recursively through the HTML
	 *
	 * @param DOMElement $element
	 * @param integer $uri_id
	 * @param boolean $parse_structure
	 * @param array $callstack
	 * @param array $npf
	 * @param string $text
	 * @param array $formatting
	 * @return array
	 */
	private static function routeChildren(DOMElement $element, int $uri_id, bool $parse_structure, array $callstack, array $npf = [], string $text = '', array $formatting = []): array
	{
		if ($parse_structure && $text) {
			[$npf, $text, $formatting] = self::addBlock($text, $formatting, $npf, $callstack);
		}

		$callstack[] = $element->nodeName;
		$level       = self::getLevelByCallstack($callstack);

		foreach ($element->childNodes as $child) {
			switch ($child->nodeName) {
				case 'b':
				case 'strong':
					[$npf, $text, $formatting] = self::addFormatting($child, $uri_id, 'bold', $callstack, $npf, $text, $formatting);
					break;

				case 'i':
				case 'em':
					[$npf, $text, $formatting] = self::addFormatting($child, $uri_id, 'italic', $callstack, $npf, $text, $formatting);
					break;

				case 's':
					[$npf, $text, $formatting] = self::addFormatting($child, $uri_id, 'strikethrough', $callstack, $npf, $text, $formatting);
					break;

				case 'u':
				case 'span':
					[$npf, $text, $formatting] = self::addFormatting($child, $uri_id, '', $callstack, $npf, $text, $formatting);
					break;

				case 'hr':
				case 'br':
					if (!empty($text)) {
						$text .= "\n";
					}
					break;

				case '#text':
					$text .= $child->textContent;
					break;

				case 'table':
				case 'summary':
					// Ignore tables and spoilers
					break;

				case 'a':
					[$npf, $text, $formatting] = self::addInlineLink($child, $uri_id, $callstack, $npf, $text, $formatting);
					break;

				case 'img':
					$npf = self::addImageBlock($child, $uri_id, $level, $npf);
					break;

				case 'audio':
				case 'video':
					$npf = self::addMediaBlock($child, $uri_id, $level, $npf);
					break;

				default:
					[$npf, $text, $formatting] = self::routeChildren($child, $uri_id, true, $callstack, $npf, $text, $formatting);
					break;
			}
		}

		if ($parse_structure && $text) {
			[$npf, $text, $formatting] = self::addBlock($text, $formatting, $npf, $callstack);
		}
		return [$npf, $text, $formatting];
	}

	/**
	 * Return the correct indent level
	 *
	 * @param array $callstack
	 * @return integer
	 */
	private static function getLevelByCallstack(array $callstack): int
	{
		$level = 0;
		foreach ($callstack as $entry) {
			if (in_array($entry, ['ol', 'ul', 'blockquote'])) {
				++$level;
			}
		}
		return max(0, $level - 1);
	}

	/**
	 * Detect the subtype via the HTML element callstack
	 *
	 * @param array $callstack
	 * @param string $text
	 * @return string
	 */
	private static function getSubTypeByCallstack(array $callstack, string $text): string
	{
		$subtype = '';
		foreach ($callstack as $entry) {
			switch ($entry) {
				case 'ol':
					$subtype = 'ordered-list-item';
					break;

				case 'ul':
					$subtype = 'unordered-list-item';
					break;

				case 'h1':
					$subtype = self::$heading_subtype[$entry];
					break;

				case 'h2':
					$subtype = self::$heading_subtype[$entry];
					break;

				case 'h3':
					$subtype = self::$heading_subtype[$entry];
					break;

				case 'h4':
					$subtype = self::$heading_subtype[$entry];
					break;

				case 'h5':
					$subtype = self::$heading_subtype[$entry];
					break;

				case 'h6':
					$subtype = self::$heading_subtype[$entry];
					break;

				case 'blockquote':
					$subtype = mb_strlen($text) < 100 ? 'quote' : 'indented';
					break;

				case 'pre':
					$subtype = 'indented';
					break;

				case 'code':
					$subtype = 'chat';
					break;
			}
		}
		return $subtype;
	}

	/**
	 * Add formatting for a text block
	 *
	 * @param DOMElement $element
	 * @param integer $uri_id
	 * @param string $type
	 * @param array $callstack
	 * @param array $npf
	 * @param string $text
	 * @param array $formatting
	 * @return array
	 */
	private static function addFormatting(DOMElement $element, int $uri_id, string $type, array $callstack, array $npf, string $text, array $formatting): array
	{
		$start = mb_strlen($text);

		[$npf, $text, $formatting] = self::routeChildren($element, $uri_id, false, $callstack, $npf, $text, $formatting);

		if (!empty($type)) {
			$formatting[] = [
				'start' => $start,
				'end'   => mb_strlen($text),
				'type'  => $type
			];
		}
		return [$npf, $text, $formatting];
	}

	/**
	 * Add an inline link for a text block
	 *
	 * @param DOMElement $element
	 * @param integer $uri_id
	 * @param array $callstack
	 * @param array $npf
	 * @param string $text
	 * @param array $formatting
	 * @return array
	 */
	private static function addInlineLink(DOMElement $element, int $uri_id, array $callstack, array $npf, string $text, array $formatting): array
	{
		$start = mb_strlen($text);

		[$npf, $text, $formatting] = self::routeChildren($element, $uri_id, false, $callstack, $npf, $text, $formatting);

		$attributes = [];
		foreach ($element->attributes as $attribute) {
			$attributes[$attribute->name] = trim($attribute->value);
		}
		if (!empty($attributes['href'])) {
			$formatting[] = [
				'start' => $start,
				'end'   => mb_strlen($text),
				'type'  => 'link',
				'url'   => $attributes['href']
			];
		}
		return [$npf, $text, $formatting];
	}

	/**
	 * Add a text block
	 *
	 * @param string $text
	 * @param array $formatting
	 * @param array $npf
	 * @param array $callstack
	 * @return array
	 */
	private static function addBlock(string $text, array $formatting, array $npf, array $callstack): array
	{
		$block = [
			'type'    => 'text',
			'subtype' => '',
			'text'    => $text,
		];

		if (!empty($formatting)) {
			$block['formatting'] = $formatting;
		}

		$level = self::getLevelByCallstack($callstack);
		if ($level > 0) {
			$block['indent_level'] = $level;
		}

		$subtype = self::getSubTypeByCallstack($callstack, $text);
		if ($subtype) {
			$block['subtype'] = $subtype;
		} else {
			unset($block['subtype']);
		}

		$npf[] = $block;
		return [$npf, '', []];
	}

	/**
	 * Add a block for a preview picture
	 *
	 * @param array $media
	 * @param array $block
	 * @return array
	 */
	private static function addPoster(array $media, array $block): array
	{
		$poster = [];
		if (!empty($media['preview'])) {
			$poster['url'] = $media['preview'];
		}
		if (!empty($media['preview-width'])) {
			$poster['width'] = $media['preview-width'];
		}
		if (!empty($media['preview-height'])) {
			$poster['height'] = $media['preview-height'];
		}
		if (!empty($poster)) {
			$block['poster'] = [$poster];
		}
		return $block;
	}

	/**
	 * Add a link block from the HTML attachment of a given post uri-id
	 *
	 * @param integer $uri_id
	 * @param integer $level
	 * @param array $npf
	 * @return array
	 */
	private static function addLinkBlockForUriId(int $uri_id, int $level, array $npf): array
	{
		foreach (Post\Media::getByURIId($uri_id, [Post\Media::HTML]) as $link) {
			$host = parse_url($link['url'], PHP_URL_HOST);
			if (in_array($host, ['www.youtube.com', 'youtu.be'])) {
				$block = [
					'type'     => 'video',
					'provider' => 'youtube',
					'url'      => $link['url'],
				];
			} elseif (in_array($host, ['vimeo.com'])) {
				$block = [
					'type'     => 'video',
					'provider' => 'vimeo',
					'url'      => $link['url'],
				];
			} elseif (in_array($host, ['open.spotify.com'])) {
				$block = [
					'type'     => 'audio',
					'provider' => 'spotify',
					'url'      => $link['url'],
				];
			} else {
				$block = [
					'type' => 'link',
					'url'  => $link['url'],
				];
				if (!empty($link['name'])) {
					$block['title'] = $link['name'];
				}
				if (!empty($link['description'])) {
					$block['description'] = $link['description'];
				}
				if (!empty($link['author-name'])) {
					$block['author'] = $link['author-name'];
				}
				if (!empty($link['publisher-name'])) {
					$block['site_name'] = $link['publisher-name'];
				}
			}

			if ($level > 0) {
				$block['indent_level'] = $level;
			}

			$npf[] = self::addPoster($link, $block);
		}
		return $npf;
	}

	/**
	 * Add an image block
	 *
	 * @param DOMElement $element
	 * @param integer $uri_id
	 * @param integer $level
	 * @param array $npf
	 * @return array
	 */
	private static function addImageBlock(DOMElement $element, int $uri_id, int $level, array $npf): array
	{
		$attributes = [];
		foreach ($element->attributes as $key => $attribute) {
			$attributes[$key] = trim($attribute->value);
		}
		if (empty($attributes['src'])) {
			return $npf;
		}

		$block = [
			'type'  => 'image',
			'media' => [],
		];

		if (!empty($attributes['alt'])) {
			$block['alt_text'] = $attributes['alt'];
		}

		if (!empty($attributes['title']) && (($attributes['alt'] ?? '') != $attributes['title'])) {
			$block['caption'] = $attributes['title'];
		}

		$rid = Photo::ridFromURI($attributes['src']);
		if (!empty($rid)) {
			$photos = Photo::selectToArray([], ['resource-id' => $rid]);
			foreach ($photos as $photo) {
				$block['media'][] = [
					'type'   => $photo['type'],
					'url'    => str_replace('-0.', '-' . $photo['scale'] . '.', $attributes['src']),
					'width'  => $photo['width'],
					'height' => $photo['height'],
				];
			}
			if (empty($attributes['alt']) && !empty($photos[0]['desc'])) {
				$block['alt_text'] = $photos[0]['desc'];
			}
		} elseif ($media = Post\Media::getByURL($uri_id, $attributes['src'], [Post\Media::IMAGE])) {
			$block['media'][] = [
				'type'   => $media['mimetype'],
				'url'    => $media['url'],
				'width'  => $media['width'],
				'height' => $media['height'],
			];
			if (empty($attributes['alt']) && !empty($media['description'])) {
				$block['alt_text'] = $media['description'];
			}
		} else {
			$block['media'][] = ['url' => $attributes['src']];
		}

		if ($level > 0) {
			$block['indent_level'] = $level;
		}

		$npf[] = $block;

		return $npf;
	}

	/**
	 * Add an audio or video block
	 *
	 * @param DOMElement $element
	 * @param integer $uri_id
	 * @param integer $level
	 * @param array $npf
	 * @return array
	 */
	private static function addMediaBlock(DOMElement $element, int $uri_id, int $level, array $npf): array
	{
		$attributes = [];
		foreach ($element->attributes as $key => $attribute) {
			$attributes[$key] = trim($attribute->value);
		}
		if (empty($attributes['src'])) {
			return $npf;
		}

		$block = [];

		$media = Post\Media::getByURL($uri_id, $attributes['src'], [Post\Media::AUDIO, Post\Media::VIDEO]);
		if (!empty($media)) {
			switch ($media['type']) {
				case Post\Media::AUDIO:
					$block = [
						'type'  => 'audio',
						'media' => [
							'type' => $media['mimetype'],
							'url'  => $media['url'],
						]
					];

					if (!empty($media['name'])) {
						$block['title'] = $media['name'];
					} elseif (!empty($media['description'])) {
						$block['title'] = $media['description'];
					}

					$block = self::addPoster($media, $block);
					break;

				case Post\Media::VIDEO:
					$block = [
						'type'  => 'video',
						'media' => [
							'type' => $media['mimetype'],
							'url'  => $media['url'],
						]
					];

					$block = self::addPoster($media, $block);
					break;
			}
		} else {
			$block = [
				'type'       => 'text',
				'text'       => $element->textContent,
				'formatting' => [
					[
						'start' => 0,
						'end'   => mb_strlen($element->textContent),
						'type'  => 'link',
						'url'   => $attributes['src']
					]
				]
			];
		}

		if ($level > 0) {
			$block['indent_level'] = $level;
		}

		$npf[] = $block;

		return $npf;
	}
}
