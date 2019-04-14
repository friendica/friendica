<?php

use Friendica\Database\DBA;

/**
 * @brief execute SQL query with printf style args - deprecated
 *
 * Please use the DBA:: functions instead:
 * DBA::select, DBA::exists, DBA::insert
 * DBA::delete, DBA::update, DBA::p, DBA::e
 *
 * @param $sql
 * @return array|bool Query array
 * @throws Exception
 * @deprecated
 */
function q($sql) {
	$args = func_get_args();
	unset($args[0]);

	$dba = DBA::getDb();

	if (!$dba->connected) {
		return false;
	}

	$sql = $dba->cleanQuery($sql);
	$sql = $dba->anyValueFallback($sql);

	$stmt = @vsprintf($sql, $args);

	$ret = $dba->p($stmt);

	if (is_bool($ret)) {
		return $ret;
	}

	$columns = $dba->columnCount($ret);

	$data = $dba->toArray($ret);

	if ((count($data) == 0) && ($columns == 0)) {
		return true;
	}

	return $data;
}
