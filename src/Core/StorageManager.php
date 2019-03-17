<?php

namespace Friendica\Core;

use Friendica\Database\DBA;
use Friendica\Model\Storage\IStorage;


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
		if (count(self::$backends) == 0) {
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
	 * @return bool
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function setBackend($class)
	{
		if (!in_array('Friendica\Model\Storage\IStorage', class_implements($class))) {
			return false;
		}

		Config::set('storage', 'class', $class);

		// Start background storage move task
		Worker::add(PRIORITY_LOW, "CronJobs", "move_storage");

		return true;
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
	 * @brief Move up to 5000 resources to storage $dest
	 *
	 * Copy existing data to destination storage and delete from source.
	 * This method cannot move to legacy in-table `data` field.
	 *
	 * @param string     $destination Storage class name
	 * @param array|null $tables      Tables to look in for resources. Optional, defaults to ['photo', 'attach']
	 * @param int        $limit       Limit of the process batch size, defaults to 5000
	 * @return int Number of moved resources
	 * @throws \Exception
	 */
	public static function move($destination, $tables = null, $limit = 5000)
	{
		if (empty($destination)) {
			throw new \Exception('Can\'t move to NULL storage backend');
		}
		
		if (is_null($tables)) {
			$tables = ['photo', 'attach'];
		}

		$moved = 0;
		foreach ($tables as $table) {
			// Get the rows where backend class is not the destination backend class
			$resources = DBA::select(
				$table, 
				['id', 'data', 'backend-class', 'backend-ref'],
				['`backend-class` IS NULL or `backend-class` != ?', $destination],
				['limit' => $limit]
			);

			while ($resource = DBA::fetch($resources)) {
				$id = $resource['id'];
				$data = $resource['data'];
				/** @var IStorage $backendClass */
				$backendClass = $resource['backend-class'];
				$backendRef = $resource['backend-ref'];
				if (!empty($backendClass)) {
					Logger::log("get data from old backend " . $backendClass . " : " . $backendRef);
					$data = $backendClass::get($backendRef);
				}

				Logger::log("save data to new backend " . $destination);
				/** @var IStorage $destination */
				$ref = $destination::put($data);
				Logger::log("saved data as " . $ref);

				if ($ref !== '') {
					Logger::log("update row");
					if (DBA::update($table, ['backend-class' => $destination, 'backend-ref' => $ref, 'data' => ''], ['id' => $id])) {
						if (!empty($backendClass)) {
							Logger::log("delete data from old backend " . $backendClass . " : " . $backendRef);
							$backendClass::delete($backendRef);
						}
						$moved++;
					}
				}
			}

			DBA::close($resources);
		}

		return $moved;
	}
}

