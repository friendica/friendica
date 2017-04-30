#!/usr/bin/php
<?php
/**
 * @file util/createdoxygen.php
 * @brief Adds a doxygen header to functions
 */

if (count($_SERVER["argv"]) < 2)
	die("usage: createdoxygen.php file\n");

$file = $_SERVER["argv"][1];
$data = file_get_contents($file);

$lines = explode("\n", $data);

$previous = "";

foreach ($lines AS $line) {
	$line = rtrim(trim($line, "\r"));

	if (strstr(strtolower($line), "function")) {
		$detect = strtolower(trim($line));
		$detect = implode(" ", explode(" ", $detect));

		$found = false;

		if (substr($detect, 0, 9) == "function ")
			$found = true;

		if (substr($detect, 0, 17) == "private function ")
			$found = true;

		if (substr($detect, 0, 23) == "public static function ")
			$found = true;

		if (substr($detect, 0, 10) == "function (")
			$found = false;

		if ($found and (trim($previous) == "*/"))
			$found = false;

		if ($found and !strstr($detect, "{"))
			$found = false;

		if ($found) {
			echo add_documentation($line);
		}
	}
	echo $line."\n";
	$previous = $line;
}

/**
 * @brief Adds a doxygen header
 *
 * @param string $line The current line of the document
 *
 * @return string added doxygen header
 */
function add_documentation($line) {

	$trimmed = ltrim($line);
	$length = strlen($line) - strlen($trimmed);
	$space = substr($line, 0, $length);

	$block = $space."/**\n".
		$space." * @brief \n".
		$space." *\n"; /**/


	$left = strpos($line, "(");
	$line = substr($line, $left + 1);

	$right = strpos($line, ")");
	$line = trim(substr($line, 0, $right));

	if ($line != "") {
		$parameters = explode(",", $line);
		foreach ($parameters AS $parameter) {
			$parameter = trim($parameter);
			$splitted = explode("=", $parameter);

			$block .= $space." * @param ".trim($splitted[0], "& ")."\n";
		}
		if (count($parameters) > 0)
			$block .= $space." *\n";
	}

	$block .= $space." * @return \n".
		$space." */\n";

	return $block;
}
