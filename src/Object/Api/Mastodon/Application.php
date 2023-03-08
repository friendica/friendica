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

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class Application
 *
 * @see https://docs.joinmastodon.org/entities/application
 */
class Application extends BaseDataTransferObject
{
	/** @var string */
	protected $client_id;
	/** @var string */
	protected $client_secret;
	/** @var string */
	protected $id;
	/** @var string */
	protected $name;
	/** @var string */
	protected $redirect_uri;
	/** @var string */
	protected $website;

	/**
	 * Creates an application entry
	 *
	 * @param array   $item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(string $name, string $client_id = null, string $client_secret = null, int $id = null, string $redirect_uri = null, string $website = null)
	{
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret;
		$this->id            = (string)$id;
		$this->name          = $name;
		$this->redirect_uri  = $redirect_uri;
		$this->website       = $website;
	}

	/**
	 * Returns the current entity as an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$application = parent::toArray();

		if (empty($application['id'])) {
			unset($application['client_id']);
			unset($application['client_secret']);
			unset($application['id']);
			unset($application['redirect_uri']);
		}

		if (empty($application['website'])) {
			unset($application['website']);
		}

		return $application;
	}
}
