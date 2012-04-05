<?php

	if(! class_exists('App')) {
		class TmpA {
			public $strings = Array();
		}
		$a = new TmpA();
	}

	if ($argc!=2) {
		print "Usage: ".$argv[0]." <strings.php>\n\n";
		return;
	}
	
	$phpfile = $argv[1];
	$pofile = dirname($phpfile)."/messages.po";

	if (!file_exists($phpfile)){
		print "Unable to find '$phpfile'\n";
		return;
	}

	include_once($phpfile);

	print "Out to '$pofile'\n";
	
	$out = "";	
	$infile = file($pofile);
	$k="";
	$ink = False;
	foreach ($infile as $l) {
	
		if ($k!="" && substr($l,0,7)=="msgstr "){
			$ink = False;
			$v = '""';
			//echo "DBG: k:'$k'\n";
			if (isset($a->strings[$k])) {
				$v= '"'.$a->strings[$k].'"';
				//echo "DBG\n";
				//var_dump($k, $v, $a->strings[$k], $v);
				//echo "/DBG\n";
				
			}
			//echo "DBG: v:'$v'\n";
			$l = "msgstr ".$v."\n";
		}
	
		if (substr($l,0,6)=="msgid_" || substr($l,0,7)=="msgstr[" )$ink = False;;
	
		if ($ink) {
			$k .= trim($l,"\"\r\n");
			$k = str_replace('\"','"',$k); 
		}
		
		if (substr($l,0,6)=="msgid "){
			$arr=False;
			$k = str_replace("msgid ","",$l);
			if ($k != '""' ) {
				$k = trim($k,"\"\r\n");
				$k = str_replace('\"','"',$k);
			} else {
				$k = "";
			}
			$ink = True;
		}
		
		$out .= $l;
	}
	//echo $out;
	file_put_contents($pofile, $out);
?>