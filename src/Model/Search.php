<?php

namespace Friendica\Model;

use Friendica\Database\DBA;

/**
 * Model for DB specific logic for the search entity
 */
class Search
{
	/**
	 * Returns the list of user defined tags (e.g. #Friendica)
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public static function getUserTags()
	{
		$termsStmt = DBA::p("SELECT DISTINCT(`term`) FROM `search`");

		$tags = [];

		while ($term = DBA::fetch($termsStmt)) {
			$tags[] = trim($term['term'], '#');
		}

		return $tags;
	}
}
