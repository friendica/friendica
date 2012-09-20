<?php
	// Tired of chasing typos and finding them after a commit. 
	// Run this from cmdline in basedir and quickly see if we've 
	// got any parse errors in our application files.


	error_reporting(E_ERROR | E_WARNING | E_PARSE );
	ini_set('display_errors', '1');
	ini_set('log_errors','0');

	include 'boot.php';
	
	$a = new App();

	echo "Directory: mod\n";
	$files = glob('mod/*.php');
	foreach($files as $file) {
		echo $file . "\n";
		include_once($file);
	}

	echo "Directory: include\n";
	$files = glob('include/*.php');
	foreach($files as $file) {
		echo $file . "\n";
		include_once($file);
	}
    
    echo "Directory: object\n";
	$files = glob('object/*.php');
	foreach($files as $file) {
		echo $file . "\n";
		include_once($file);
	}

	echo "Directory: addon\n";
	$dirs = glob('addon/*');

	foreach($dirs as $dir) {
		$addon = basename($dir);
		$files = glob($dir . '/' . $addon . '.php');
		foreach($files as $file) {
			echo $file . "\n";
			include_once($file);
		}
	}

	if(x($a->config,'php_path'))
		$phpath = $a->config['php_path'];
	else
		$phpath = 'php';

	echo "String files\n";

	echo 'util/strings.php' . "\n";
	include_once('util/strings.php');
	echo count($a->strings) . ' strings' . "\n";

	$files = glob('view/*/strings.php');

	foreach($files as $file) {
		echo $file . "\n";
	passthru($phpath . ' util/typohelper.php ' . $file);
//		include_once($file);
	}
