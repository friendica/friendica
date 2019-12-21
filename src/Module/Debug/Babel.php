<?php

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Content\Text;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Model\Item;
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
			$s = str_replace(' ', '&nbsp;', $s);

			return str_replace(["\r\n", "\n", "\r"], '<br />', $s);
		}

		$results = [];
		if (!empty($_REQUEST['text'])) {
			switch (($_REQUEST['type'] ?? '') ?: 'bbcode') {
				case 'bbcode':
					$bbcode = trim($_REQUEST['text']);
					$results[] = [
						'title'   => L10n::t('Source input'),
						'content' => visible_whitespace($bbcode)
					];

					$plain = Text\BBCode::toPlaintext($bbcode, false);
					$results[] = [
						'title'   => L10n::t('BBCode::toPlaintext'),
						'content' => visible_whitespace($plain)
					];

					$html = Text\BBCode::convert($bbcode);
					$results[] = [
						'title'   => L10n::t('BBCode::convert (raw HTML)'),
						'content' => visible_whitespace(htmlspecialchars($html))
					];

					$results[] = [
						'title'   => L10n::t('BBCode::convert'),
						'content' => $html
					];

					$bbcode2 = Text\HTML::toBBCode($html);
					$results[] = [
						'title'   => L10n::t('BBCode::convert => HTML::toBBCode'),
						'content' => visible_whitespace($bbcode2)
					];

					$markdown = Text\BBCode::toMarkdown($bbcode);
					$results[] = [
						'title'   => L10n::t('BBCode::toMarkdown'),
						'content' => visible_whitespace(htmlspecialchars($markdown))
					];

					$html2 = Text\Markdown::convert($markdown);
					$results[] = [
						'title'   => L10n::t('BBCode::toMarkdown => Markdown::convert (raw HTML)'),
						'content' => visible_whitespace(htmlspecialchars($html2))
					];
					$results[] = [
						'title'   => L10n::t('BBCode::toMarkdown => Markdown::convert'),
						'content' => $html2
					];

					$bbcode3 = Text\Markdown::toBBCode($markdown);
					$results[] = [
						'title'   => L10n::t('BBCode::toMarkdown => Markdown::toBBCode'),
						'content' => visible_whitespace($bbcode3)
					];

					$bbcode4 = Text\HTML::toBBCode($html2);
					$results[] = [
						'title'   => L10n::t('BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'),
						'content' => visible_whitespace($bbcode4)
					];

					$item = [
						'body' => $bbcode,
						'tag'  => '',
					];

					Item::setHashtags($item);
					$results[] = [
						'title'   => L10n::t('Item Body'),
						'content' => visible_whitespace($item['body'])
					];
					$results[] = [
						'title'   => L10n::t('Item Tags'),
						'content' => $item['tag']
					];
					break;
				case 'markdown':
					$markdown = trim($_REQUEST['text']);
					$results[] = [
						'title'   => L10n::t('Source input (Diaspora format)'),
						'content' => '<pre>' . htmlspecialchars($markdown) . '</pre>'
					];

					$html = Text\Markdown::convert(html_entity_decode($markdown,ENT_COMPAT, 'UTF-8'));
					$results[] = [
						'title'   => L10n::t('Markdown::convert (raw HTML)'),
						'content' => visible_whitespace(htmlspecialchars($html))
					];

					$results[] = [
						'title'   => L10n::t('Markdown::convert'),
						'content' => $html
					];

					$bbcode = Text\Markdown::toBBCode(XML::unescape($markdown));
					$results[] = [
						'title'   => L10n::t('Markdown::toBBCode'),
						'content' => '<pre>' . $bbcode . '</pre>'
					];
					break;
				case 'html' :
					$html = trim($_REQUEST['text']);
					$results[] = [
						'title'   => L10n::t('Raw HTML input'),
						'content' => htmlspecialchars($html)
					];

					$results[] = [
						'title'   => L10n::t('HTML Input'),
						'content' => $html
					];

					$bbcode = Text\HTML::toBBCode($html);
					$results[] = [
						'title'   => L10n::t('HTML::toBBCode'),
						'content' => visible_whitespace($bbcode)
					];

					$html2 = Text\BBCode::convert($bbcode);
					$results[] = [
						'title'   => L10n::t('HTML::toBBCode => BBCode::convert'),
						'content' => $html2
					];

					$results[] = [
						'title'   => L10n::t('HTML::toBBCode => BBCode::convert (raw HTML)'),
						'content' => htmlspecialchars($html2)
					];

					$bbcode2plain = Text\BBCode::toPlaintext($bbcode);
					$results[] = [
						'title'   => L10n::t('HTML::toBBCode => BBCode::toPlaintext'),
						'content' => '<pre>' . $bbcode2plain . '</pre>'
					];

					$markdown = Text\HTML::toMarkdown($html);
					$results[] = [
						'title'   => L10n::t('HTML::toMarkdown'),
						'content' => visible_whitespace($markdown)
					];

					$text = Text\HTML::toPlaintext($html, 0);
					$results[] = [
						'title'   => L10n::t('HTML::toPlaintext'),
						'content' => '<pre>' . $text . '</pre>'
					];

					$text = Text\HTML::toPlaintext($html, 0, true);
					$results[] = [
						'title'   => L10n::t('HTML::toPlaintext (compact)'),
						'content' => '<pre>' . $text . '</pre>'
					];
			}
		}

		$tpl = Renderer::getMarkupTemplate('babel.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$text'          => ['text', L10n::t('Source text'), $_REQUEST['text'] ?? '', ''],
			'$type_bbcode'   => ['type', L10n::t('BBCode'), 'bbcode', '', (($_REQUEST['type'] ?? '') ?: 'bbcode') == 'bbcode'],
			'$type_markdown' => ['type', L10n::t('Markdown'), 'markdown', '', (($_REQUEST['type'] ?? '') ?: 'bbcode') == 'markdown'],
			'$type_html'     => ['type', L10n::t('HTML'), 'html', '', (($_REQUEST['type'] ?? '') ?: 'bbcode') == 'html'],
			'$results'       => $results
		]);

		return $o;
	}
}
