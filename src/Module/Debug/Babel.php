<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Content\PageInfo;
use Friendica\Content\Text;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Model\Tag;
use Friendica\Util\XML;

/**
 * Translates input text into different formats (HTML, BBCode, Markdown)
 */
class Babel extends BaseModule
{
	public static function content(array $parameters = [])
	{
		function visible_whitespace($s)
		{
			return '<pre>' . htmlspecialchars($s) . '</pre>';
		}

		$results = [];
		if (!empty($_REQUEST['text'])) {
			switch (($_REQUEST['type'] ?? '') ?: 'bbcode') {
				case 'bbcode':
					$bbcode = trim($_REQUEST['text']);
					$results[] = [
						'title'   => DI::l10n()->t('Source input'),
						'content' => visible_whitespace($bbcode)
					];

					$plain = Text\BBCode::toPlaintext($bbcode, false);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toPlaintext'),
						'content' => visible_whitespace($plain)
					];

					$html = Text\BBCode::convert($bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::convert (raw HTML)'),
						'content' => visible_whitespace($html)
					];

					$results[] = [
						'title'   => DI::l10n()->t('BBCode::convert'),
						'content' => $html
					];

					$bbcode2 = Text\HTML::toBBCode($html);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::convert => HTML::toBBCode'),
						'content' => visible_whitespace($bbcode2)
					];

					$markdown = Text\BBCode::toMarkdown($bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown'),
						'content' => visible_whitespace($markdown)
					];

					$html2 = Text\Markdown::convert($markdown);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown => Markdown::convert (raw HTML)'),
						'content' => visible_whitespace($html2)
					];
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown => Markdown::convert'),
						'content' => $html2
					];

					$bbcode3 = Text\Markdown::toBBCode($markdown);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown => Markdown::toBBCode'),
						'content' => visible_whitespace($bbcode3)
					];

					$bbcode4 = Text\HTML::toBBCode($html2);
					$results[] = [
						'title'   => DI::l10n()->t('BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'),
						'content' => visible_whitespace($bbcode4)
					];

					$tags = Text\BBCode::getTags($bbcode);

					$body = Item::setHashtags($bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('Item Body'),
						'content' => visible_whitespace($body)
					];
					$results[] = [
						'title'   => DI::l10n()->t('Item Tags'),
						'content' => visible_whitespace(var_export($tags, true)),
					];

					$body2 = PageInfo::appendToBody($bbcode, true);
					$results[] = [
						'title'   => DI::l10n()->t('PageInfo::appendToBody'),
						'content' => visible_whitespace($body2)
					];
					$html3 = Text\BBCode::convert($body2);
					$results[] = [
						'title'   => DI::l10n()->t('PageInfo::appendToBody => BBCode::convert (raw HTML)'),
						'content' => visible_whitespace($html3)
					];
					$results[] = [
						'title'   => DI::l10n()->t('PageInfo::appendToBody => BBCode::convert'),
						'content' => $html3
					];
					break;
				case 'diaspora':
					$diaspora = trim($_REQUEST['text']);
					$results[] = [
						'title'   => DI::l10n()->t('Source input (Diaspora format)'),
						'content' => visible_whitespace($diaspora),
					];

					$markdown = XML::unescape($diaspora);
				case 'markdown':
					$markdown = $markdown ?? trim($_REQUEST['text']);

					$results[] = [
						'title'   => DI::l10n()->t('Source input (Markdown)'),
						'content' => visible_whitespace($markdown),
					];

					$html = Text\Markdown::convert($markdown);
					$results[] = [
						'title'   => DI::l10n()->t('Markdown::convert (raw HTML)'),
						'content' => visible_whitespace($html),
					];

					$results[] = [
						'title'   => DI::l10n()->t('Markdown::convert'),
						'content' => $html
					];

					$bbcode = Text\Markdown::toBBCode($markdown);
					$results[] = [
						'title'   => DI::l10n()->t('Markdown::toBBCode'),
						'content' => visible_whitespace($bbcode),
					];
					break;
				case 'html' :
					$html = trim($_REQUEST['text']);
					$results[] = [
						'title'   => DI::l10n()->t('Raw HTML input'),
						'content' => visible_whitespace($html),
					];

					$results[] = [
						'title'   => DI::l10n()->t('HTML Input'),
						'content' => $html
					];

					$bbcode = Text\HTML::toBBCode($html);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toBBCode'),
						'content' => visible_whitespace($bbcode)
					];

					$html2 = Text\BBCode::convert($bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toBBCode => BBCode::convert'),
						'content' => $html2
					];

					$results[] = [
						'title'   => DI::l10n()->t('HTML::toBBCode => BBCode::convert (raw HTML)'),
						'content' => htmlspecialchars($html2)
					];

					$bbcode2plain = Text\BBCode::toPlaintext($bbcode);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toBBCode => BBCode::toPlaintext'),
						'content' => visible_whitespace($bbcode2plain),
					];

					$markdown = Text\HTML::toMarkdown($html);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toMarkdown'),
						'content' => visible_whitespace($markdown)
					];

					$text = Text\HTML::toPlaintext($html, 0);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toPlaintext'),
						'content' => visible_whitespace($text),
					];

					$text = Text\HTML::toPlaintext($html, 0, true);
					$results[] = [
						'title'   => DI::l10n()->t('HTML::toPlaintext (compact)'),
						'content' => visible_whitespace($text),
					];
			}
		}

		$tpl = Renderer::getMarkupTemplate('babel.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$text'          => ['text', DI::l10n()->t('Source text'), $_REQUEST['text'] ?? '', ''],
			'$type_bbcode'   => ['type', DI::l10n()->t('BBCode'), 'bbcode', '', (($_REQUEST['type'] ?? '') ?: 'bbcode') == 'bbcode'],
			'$type_diaspora' => ['type', DI::l10n()->t('Diaspora'), 'diaspora', '', (($_REQUEST['type'] ?? '') ?: 'bbcode') == 'diaspora'],
			'$type_markdown' => ['type', DI::l10n()->t('Markdown'), 'markdown', '', (($_REQUEST['type'] ?? '') ?: 'bbcode') == 'markdown'],
			'$type_html'     => ['type', DI::l10n()->t('HTML'), 'html', '', (($_REQUEST['type'] ?? '') ?: 'bbcode') == 'html'],
			'$results'       => $results
		]);

		return $o;
	}
}
