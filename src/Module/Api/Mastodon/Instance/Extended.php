<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Module\Api\Mastodon\Instance;

use DateTime;
use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Database\Database;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Object\Api\Mastodon\ExtendedDescription;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Undocumented API endpoint that is implemented by both Mastodon and Pleroma
 */
class Extended extends BaseApi
{
	/** @var IManageConfigValues */
	private $config;

	public function __construct(\Friendica\Factory\Api\Mastodon\Error $errorFactory, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, Database $database, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config = $config;
	}

	/**
	 * @throws HTTPException\InternalServerErrorException
	 */
	protected function rawContent(array $request = [])
	{
		$account = User::getSystemAccount();

		$this->jsonExit(new ExtendedDescription(new DateTime($account['updated']), $this->config->get('config', 'info')));
	}
}
