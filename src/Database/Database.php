<?php

namespace Friendica\Database;

use Friendica\Core\Config\Cache\IConfigCache;
use Friendica\Core\System;
use Friendica\Database\Driver\DriverException;
use Friendica\Database\Driver\IDriver;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Database implements IDatabase, IDatabaseLock
{
	/**
	 * The profile the database calls
	 * @var Profiler
	 */
	private $profiler;

	/**
	 * The basic configuration cache
	 * @var IConfigCache
	 */
	private $configCache;

	/**
	 * The current driver of the database
	 * @var IDriver
	 */
	private $driver;

	/**
	 * The logger instance
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * True, if the database is currently in a transaction
	 * @var bool
	 */
	private $inTransaction;

	/**
	 * Saves the whole DB relation for performance reason
	 * @var array
	 */
	private $dbRelation;

	/**
	 * A possible driver exception of a current call
	 * @var DriverException
	 */
	private $currDriverException;

	/**
	 * The number of affected rows of a current call
	 * @var int
	 */
	private $currNumRows;

	public function __construct(IDriver $driver, IConfigCache $configCache, Profiler $profiler, LoggerInterface $logger)
	{
		$this->configCache = $configCache;
		$this->profiler    = $profiler;
		$this->driver      = $driver;
		$this->logger      = $logger;
		$this->driver->connect();
	}

	/**
	 * {@inheritDoc}
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDriver()
	{
		return $this->driver;
	}

	/**
	 * {@inheritdoc}
	 * @throws \Exception
	 */
	public function getDatabaseName()
	{
		$ret = $this->prepared("SELECT DATABASE() AS `db`");
		$data = $this->toArray($ret);
		return $data[0]['db'];
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAffectedRows()
	{
		return $this->currNumRows;
	}

	/**
	 * {@inheritDoc}
	 */
	public function close($stmt)
	{
		$stamp1 = microtime(true);

		if (!is_object($stmt)) {
			return false;
		}

		$ret = $this->driver->closeStatement($stmt);

		$this->profiler->saveTimestamp($stamp1, 'database', System::callstack());

		return $ret;
	}

	/**
	 * {@inheritDoc}
	 */
	public function transaction()
	{
		if (!$this->driver->performCommit() ||
			!$this->driver->startTransaction()) {
			return false;
		}

		$this->inTransaction = true;

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function exists($table, array $condition)
	{
		return DBA::exists($table, $condition);
	}

	public function count($table, array $condition = [])
	{
		return DBA::count($table, $condition);
	}

	public function fetch($stmt)
	{
		$stamp1 = microtime(true);

		if (!is_object($stmt)) {
			return false;
		}

		$columns = $this->driver->fetchRow($stmt);

		$this->profiler->saveTimestamp($stamp1, 'database', System::callstack());

		return $columns;
	}

	/**
	 * {@inheritDoc}
	 */
	public function commit()
	{
		if (!$this->driver->performCommit()) {
			return false;
		}

		$this->inTransaction = false;
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function rollback()
	{
		$ret = $this->driver->rollbackTransaction();

		$this->inTransaction = false;

		return $ret;
	}

	public function insert($table, array $param, $on_duplicate_update = false)
	{
		return DBA::insert($table, $param, $on_duplicate_update);
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete($table, array $conditions, $cascade = true, array &$callstack = [])
	{
		$driver = $this->driver;
		$logger = $this->logger;

		if (empty($table) || empty($conditions)) {
			$logger->info('Table and conditions have to be set');
			return false;
		}

		$commands = [];

		// Create a key for the loop prevention
		$key = $table . ':' . json_encode($conditions);

		// We quit when this key already exists in the callstack.
		if (isset($callstack[$key])) {
			return $commands;
		}

		$callstack[$key] = true;

		$table = $driver->escape($table);

		$commands[$key] = ['table' => $table, 'conditions' => $conditions];

		// To speed up the whole process we cache the table relations
		if ($cascade && count($this->dbRelation) == 0) {
			$this->buildRelationData();
		}

		// Is there a relation entry for the table?
		if ($cascade && isset($this->dbRelation[$table])) {
			// We only allow a simple "one field" relation.
			$field = array_keys($this->dbRelation[$table])[0];
			$rel_def = array_values($this->dbRelation[$table])[0];

			// Create a key for preventing double queries
			$qkey = $field . '-' . $table . ':' . json_encode($conditions);

			// When the search field is the relation field, we don't need to fetch the rows
			// This is useful when the leading record is already deleted in the frontend but the rest is done in the backend
			if ((count($conditions) == 1) && ($field == array_keys($conditions)[0])) {
				foreach ($rel_def AS $rel_table => $rel_fields) {
					foreach ($rel_fields AS $rel_field) {
						$this->delete($rel_table, [$rel_field => array_values($conditions)[0]], $cascade, $callstack);
					}
				}
				// We quit when this key already exists in the callstack.
			} elseif (!isset($callstack[$qkey])) {
				$callstack[$qkey] = true;

				// Fetch all rows that are to be deleted
				$data = self::select($table, [$field], $conditions);

				while ($row = self::fetch($data)) {
					$this->delete($table, [$field => $row[$field]], $cascade, $callstack);
				}

				$this->close($data);

				// Since we had split the delete command we don't need the original command anymore
				unset($commands[$key]);
			}
		}

		// Now we finalize the process
		$do_transaction = !$this->inTransaction;

		if ($do_transaction) {
			$this->transaction();
		}

		$compacted = [];
		$counter = [];

		foreach ($commands AS $command) {
			$conditions = $command['conditions'];
			reset($conditions);
			$first_key = key($conditions);

			$condition_string = self::buildCondition($conditions);

			if ((count($command['conditions']) > 1) || is_int($first_key)) {
				$sql = "DELETE FROM `" . $command['table'] . "`" . $condition_string;
				$logger->debug($driver->replaceParameters($sql, $conditions));

				if (!$this->execute($sql, $conditions)) {
					if ($do_transaction) {
						$this->rollback();
					}
					return false;
				}
			} else {
				$key_table = $command['table'];
				$key_condition = array_keys($command['conditions'])[0];
				$value = array_values($command['conditions'])[0];

				// Split the SQL queries in chunks of 100 values
				// We do the $i stuff here to make the code better readable
				$i = isset($counter[$key_table][$key_condition]) ? $counter[$key_table][$key_condition] : 0;
				if (isset($compacted[$key_table][$key_condition][$i]) && count($compacted[$key_table][$key_condition][$i]) > 100) {
					++$i;
				}

				$compacted[$key_table][$key_condition][$i][$value] = $value;
				$counter[$key_table][$key_condition] = $i;
			}
		}
		foreach ($compacted AS $table => $values) {
			foreach ($values AS $field => $field_value_list) {
				foreach ($field_value_list AS $field_values) {
					$sql = "DELETE FROM `" . $table . "` WHERE `" . $field . "` IN (" .
						substr(str_repeat("?, ", count($field_values)), 0, -2) . ");";

					$logger->debug($driver->replaceParameters($sql, $field_values));

					if (!$this->execute($sql, $field_values)) {
						if ($do_transaction) {
							$this->rollback();
						}
						return false;
					}
				}
			}
		}
		if ($do_transaction) {
			$this->commit();
		}
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function update($table, array $fields, array $condition, $old_fields = [])
	{
		$driver = $this->driver;
		$logger = $this->logger;

		if (empty($table) || empty($fields) || empty($condition)) {
			$logger->info('Table, fields and condition have to be set');
			return false;
		}

		$table = $driver->escape($table);

		$condition_string = self::buildCondition($condition);

		if (is_bool($old_fields)) {
			$do_insert = $old_fields;

			$old_fields = $this->selectFirst($table, [], $condition);

			if (is_bool($old_fields)) {
				if ($do_insert) {
					$values = array_merge($condition, $fields);
					return $this->insert($table, $values, $do_insert);
				}
				$old_fields = [];
			}
		}

		$do_update = (count($old_fields) == 0);

		foreach ($old_fields AS $fieldname => $content) {
			if (isset($fields[$fieldname])) {
				if (($fields[$fieldname] == $content) && !is_null($content)) {
					unset($fields[$fieldname]);
				} else {
					$do_update = true;
				}
			}
		}

		if (!$do_update || (count($fields) == 0)) {
			return true;
		}

		$sql = "UPDATE `".$table."` SET `".
			implode("` = ?, `", array_keys($fields))."` = ?".$condition_string;

		$params1 = array_values($fields);
		$params2 = array_values($condition);
		$params = array_merge_recursive($params1, $params2);

		return $this->execute($sql, $params);
	}

	/**
	 * {@inheritDoc}
	 */
	public function select($table, array $fields = [], array $condition = [], array $params = [])
	{
		if ($table == '') {
			return false;
		}

		$table = $this->driver->escape($table);

		if (count($fields) > 0) {
			$select_fields = "`" . implode("`, `", array_values($fields)) . "`";
		} else {
			$select_fields = "*";
		}

		$condition_string = self::buildCondition($condition);

		$param_string = self::buildParameter($params);

		$sql = "SELECT " . $select_fields . " FROM `" . $table . "`" . $condition_string . $param_string;

		$result = $this->prepared($sql, $condition);

		return $result;
	}

	/**
	 * {@inheritDoc}
	 */
	public function selectFirst($table, array $fields = [], array $condition = [], array $params = [])
	{
		$params['limit'] = 1;
		$result = $this->select($table, $fields, $condition, $params);

		if (is_bool($result)) {
			return $result;
		} else {
			$row = $this->fetch($result);
			$this->close($result);
			return $row;
		}
	}

	public function lock($table)
	{
		return DBA::lock($table);
	}

	public function unlock()
	{
		return DBA::unlock();
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param bool $retried if true, this is a retry of the current call
	 */
	public function prepared($sql, array $params = [], $retried = false)
	{
		$logger   = $this->logger;
		$profiler = $this->profiler;
		$config   = $this->configCache;
		$driver   = $this->driver;

		$stamp1 = microtime(true);

		// Renumber the array keys to be sure that they fit
		$i = 0;
		$args = [];
		foreach ($params AS $param) {
			// Avoid problems with some MySQL servers and boolean values. See issue #3645
			if (is_bool($param)) {
				$param = (int)$param;
			}
			$args[++$i] = $param;
		}

		if (!$driver->isConnected()) {
			return false;
		}

		if ((substr_count($sql, '?') != count($args)) && (count($args) > 0)) {
			// Question: Should we continue or stop the query here?
			$logger->warning('Query parameters mismatch.', ['query' => $sql, 'args' => $args, 'callstack' => System::callstack()]);
		}

		$sql = Util::cleanQuery($sql);

		if ($config->get('system', 'db_callstack') !== null) {
			$sql = "/*".System::callstack()." */ ".$sql;
		}

		$this->currDriverException = null;
		$this->currNumRows = 0;

		// We have to make some things different if this function is called from "e"
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

		if (isset($trace[1])) {
			$called_from = $trace[1];
		} else {
			// We use just something that is defined to avoid warnings
			$called_from = $trace[0];
		}
		// We are having an own error logging in the function "e"
		$called_from_e = ($called_from['function'] == 'e');

		try {

			$retval = $driver->executePrepared($sql, $args);
			$this->currNumRows = $driver->getNumRows($retval);

		} catch (DriverException $exception) {
			// We are having an own error logging in the function "e"
			if (($exception->getCode() != 0) && !$called_from_e) {

				// We have to preserve the error code, somewhere in the logging it get lost
				$this->currDriverException = $exception;

				$this->logger->error('DB Error', [
					'code'      => $exception->getCode(),
					'error'     => $exception->getMessage(),
					'callstack' => System::callstack(8),
					'param    ' => $driver->replaceParameters($sql, $args),
				]);

				// On a lost connection we try to reconnect - but only once.
				if ($exception->getCode() == 2006) {
					if ($retried || !$driver->reconnect()) {
						// It doesn't make sense to continue when the database connection was lost
						if ($retried) {
							$logger->notice('Giving up retrial because of database error',
							[
								'code'  => $exception->getCode(),
								'error' => $exception->getMessage(),
							]);
						} else {
							$logger->notice("Couldn't reconnect after database error",
							[
								'code'  => $exception->getCode(),
								'error' => $exception->getMessage(),
							]);
						}

						exit(1);
					} else {
						// We try it again
						$logger->notice('Reconnected after database error',
							[
								'code'  => $exception->getCode(),
								'error' => $exception->getMessage(),
							]);
						return $this->prepared($sql, $args, true);
					}
				}
			}
		}

		$profiler->saveTimestamp($stamp1, 'database', System::callstack());

		if ($config->get('system', 'db_log')) {
			$stamp2 = microtime(true);
			$duration = (float)($stamp2 - $stamp1);

			if (($duration > $config->get('system', 'db_loglimit'))) {
				$duration = round($duration, 3);
				$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				@file_put_contents($config->get('system', 'db_log'), DateTimeFormat::utcNow()  . $duration . "\t" .
					basename($backtrace[1]["file"])."\t" .
					$backtrace[1]["line"]."\t".$backtrace[2]["function"]."\t" .
					substr($driver->replaceParameters($sql, $args), 0, 2000)."\n", FILE_APPEND);
			}
		}

		return $retval;
	}

	/**
	 * {@inheritDoc}
	 */
	public function execute($sql, array $params = [])
	{
		$profiler = $this->profiler;
		$logger   = $this->logger;

		$stamp = microtime(true);

		// In a case of a deadlock we are repeating the query 20 times
		$timeout = 20;
		$errorno = 0;
		$retval  = false;

		do {
				$stmt = $this->prepared($sql, $params);

				if (is_bool($stmt)) {
					$retval = $stmt;
				} elseif (is_object($stmt)) {
					$retval = true;
				} else {
					$retval = false;
				}

				$this->close($stmt);

				$errorno = isset($this->currDriverException) ? $this->currDriverException->getCode() : 0;

		} while (($errorno == 1213) && (--$timeout > 0));

		if ($errorno != 0) {
			// We have to preserve the error code, somewhere in the logging it get lost
			$exception = $this->currDriverException;

			$this->logger->error('DB Error', [
				'code'      => $exception->getCode(),
				'error'     => $exception->getMessage(),
				'callstack' => System::callstack(8),
				'param    ' => $this->driver->replaceParameters($sql, $params),
			]);

			// On a lost connection we simply quit.
			// A reconnect like in $this->prepared() could be dangerous with modifications
			if ($errorno == 2006) {
				$logger->notice('Giving up retrial because of database error',
					[
						'code'  => $exception->getCode(),
						'error' => $exception->getMessage(),
					]);
				exit(1);
			}
		}

		$profiler->saveTimestamp($stamp, "database_write", System::callstack());

		return $retval;
	}

	/**
	 * @brief Returns the SQL condition string built from the provided condition array
	 *
	 * This function operates with two modes.
	 * - Supplied with a filed/value associative array, it builds simple strict
	 *   equality conditions linked by AND.
	 * - Supplied with a flat list, the first element is the condition string and
	 *   the following arguments are the values to be interpolated
	 *
	 * $condition = ["uid" => 1, "network" => 'dspr'];
	 * or:
	 * $condition = ["`uid` = ? AND `network` IN (?, ?)", 1, 'dfrn', 'dspr'];
	 *
	 * In either case, the provided array is left with the parameters only
	 *
	 * @param array $condition
	 * @return string
	 */
	public static function buildCondition(array &$condition = [])
	{
		$condition_string = '';
		if (count($condition) > 0) {
			reset($condition);
			$first_key = key($condition);
			if (is_int($first_key)) {
				$condition_string = " WHERE (" . array_shift($condition) . ")";
			} else {
				$new_values = [];
				$condition_string = "";
				foreach ($condition as $field => $value) {
					if ($condition_string != "") {
						$condition_string .= " AND ";
					}
					if (is_array($value)) {
						/* Workaround for MySQL Bug #64791.
						 * Never mix data types inside any IN() condition.
						 * In case of mixed types, cast all as string.
						 * Logic needs to be consistent with DBA::p() data types.
						 */
						$is_int = false;
						$is_alpha = false;
						foreach ($value as $single_value) {
							if (is_int($single_value)) {
								$is_int = true;
							} else {
								$is_alpha = true;
							}
						}

						if ($is_int && $is_alpha) {
							foreach ($value as &$ref) {
								if (is_int($ref)) {
									$ref = (string)$ref;
								}
							}
							unset($ref); //Prevent accidental re-use.
						}

						$new_values = array_merge($new_values, array_values($value));
						$placeholders = substr(str_repeat("?, ", count($value)), 0, -2);
						$condition_string .= "`" . $field . "` IN (" . $placeholders . ")";
					} else {
						$new_values[$field] = $value;
						$condition_string .= "`" . $field . "` = ?";
					}
				}
				$condition_string = " WHERE (" . $condition_string . ")";
				$condition = $new_values;
			}
		}

		return $condition_string;
	}

	/**
	 * Build the array with the table relations
	 *
	 * The array is build from the database definitions in DBStructure.php
	 *
	 * This process must only be started once, since the value is cached.
	 */
	private function buildRelationData()
	{
		if ($this->exists('config', ['cat' => 'system', 'k' => 'basepath'])) {
			$basePath = $this->select('config', 'v', ['cat' => 'system', 'k' => 'basepath']);
		} else {
			$basePath = $this->configCache->get('system', 'basepath');
		}

		$definition = DBStructure::definition($basePath);

		foreach ($definition AS $table => $structure) {
			foreach ($structure['fields'] AS $field => $field_struct) {
				if (isset($field_struct['relation'])) {
					foreach ($field_struct['relation'] AS $rel_table => $rel_field) {
						$this->dbRelation[$rel_table][$rel_field][$table][] = $field;
					}
				}
			}
		}
	}

	/**
	 * @brief Callback function for "esc_array"
	 *
	 * @param mixed   $value         Array value
	 * @param string  $key           Array key
	 * @param boolean $add_quotation add quotation marks for string values
	 * @return void
	 */
	private function escapeArrayCallback(&$value, $key, $add_quotation)
	{
		$conn = $this->driver;

		if (!$add_quotation) {
			if (is_bool($value)) {
				$value = ($value ? '1' : '0');
			} else {
				$value = $conn->escape($value);
			}
			return;
		}

		if (is_bool($value)) {
			$value = ($value ? 'true' : 'false');
		} elseif (is_float($value) || is_integer($value)) {
			$value = (string) $value;
		} else {
			$value = "'" . $conn->escape($value) . "'";
		}
	}
}
