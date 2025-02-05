<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Storage\Repository;

use Exception;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Storage\Exception\InvalidClassStorageException;
use Friendica\Core\Storage\Exception\ReferenceStorageException;
use Friendica\Core\Storage\Exception\StorageException;
use Friendica\Core\Storage\Capability\ICanReadFromStorage;
use Friendica\Core\Storage\Capability\ICanConfigureStorage;
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
use Friendica\Database\Database;
use Friendica\Core\Storage\Type;
use Friendica\DI;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Log\LoggerInterface;

/**
 * Manage storage backends
 *
 * Core code uses this class to get and set current storage backend class.
 * Addons use this class to register and unregister additional backends.
 */
class StorageManager
{
	// Default tables to look for data
	const TABLES = ['photo', 'attach'];

	// Default storage backends
	/** @var string[]  */
	const DEFAULT_BACKENDS = [
		Type\Filesystem::NAME,
		Type\Database::NAME,
	];

	/** @var string[] List of valid backend classes */
	private $validBackends;

	/**
	 * @var ICanReadFromStorage[] A local cache for storage instances
	 */
	private $backendInstances = [];

	/** @var Database */
	private $dba;
	/** @var IManageConfigValues */
	private $config;
	/** @var LoggerInterface */
	private $logger;
	/** @var L10n */
	private $l10n;

	/** @var ICanWriteToStorage */
	private $currentBackend;

	/**
	 * @param Database            $dba
	 * @param IManageConfigValues $config
	 * @param LoggerInterface     $logger
	 * @param L10n                $l10n
	 * @param bool                $includeAddon (DEVELOP ONLY) Used for testing only - avoids loading addons because of DB direct access
	 *
	 * @throws InvalidClassStorageException in case the active backend class is invalid
	 * @throws StorageException in case of unexpected errors during the active backend class loading
	 */
	public function __construct(Database $dba, IManageConfigValues $config, LoggerInterface $logger, L10n $l10n, bool $includeAddon = true)
	{
		$this->dba           = $dba;
		$this->config        = $config;
		$this->logger        = $logger;
		$this->l10n          = $l10n;
		$this->validBackends = $config->get('storage', 'backends', self::DEFAULT_BACKENDS);

		$currentName = $this->config->get('storage', 'name');

		/// @fixme Loading the addons & hooks here is really bad practice, but solves https://github.com/friendica/friendica/issues/11178
		/// clean solution = Making Addon & Hook dynamic and load them inside the constructor, so there's no custom load logic necessary anymore
		if ($includeAddon) {
			DI::addonHelper()->loadAddons();
			Hook::loadHooks();
		}

		// you can only use user backends as a "default" backend, so the second parameter is true
		$this->currentBackend = $this->getWritableStorageByName($currentName);
	}

	/**
	 * Return current storage backend class
	 *
	 * @return ICanWriteToStorage
	 */
	public function getBackend(): ICanWriteToStorage
	{
		return $this->currentBackend;
	}

	/**
	 * Returns a writable storage backend class by registered name
	 *
	 * @param string $name Backend name
	 *
	 * @return ICanWriteToStorage
	 *
	 * @throws InvalidClassStorageException in case there's no backend class for the name
	 * @throws StorageException in case of an unexpected failure during the hook call
	 */
	public function getWritableStorageByName(string $name): ICanWriteToStorage
	{
		$storage = $this->getByName($name, $this->validBackends);
		if (!$storage instanceof ICanWriteToStorage) {
			throw new InvalidClassStorageException(sprintf('Backend %s is not writable', $name));
		}

		return $storage;
	}

	/**
	 * Return storage backend configuration by registered name
	 *
	 * @param string     $name Backend name
	 *
	 * @return ICanConfigureStorage|false
	 *
	 * @throws InvalidClassStorageException in case there's no backend class for the name
	 * @throws StorageException in case of an unexpected failure during the hook call
	 */
	public function getConfigurationByName(string $name)
	{
		switch ($name) {
			// Try the filesystem backend
			case Type\Filesystem::getName():
				return new Type\FilesystemConfig($this->config, $this->l10n);
				// try the database backend
			case Type\Database::getName():
				return false;
			default:
				$data = [
					'name'           => $name,
					'storage_config' => null,
				];
				try {
					Hook::callAll('storage_config', $data);
					if (!($data['storage_config'] ?? null) instanceof ICanConfigureStorage) {
						throw new InvalidClassStorageException(sprintf('Configuration for backend %s was not found', $name));
					}

					return $data['storage_config'];
				} catch (InternalServerErrorException $exception) {
					throw new StorageException(sprintf('Failed calling hook::storage_config for backend %s', $name), $exception->__toString());
				}
		}
	}

	/**
	 * Return storage backend class by registered name
	 *
	 * @param string     $name Backend name
	 * @param string[]|null $validBackends possible, manual override of the valid backends
	 *
	 * @return ICanReadFromStorage
	 *
	 * @throws InvalidClassStorageException in case there's no backend class for the name
	 * @throws StorageException in case of an unexpected failure during the hook call
	 */
	public function getByName(string $name, array $validBackends = null): ICanReadFromStorage
	{
		// If there's no cached instance create a new instance
		if (!isset($this->backendInstances[$name])) {
			// If the current name isn't a valid backend (or the SystemResource instance) create it
			if (!$this->isValidBackend($name, $validBackends)) {
				throw new InvalidClassStorageException(sprintf('Backend %s is not valid', $name));
			}

			switch ($name) {
				// Try the filesystem backend
				case Type\Filesystem::getName():
					$storageConfig                 = new Type\FilesystemConfig($this->config, $this->l10n);
					$this->backendInstances[$name] = new Type\Filesystem($storageConfig->getStoragePath());
					break;
					// try the database backend
				case Type\Database::getName():
					$this->backendInstances[$name] = new Type\Database($this->dba);
					break;
					// at least, try if there's an addon for the backend
				case Type\SystemResource::getName():
					$this->backendInstances[$name] = new Type\SystemResource();
					break;
				case Type\ExternalResource::getName():
					$this->backendInstances[$name] = new Type\ExternalResource($this->logger);
					break;
				default:
					$data = [
						'name'    => $name,
						'storage' => null,
					];
					try {
						Hook::callAll('storage_instance', $data);
						if (!($data['storage'] ?? null) instanceof ICanReadFromStorage) {
							throw new InvalidClassStorageException(sprintf('Backend %s was not found', $name));
						}

						$this->backendInstances[$data['name'] ?? $name] = $data['storage'];
					} catch (InternalServerErrorException $exception) {
						throw new StorageException(sprintf('Failed calling hook::storage_instance for backend %s', $name), $exception->__toString());
					}
					break;
			}
		}

		return $this->backendInstances[$name];
	}

	/**
	 * Checks, if the storage is a valid backend
	 *
	 * @param string|null   $name          The name or class of the backend
	 * @param string[]|null $validBackends Possible, valid backends to check
	 *
	 * @return boolean True, if the backend is a valid backend
	 */
	public function isValidBackend(string $name = null, array $validBackends = null): bool
	{
		$validBackends = $validBackends ?? array_merge(
			$this->validBackends,
			[
				Type\SystemResource::getName(),
				Type\ExternalResource::getName(),
			]
		);
		return in_array($name, $validBackends);
	}

	/**
	 * Set current storage backend class
	 *
	 * @param ICanWriteToStorage $storage The storage class
	 *
	 * @return boolean True, if the set was successful
	 */
	public function setBackend(ICanWriteToStorage $storage): bool
	{
		if ($this->config->set('storage', 'name', $storage::getName())) {
			$this->currentBackend = $storage;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get registered backends
	 *
	 * @return string[]
	 */
	public function listBackends(): array
	{
		return $this->validBackends;
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
	public function register(string $class): bool
	{
		if (!is_subclass_of($class, ICanReadFromStorage::class)) {
			return false;
		}

		/** @var class-string<ICanReadFromStorage> $class */
		if ($this->isValidBackend($class::getName(), $this->validBackends)) {
			return true;
		}

		$backends   = $this->validBackends;
		$backends[] = $class::getName();

		if ($this->config->set('storage', 'backends', $backends)) {
			$this->validBackends = $backends;
			return true;
		}

		return false;
	}

	/**
	 * Unregister a storage backend class
	 *
	 * @param string $class Backend class name
	 *
	 * @return boolean True, if unregistering was successful
	 *
	 * @throws StorageException
	 */
	public function unregister(string $class): bool
	{
		if (!is_subclass_of($class, ICanReadFromStorage::class)) {
			return false;
		}

		/** @var class-string<ICanReadFromStorage> $class */
		if ($this->currentBackend::getName() == $class::getName()) {
			throw new StorageException(sprintf('Cannot unregister %s, because it\'s currently active.', $class::getName()));
		}

		$key = array_search($class::getName(), $this->validBackends);

		if ($key === false) {
			return true;
		}

		$backends = $this->validBackends;
		unset($backends[$key]);
		$backends = array_values($backends);

		if ($this->config->set('storage', 'backends', $backends)) {
			$this->validBackends = $backends;
			return true;
		}

		return false;
	}

	/**
	 * Move up to 5000 resources to storage $dest
	 *
	 * Copy existing data to destination storage and delete from source.
	 * This method cannot move to legacy in-table `data` field.
	 *
	 * @param ICanWriteToStorage $destination Destination storage class name
	 * @param array              $tables      Tables to look in for resources. Optional, defaults to ['photo', 'attach']
	 * @param int                $limit       Limit of the process batch size, defaults to 5000
	 *
	 * @return int Number of moved resources
	 * @throws StorageException
	 * @throws Exception
	 */
	public function move(ICanWriteToStorage $destination, array $tables = self::TABLES, int $limit = 5000): int
	{
		if (!$this->isValidBackend($destination, $this->validBackends)) {
			throw new StorageException(sprintf("Can't move to storage backend '%s'", $destination::getName()));
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
				$sourceRef = $resource['backend-ref'];
				$source    = null;

				try {
					$source = $this->getWritableStorageByName($resource['backend-class'] ?? '');
					$this->logger->info('Get data from old backend.', ['oldBackend' => $source, 'oldReference' => $sourceRef]);
					$data = $source->get($sourceRef);
				} catch (InvalidClassStorageException $exception) {
					$this->logger->info('Get data from DB resource field.', ['oldReference' => $sourceRef]);
					$data = $resource['data'];
				} catch (ReferenceStorageException $exception) {
					$this->logger->info('Invalid source reference.', ['oldBackend' => $source, 'oldReference' => $sourceRef]);
					continue;
				}

				$this->logger->info('Save data to new backend.', ['newBackend' => $destination::getName()]);
				$destinationRef = $destination->put($data);
				$this->logger->info('Saved data.', ['newReference' => $destinationRef]);

				if ($destinationRef !== '') {
					$this->logger->info('update row');
					if ($this->dba->update($table, ['backend-class' => $destination::getName(), 'backend-ref' => $destinationRef, 'data' => ''], ['id' => $id])) {
						if (!empty($source)) {
							$this->logger->info('Deleted data from old backend.', ['oldBackend' => $source, 'oldReference' => $sourceRef]);
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
