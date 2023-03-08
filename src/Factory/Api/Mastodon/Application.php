<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\UnprocessableEntityException;
use Psr\Log\LoggerInterface;

class Application extends BaseFactory
{
	/** @var Database */
	private $dba;

	public function __construct(LoggerInterface $logger, Database $dba)
	{
		parent::__construct($logger);
		$this->dba = $dba;
	}

	/**
	 * @param int $id Application ID
	 *
	 * @return \Friendica\Object\Api\Mastodon\Application
	 * @throws UnprocessableEntityException
	 */
	public function createFromApplicationId(int $id): \Friendica\Object\Api\Mastodon\Application
	{
		$application = $this->dba->selectFirst('application', ['client_id', 'client_secret', 'id', 'name', 'redirect_uri', 'website'], ['id' => $id]);
		if (!$this->dba->isResult($application)) {
			throw new UnprocessableEntityException(sprintf("ID '%s' not found", $id));
		}

		return new \Friendica\Object\Api\Mastodon\Application(
			$application['name'],
			$application['client_id'],
			$application['client_secret'],
			$application['id'],
			$application['redirect_uri'],
			$application['website']);
	}
}
