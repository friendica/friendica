<?php

require_once('include/datetime.php');

$objDDDBLResultHandler = new \DDDBL\DataObjectPool('Result-Handler');

/**
  * create handler, which returns just the PDOStatement object
  * this allows usage of the cursor to scroll through
  * big result-sets
  *
  **/
$cloPDOStatementResultHandler = function(\DDDBL\Queue $objQueue) {

  $objPDO = $objQueue->getState()->get('PDOStatement');
  $objQueue->getState()->update(array('result' => $objPDO));
  
  # delete handler which closes the PDOStatement-cursor
  # this will be done manual if using this handler
  $objQueue->deleteHandler(QUEUE_CLOSE_CURSOR_POSITION);

};

$objDDDBLResultHandler->add('PDOStatement', array('HANDLER' => $cloPDOStatementResultHandler));

/**
 *
 * MySQL database class
 *
 * For debugging, insert 'dbg(1);' anywhere in the program flow.
 * dbg(0); will turn it off. Logging is performed at LOGGER_DATA level.
 * When logging, all binary info is converted to text and html entities are escaped so that 
 * the debugging stream is safe to view within both terminals and web pages.
 *
 */
 
if (! class_exists('dba')) { 
class dba {

	private $debug = 0;
	private $db;
	private $result;
	public  $connected = false;
	public  $error = false;

	function __construct($server,$user,$pass,$db,$install = false) {
		$a = get_app();
    
    # work around, to store the database - configuration in DDDBL
    $objDataObjectPool = new \DDDBL\DataObjectPool('Database-Definition');
    $objDataObjectPool->add('DEFAULT', array('CONNECTION' => "mysql:host=$server;dbname=$db",
                                             'USER'       => $user,
                                             'PASS'       => $pass,
                                             'DEFAULT'    => true));

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

		if ($install) {
			if (strlen($server) && ($server !== 'localhost') && ($server !== '127.0.0.1')) {
				if (! dns_get_record($server, DNS_A + DNS_CNAME + DNS_PTR)) {
					$this->error = sprintf( t('Cannot locate DNS info for database server \'%s\''), $server);
					$this->connected = false;
					$this->db = null;
					return;
				}
			}
		}

    # etablish connection to database and store PDO object
    \DDDBL\connect();
    $this->db = \DDDBL\getDB();
    
    if (\DDDBL\isConnected()) {
      $this->connected = true;
    }
  
		if (! $this->connected) {
			$this->db = null;
			if (! $install)
				system_unavailable();
		}

		$a->save_timestamp($stamp1, "network");
	}

	public function getdb() {
		return $this->db;
	}

	public function q($sql, $onlyquery = false) {
		$a = get_app();

    $strHandler = (true === $onlyquery) ? 'PDOStatement' : 'MULTI';
    
    $strQueryAlias = md5($sql);
    $strSQLType    = strtoupper(strstr($sql, ' ', true));
    
    $objPreparedQueryPool = new \DDDBL\DataObjectPool('Query-Definition');
    
    # check if query do not exists till now, if so create its definition
    if (!$objPreparedQueryPool->exists($strQueryAlias))
      $objPreparedQueryPool->add($strQueryAlias, array('QUERY'   => $sql,
                                                       'HANDLER' => $strHandler));

		if ((! $this->db) || (! $this->connected))
			return false;

		$this->error = '';

		$stamp1 = microtime(true);

    try {
      $r = \DDDBL\get($strQueryAlias);
      
      # bad workaround to emulate the bizzare behavior of mysql_query
      if (in_array($strSQLType, array('INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'SET')))
        $result = true;
      $intErrorCode = false;
        
    } catch (\Exception $objException) {
      $result = false;
      $intErrorCode = $objPreparedQueryPool->get($strQueryAlias)->get('PDOStatement')->errorCode();
    }

		$stamp2 = microtime(true);
		$duration = (float)($stamp2-$stamp1);

		$a->save_timestamp($stamp1, "database");

		if (x($a->config,'system') && x($a->config['system'],'db_log')) {
			if (($duration > $a->config["system"]["db_loglimit"])) {
				$duration = round($duration, 3);
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
				@file_put_contents($a->config["system"]["db_log"], datetime_convert()."\t".$duration."\t".
						basename($backtrace[1]["file"])."\t".
						$backtrace[1]["line"]."\t".$backtrace[2]["function"]."\t".
						substr($sql, 0, 2000)."\n", FILE_APPEND);
			}
		}

		if ($intErrorCode)
      $this->error = $intErrorCode;

		if (strlen($this->error)) {
			logger('dba: ' . $this->error);
		}

		if ($this->debug) {

			$mesg = '';

			if ($result === false)
				$mesg = 'false';
			elseif ($result === true)
				$mesg = 'true';
			else {
        # this needs fixing, but is a bug itself
				#$mesg = mysql_num_rows($result) . ' results' . EOL; 
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

		if (isset($result) AND ($result === false)) {
			logger('dba: ' . printable($sql) . ' returned false.' . "\n" . $this->error);
			if (file_exists('dbfail.out'))
				file_put_contents('dbfail.out', datetime_convert() . "\n" . printable($sql) . ' returned false' . "\n" . $this->error . "\n", FILE_APPEND);
		}

		if (isset($result) AND (($result === true) || ($result === false)))
			return $result;
    
		if ($onlyquery) {
			$this->result = $r;       # this will store an PDOStatement Object in result
      $this->result->execute(); # execute the Statement, to get its result
			return true;
		}
    
		//$a->save_timestamp($stamp1, "database");

		if ($this->debug)
			logger('dba: ' . printable(print_r($r, true)));
		return($r);
	}

	public function qfetch() {

		if (false === $this->result)
      return false;

    return $this->result->fetch();

	}
  
	public function qclose() {
		if ($this->result)
      return $this->result->closeCursor();
	}

	public function dbg($dbg) {
		$this->debug = $dbg;
	}

	public function escape($str) {
		if ($this->db && $this->connected) {
      $strQuoted = $this->db->quote($str);
      # this workaround is needed, because quote creates "'" and the beginning and the end
      # of the string, which is correct. but until now the queries set this delimiter manually,
      # so we must remove them from here and wait until everything uses prepared statements
      return mb_substr($strQuoted, 1, mb_strlen($strQuoted) - 2); 
		}
	}

	function __destruct() {
		if ($this->db) 
		  \DDDBL\disconnect();
	}
}}

if (! function_exists('printable')) {
function printable($s) {
	$s = preg_replace("~([\x01-\x08\x0E-\x0F\x10-\x1F\x7F-\xFF])~",".", $s);
	$s = str_replace("\x00",'.',$s);
	if (x($_SERVER,'SERVER_NAME'))
		$s = escape_tags($s);
	return $s;
}}

// Procedural functions
if (! function_exists('dbg')) { 
function dbg($state) {
	global $db;
	if ($db)
	$db->dbg($state);
}}

if (! function_exists('dbesc')) { 
function dbesc($str) {
	global $db;
	if ($db && $db->connected)
		return($db->escape($str));
	else
		return(str_replace("'","\\'",$str));
}}



// Function: q($sql,$args);
// Description: execute SQL query with printf style args.
// Example: $r = q("SELECT * FROM `%s` WHERE `uid` = %d",
//                   'user', 1);

if (! function_exists('q')) { 
function q($sql) {

	global $db;
	$args = func_get_args();
	unset($args[0]);

	if ($db && $db->connected) {
		$stmt = @vsprintf($sql,$args); // Disabled warnings
		//logger("dba: q: $stmt", LOGGER_ALL);
		if ($stmt === false)
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

if (! function_exists('dbq')) { 
function dbq($sql) {

	global $db;
	if ($db && $db->connected)
		$ret = $db->q($sql);
	else
		$ret = false;
	return $ret;
}}


// Caller is responsible for ensuring that any integer arguments to 
// dbesc_array are actually integers and not malformed strings containing
// SQL injection vectors. All integer array elements should be specifically 
// cast to int to avoid trouble. 


if (! function_exists('dbesc_array_cb')) {
function dbesc_array_cb(&$item, $key) {
	if (is_string($item))
		$item = dbesc($item);
}}


if (! function_exists('dbesc_array')) {
function dbesc_array(&$arr) {
	if (is_array($arr) && count($arr)) {
		array_walk($arr,'dbesc_array_cb');
	}
}}

if (! function_exists('dba_timer')) {
function dba_timer() {
  return microtime(true);
}}
