<?php
/**
 * @file src/Model/Storage/Filesystem.php
 * @brief Storage backend system
 */

namespace Friendica\Model\Storage;

use Friendica\Core\Logger;
use Friendica\Core\L10n;
use Friendica\Database\DBA;

/**
 * @brief Database based storage system
 *
 * This class manage data stored in database table.
 */
class Database implements IStorage
{
	public static function get($ref)
	{
		$r = DBA::selectFirst('storage', ['data'], ['id' => $ref]);
		if (!DBA::isResult($r)) {
			return '';
		}

		return $r['data'];
	}

	public static function put($data, $ref = '')
	{
		if ($ref !== '') {
			$r = DBA::update('storage', ['data' => $data], ['id' => $ref]);
			if ($r === false) {
				Logger::log('Failed to update data with id ' . $ref . ': ' . DBA::errorNo() . ' : ' . DBA::errorMessage());
				throw new StorageException(L10n::t('Database storage failed to update %s', $ref));
 			}
			return $ref;
		} else {
			$r = DBA::insert('storage', ['data' => $data]);
			if ($r === false) {
				Logger::log('Failed to insert data: ' . DBA::errorNo() . ' : ' . DBA::errorMessage());
				throw new StorageException(L10n::t('Database storage failed to insert data'));
			}
			return DBA::lastInsertId();
		}
	}

	public static function delete($ref)
	{
		return DBA::delete('storage', ['id' => $ref]);
	}
	
	public static function getOptions() { return []; }
	
	public static function saveOptions($data) { return []; }
}
