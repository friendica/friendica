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

namespace Friendica\Module\Api\Mastodon;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\Factory\Api\Mastodon\Account as AccountFactory;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Object\Api\Mastodon\Instance as InstanceEntity;
use Friendica\Object\Api\Mastodon\InstanceV2 as InstanceV2Entity;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/api/rest/instances/
 */
class Instance extends BaseApi
{
	/** @var Database */
	private $database;

	/** @var IManageConfigValues */
	private $config;

	/** @var AccountFactory */
	private $accountFactory;

	public function __construct(AccountFactory $accountFactory, \Friendica\Factory\Api\Mastodon\Error $errorFactory, App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, ApiResponse $response, Database $database, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($errorFactory, $app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
		$this->config = $config;
		$this->accountFactory = $accountFactory;
	}

	/**
	 * @param array $request
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	protected function rawContent(array $request = [])
	{
		$administrator = User::getFirstAdmin(['nickname']);
		if ($administrator) {
			$adminContact = $this->database->selectFirst('contact', ['uri-id'], ['nick' => $administrator['nickname'], 'self' => true]);
			$contact_account = $this->accountFactory->createFromUriId($adminContact['uri-id']);
		}

		$this->jsonExit(new InstanceEntity($this->config, $this->baseUrl, $this->database, $this->buildConfigurationInfo(), $contact_account ?? null, System::getRules()));
	}

	private function buildConfigurationInfo(): InstanceV2Entity\Configuration
	{
		$statuses_config = new InstanceV2Entity\StatusesConfig((int)$this->config->get(
			'config',
			'api_import_size',
			$this->config->get('config', 'max_import_size')
		), 99, 23);

		$image_size_limit = Strings::getBytesFromShorthand($this->config->get('system', 'maximagesize'));
		$max_image_length = $this->config->get('system', 'max_image_length');
		if ($max_image_length > 0) {
			$image_matrix_limit = pow($max_image_length, 2);
		} else {
			$image_matrix_limit = 33177600; // 5760^2
		}

		return new InstanceV2Entity\Configuration(
			$statuses_config,
			new InstanceV2Entity\MediaAttachmentsConfig(Images::supportedMimeTypes(), $image_size_limit, $image_matrix_limit),
			new InstanceV2Entity\Polls(),
			new InstanceV2Entity\Accounts(),
		);
	}
}
