<?php 
/**
 * When I installed docblox, I had the experience that it does not generate any output at all.
 * This script may be used to find that kind of problems with the documentation build process.
 * If docblox generates output, use another approach for debugging.
 *
 * Basically, docblox takes a list of files to build documentation from. This script assumes there is a file or set of files
 * breaking the build when it is included in that list. It tries to calculate the smallest list containing these files.
 * Unfortunatly, the original problem is NP-complete, so what the script does is a best guess only.
 *
 * So it starts with a list of all files in the project.
 * If that list can't be build, it cuts it in two parts and tries both parts independently. If only one of them breaks,
 * it takes that one and tries the same independently. If both break, it assumes this is the smallest set. This assumption
 * is not necessarily true. Maybe the smallest set consists of two files and both of them were in different parts when
 * the list was divided, but by now it is my best guess. To make this assumption better, the list is shuffled after every step. 
 *
 * After that, the script tries to remove a file from the list. It tests if the list breaks and if so, it
 * assumes that the file it removed belongs to the set of errorneous files. 
 * This is done for all files, so, in the end removing one file leads to a working doc build. 
 *
 * @package util
 * @author Alexander Kampmann
 */

/**
 * This function generates a comma seperated list of file names.
 * 
 * @package util
 * 
 * @param array $fileset Set of file names
 * 
 * @return string comma-seperated list of the file names
 */
function namesList($fileset) {
	$fsparam="";
	foreach($fileset as $file) {
		$fsparam=$fsparam.",".$file;
	}
	return $fsparam;
};

/**
 * This functions runs phpdoc on the provided list of files
 * @package util
 * 
 * @param array $fileset Set of filenames
 * 
 * @return bool true, if that set can be built
 */
function runs($fileset) {
	$fsParam=namesList($fileset);
	exec('docblox -t phpdoc_out -f '.$fsParam);
	if(file_exists("phpdoc_out/index.html")) {
		echo "\n Subset ".$fsParam." is okay. \n";
		exec('rm -r phpdoc_out');
		return true;
	} else {
		echo "\n Subset ".$fsParam." failed. \n";
		return false;
	}
};

/**
 * This functions cuts down a fileset by removing files until it finally works.
 * it was meant to be recursive, but php's maximum stack size is to small. So it just simulates recursion.
 *
 * In that version, it does not necessarily generate the smallest set, because it may not alter the elements order enough.
 * 
 * @package util
 * 
 * @param array $fileset set of filenames
 * @param int $ps number of files in subsets
 * 
 * @return array a part of $fileset, that crashes
 */
function reduce($fileset, $ps) {
	//split array...
	$parts=array_chunk($fileset, $ps);
	//filter working subsets...
	$parts=array_filter($parts, "runs");
	//melt remaining parts together
	if(is_array($parts)) {
		return array_reduce($parts, "array_merge", array());
	}
	return array();
};

//return from util folder to frindica base dir
$dir='..';

//stack for dirs to search
$dirstack=array();
//list of source files
$filelist=array();

//loop over all files in $dir
while($dh=opendir($dir)) {
	while($file=readdir($dh)) {
		if(is_dir($dir."/".$file)) {
			//add to directory stack
			if($file!=".." && $file!=".") {
				array_push($dirstack, $dir."/".$file);
				echo "dir ".$dir."/".$file."\n";
			}
		} else  {
			//test if it is a source file and add to filelist
			if(substr($file, strlen($file)-4)==".php") {
				array_push($filelist, $dir."/".$file);
				echo $dir."/".$file."\n";
			}
		}
	}
	//look at the next dir
	$dir=array_pop($dirstack);
}

//check the entire set
if(runs($filelist)) {
	echo "I can not detect a problem. \n";
	exit;
}

//check half of the set and discard if that half is okay
$res=$filelist;
$i=0;
do {
	$i=count($res);
	echo $i."/".count($fileset)." elements remaining. \n";
	$res=reduce($res, count($res)/2);
	shuffle($res);
} while(count($res)<$i);

//check one file after another
$needed=array();

while(count($res)!=0) {
	$file=array_pop($res);

	if(runs(array_merge($res, $needed))) {
		echo "needs: ".$file." and file count ".count($needed);
		array_push($needed, $file);
	}
}

echo "\nSmallest Set is: ".namesList($needed)." with ".count($needed)." files. ";
