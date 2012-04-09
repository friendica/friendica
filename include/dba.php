<?php

require_once('include/datetime.php');

/**
 *
 * MySQL database class
 *
 * For debugging, insert 'dbg(1);' anywhere in the program flow.
 * dbg(0); will turn it off. Logging is performed at LOGGER_DATA level.
 * When logging, all binary info is converted to
 * text and html entities are escaped so that
 * the debugging stream is safe to view
 * within both terminals and web pages.
 *
 */

if(! class_exists('dba')) {

	class dba {

		private $debug = 0;
		private $db;
		private $exceptions; 
		
		public  $mysqli = true;
		public  $connected = false;
		public  $error = false;

		function __construct($server,$user,$pass,$db,$install = false) {

			$server = trim($server);
			$user = trim($user);
			$pass = trim($pass);
			$db = trim($db);

			//we need both, server and username, so fail if one is missing
			if (!(strlen($server) && strlen($user))){
				$this->connected = false;
				$this->db = null;
				throw new InvalidArgumentException(t("Server name of user name are missing. "));
			}

			//when we are installing
			if($install) {
				if(strlen($server) && ($server !== 'localhost') && ($server !== '127.0.0.1')) {
					if(! dns_get_record($server, DNS_A + DNS_CNAME + DNS_PTR)) {
						$this->connected = false;
						$this->db = null;
						throw new InvalidArgumentException( t('Cannot locate DNS info for database server \'%s\''), $server);
					}
				}
			}

			if(class_exists('mysqli')) {
				$this->db = new mysqli($server,$user,$pass,$db);
				if(NULL === $this->db->connect_error) {
					$this->connected = true;
				} else {
					throw new RuntimeException($this->db->connect_error);
				}
			} else {
				$this->mysqli = false;
				$this->db = mysql_connect($server,$user,$pass);
				if($this->db && mysql_select_db($db,$this->db)) {
					$this->connected = true;
				} else {
					throw new RuntimeException(mysql_error());
				}
			}
		}

		public function excep($excep) {
			$this->exceptions=$excep; 
		}
		
		public function getdb() {
			return $this->db;
		}

		public function q($sql) {

			if((! $this->db) || (! $this->connected)) {
				$this->throwOrLog(new RuntimeException(t("There is no db connection. ")));
				return;
			}

			if($this->mysqli) {
				$result = $this->db->query($sql);
			} else {
				$result = mysql_query($sql,$this->db);
			}

			//on debug mode or fail, the query is written to the log.
			//this won't work if logger can not read it's logging level
			//from the db.
			if($this->debug || FALSE === $result) {

				$mesg = '';

				if($result === false) {
					$mesg = 'false '.$this->error();
				} elseif($result === true) {
					$mesg = 'true';
				} else {
					if($this->mysqli) {
						$mesg = $result->num_rows . t(' results') . EOL;
					} else {
						$mesg = mysql_num_rows($result) . t(' results') . EOL;
					}
				}

				$str =  'SQL = ' . printable($sql) . EOL . t('SQL returned ') . $mesg . EOL;


			 // If dbfail.out exists, we will write any failed calls directly to it,
			 // regardless of any logging that may or may nor be in effect.
			 // These usually indicate SQL syntax errors that need to be resolved.
				if(file_exists('dbfail.out')) {
					file_put_contents('dbfail.out', datetime_convert() . "\n" . $str . "\n", FILE_APPEND);
				}
				logger('dba: ' . $str );
				if(FALSE===$result) {
					$this->throwOrLog(new RuntimeException('dba: ' . $str));
					return; 
				}
			}
				

			if($result === true) {
				return $result;
			}

			$r = array();
			if($this->mysqli) {
				if($result->num_rows) {
					while($x = $result->fetch_array(MYSQLI_ASSOC)) {
						$r[] = $x;
					}
					$result->free_result();
				}
			} else {
				if(mysql_num_rows($result)) {
					while($x = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$r[] = $x;
					}
					mysql_free_result($result);
				}
			}


			if($this->debug) {
				logger('dba: ' . printable(print_r($r, true)));
			}
			return($r);
		}

		private function error() {
			if($this->mysqli) {
				return $this->db->error;
			} else {
				return mysql_error($this->db);
			}
		}
		
		private function throwOrLog(Exception $ex) {
			if($this->exceptions) {
				throw $ex; 
			} else {
				logger('dba: '.$ex->getMessage()); 
			}
		}
		
		/**
		 * starts a transaction. Transactions need to be finished with 
		 * commit() or rollback(). Please mind that the db table engine may
		 * not support this. 
		 */
		public function beginTransaction() {
			if($this->mysqli) {
				return $this->db->autocommit(false);
			} else {
				//no transaction support in mysql module...
				mysql_query('SET AUTOCOMMIT = 0;', $db); 
			}
		}
		
		/**
		 * rollback a transaction. So, rollback anything that was done since the last call 
		 * to beginTransaction(). 
		 */
		public function rollback() {
			if($this->mysqli) {
				return $this->db->rollback();
			} else {
				//no transaction support in mysql module...
				mysql_query('ROLLBACK;', $db);
			}
			$this->stopTransaction(); 
		}

		/**
		 * commit a transaction. So, write any query to the database. 
		 */
		public function commit() {
			if($this->mysqli) {
				return $this->db->commit();
			} else {
				//no transaction support in mysql module...
				mysql_query('COMMIT;', $db);
			}
			$this->stopTransaction();
		}
		
		private function stopTransaction() {
			if($this->mysqli) {
				return $this->db->autocommit(true);
			} else {
				//no transaction support in mysql module...
				mysql_query('SET AUTOCOMMIT = 1;', $db);
			}
		}
		
		public function dbg($dbg) {
			$this->debug = $dbg;
		}

		public function escape($str) {
			if($this->db && $this->connected) {
				if($this->mysqli) {
					return $this->db->real_escape_string($str);
				} else {
					return mysql_real_escape_string($str,$this->db);
				}
			}
		}

		function __destruct() {
			if ($this->db) {
				if($this->mysqli) {
					$this->db->close();
				}
			} else {
				mysql_close($this->db);
			}
		}
	}
}

if(! function_exists('printable')) {
	function printable($s) {
		$s = preg_replace("~([\x01-\x08\x0E-\x0F\x10-\x1F\x7F-\xFF])~",".", $s);
		$s = str_replace("\x00",'.',$s);
		if(x($_SERVER,'SERVER_NAME'))
			$s = escape_tags($s);
		return $s;
	}
}

// Procedural functions
if(! function_exists('dbg')) {
	function dbg($state) {
		global $db;
		if($db)
			$db->dbg($state);
	}
}

if(! function_exists('dbesc')) {
	function dbesc($str) {
		global $db;
		if($db && $db->connected)
			return($db->escape($str));
		else
			return(str_replace("'","\\'",$str));
	}
}



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
			$stmt = vsprintf($sql,$args);
			if($stmt === false)
				logger('dba: vsprintf error: ' . print_r(debug_backtrace(),true));
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

	}
}

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
	}
}


// Caller is responsible for ensuring that any integer arguments to
// dbesc_array are actually integers and not malformed strings containing
// SQL injection vectors. All integer array elements should be specifically
// cast to int to avoid trouble.


if(! function_exists('dbesc_array_cb')) {
	function dbesc_array_cb(&$item, $key) {
		if(is_string($item))
			$item = dbesc($item);
	}
}


if(! function_exists('dbesc_array')) {
	function dbesc_array(&$arr) {
		if(is_array($arr) && count($arr)) {
			array_walk($arr,'dbesc_array_cb');
		}
	}
}


