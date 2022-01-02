<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\BaseModule;
use Friendica\Core;
use Friendica\DI;

class Manifest extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$config = DI::config();

		$touch_icon = $config->get('system', 'touch_icon') ?: 'images/friendica-192.png';

		$theme = DI::config()->get('system', 'theme');

		$manifest = [
			'name'          => $config->get('config', 'sitename', 'Friendica'),
			'start_url'     => DI::baseUrl()->get(),
			'display'       => 'standalone',
			'description'   => $config->get('config', 'info', DI::l10n()->t('A Decentralized Social Network')),
			'short_name'    => 'Friendica',
			'lang'          => $config->get('system', 'language'),
			'dir'           => 'auto',
			'categories'    => ['social network', 'internet'],
			'icons'         => [
				[
					'src'   => DI::baseUrl()->get() . '/' . $touch_icon,
					'sizes' => '192x192',
					'type'  => 'image/png',
				],
				[
					'src'   => DI::baseUrl()->get() . '/' . $touch_icon,
					'sizes' => '512x512',
					'type'  => 'image/png',
				],
			],
			'shortcuts'     => [
				[
					'name'  => 'Latest posts',
					'url'   => '/network'
				],
				[
					'name'  => 'Messages',
					'url'   => '/message'
				],
				[
					'name'  => 'Notifications',
					'url'   => '/notifications/system'
				],
				[
					'name'  => 'Contacts',
					'url'   => '/contact'
				],
				[
					'name'  => 'Events',
					'url'   => '/events'
				]
			]
		];

		if ($background_color = Core\Theme::getBackgroundColor($theme)) {
			$manifest['background_color'] = $background_color;
		}

		if ($theme_color = Core\Theme::getThemeColor($theme)) {
			$manifest['theme_color'] = $theme_color;
		}

		Core\System::jsonExit($manifest, 'application/manifest+json');
	}
}
