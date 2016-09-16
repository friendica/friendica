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

if(is_null($a)) {
	$a = new App;
}

if(is_null($db)) {
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

class exAuth
{
	private $sLogFile;
	private $bDebug;

	private $rLogFile;

	public function __construct($sLogFile, $bDebug)
	{
		global $a, $db;

		// setter
		$this->sLogFile 	= $sLogFile;
		$this->bDebug		= $bDebug;

		// ovo ne provjeravamo jer ako ne mozes kreirati log file, onda si u kvascu :)
		if ($this->sLogFile != '')
			$this->rLogFile = fopen($this->sLogFile, "a") or die("Error opening log file: ". $this->sLogFile);

		$this->writeLog("[exAuth] start");

		// ovdje bi trebali biti spojeni na MySQL, imati otvoren log i zavrtit cekalicu
		do {
			$iHeader	= fgets(STDIN, 3);
			$aLength 	= unpack("n", $iHeader);
			$iLength	= $aLength["1"];
			if($iLength > 0) {
				// ovo znaci da smo nesto dobili
				$sData = fgets(STDIN, $iLength + 1);
				$this->writeDebugLog("[debug] received data: ". $sData);
				$aCommand = explode(":", $sData);
				if (is_array($aCommand)){
					switch ($aCommand[0]){
						case "isuser":
							// provjeravamo je li korisnik dobar
							if (!isset($aCommand[1])){
								$this->writeLog("[exAuth] invalid isuser command, no username given");
								fwrite(STDOUT, pack("nn", 2, 0));
							} else {
								// ovdje provjeri je li korisnik OK
								$sUser = str_replace(array("%20", "(a)"), array(" ", "@"), $aCommand[1]);
								$this->writeDebugLog("[debug] checking isuser for ". $sUser);
								$sQuery = "SELECT `uid` FROM `user` WHERE `nickname`='". $db->escape($sUser) ."'";
								$this->writeDebugLog("[debug] using query ". $sQuery);
								if ($oResult = q($sQuery)){
									if ($oResult) {
										// korisnik OK
										$this->writeLog("[exAuth] valid user: ". $sUser);
										fwrite(STDOUT, pack("nn", 2, 1));
									} else {
										// korisnik nije OK
										$this->writeLog("[exAuth] invalid user: ". $sUser);
										fwrite(STDOUT, pack("nn", 2, 0));
									}
									//$oResult->close();
								} else {
									$this->writeLog("[MySQL] invalid query: ". $sQuery);
									fwrite(STDOUT, pack("nn", 2, 0));
								}
							}
						break;
						case "auth":
							// provjeravamo autentifikaciju korisnika
							if (sizeof($aCommand) != 4){
								$this->writeLog("[exAuth] invalid auth command, data missing");
								fwrite(STDOUT, pack("nn", 2, 0));
							} else {
								// ovdje provjeri prijavu
								$sUser = str_replace(array("%20", "(a)"), array(" ", "@"), $aCommand[1]);
								$this->writeDebugLog("[debug] doing auth for ".$sUser."@".$aCommand[2]);

								// If the hostnames doesn't match, we try to authenticate remotely
								if ($a->get_hostname() != $aCommand[2])
									$Error = !$this->check_credentials($aCommand[2], $aCommand[1], $aCommand[3], true);
								else {

									//$sQuery = "SELECT `uid`, `password` FROM `user` WHERE `password`='".hash('whirlpool',$aCommand[3])."' AND `nickname`='". $db->escape($sUser) ."'";
									$sQuery = "SELECT `uid`, `password` FROM `user` WHERE `nickname`='". $db->escape($sUser) ."'";
									$this->writeDebugLog("[debug] using query ". $sQuery);
									if ($oResult = q($sQuery)){
										$uid = $oResult[0]["uid"];
										$Error = ($oResult[0]["password"] != hash('whirlpool',$aCommand[3]));
									} else {
										$this->writeLog("[MySQL] invalid query: ". $sQuery);
										$Error = true;
										$uid = -1;
									}
									if ($Error) {
										$oConfig = q("SELECT `v` FROM `pconfig` WHERE `uid`=%d AND `cat` = 'xmpp' AND `k`='password' LIMIT 1;", intval($uid));
										$this->writeLog("[exAuth] got password ".$oConfig[0]["v"]);
										$Error = ($aCommand[3] != $oConfig[0]["v"]);
									}
								}
								if ($Error) {
									$this->writeLog("[exAuth] authentification failed for user ". $sUser ."@". $aCommand[2]);
									fwrite(STDOUT, pack("nn", 2, 0));
								} else {
									$this->writeLog("[exAuth] authentificated user ". $sUser ."@". $aCommand[2]);
									fwrite(STDOUT, pack("nn", 2, 1));
								}
							}
						break;
						case "setpass":
							// postavljanje zaporke, onemoguceno
							$this->writeLog("[exAuth] setpass command disabled");
							fwrite(STDOUT, pack("nn", 2, 0));
						break;
						default:
							// ako je uhvaceno ista drugo
							$this->writeLog("[exAuth] unknown command ". $aCommand[0]);
							fwrite(STDOUT, pack("nn", 2, 0));
						break;
					}
				} else {
					$this->writeDebugLog("[debug] invalid command string");
					fwrite(STDOUT, pack("nn", 2, 0));
				}
			}
			unset ($iHeader);
			unset ($aLength);
			unset ($iLength);
			unset($aCommand);
		} while (true);
	}

	public function __destruct()
	{
		// zatvori log file
		$this->writeLog("[exAuth] stop");

		if (is_resource($this->rLogFile)){
			fclose($this->rLogFile);
		}
	}

	private function check_credentials($host, $user, $password, $ssl) {

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

		return($http_code == 200);
	}

	private function writeLog($sMessage)
	{
		if (is_resource($this->rLogFile)) {
			fwrite($this->rLogFile, date("r") ." ". $sMessage ."\n");
		}
	}

	private function writeDebugLog($sMessage)
	{
		if ($this->bDebug){
			$this->writeLog($sMessage);
		}
	}

}
?>


