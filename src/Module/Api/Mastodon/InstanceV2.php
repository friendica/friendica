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

namespace Friendica\Module\Api\Mastodon;

use Exception;
use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Api\ApiResponse;
use Friendica\Module\BaseApi;
use Friendica\Module\Register;
use Friendica\Object\Api\Mastodon\InstanceV2 as InstanceEntity;
use Friendica\Util\Images;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * @see https://docs.joinmastodon.org/methods/instance/
 */
class InstanceV2 extends BaseApi
{
	/** @var Database */
	private $database;

	/** @var IManageConfigValues */
	private $config;

	public function __construct(
		App $app,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		ApiResponse $response,
		Database $database,
		IManageConfigValues $config,
		array $server,
		array $parameters = []
	) {
		parent::__construct($app, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
		$this->config   = $config;
	}

	/**
	 * @param array $request
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 * @throws \ImagickException
	 * @throws Exception
	 */
	protected function rawContent(array $request = [])
	{
		$domain               = $this->baseUrl->getHost();
		$title                = $this->config->get('config', 'sitename');
		$version              = '2.8.0 (compatible; Friendica ' . App::VERSION . ')';
		$description          = $this->config->get('config', 'info');
		$usage                = $this->buildUsageInfo();
		$thumbnail            = new InstanceEntity\Thumbnail($this->baseUrl->withPath('images/friendica-banner.jpg'));
		$languages            = [$this->config->get('system', 'language')];
		$configuration        = $this->buildConfigurationInfo();
		$registration         = $this->buildRegistrationsInfo();
		$contact              = $this->buildContactInfo();
		$friendica_extensions = $this->buildFriendicaExtensionInfo();
		$rules                = System::getRules();
		System::jsonExit(new InstanceEntity(
			$domain,
			$title,
			$version,
			$description,
			$usage,
			$thumbnail,
			$languages,
			$configuration,
			$registration,
			$contact,
			$friendica_extensions,
			$rules
		));
	}

	private function buildConfigurationInfo(): InstanceEntity\Configuration
	{
		$statuses_config = new InstanceEntity\StatusesConfig((int)$this->config->get(
			'config',
			'api_import_size',
			$this->config->get('config', 'max_import_size')
		));

		return new InstanceEntity\Configuration(
			$statuses_config,
			new InstanceEntity\MediaAttachmentsConfig(Images::supportedTypes()),
			$this->config->get('system', 'maximagesize')
		);
	}

	private function buildContactInfo(): InstanceEntity\Contact
	{
		$email         = implode(',', User::getAdminEmailList());
		$administrator = User::getFirstAdmin();
		$account       = null;

		if ($administrator) {
			$adminContact = $this->database->selectFirst(
				'contact',
				['uri-id'],
				['nick' => $administrator['nickname'], 'self' => true]
			);
			$account = DI::mstdnAccount()->createFromUriId($adminContact['uri-id']);
		}

		return new InstanceEntity\Contact($email, $account);
	}

	private function buildFriendicaExtensionInfo(): InstanceEntity\FriendicaExtensions
	{
		return new InstanceEntity\FriendicaExtensions(
			App::VERSION,
			App::CODENAME,
			$this->config->get('system', 'build')
		);
	}

	private function buildRegistrationsInfo(): InstanceEntity\Registrations
	{
		$register_policy   = intval($this->config->get('config', 'register_policy'));
		$enabled           = ($register_policy != Register::CLOSED);
		$approval_required = ($register_policy == Register::APPROVE);

		return new InstanceEntity\Registrations($enabled, $approval_required);
	}

	private function buildUsageInfo(): InstanceEntity\Usage
	{
		if (!empty($this->config->get('system', 'nodeinfo'))) {
			$active_monthly = intval(DI::keyValue()->get('nodeinfo_active_users_monthly'));
		} else {
			$active_monthly = 0;
		}

		return new InstanceEntity\Usage(new InstanceEntity\UserStats($active_monthly));
	}
}
