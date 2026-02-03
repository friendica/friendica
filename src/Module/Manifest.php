<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core;
use Friendica\DI;

class Manifest extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$config = DI::config();

		$theme = DI::config()->get('system', 'theme');

		$manifest = [
			'name'        => $config->get('config', 'sitename', 'Friendica'),
			'start_url'   => DI::baseUrl(),
			'display'     => 'standalone',
			'description' => $config->get('config', 'info', DI::l10n()->t('A Decentralized Social Network')),
			'short_name'  => DI::baseUrl()->getHost(),
			'lang'        => $config->get('system', 'language'),
			'dir'         => 'auto',
			'categories'  => ['social network', 'internet'],
			'shortcuts'   => [
				[
					'name' => 'Latest posts',
					'url'  => '/network'
				],
				[
					'name' => 'Messages',
					'url'  => '/message'
				],
				[
					'name' => 'Notifications',
					'url'  => '/notifications/system'
				],
				[
					'name' => 'Contacts',
					'url'  => '/contact'
				],
				[
					'name' => 'Calendar',
					'url'  => '/calendar'
				]
			]
		];

		/// @TODO If the admin provides their own touch icon, the manifest will regress
		/// to a smaller set of icons that do not follow the web app manifest spec.
		/// There should be a mechanism to allow the admin to provide all of the 6
		/// different images that are required for a fully valid web app manifest.
		$touch_icon = $config->get('system', 'touch_icon');
		if($touch_icon) {
			$manifest['icons'] = [
				[
					'src'   => DI::baseUrl() . '/' . $touch_icon,
					'sizes' => '192x192',
					'type'  => 'image/png',
				],
				[
					'src'   => DI::baseUrl() . '/' . $touch_icon,
					'sizes' => '512x512',
					'type'  => 'image/png',
				],
			];
		} else {
			$manifest['icons'] = [
				[
					'src'     => DI::baseUrl() . '/images/friendica.svg',
					'sizes'   => 'any',
					'type'    => 'image/svg+xml',
					'purpose' => 'any',
				],
				[
					'src'     => DI::baseUrl() . '/images/friendica-192.png',
					'sizes'   => '192x192',
					'type'    => 'image/png',
					'purpose' => 'any',
				],
				[
					'src'     => DI::baseUrl() . '/images/friendica-512.png',
					'sizes'   => '512x512',
					'type'    => 'image/png',
					'purpose' => 'any',
				],
				[
					'src'     => DI::baseUrl() . '/images/friendica-maskable.svg',
					'sizes'   => 'any',
					'type'    => 'image/svg+xml',
					'purpose' => 'maskable',
				],
				[
					'src'     => DI::baseUrl() . '/images/friendica-maskable-192.png',
					'sizes'   => '192x192',
					'type'    => 'image/png',
					'purpose' => 'maskable',
				],
				[
					'src'     => DI::baseUrl() . '/images/friendica-maskable-512.png',
					'sizes'   => '512x512',
					'type'    => 'image/png',
					'purpose' => 'maskable',
				],
			];
		}

		if ($background_color = Core\Theme::getBackgroundColor($theme)) {
			$manifest['background_color'] = $background_color;
		}

		if ($theme_color = Core\Theme::getThemeColor($theme)) {
			$manifest['theme_color'] = $theme_color;
		}

		$this->jsonExit($manifest, 'application/manifest+json');
	}
}
