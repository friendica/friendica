<?php
/* update friendica */
define('APIBASE', 'http://github.com/api/v2/');
define('F9KREPO', 'friendica/friendica');

$up_totalfiles = 0;
$up_countfiles = 0;
$up_lastp = -1;

function checkUpdate(){
	$r = fetch_url( APIBASE."json/repos/show/".F9KREPO."/tags" );
	$tags = json_decode($r);
	
	$tag = 0.0;
	foreach ($tags->tags as $i=>$v){
		$i = (float)$i;
		if ($i>$tag) $tag=$i;
	}
	
	if ($tag==0.0) return false;
	$f = fetch_url("https://raw.github.com/".F9KREPO."/".$tag."/boot.php","r");
	preg_match("|'FRIENDICA_VERSION', *'([^']*)'|", $f, $m);
	$version =  $m[1];
	
	$lv = explode(".", FRIENDICA_VERSION);
	$rv = explode(".",$version);
	foreach($lv as $i=>$v){
		if ((int)$lv[$i] < (int)$rv[$i]) {
			return array($tag, $version, "https://github.com/friendica/friendica/zipball/".$tag);
			break;
		}
	}
	return false;
}
function canWeWrite(){
	$bd = dirname(dirname(__file__));
	return is_writable( $bd."/boot.php" );
}

function out($txt){ echo "§".$txt."§"; ob_end_flush(); flush();}

function up_count($path){
 
    $file_count = 0;
 
    $dir_handle = opendir($path);
 
    if (!$dir_handle) return -1;
 
    while ($file = readdir($dir_handle)) {
 
        if ($file == '.' || $file == '..') continue;
		$file_count++;
        
        if (is_dir($path . $file)){      
            $file_count += up_count($path . $file . DIRECTORY_SEPARATOR);
        }
        
    }
 
    closedir($dir_handle);
 
    return $file_count;
}



function up_unzip($file, $folder="/tmp"){
	$folder.="/";
	$zip = zip_open($file);
	if ($zip) {
	  while ($zip_entry = zip_read($zip)) {
		$zip_entry_name = zip_entry_name($zip_entry);
		if (substr($zip_entry_name,strlen($zip_entry_name)-1,1)=="/"){
			mkdir($folder.$zip_entry_name,0777, true);
		} else {
			$fp = fopen($folder.$zip_entry_name, "w");
			if (zip_entry_open($zip, $zip_entry, "r")) {
			  $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
			  fwrite($fp,"$buf");
			  zip_entry_close($zip_entry);
			  fclose($fp);
			}
		}
	  }
	  zip_close($zip);
	}	
}

/**
 * Walk recoursively in a folder and call a callback function on every
 * dir entry.
 * args:
 * 	$dir		string		base dir to walk
 * 	$callback	function	callback function
 *  $sort		int			0: ascending, 1: descending
 * 	$cb_argv	any			extra value passed to callback
 * 
 * callback signature:
 * function name($fn, $dir [, $argv])
 * 	$fn		string		full dir entry name
 * 	$dir	string		start dir path
 *  $argv	any			user value to callback
 * 
 */
function up_walktree($dir, $callback=Null, $sort=0, $cb_argv=Null ,  $startdir=Null){
	if (is_null($callback)) return;
	if (is_null($startdir)) $startdir = $dir;
	$res = scandir($dir, $sort);
	foreach($res as $i=>$v){
		if ($v!="." && $v!=".."){
			$fn = $dir."/".$v;
			if ($sort==0) $callback($fn, $startdir, $cb_argv);	
			if (is_dir($fn)) up_walktree($fn, $callback, $sort, $cb_argv, $startdir);
			if ($sort==1) $callback($fn, $startdir, $cb_argv);	
		}
	}
	
}

function up_copy($fn, $dir){
	global $up_countfiles, $up_totalfiles, $up_lastp;
	$up_countfiles++; $prc=(int)(((float)$up_countfiles/(float)$up_totalfiles)*100);
	
	if (strpos($fn, ".gitignore")>-1 || strpos($fn, ".htaccess")>-1) return;  
	$ddest = dirname(dirname(__file__));
	$fd = str_replace($dir, $ddest, $fn);
	
	if (is_dir($fn) && !is_dir($fd)) {
		$re=mkdir($fd,0777,true);
	}
	if (!is_dir($fn)){
		$re=copy($fn, $fd);
	}
	
	if ($re===false) { 
		out("ERROR. Abort."); 
		killme();
	}
	out("copy@Copy@$prc%");
}

function up_ftp($fn, $dir, $argv){
	global $up_countfiles, $up_totalfiles, $up_lastp;
	$up_countfiles++; $prc=(int)(((float)$up_countfiles/(float)$up_totalfiles)*100);

	if (strpos($fn, ".gitignore")>-1 || strpos($fn, ".htaccess")>-1) return;
	
	list($ddest, $conn_id) = $argv;
	$l = strlen($ddest)-1;
	if (substr($ddest,$l,1)=="/") $ddest = substr($ddest,0,$l);
	$fd = str_replace($dir, $ddest, $fn);
	
	if (is_dir($fn)){
		if (ftp_nlist($conn_id, $fd)===false) { 
			$ret = ftp_mkdir($conn_id, $fd);
		} else {
			$ret=true;
		}
	} else {
		$ret = ftp_put($conn_id, $fd, $fn, FTP_BINARY); 
	}
	if (!$ret) { 
		out("ERROR. Abort."); 
		killme();
	} 
	out("copy@Copy@$prc%");
}

function up_rm($fn, $dir){
	if (is_dir($fn)){
		rmdir($fn);
	} else {
		unlink($fn);
	}
}

function up_dlfile($url, $file) {
	$in = fopen ($url, "r");
	$out = fopen ($file, "w");
	
	$fs = filesize($url);
	

	if (!$in || !$out) return false;
	
	$s=0; $count=0;
	while (!feof ($in)) {
		$line = fgets ($in, 1024);
		fwrite( $out, $line);
		
		$count++; $s += strlen($line);
		if ($count==50){
			$count=0;
			$sp=$s/1024.0; $ex="Kb";
			if ($sp>1024) { $sp=$sp/1024; $ex="Mb"; }
			if ($sp>1024) { $sp=$sp/1024; $ex="Gb"; }
			$sp = ((int)($sp*100))/100;
			out("dwl@Download@".$sp.$ex);
		}
	}
	fclose($in);
	return true;
}

function doUpdate($remotefile, $ftpdata=false){
	global $up_totalfiles;
	
	
	$localtmpfile = tempnam("/tmp", "fk");
	out("dwl@Download@starting...");
	$rt= up_dlfile($remotefile, $localtmpfile);
	if ($rt==false || filesize($localtmpfile)==0){
		out("dwl@Download@ERROR.");
		unlink($localtmpfile);
		return;
	}
	out("dwl@Download@Ok.");
	
	out("unzip@Unzip@");
	$tmpdirname = $localfile."ex";
	mkdir($tmpdirname);
	up_unzip($localtmpfile, $tmpdirname);
	$basedir = glob($tmpdirname."/*"); $basedir=$basedir[0];
	out ("unzip@Unzip@Ok.");
	
	$up_totalfiles = up_count($basedir."/");
	
	if (canWeWrite()){
		out("copy@Copy@");
		up_walktree($basedir, 'up_copy');
	}
	if ($ftpdata!==false && is_array($ftpdata) && $ftpdata['ftphost']!="" ){
		out("ftpcon@Connect to FTP@");
		$conn_id = ftp_connect($ftpdata['ftphost']);
		$login_result = ftp_login($conn_id, $ftpdata['ftpuser'], $ftpdata['ftppwd']);
		
		if ((!$conn_id) || (!$login_result)) { 
			out("ftpcon@Connect to FTP@FAILED");
			up_clean($tmpdirname, $localtmpfile); 
			return;
		} else {
			out("ftpcon@Connect to FTP@Ok.");
		}
		out("copy@Copy@");
		up_walktree($basedir, 'up_ftp', 0, array( $ftpdata['ftppath'], $conn_id));
 
		ftp_close($conn_id);
	}

	up_clean($tmpdirname, $localtmpfile); 
	
}

function up_clean($tmpdirname, $localtmpfile){
	out("clean@Clean up@");
	unlink($localtmpfile);
	up_walktree($tmpdirname, 'up_rm', 1);
	rmdir($tmpdirname);
	out("clean@Clean up@Ok.");
}
