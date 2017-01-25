<?php
define("DQ_ESCAPE", "__DQ__");


function po2php_run(&$argv, &$argc) {

	if ($argc!=2) {
		print "Usage: ".$argv[0]." <file.po>\n\n";
		return;
	}

	$pofile = $argv[1];
	$outfile = dirname($pofile)."/strings.php";

	if (strstr($outfile,'util'))
		$lang = 'en';
	else
		$lang = str_replace('-','_',basename(dirname($pofile)));



	if (!file_exists($pofile)){
		print "Unable to find '$pofile'\n";
		return;
	}

	print "Out to '$outfile'\n";

	$out="<?php\n\n";

	$infile = file($pofile);
	$k="";
	$v="";
	$arr = False;
	$ink = False;
	$inv = False;
	$escape_s_exp = '|[^\\\\]\$[a-z]|';
	function escape_s($match){
		return str_replace('$','\$',$match[0]);
	}
	foreach ($infile as $l) {
		$l = str_replace('\"', DQ_ESCAPE, $l);
		$len = strlen($l);
		if ($l[0]=="#") $l="";
		if (substr($l,0,15)=='"Plural-Forms: '){
			$match=Array();
			preg_match("|nplurals=([0-9]*); *plural=(.*)[;\\\\]|", $l, $match);
			$cond = str_replace('n','$n',$match[2]);
			// define plural select function if not already defined
			$fnname = 'string_plural_select_' . $lang;
			$out .= 'if(! function_exists("'.$fnname.'")) {'."\n";
			$out .= 'function '. $fnname . '($n){'."\n";
			$out .= '	return '.$cond.';'."\n";
			$out .= '}}'."\n";
		}




		if ($k!="" && substr($l,0,7)=="msgstr "){
			if ($ink) { $ink = False; $out .= '$a->strings["'.$k.'"] = '; }
			if ($inv) { $inv = False; $out .= '"'.$v.'"'; }

			$v = substr($l,8,$len-10);
			$v = preg_replace_callback($escape_s_exp,'escape_s',$v);
			$inv = True;
			//$out .= $v;
		}
		if ($k!="" && substr($l,0,7)=="msgstr["){
			if ($ink) { $ink = False; $out .= '$a->strings["'.$k.'"] = '; }
			if ($inv) {	$inv = False; $out .= '"'.$v.'"'; }

			if (!$arr) {
				$arr=True;
				$out .= "array(\n";
			}
			$match=Array();
			preg_match("|\[([0-9]*)\] (.*)|", $l, $match);
			$out .= "\t".
				preg_replace_callback($escape_s_exp,'escape_s',$match[1])
				." => "
				.preg_replace_callback($escape_s_exp,'escape_s',$match[2]) .",\n";
		}

		if (substr($l,0,6)=="msgid_") { $ink = False; $out .= '$a->strings["'.$k.'"] = '; };


		if ($ink) {
			$k .= trim($l,"\"\r\n");
			$k = preg_replace_callback($escape_s_exp,'escape_s',$k);
			//$out .= '$a->strings['.$k.'] = ';
		}

		if (substr($l,0,6)=="msgid "){
			if ($inv) {	$inv = False; $out .= '"'.$v.'"'; }
			if ($k!="") $out .= $arr?");\n":";\n";
			$arr=False;
			$k = str_replace("msgid ","",$l);
			if ($k != '""' ) {
				$k = trim($k,"\"\r\n");
			} else {
				$k = "";
			}

			$k = preg_replace_callback($escape_s_exp,'escape_s',$k);
			$ink = True;
		}

		if ($inv && substr($l,0,6)!="msgstr") {
			$v .= trim($l,"\"\r\n");
			$v = preg_replace_callback($escape_s_exp,'escape_s',$v);
			//$out .= '$a->strings['.$k.'] = ';
		}


	}

	if ($inv) {	$inv = False; $out .= '"'.$v.'"'; }
	if ($k!="") $out .= $arr?");\n":";\n";

	$out = str_replace(DQ_ESCAPE, '\"', $out);
	file_put_contents($outfile, $out);

}

if (array_search(__file__,get_included_files())===0){
  po2php_run($_SERVER["argv"],$_SERVER["argc"]);
}
