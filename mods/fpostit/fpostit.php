<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

if (($_POST["friendica_acct_name"] != '') && ($_POST["friendica_password"] != '')) {
	setcookie("username", $_POST["friendica_acct_name"], time()+60*60*24*300);
	setcookie("password", $_POST["friendica_password"], time()+60*60*24*300);
}

?>
<html>
<head>
	<style>
		body {
			font-family: arial, Helvetica,sans-serif;
			margin: 0px;
		}
		.wrap1 {
			padding: 2px 5px;
			background-color: #729FCF;
			margin-bottom: 10px;
		}
		.wrap2 {
			margin-left: 10px;
			font-size: 12px;
		}
		.logo {
			margin-left: 3px;
			margin-right: 5px;
			float: left;
		}
		h2 {
			color: #ffffff;
		}
		.error {
			background-color: #FFFF66;
			font-size: 12px;
			margin-left: 10px;
		}
	</style>
</head>

<body>
<?php

if (isset($_GET['title'])) {
	$title = $_GET['title'];
}
if (isset($_GET['text'])) {
	$text = $_GET['text'];
}
if (isset($_GET['url'])) {
	$url = $_GET['url'];
}

if ((isset($title)) && (isset($text)) && (isset($url))) {
	$content = "$title\nsource:$url\n\n$text";
} else {
	$content = $_POST['content'];
}

if (isset($_POST['submit'])) {

	if (($_POST["friendica_acct_name"] != '') && ($_POST["friendica_password"] != '')) {
		$acctname = $_POST["friendica_acct_name"];
		$tmp_account_array = explode("@", $acctname);
		if (isset($tmp_account_array[1])) {
			$username = $tmp_account_array[0];
			$hostname = $tmp_account_array[1];
		}
		$password = $_POST["friendica_password"];
		$content = $_POST["content"];

		$url = "http://" . $hostname . '/api/statuses/update';
		$data = ['status' => $content];

		// echo "posting to: $url<br/>";

		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, $url);
		curl_setopt($c, CURLOPT_USERPWD, "$username:$password");
		curl_setopt($c, CURLOPT_POSTFIELDS, $data);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
		$c_result = curl_exec($c);
		if(curl_errno($c)){
			$error = curl_error($c);
			showForm($error, $content);
		}

		curl_close($c);
		if (!isset($error)) {
			echo '<script language="javascript" type="text/javascript">window.close();</script>';
		}

	} else {
		$error = "Missing account name and/or password...try again please";
		showForm($error, $content);
	}

} else {
	showForm(null, $content);
}

function showForm($error, $content) {
	$username_cookie = $_COOKIE['username'];
	$password_cookie = $_COOKIE['password'];

	echo <<<EOF
	<div class='wrap1'>
		<h2><img class='logo' width="32" height="32" src='friendica.svg' align='middle';/>
		Friendica Bookmarklet</h2>
	</div>

	<div class="wrap2">
		<form method="post" action="{$_SERVER['PHP_SELF']}">
			Enter the email address of the Friendica Account that you want to cross-post to:(example: user@friendica.org)<br /><br />
			Account ID: <input type="text" name="friendica_acct_name" value="{$username_cookie}" size="50"/><br />
			Password: <input type="password" name="friendica_password" value="{$password_cookie}" size="50"/><br />
			<textarea name="content" id="content" rows="6" cols="70">{$content}</textarea><br />
			<input type="submit" value="PostIt!" name="submit" />&nbsp;&nbsp;<span class='error'>$error</span>
		</form>
		<p></p>
	</div>
EOF;

}
?>

</body>
</html>