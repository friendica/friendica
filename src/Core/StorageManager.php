<?php

namespace Friendica\Core;

use Friendica\Database\DBA;
use Friendica\Core\Config;
use Friendica\Core\Logger;



/**
 * @brief Manage storage backends
 *
 * Core code uses this class to get and set current storage backend class.
 * Addons use this class to register and unregister additional backends.
 */
class StorageManager
{
	private static $default_backends = [
		'Filesystem' => \Friendica\Model\Storage\Filesystem::class,
		'Database' => \Friendica\Model\Storage\Database::class,
	];

	private static $backends = [];

	private static function setup()
	{
		if (count(self::$backends)==0) {
			self::$backends = Config::get('storage', 'backends', self::$default_backends);
		}
	}

	/**
	 * @brief Return current storage backend class
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getBackend()
	{
		return Config::get('storage', 'class', '');
	}

	/**
	 * @brief Return storage backend class by registered name
	 *
	 * @param string  $name  Backend name
	 * @return string Empty if no backend registered at $name exists
	 */
	public static function getByName($name)
	{
		self::setup();
		return defaults(self::$backends, $name, '');
	}

	/**
	 * @brief Set current storage backend class
	 *
	 * @param string $class Backend class name
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function setBackend($class)
	{
		/// @todo Check that $class implements IStorage
		Config::set('storage', 'class', $class);
	}

	/**
	 * @brief Get registered backends
	 *
	 * @return array
	 */
	public static function listBackends()
	{
		self::setup();
		return self::$backends;
	}


	/**
	 * @brief Register a storage backend class
	 *
	 * @param string $name  User readable backend name
	 * @param string $class Backend class name
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function register($name, $class)
	{
		/// @todo Check that $class implements IStorage
		self::setup();
		self::$backends[$name] = $class;
		Config::set('storage', 'backends', self::$backends);
	}


	/**
	 * @brief Unregister a storage backend class
	 *
	 * @param string $name User readable backend name
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function unregister($name)
	{
		self::setup();
		unset(self::$backends[$name]);
		Config::set('storage', 'backends', self::$backends);
	}


	/**
	 * @brief Move resources to storage $dest
	 *
	 * Copy existing data to destination storage and delete from source.
	 * This method cannot move to legacy in-table `data` field.
	 *
	 * @param string  $dest    Destination storage class name
	 * @param array   $tables  Tables to look in for resources. Optional, defaults to ['photo', 'attach']
	 *
	 * @throws \Exception
	 * @return int Number of moved resources
	 */
	public static function move($dest, $tables = null)
	{
		if (is_null($dest) || empty($dest)) {
			throw Exception('Can\'t move to NULL storage backend');
		}
		
		if (is_null($tables)) {
			$tables = ['photo', 'attach'];
		}

		$moved = 0;
		foreach ($tables as $table) {
			// Get the rows where backend class is not the destination backend class
			$rr = DBA::select(
				$table, 
				['id', 'data', 'backend-class', 'backend-ref'],
				['`backend-class` IS NULL or `backend-class` != ?' , $dest ]
			);

			if (DBA::isResult($rr)) {
				while($r = DBA::fetch($rr)) {
					$id = $r['id'];
					$data = $r['data'];
					$backendClass = $r['backend-class'];
					$backendRef = $r['backend-ref'];
					if (!is_null($backendClass) && $backendClass !== '') {
						Logger::log("get data from old backend " .  $backendClass . " : " . $backendRef);
						$data = $backendClass::get($backendRef);
					}
					
					Logger::log("save data to new backend " . $dest);
					$ref = $dest::put($data);
					Logger::log("saved data as " . $ref);

					if ($ref !== '') {
						Logger::log("update row");
						$ru = DBA::update($table, ['backend-class' => $dest, 'backend-ref' => $ref, 'data' => ''], ['id' => $id]);
						
						if ($ru) {
							if (!is_null($backendClass) && $backendClass !== '') {
								Logger::log("delete data from old backend " . $backendClass . " : " . $backendRef);
								$backendClass::delete($backendRef);
							}
							$moved++;
						}
					}
				}
			}
		}

		return $moved;
	}
}

