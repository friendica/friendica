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

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\KeyValueStorage\Capabilities\IManageKeyValuePairs;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Statistics extends BaseModule
{
	/** @var IManageConfigValues */
	protected $config;
	/** @var IManageKeyValuePairs */
	protected $keyValue;
	
	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, IManageConfigValues $config, IManageKeyValuePairs $keyValue, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config   = $config;
		$this->keyValue = $keyValue;
		if (!$this->config->get("system", "nodeinfo")) {
			throw new NotFoundException();
		}
	}

	protected function rawContent(array $request = [])
	{
		$registration_open =
			intval($this->config->get('config', 'register_policy')) !== Register::CLOSED
			&& !$this->config->get('config', 'invitation_only');

		/// @todo mark the "service" addons and load them dynamically here
		$services = [
			'appnet'      => Addon::isEnabled('appnet'),
			'buffer'      => Addon::isEnabled('buffer'),
			'dreamwidth'  => Addon::isEnabled('dreamwidth'),
			'gnusocial'   => Addon::isEnabled('gnusocial'),
			'libertree'   => Addon::isEnabled('libertree'),
			'livejournal' => Addon::isEnabled('livejournal'),
			'pumpio'      => Addon::isEnabled('pumpio'),
			'twitter'     => Addon::isEnabled('twitter'),
			'tumblr'      => Addon::isEnabled('tumblr'),
			'wordpress'   => Addon::isEnabled('wordpress'),
		];

		$statistics = array_merge([
			'name'                  => $this->config->get('config', 'sitename'),
			'network'               => App::PLATFORM,
			'version'               => App::VERSION . '-' . DB_UPDATE_VERSION,
			'registrations_open'    => $registration_open,
			'total_users'           => $this->keyValue->get('nodeinfo_total_users'),
			'active_users_halfyear' => $this->keyValue->get('nodeinfo_active_users_halfyear'),
			'active_users_monthly'  => $this->keyValue->get('nodeinfo_active_users_monthly'),
			'local_posts'           => $this->keyValue->get('nodeinfo_local_posts'),
			'services'              => $services,
		], $services);

		$this->logger->debug("statistics.", ['statistics' => $statistics]);
		System::jsonExit($statistics);
	}
}
