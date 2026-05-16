<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Content\PageInfo;
use Friendica\Content\Text;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Protocol\Activity;
use Friendica\Util\XML;

/**
 * Translates input text into different formats (HTML, BBCode, Markdown)
 */
class Babel extends BaseModule
{
	protected function post(array $request = [])
	{
		// @todo check if POST is really used here
		$this->content($request);
	}

	protected function content(array $request = []): string
	{
		$results = [];

		$visible_whitespace = function (string $s): string {
			return '<pre>' . htmlspecialchars($s) . '</pre>';
		};

		if (!empty($request['text'])) {
			self::checkFormSecurityTokenForbiddenOnError('babel');
			switch (($request['type'] ?? '') ?: 'bbcode') {
				case 'bbcode':
					$bbcode    = $request['text'];
					$results[] = [
						'title'   => 'Source input',
						'content' => $visible_whitespace($bbcode),
					];

					$plain     = Text\BBCode::toPlaintext($bbcode, false);
					$results[] = [
						'title'   => 'BBCode::toPlaintext',
						'content' => $visible_whitespace($plain),
					];

					$html      = Text\BBCode::convertForUriId(0, $bbcode);
					$results[] = [
						'title'   => 'BBCode::convert (raw HTML)',
						'content' => $visible_whitespace($html),
					];

					$results[] = [
						'title'   => 'BBCode::convert (hex)',
						'content' => $visible_whitespace(bin2hex($html)),
					];

					$results[] = [
						'title'   => 'BBCode::convert',
						'content' => $html,
					];

					$bbcode2   = Text\HTML::toBBCode($html);
					$results[] = [
						'title'   => 'BBCode::convert => HTML::toBBCode',
						'content' => $visible_whitespace($bbcode2),
					];

					$markdown  = Text\BBCode::toMarkdown($bbcode);
					$results[] = [
						'title'   => 'BBCode::toMarkdown',
						'content' => $visible_whitespace($markdown),
					];

					$html2     = Text\Markdown::convert($markdown);
					$results[] = [
						'title'   => 'BBCode::toMarkdown => Markdown::convert (raw HTML)',
						'content' => $visible_whitespace($html2),
					];
					$results[] = [
						'title'   => 'BBCode::toMarkdown => Markdown::convert',
						'content' => $html2,
					];

					$bbcode3   = Text\Markdown::toBBCode($markdown);
					$results[] = [
						'title'   => 'BBCode::toMarkdown => Markdown::toBBCode',
						'content' => $visible_whitespace($bbcode3),
					];

					$bbcode4   = Text\HTML::toBBCode($html2);
					$results[] = [
						'title'   => 'BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode',
						'content' => $visible_whitespace($bbcode4),
					];

					$tags = Text\BBCode::getTags($bbcode);

					$body      = Item::setHashtags($bbcode);
					$results[] = [
						'title'   => 'Item Body',
						'content' => $visible_whitespace($body),
					];
					$results[] = [
						'title'   => 'Item Tags',
						'content' => $visible_whitespace(var_export($tags, true)),
					];

					$body2     = PageInfo::searchAndAppendToBody($bbcode, true);
					$results[] = [
						'title'   => 'PageInfo::appendToBody',
						'content' => $visible_whitespace($body2),
					];
					$html3     = Text\BBCode::convertForUriId(0, $body2);
					$results[] = [
						'title'   => 'PageInfo::appendToBody => BBCode::convert (raw HTML)',
						'content' => $visible_whitespace($html3),
					];
					$results[] = [
						'title'   => 'PageInfo::appendToBody => BBCode::convert',
						'content' => $html3,
					];
					break;
				case 'diaspora':
					$diaspora  = trim($request['text']);
					$results[] = [
						'title'   => 'Source input (Diaspora format)',
						'content' => $visible_whitespace($diaspora),
					];

					$markdown = XML::unescape($diaspora);
					// no break
				case 'markdown':
					$markdown ??= trim($request['text']);

					$results[] = [
						'title'   => 'Source input (Markdown)',
						'content' => $visible_whitespace($markdown),
					];

					$html      = Text\Markdown::convert($markdown);
					$results[] = [
						'title'   => 'Markdown::convert (raw HTML)',
						'content' => $visible_whitespace($html),
					];

					$results[] = [
						'title'   => 'Markdown::convert',
						'content' => $html,
					];

					$bbcode    = Text\Markdown::toBBCode($markdown);
					$results[] = [
						'title'   => 'Markdown::toBBCode',
						'content' => $visible_whitespace($bbcode),
					];
					break;
				case 'html':
					$html      = trim($request['text']);
					$results[] = [
						'title'   => 'Raw HTML input',
						'content' => $visible_whitespace($html),
					];

					$results[] = [
						'title'   => 'HTML Input',
						'content' => $html,
					];

					$purified = Text\HTML::purify($html);

					$results[] = [
						'title'   => 'HTML Purified (raw)',
						'content' => $visible_whitespace($purified),
					];

					$results[] = [
						'title'   => 'HTML Purified (hex)',
						'content' => $visible_whitespace(bin2hex($purified)),
					];

					$results[] = [
						'title'   => 'HTML Purified',
						'content' => $purified,
					];

					$bbcode    = Text\HTML::toBBCode($html);
					$results[] = [
						'title'   => 'HTML::toBBCode',
						'content' => $visible_whitespace($bbcode),
					];

					$html2     = Text\BBCode::convertForUriId(0, $bbcode);
					$results[] = [
						'title'   => 'HTML::toBBCode => BBCode::convert',
						'content' => $html2,
					];

					$results[] = [
						'title'   => 'HTML::toBBCode => BBCode::convert (raw HTML)',
						'content' => htmlspecialchars($html2),
					];

					$bbcode2plain = Text\BBCode::toPlaintext($bbcode);
					$results[]    = [
						'title'   => 'HTML::toBBCode => BBCode::toPlaintext',
						'content' => $visible_whitespace($bbcode2plain),
					];

					$markdown  = Text\HTML::toMarkdown($html);
					$results[] = [
						'title'   => 'HTML::toMarkdown',
						'content' => $visible_whitespace($markdown),
					];

					$text      = Text\HTML::toPlaintext($html, 0);
					$results[] = [
						'title'   => 'HTML::toPlaintext',
						'content' => $visible_whitespace($text),
					];

					$text      = Text\HTML::toPlaintext($html, 0, true);
					$results[] = [
						'title'   => 'HTML::toPlaintext (compact)',
						'content' => $visible_whitespace($text),
					];
					break;
				case 'twitter':
					$json = trim($request['text']);

					$status = json_decode($json);

					$results[] = [
						'title'   => 'Decoded post',
						'content' => $visible_whitespace(var_export($status, true)),
					];

					$postarray                = [];
					$postarray['object-type'] = Activity\ObjectType::NOTE;

					if (!empty($status->full_text)) {
						$postarray['body'] = $status->full_text;
					} else {
						$postarray['body'] = $status->text;
					}

					// When the post contains links then use the correct object type
					if (count($status->entities->urls) > 0) {
						$postarray['object-type'] = Activity\ObjectType::BOOKMARK;
					}

					$results[] = [
						'title'   => 'Post array before expand entities',
						'content' => $visible_whitespace(var_export($postarray, true)),
					];

					break;
			}
		}

		$tpl = Renderer::getMarkupTemplate('babel.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$title'               => DI::l10n()->t('Babel Diagnostic'),
			'$form_security_token' => self::getFormSecurityToken('babel'),
			'$text'                => ['text', 'Source text', $request['text'] ?? '', ''],
			'$type_bbcode'         => ['type', 'BBCode', 'bbcode', '', (($request['type'] ?? '') ?: 'bbcode') == 'bbcode'],
			'$type_diaspora'       => ['type', 'Diaspora', 'diaspora', '', (($request['type'] ?? '') ?: 'bbcode') == 'diaspora'],
			'$type_markdown'       => ['type', 'Markdown', 'markdown', '', (($request['type'] ?? '') ?: 'bbcode') == 'markdown'],
			'$type_html'           => ['type', 'HTML', 'html', '', (($request['type'] ?? '') ?: 'bbcode') == 'html'],
			'$flag_twitter'        => file_exists('addon/twitter/twitter.php'),
			'$type_twitter'        => ['type', DI::l10n()->t('Twitter Source / Tweet URL (requires API key)'), 'twitter', '', (($request['type'] ?? '') ?: 'bbcode') == 'twitter'],
			'$results'             => $results,
			'$submit'              => DI::l10n()->t('Submit'),
		]);

		return $o;
	}
}
