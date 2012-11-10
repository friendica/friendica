<?php
define( 'MARKDOWN_PARSER_CLASS',  'ExtendedMarkdown' );
require_once('library/markdown.php');

class ExtendedMarkdown extends MarkdownExtra_Parser {

	function ExtendedMarkdown() {
		$this->block_gamut += array(
			"doBlockWarning" => 45,
		);
		parent::MarkdownExtra_Parser();
	}

	function doBlockWarning($text) {
		$text = preg_replace_callback('/
			  (								# Wrap whole match in $1
				(?>
				  ^[ ]*![ ]?				# "!" at the start of a line
					.+\n					# rest of the first line
				  (.+\n)*					# subsequent consecutive lines
				  \n*						# blanks
				)+
			  )
			/xm', array(&$this, '_doBlockWarning_callback'), $text);

		return $text;
	}

	function _doBlockWarning_callback($matches) {
		$bq = $matches[1];
		# trim one level of quoting - trim whitespace-only lines
		$bq = preg_replace('/^[ ]*![ ]?|^[ ]+$/m', '', $bq);
		$bq = $this->runBlockGamut($bq);  # recurse

		$bq = preg_replace('/^/m', "  ", $bq);
		# These leading spaces cause problem with <pre> content, 
		# so we need to fix that:
//		$bq = preg_replace_callback('{(\s*<pre>.+?</pre>)}sx', array(&$this, '__doBlockWarning_callback2'), $bq);

		return "\n" . $this->hashBlock("<div class='md_warning'>\n$bq\n</div>") . "\n\n";
	}

	function _doBlockWarning_callback2($matches) {
		$pre = $matches[1];
		$pre = preg_replace('/^  /m', '', $pre);
		return $pre;
	}

}

if (!function_exists('load_doc_file')) {

	function load_doc_file($s) {
		global $lang;
		if (!isset($lang))
			$lang = 'en';
		$b = basename($s);
		$d = dirname($s);
		if (file_exists("$d/$lang/$b"))
			return file_get_contents("$d/$lang/$b");
		if (file_exists($s))
			return file_get_contents($s);
		return '';
	}

}

function help_content(&$a) {
	
	
	nav_set_selected('help');

	global $lang;

	$text = '';

	if ($a->argc > 1) {
		$text = load_doc_file('doc/' . $a->argv[1] . '.md');
		$a->page['title'] = t('Help:') . ' ' . str_replace('-', ' ', notags($a->argv[1]));
	}
	$home = load_doc_file('doc/Home.md');
	if (!$text) {
		$text = $home;
		$a->page['title'] = t('Help');
	} else {
		$a->page['aside'] = Markdown($home);
	}

	if (!strlen($text)) {
		header($_SERVER["SERVER_PROTOCOL"] . ' 404 ' . t('Not Found'));
		$tpl = get_markup_template("404.tpl");
		return replace_macros($tpl, array(
					'$message' => t('Page not found.')
				));
	}
	
	$html = Markdown($text);
	$html = "<style>.md_warning { padding: 1em; border: #ff0000 solid 2px; background-color: #f9a3a3; color: #ffffff;</style>".$html;
	return $html;
		
}
