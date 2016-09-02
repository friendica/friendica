<?php
require_once("dbm.php");

# if PDO is avaible for mysql, use the new database abstraction
# TODO: PDO is disabled for release 3.3. We need to investigate why
# the update from 3.2 fails with pdo
/*
if(class_exists('\PDO') && in_array('mysql', PDO::getAvailableDrivers())) {
  require_once("library/dddbl2/dddbl.php");
  require_once("include/dba_pdo.php");
}
*/


require_once('include/datetime.php');

/**
 * @class MySQL database class
 *
 * For debugging, insert 'dbg(1);' anywhere in the program flow.
 * dbg(0); will turn it off. Logging is performed at LOGGER_DATA level.
 * When logging, all binary info is converted to text and html entities are escaped so that
 * the debugging stream is safe to view within both terminals and web pages.
 *
 */

if(! class_exists('dba')) {
class dba {

	private $debug = 0;
	private $db;
	private $result;
	public  $mysqli = true;
	public  $connected = false;
	public  $error = false;

	function __construct($server,$user,$pass,$db,$install = false) {
		global $a;

		$stamp1 = microtime(true);

		$server = trim($server);
		$user = trim($user);
		$pass = trim($pass);
		$db = trim($db);

		if (!(strlen($server) && strlen($user))){
			$this->connected = false;
			$this->db = null;
			return;
		}

		if($install) {
			if(strlen($server) && ($server !== 'localhost') && ($server !== '127.0.0.1')) {
				if(! dns_get_record($server, DNS_A + DNS_CNAME + DNS_PTR)) {
					$this->error = sprintf( t('Cannot locate DNS info for database server \'%s\''), $server);
					$this->connected = false;
					$this->db = null;
					return;
				}
			}
		}

		if(class_exists('mysqli')) {
			$this->db = @new mysqli($server,$user,$pass,$db);
			if(! mysqli_connect_errno()) {
				$this->connected = true;
			}
			if (isset($a->config["system"]["db_charset"]))
				$this->db->set_charset($a->config["system"]["db_charset"]);
		}
		else {
			$this->mysqli = false;
			$this->db = mysql_connect($server,$user,$pass);
			if($this->db && mysql_select_db($db,$this->db)) {
				$this->connected = true;
			}
			if (isset($a->config["system"]["db_charset"]))
				mysql_set_charset($a->config["system"]["db_charset"], $this->db);
		}
		if(! $this->connected) {
			$this->db = null;
			if(! $install)
				system_unavailable();
		}

		$a->save_timestamp($stamp1, "network");
	}

	public function getdb() {
		return $this->db;
	}

	public function q($sql, $onlyquery = false) {
		global $a;

		if((! $this->db) || (! $this->connected))
			return false;

		$this->error = '';

		// Check the connection (This can reconnect the connection - if configured)
		if ($this->mysqli)
			$connected = $this->db->ping();
		else
			$connected = mysql_ping($this->db);

		$connstr = ($connected ? "Connected": "Disonnected");

		$stamp1 = microtime(true);

		if($this->mysqli)
			$result = @$this->db->query($sql);
		else
			$result = @mysql_query($sql,$this->db);

		$stamp2 = microtime(true);
		$duration = (float)($stamp2-$stamp1);

		$a->save_timestamp($stamp1, "database");

		if (strtolower(substr($sql, 0, 6)) != "select")
			$a->save_timestamp($stamp1, "database_write");

		if(x($a->config,'system') && x($a->config['system'],'db_log')) {
			if (($duration > $a->config["system"]["db_loglimit"])) {
				$duration = round($duration, 3);
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				@file_put_contents($a->config["system"]["db_log"], datetime_convert()."\t".$duration."\t".
						basename($backtrace[1]["file"])."\t".
						$backtrace[1]["line"]."\t".$backtrace[2]["function"]."\t".
						substr($sql, 0, 2000)."\n", FILE_APPEND);
			}
		}

		if($this->mysqli) {
			if($this->db->errno) {
				$this->error = $this->db->error;
				$this->errorno = $this->db->errno;
			}
		} elseif(mysql_errno($this->db)) {
			$this->error = mysql_error($this->db);
			$this->errorno = mysql_errno($this->db);
		}

		if(strlen($this->error)) {
			logger('DB Error ('.$connstr.') '.$this->errorno.': '.$this->error);
		}

		if($this->debug) {

			$mesg = '';

			if($result === false)
				$mesg = 'false';
			elseif($result === true)
				$mesg = 'true';
			else {
				if($this->mysqli)
					$mesg = $result->num_rows . ' results' . EOL;
    			else
					$mesg = mysql_num_rows($result) . ' results' . EOL;
			}

			$str =  'SQL = ' . printable($sql) . EOL . 'SQL returned ' . $mesg
				. (($this->error) ? ' error: ' . $this->error : '')
				. EOL;

			logger('dba: ' . $str );
		}

		/**
		 * If dbfail.out exists, we will write any failed calls directly to it,
		 * regardless of any logging that may or may nor be in effect.
		 * These usually indicate SQL syntax errors that need to be resolved.
		 */

		if($result === false) {
			logger('dba: ' . printable($sql) . ' returned false.' . "\n" . $this->error);
			if(file_exists('dbfail.out'))
				file_put_contents('dbfail.out', datetime_convert() . "\n" . printable($sql) . ' returned false' . "\n" . $this->error . "\n", FILE_APPEND);
		}

		if(($result === true) || ($result === false))
			return $result;

		if ($onlyquery) {
			$this->result = $result;
			return true;
		}

		$r = array();
		if($this->mysqli) {
			if($result->num_rows) {
				while($x = $result->fetch_array(MYSQLI_ASSOC))
					$r[] = $x;
				$result->free_result();
			}
		}
		else {
			if(mysql_num_rows($result)) {
				while($x = mysql_fetch_array($result, MYSQL_ASSOC))
					$r[] = $x;
				mysql_free_result($result);
			}
		}

		//$a->save_timestamp($stamp1, "database");

		if($this->debug)
			logger('dba: ' . printable(print_r($r, true)));
		return($r);
	}

	public function qfetch() {
		$x = false;

		if ($this->result)
			if($this->mysqli) {
				if($this->result->num_rows)
					$x = $this->result->fetch_array(MYSQLI_ASSOC);
			} else {
				if(mysql_num_rows($this->result))
					$x = mysql_fetch_array($this->result, MYSQL_ASSOC);
			}

		return($x);
	}

	public function qclose() {
		if ($this->result)
			if($this->mysqli) {
				$this->result->free_result();
			} else {
				mysql_free_result($this->result);
			}
	}

	public function dbg($dbg) {
		$this->debug = $dbg;
	}

	public function escape($str) {
		if($this->db && $this->connected) {
			if($this->mysqli)
				return @$this->db->real_escape_string($str);
			else
				return @mysql_real_escape_string($str,$this->db);
		}
	}

	function __destruct() {
		if ($this->db)
			if($this->mysqli)
				$this->db->close();
			else
				mysql_close($this->db);
	}
}}

if(! function_exists('printable')) {
function printable($s) {
	$s = preg_replace("~([\x01-\x08\x0E-\x0F\x10-\x1F\x7F-\xFF])~",".", $s);
	$s = str_replace("\x00",'.',$s);
	if(x($_SERVER,'SERVER_NAME'))
		$s = escape_tags($s);
	return $s;
}}

// Procedural functions
if(! function_exists('dbg')) {
function dbg($state) {
	global $db;
	if($db)
	$db->dbg($state);
}}

if(! function_exists('dbesc')) {
function dbesc($str) {
	global $db;
	if($db && $db->connected)
		return($db->escape($str));
	else
		return(str_replace("'","\\'",$str));
}}



// Function: q($sql,$args);
// Description: execute SQL query with printf style args.
// Example: $r = q("SELECT * FROM `%s` WHERE `uid` = %d",
//                   'user', 1);

if(! function_exists('q')) {
function q($sql) {

	global $db;
	$args = func_get_args();
	unset($args[0]);

	if($db && $db->connected) {
		$stmt = @vsprintf($sql,$args); // Disabled warnings
		//logger("dba: q: $stmt", LOGGER_ALL);
		if($stmt === false)
			logger('dba: vsprintf error: ' . print_r(debug_backtrace(),true), LOGGER_DEBUG);
		return $db->q($stmt);
	}

	/**
	 *
	 * This will happen occasionally trying to store the
	 * session data after abnormal program termination
	 *
	 */
	logger('dba: no database: ' . print_r($args,true));
	return false;

}}

/**
 *
 * Raw db query, no arguments
 *
 */

if(! function_exists('dbq')) {
function dbq($sql) {

	global $db;
	if($db && $db->connected)
		$ret = $db->q($sql);
	else
		$ret = false;
	return $ret;
}}


// Caller is responsible for ensuring that any integer arguments to
// dbesc_array are actually integers and not malformed strings containing
// SQL injection vectors. All integer array elements should be specifically
// cast to int to avoid trouble.


if(! function_exists('dbesc_array_cb')) {
function dbesc_array_cb(&$item, $key) {
	if(is_string($item))
		$item = dbesc($item);
}}


if(! function_exists('dbesc_array')) {
function dbesc_array(&$arr) {
	if(is_array($arr) && count($arr)) {
		array_walk($arr,'dbesc_array_cb');
	}
}}


function dba_timer() {
	return microtime(true);
}
