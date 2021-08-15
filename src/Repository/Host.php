<?php

namespace Friendica\Repository;

use Exception;
use Friendica\BaseRepository;
use Friendica\Model;
use Friendica\Collection;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;

class Host extends BaseRepository
{
	/**
	 * Defines the environment variable, which includes the current node name instead of the detected hostname
	 *
	 * @var string
	 *
	 * @notice This is used for cluster environments, where the each node defines it own hostname.
	 */
	const ENV_VARIABLE = 'NODE_NAME';

	protected static $table_name = 'host';

	protected static $model_class = Model\Host::class;

	protected static $collection_class = Collection\Hosts::class;

	/**
	 * @param array $data
	 * @return Model\Host
	 */
	protected function create(array $data): Model\Host
	{
		return new Model\Host($this->dba, $this->logger, $data);
	}

	/**
	 * @param array $server The $_SERVER variable
	 *
	 * @return Model\Host
	 * @throws InternalServerErrorException
	 * @throws Exception
	 */
	public function selectCurrentHost(array $server = []): Model\Host
	{
		$hostname = $server[self::ENV_VARIABLE] ?? null;

		if (empty($hostname)) {
			$hostname = gethostname();
		}

		// Trim whitespaces first to avoid getting an empty hostname
		// For linux the hostname is read from file /proc/sys/kernel/hostname directly
		$hostname = trim($hostname);
		if (empty($hostname)) {
			throw new InternalServerErrorException('Empty hostname is invalid.');
		}

		$hostname = strtolower($hostname);

		$data = $this->dba->selectFirst(self::$table_name, ['id', 'name'], ['name' => $hostname]);
		if (!empty($data['id'])) {
			return $this->create($data);
		} else {
			$this->dba->replace(self::$table_name, ['name' => $hostname]);

			return $this->selectFirst(['name' => $hostname]);
		}
	}

	/**
	 * @param array $condition
	 * @return Model\Host
	 * @throws NotFoundException
	 */
	public function selectFirst(array $condition): Model\Host
	{
		return parent::selectFirst($condition);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @return Collection\Hosts
	 * @throws Exception
	 */
	public function select(array $condition = [], array $params = []): Collection\Hosts
	{
		return parent::select($condition, $params);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 * @param int|null $max_id
	 * @param int|null $since_id
	 * @param int $limit
	 * @return Collection\Hosts
	 * @throws Exception
	 */
	public function selectByBoundaries(array $condition = [], array $params = [], int $max_id = null, int $since_id = null, int $limit = self::LIMIT): Collection\Hosts
	{
		return parent::selectByBoundaries($condition, $params, $max_id, $since_id, $limit);
	}
}
