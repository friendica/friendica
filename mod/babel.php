<?php

require_once('include/bbcode.php');
require_once('library/markdown.php');
require_once('include/bb2diaspora.php');
require_once('include/html2bbcode.php');

function visible_lf($s) {
	return str_replace("\n",'<br />', $s);
}

function babel_content(App $a) {

	$o .= '<h1>Babel Diagnostic</h1>';

	$o .= '<form action="babel" method="post">';
	$o .= t('Source (bbcode) text:') . EOL . '<textarea name="text" >' . htmlspecialchars($_REQUEST['text']) .'</textarea>' . EOL;
	$o .= '<input type="submit" name="submit" value="Submit" /></form>';

	$o .= '<br /><br />';

	$o .= '<form action="babel" method="post">';
	$o .= t('Source (Diaspora) text to convert to BBcode:') . EOL . '<textarea name="d2bbtext" >' . htmlspecialchars($_REQUEST['d2bbtext']) .'</textarea>' . EOL;
	$o .= '<input type="submit" name="submit" value="Submit" /></form>';

	$o .= '<br /><br />';

	if(x($_REQUEST,'text')) {

		$text = trim($_REQUEST['text']);
		$o .= "<h2>" . t("Source input: ") . "</h2>" . EOL. EOL;
		$o .= visible_lf($text) . EOL. EOL;

		$html = bbcode($text);
		$o .= "<h2>" . t("bb2html (raw HTML): ") . "</h2>" . EOL. EOL;
		$o .= htmlspecialchars($html). EOL. EOL;

		//$html = bbcode($text);
		$o .= "<h2>" . t("bb2html: ") . "</h2>" . EOL. EOL;
		$o .= $html. EOL. EOL;

		$bbcode = html2bbcode($html);
		$o .= "<h2>" . t("bb2html2bb: ") . "</h2>" . EOL. EOL;
		$o .= visible_lf($bbcode) . EOL. EOL;

		$diaspora = bb2diaspora($text);
		$o .= "<h2>" . t("bb2md: ") . "</h2>" . EOL. EOL;
		$o .= visible_lf($diaspora) . EOL. EOL;

		$html = Markdown($diaspora);
		$o .= "<h2>" . t("bb2md2html: ") . "</h2>" . EOL. EOL;
		$o .= $html. EOL. EOL;

		$bbcode = diaspora2bb($diaspora);
		$o .= "<h2>" . t("bb2dia2bb: ") . "</h2>" . EOL. EOL;
		$o .= visible_lf($bbcode) . EOL. EOL;

		$bbcode = html2bbcode($html);
		$o .= "<h2>" . t("bb2md2html2bb: ") . "</h2>" . EOL. EOL;
		$o .= visible_lf($bbcode) . EOL. EOL;



	}

	if(x($_REQUEST,'d2bbtext')) {

		$d2bbtext = trim($_REQUEST['d2bbtext']);
		$o .= "<h2>" . t("Source input (Diaspora format): ") . "</h2>" . EOL. EOL;
		$o .= visible_lf($d2bbtext) . EOL. EOL;


		$bb = diaspora2bb($d2bbtext);
		$o .= "<h2>" . t("diaspora2bb: ") . "</h2>" . EOL. EOL;
		$o .= visible_lf($bb) . EOL. EOL;
	}

	return $o;
}
