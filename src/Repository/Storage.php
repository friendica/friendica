<?php

namespace Friendica\Repository;

use Exception;
use Friendica\BaseRepository;
use Friendica\Core\Config\IConfiguration;
use Friendica\Core\Hook;
use Friendica\Core\L10n\L10n;
use Friendica\Database\Database;
use Friendica\Model\Storage as S;
use Friendica\Network\HTTPException;
use Psr\Log\LoggerInterface;


/**
 * @brief Manage storage backends
 *
 * Core code uses this class to get and set current storage backend class.
 * Addons use this class to register and unregister additional backends.
 */
class Storage extends BaseRepository
{
	protected static $model_class = S\IStorage::class;

	// Default tables to look for data
	const TABLES = ['photo', 'attach'];

	// Default storage backends
	const DEFAULT_BACKENDS = [
		S\Filesystem::NAME => S\Filesystem::class,
		S\Database::NAME   => S\Database::class,
	];

	private $backends = [];

	/**
	 * @var S\IStorage[] A local cache for storage instances
	 */
	private $backendInstances = [];

	/** @var IConfiguration */
	private $config;
	/** @var L10n */
	private $l10n;

	/** @var S\IStorage */
	private $currentBackend;

	/**
	 * @param Database        $dba
	 * @param IConfiguration  $config
	 * @param LoggerInterface $logger
	 * @param L10n            $l10n
	 */
	public function __construct(Database $dba, IConfiguration $config, LoggerInterface $logger, L10n $l10n)
	{
		parent::__construct($dba, $logger);

		$this->config   = $config;
		$this->l10n     = $l10n;
		$this->backends = $config->get('storage', 'backends', self::DEFAULT_BACKENDS);

		$currentName = $this->config->get('storage', 'name', '');

		try {
			$this->currentBackend = $this->selectFirst(['name' => $currentName]);
		} catch (HTTPException\NotFoundException $e) {
			$this->currentBackend = null;
		}
	}

	/**
	 * @brief Return current storage backend class
	 *
	 * @return S\IStorage|null
	 */
	public function getBackend()
	{
		return $this->currentBackend;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array $condition The possible condition of the selection are:
	 *                         'name'        = Backend name (mandatory)
	 *                         'userBackend' = Just return instances in case it's a user backend (e.g. not
	 *                         SystemResource (Default is true))
	 *
	 * @return S\IStorage|null null if no backend registered at $name
	 *
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function selectFirst(array $conditions)
	{
		$name        = $conditions['name'] ?? null;
		$userBackend = $conditions['userBackend'] ?? true;

		// If there's no cached instance create a new instance
		if (!isset($this->backendInstances[$name])) {
			// If the current name isn't a valid backend (or the SystemResource instance) create it
			if ($this->isValidBackend($name, $userBackend)) {
				switch ($name) {
					// Try the filesystem backend
					case S\Filesystem::getName():
						$this->backendInstances[$name] = new S\Filesystem($this->config, $this->logger, $this->l10n);
						break;
					// try the database backend
					case S\Database::getName():
						$this->backendInstances[$name] = new S\Database($this->dba, $this->logger, $this->l10n);
						break;
					// at least, try if there's an addon for the backend
					case S\SystemResource::getName():
						$this->backendInstances[$name] = new S\SystemResource();
						break;
					default:
						$data = [
							'name'    => $name,
							'storage' => null,
						];
						Hook::callAll('storage_instance', $data);
						if (($data['storage'] ?? null) instanceof S\IStorage) {
							$this->backendInstances[$data['name'] ?? $name] = $data['storage'];
						} else {
							return null;
						}
						break;
				}
			} else {
				return null;
			}
		}

		return $this->backendInstances[$name];
	}

	/**
	 * Checks, if the storage is a valid backend
	 *
	 * @param string|null $name        The name or class of the backend
	 * @param boolean     $userBackend True, if just user backend should get returned (e.g. not SystemResource)
	 *
	 * @return boolean True, if the backend is a valid backend
	 */
	public function isValidBackend(string $name = null, bool $userBackend = true)
	{
		return array_key_exists($name, $this->backends) ||
		       (!$userBackend && $name === S\SystemResource::getName());
	}

	/**
	 * @brief Set current storage backend class
	 *
	 * @param string $name Backend class name
	 *
	 * @return boolean True, if the set was successful
	 *
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 */
	public function setBackend(string $name = null)
	{
		if (!$this->isValidBackend($name)) {
			return false;
		}

		if ($this->config->set('storage', 'name', $name)) {
			$this->currentBackend = $this->selectFirst(['name' => $name]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @brief Get registered backends
	 *
	 * @return array
	 */
	public function listBackends()
	{
		return $this->backends;
	}

	/**
	 * Register a storage backend class
	 *
	 * You have to register the hook "storage_instance" as well to make this class work!
	 *
	 * @param string $class Backend class name
	 *
	 * @return boolean True, if the registration was successful
	 */
	public function register(string $class)
	{
		if (is_subclass_of($class, S\IStorage::class)) {
			/** @var S\IStorage $class */

			$backends                    = $this->backends;
			$backends[$class::getName()] = $class;

			if ($this->config->set('storage', 'backends', $backends)) {
				$this->backends = $backends;
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @brief Unregister a storage backend class
	 *
	 * @param string $class Backend class name
	 *
	 * @return boolean True, if unregistering was successful
	 */
	public function unregister(string $class)
	{
		if (is_subclass_of($class, S\IStorage::class)) {
			/** @var S\IStorage $class */

			unset($this->backends[$class::getName()]);

			if ($this->currentBackend instanceof $class) {
				$this->config->set('storage', 'name', null);
				$this->currentBackend = null;
			}

			return $this->config->set('storage', 'backends', $this->backends);
		} else {
			return false;
		}
	}

	/**
	 * @brief Move up to 5000 resources to storage $dest
	 *
	 * Copy existing data to destination storage and delete from source.
	 * This method cannot move to legacy in-table `data` field.
	 *
	 * @param S\IStorage $destination Destination storage class name
	 * @param array      $tables      Tables to look in for resources. Optional, defaults to ['photo', 'attach']
	 * @param int        $limit       Limit of the process batch size, defaults to 5000
	 *
	 * @return int Number of moved resources
	 * @throws S\StorageException
	 * @throws Exception
	 */
	public function move(S\IStorage $destination, array $tables = self::TABLES, int $limit = 5000)
	{
		if ($destination === null) {
			throw new S\StorageException('Can\'t move to NULL storage backend');
		}

		$moved = 0;
		foreach ($tables as $table) {
			// Get the rows where backend class is not the destination backend class
			$resources = $this->dba->select(
				$table,
				['id', 'data', 'backend-class', 'backend-ref'],
				['`backend-class` IS NULL or `backend-class` != ?', $destination::getName()],
				['limit' => $limit]
			);

			while ($resource = $this->dba->fetch($resources)) {
				$id        = $resource['id'];
				$data      = $resource['data'];
				$source    = $this->selectFirst(['name' => $resource['backend-class']]);
				$sourceRef = $resource['backend-ref'];

				if (!empty($source)) {
					$this->logger->info('Get data from old backend.', ['oldBackend' => $source, 'oldReference' => $sourceRef]);
					$data = $source->get($sourceRef);
				}

				$this->logger->info('Save data to new backend.', ['newBackend' => $destination]);
				$destinationRef = $destination->put($data);
				$this->logger->info('Saved data.', ['newReference' => $destinationRef]);

				if ($destinationRef !== '') {
					$this->logger->info('update row');
					if ($this->dba->update($table, ['backend-class' => $destination, 'backend-ref' => $destinationRef, 'data' => ''], ['id' => $id])) {
						if (!empty($source)) {
							$this->logger->info('Delete data from old backend.', ['oldBackend' => $source, 'oldReference' => $sourceRef]);
							$source->delete($sourceRef);
						}
						$moved++;
					}
				}
			}

			$this->dba->close($resources);
		}

		return $moved;
	}
}
