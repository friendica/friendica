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

namespace Friendica\Module\Api\GNUSocial\GNUSocial;

use Friendica\App;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\BaseApi;
use Friendica\Module\Register;

/**
 * API endpoint: /api/gnusocial/version, /api/statusnet/version
 */
class Config extends BaseApi
{
	protected function rawContent(array $request = [])
	{
		$config = [
			'site' => [
				'name'         => DI::config()->get('config', 'sitename'),
				'server'       => DI::baseUrl()->getHost(),
				'theme'        => DI::config()->get('system', 'theme'),
				'path'         => DI::baseUrl()->getPath(),
				'logo'         => DI::baseUrl() . '/images/friendica-64.png',
				'fancy'        => true,
				'language'     => DI::config()->get('system', 'language'),
				'email'        => implode(',', User::getAdminEmailList()),
				'broughtby'    => '',
				'broughtbyurl' => '',
				'timezone'     => DI::config()->get('system', 'default_timezone'),
				'closed'       => (DI::config()->get('config', 'register_policy') == Register::CLOSED),
				'inviteonly'   => (bool)DI::config()->get('system', 'invitation_only'),
				'private'      => (bool)DI::config()->get('system', 'block_public'),
				'textlimit'    => (string) DI::config()->get('config', 'api_import_size', DI::config()->get('config', 'max_import_size')),
				'sslserver'    => null,
				'ssl'          => DI::baseUrl()->getScheme() === 'https' ? 'always' : '0',
				'friendica'    => [
					'FRIENDICA_PLATFORM'    => App::PLATFORM,
					'FRIENDICA_VERSION'     => App::VERSION,
					'DB_UPDATE_VERSION'     => DB_UPDATE_VERSION,
				]
			],
		];

		$this->response->exit('config', ['config' => $config], $this->parameters['extension'] ?? null);
	}
}
