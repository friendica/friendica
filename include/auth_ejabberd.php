#!/usr/bin/php
<?php
/*
 * ejabberd extauth script for the integration with friendica
 *
 * Originally written for joomla by Dalibor Karlovic <dado@krizevci.info>
 * modified for Friendica by Michael Vogel <icarus@dabo.de>
 * published under GPL
 *
 * Latest version of the original script for joomla is available at:
 * http://87.230.15.86/~dado/ejabberd/joomla-login
 *
 * Installation:
 *
 *	- Change it's owner to whichever user is running the server, ie. ejabberd
 *	  $ chown ejabberd:ejabberd /path/to/friendica/include/auth_ejabberd.php
 *
 * 	- Change the access mode so it is readable only to the user ejabberd and has exec
 *	  $ chmod 700 /path/to/friendica/include/auth_ejabberd.php
 *
 *	- Edit your ejabberd.cfg file, comment out your auth_method and add:
 *	  {auth_method, external}.
 *	  {extauth_program, "/path/to/friendica/include/auth_ejabberd.php"}.
 *
 *	- Restart your ejabberd service, you should be able to login with your friendica auth info
 *
 * Other hints:
 *	- if your users have a space or a @ in their nickname, they'll run into trouble
 *	  registering with any client so they should be instructed to replace these chars
 *	  " " (space) is replaced with "%20"
 *	  "@" is replaced with "(a)"
 *
 */

if (sizeof($_SERVER["argv"]) == 0)
	die();

$directory = dirname($_SERVER["argv"][0]);

if (substr($directory, 0, 1) != "/")
	$directory = $_SERVER["PWD"]."/".$directory;

$directory = realpath($directory."/..");

chdir($directory);
require_once("boot.php");

global $a, $db;

if (is_null($a))
	$a = new App;

if (is_null($db)) {
	@include(".htconfig.php");
	require_once("include/dba.php");
	$db = new dba($db_host, $db_user, $db_pass, $db_data);
	unset($db_host, $db_user, $db_pass, $db_data);
};

// the logfile to which to write, should be writeable by the user which is running the server
$sLogFile = get_config('jabber','logfile');

// set true to debug if needed
$bDebug	= get_config('jabber','debug');

$oAuth = new exAuth($sLogFile, $bDebug);

class exAuth {
	private $sLogFile;
	private $bDebug;

	private $rLogFile;

	/**
	 * @brief Create the class and do the authentification studd
	 *
	 * @param string $sLogFile The logfile name
	 * @param boolean $bDebug Debug mode
	 */
	public function __construct($sLogFile, $bDebug) {
		global $db;

		// setter
		$this->sLogFile 	= $sLogFile;
		$this->bDebug		= $bDebug;

		// Open the logfile if the logfile name is defined
		if ($this->sLogFile != '')
			$this->rLogFile = fopen($this->sLogFile, "a") or die("Error opening log file: ". $this->sLogFile);

		$this->writeLog("[exAuth] start");

		// We are connected to the SQL server and are having a log file.
		do {
			// Quit if the database connection went down
			if (!$db->connected()) {
				$this->writeDebugLog("[debug] the database connection went down");
				return;
			}

			$iHeader = fgets(STDIN, 3);
			$aLength = unpack("n", $iHeader);
			$iLength = $aLength["1"];

			// No data? Then quit
			if ($iLength == 0) {
				$this->writeDebugLog("[debug] we got no data");
				return;
			}

			// Fetching the data
			$sData = fgets(STDIN, $iLength + 1);
			$this->writeDebugLog("[debug] received data: ". $sData);
			$aCommand = explode(":", $sData);
			if (is_array($aCommand)) {
				switch ($aCommand[0]) {
					case "isuser":
						// Check the existance of a given username
						$this->isuser($aCommand);
						break;
					case "auth":
						// Check if the givven password is correct
						$this->auth($aCommand);
						break;
					case "setpass":
						// We don't accept the setting of passwords here
						$this->writeLog("[exAuth] setpass command disabled");
						fwrite(STDOUT, pack("nn", 2, 0));
						break;
					default:
						// We don't know the given command
						$this->writeLog("[exAuth] unknown command ". $aCommand[0]);
						fwrite(STDOUT, pack("nn", 2, 0));
						break;
				}
			} else {
				$this->writeDebugLog("[debug] invalid command string");
				fwrite(STDOUT, pack("nn", 2, 0));
			}
		} while (true);
	}

	/**
	 * @brief Check if the given username exists
	 *
	 * @param array $aCommand The command array
	 */
	private function isuser($aCommand) {
		global $a;

		// Check if there is a username
		if (!isset($aCommand[1])) {
			$this->writeLog("[exAuth] invalid isuser command, no username given");
			fwrite(STDOUT, pack("nn", 2, 0));
			return;
		}

		// Now we check if the given user is valid
		$sUser = str_replace(array("%20", "(a)"), array(" ", "@"), $aCommand[1]);
		$this->writeDebugLog("[debug] checking isuser for ". $sUser."@".$aCommand[2]);

		// If the hostnames doesn't match, we try to check remotely
		if ($a->get_hostname() != $aCommand[2])
			$found = $this->check_user($aCommand[2], $aCommand[1], true);
		else {
			$sQuery = "SELECT `uid` FROM `user` WHERE `nickname`='".dbesc($sUser)."'";
			$this->writeDebugLog("[debug] using query ". $sQuery);
			$r = q($sQuery);
			$found = dbm::is_result($r);
		}

		if ($found) {
			// The user is okay
			$this->writeLog("[exAuth] valid user: ". $sUser);
			fwrite(STDOUT, pack("nn", 2, 1));
		} else {
			// The user isn't okay
			$this->writeLog("[exAuth] invalid user: ". $sUser);
			fwrite(STDOUT, pack("nn", 2, 0));
		}
	}

	/**
	 * @brief Check remote user existance via HTTP(S)
	 *
	 * @param string $host The hostname
	 * @param string $user Username
	 * @param boolean $ssl Should the check be done via SSL?
	 *
	 * @return boolean Was the user found?
	 */
	private function check_user($host, $user, $ssl) {

		$url = ($ssl ? "https":"http")."://".$host."/noscrape/".$user;

		$data = z_fetch_url($url);

		if (!is_array($data))
			return(false);

		if ($data["return_code"] != "200")
			return(false);

		$json = @json_decode($data["body"]);
		if (!is_object($json))
			return(false);

		return($json->nick == $user);
	}

	/**
	 * @brief Authenticate the givven user and password
	 *
	 * @param array $aCommand The command array
	 */
	private function auth($aCommand) {
		global $a;

		// check user authentication
		if (sizeof($aCommand) != 4) {
			$this->writeLog("[exAuth] invalid auth command, data missing");
			fwrite(STDOUT, pack("nn", 2, 0));
			return;
		}

		// We now check if the password match
		$sUser = str_replace(array("%20", "(a)"), array(" ", "@"), $aCommand[1]);
		$this->writeDebugLog("[debug] doing auth for ".$sUser."@".$aCommand[2]);

		// If the hostnames doesn't match, we try to authenticate remotely
		if ($a->get_hostname() != $aCommand[2])
			$Error = !$this->check_credentials($aCommand[2], $aCommand[1], $aCommand[3], true);
		else {
			$sQuery = "SELECT `uid`, `password` FROM `user` WHERE `nickname`='".dbesc($sUser)."'";
			$this->writeDebugLog("[debug] using query ". $sQuery);
			if ($oResult = q($sQuery)) {
				$uid = $oResult[0]["uid"];
				$Error = ($oResult[0]["password"] != hash('whirlpool',$aCommand[3]));
			} else {
				$this->writeLog("[MySQL] invalid query: ". $sQuery);
				$Error = true;
				$uid = -1;
			}
			if ($Error) {
				$oConfig = q("SELECT `v` FROM `pconfig` WHERE `uid` = %d AND `cat` = 'xmpp' AND `k`='password' LIMIT 1;", intval($uid));
				$this->writeLog("[exAuth] got password ".$oConfig[0]["v"]);
				$Error = ($aCommand[3] != $oConfig[0]["v"]);
			}
		}

		if ($Error) {
			$this->writeLog("[exAuth] authentification failed for user ".$sUser."@". $aCommand[2]);
			fwrite(STDOUT, pack("nn", 2, 0));
		} else {
			$this->writeLog("[exAuth] authentificated user ".$sUser."@".$aCommand[2]);
			fwrite(STDOUT, pack("nn", 2, 1));
		}
	}

	/**
	 * @brief Check remote credentials via HTTP(S)
	 *
	 * @param string $host The hostname
	 * @param string $user Username
	 * @param string $password Password
	 * @param boolean $ssl Should the check be done via SSL?
	 *
	 * @return boolean Are the credentials okay?
	 */
	private function check_credentials($host, $user, $password, $ssl) {
		$this->writeDebugLog("[debug] check credentials for user ".$user." on ".$host);

		$url = ($ssl ? "https":"http")."://".$host."/api/account/verify_credentials.json";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$password);

		$header = curl_exec($ch);
		$curl_info = @curl_getinfo($ch);
		$http_code = $curl_info["http_code"];
		curl_close($ch);

		$this->writeDebugLog("[debug] got HTTP code ".$http_code);

		return ($http_code == 200);
	}

	/**
	 * @brief write data to the logfile
	 *
	 * @param string $sMessage The logfile message
	 */
	private function writeLog($sMessage) {
		if (is_resource($this->rLogFile))
			fwrite($this->rLogFile, date("r")." ".$sMessage."\n");
	}

	/**
	 * @brief write debug data to the logfile
	 *
	 * @param string $sMessage The logfile message
	 */
	private function writeDebugLog($sMessage) {
		if ($this->bDebug)
			$this->writeLog($sMessage);
	}

	/**
	 * @brief destroy the class
	 */
	public function __destruct() {
		// close the log file
		$this->writeLog("[exAuth] stop");

		if (is_resource($this->rLogFile))
			fclose($this->rLogFile);
	}
}
?>
