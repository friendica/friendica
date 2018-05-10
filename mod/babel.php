<?php
/**
 * @file mod/babel.php
 */

use Friendica\Content\Text;
use Friendica\Core\L10n;

function visible_lf($s)
{
	return str_replace("\n", '<br />', $s);
}

function babel_content()
{
	$results = [];
	if (!empty($_REQUEST['text'])) {
		switch (defaults($_REQUEST, 'type', 'bbcode')) {
			case 'bbcode':
				$bbcode = trim($_REQUEST['text']);
				$results[] = [
					'title' => L10n::t('Source input'),
					'content' => visible_lf($bbcode)
				];

				$plain = Text\BBCode::toPlaintext($bbcode, false);
				$results[] = [
					'title' => L10n::t('BBCode::toPlaintext'),
					'content' => visible_lf($plain)
				];

				$html = Text\BBCode::convert($bbcode);
				$results[] = [
					'title' => L10n::t("BBCode::convert \x28raw HTML\x29"),
					'content' => htmlspecialchars($html)
				];

				$results[] = [
					'title' => L10n::t('BBCode::convert'),
					'content' => $html
				];

				$bbcode2 = Text\HTML::toBBCode($html);
				$results[] = [
					'title' => L10n::t('BBCode::convert => HTML::toBBCode'),
					'content' => visible_lf($bbcode2)
				];

				$markdown = Text\BBCode::toMarkdown($bbcode);
				$results[] = [
					'title' => L10n::t('BBCode::toMarkdown'),
					'content' => visible_lf($markdown)
				];

				$html2 = Text\Markdown::convert($markdown);
				$results[] = [
					'title' => L10n::t('BBCode::toMarkdown => Markdown::convert'),
					'content' => $html2
				];

				$bbcode3 = Text\Markdown::toBBCode($markdown);
				$results[] = [
					'title' => L10n::t('BBCode::toMarkdown => Markdown::toBBCode'),
					'content' => visible_lf($bbcode3)
				];

				$bbcode4 = Text\HTML::toBBCode($html2);
				$results[] = [
					'title' => L10n::t('BBCode::toMarkdown =>  Markdown::convert => HTML::toBBCode'),
					'content' => visible_lf($bbcode4)
				];
				break;
			case 'markdown':
				$markdown = trim($_REQUEST['text']);
				$results[] = [
					'title' => L10n::t('Source input \x28Diaspora format\x29'),
					'content' => '<pre>' . $markdown . '</pre>'
				];

				$bbcode = Text\Markdown::toBBCode($markdown);
				$results[] = [
					'title' => L10n::t('Markdown::toBBCode'),
					'content' => '<pre>' . $bbcode . '</pre>'
				];
				break;
			case 'html' :
				$html = trim($_REQUEST['text']);
				$results[] = [
					'title' => L10n::t("Raw HTML input"),
					'content' => htmlspecialchars($html)
				];

				$results[] = [
					'title' => L10n::t('HTML Input'),
					'content' => $html
				];

				$bbcode = Text\HTML::toBBCode($html);
				$results[] = [
					'title' => L10n::t('HTML::toBBCode'),
					'content' => visible_lf($bbcode)
				];

				$text = Text\HTML::toPlaintext($html);
				$results[] = [
					'title' => L10n::t('HTML::toPlaintext'),
					'content' => '<pre>' . $text . '</pre>'
				];
		}
	}

	$tpl = get_markup_template('babel.tpl');
	$o = replace_macros($tpl, [
		'$text'          => ['text', L10n::t('Source text'), defaults($_REQUEST, 'text', ''), ''],
		'$type_bbcode'   => ['type', L10n::t('BBCode'), 'bbcode', '', defaults($_REQUEST, 'type', 'bbcode') == 'bbcode'],
		'$type_markdown' => ['type', L10n::t('Markdown'), 'markdown', '', defaults($_REQUEST, 'type', 'bbcode') == 'markdown'],
		'$type_html'     => ['type', L10n::t('HTML'), 'html', '', defaults($_REQUEST, 'type', 'bbcode') == 'html'],
		'$results'       => $results
	]);

	return $o;
}
