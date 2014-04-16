<?php
	/**
	 *  cache api
	 */

	class Cache {
		public static function get($key) {
			if (function_exists("apc_fetch") AND function_exists("apc_exists"))
				if (apc_exists($key))
					return(apc_fetch($key));

			$r = q("SELECT `v` FROM `cache` WHERE `k`='%s' limit 1",
				dbesc($key)
			);

			if (count($r)) {
				if (function_exists("apc_store"))
					apc_store($key, $r[0]['v'], 600);

				return $r[0]['v'];
			}
			return null;
		}

		public static function set($key,$value) {

			q("REPLACE INTO `cache` (`k`,`v`,`updated`) VALUES ('%s','%s','%s')",
					dbesc($key),
					dbesc($value),
					dbesc(datetime_convert()));

			if (function_exists("apc_store"))
				apc_store($key, $value, 600);

		}


/*
 *
 * Leaving this legacy code temporaily to see how REPLACE fares
 * as opposed to non-atomic checks when faced with fast moving key duplication.
 * As a MySQL extension it isn't portable, but we're not yet very portable.
 */

/*
 *			$r = q("SELECT * FROM `cache` WHERE `k`='%s' limit 1",
 *				dbesc($key)
 *			);
 *			if(count($r)) {
 *				q("UPDATE `cache` SET `v` = '%s', `updated = '%s' WHERE `k` = '%s'",
 *					dbesc($value),
 *					dbesc(datetime_convert()),
 *					dbesc($key));
 *			}
 *			else {
 *				q("INSERT INTO `cache` (`k`,`v`,`updated`) VALUES ('%s','%s','%s')",
 *					dbesc($key),
 *					dbesc($value),
 *					dbesc(datetime_convert()));
 *			}
 *		}
 */


		public static function clear(){
			q("DELETE FROM `cache` WHERE `updated` < '%s'",
				dbesc(datetime_convert('UTC','UTC',"now - 30 days")));
		}

	}

