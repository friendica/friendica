<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content\Widget;

use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;

/**
 * Menu that replaces the widgets for circles, channels, saved searches and saved folders.
 *
 * Each entry links to an overview page instead of expanding a list of entries in the sidebar.
 */
class SidebarMenu
{
	public const HOME     = 'home';
	public const CIRCLES  = 'circles';
	public const GROUPS   = 'groups';
	public const CHANNELS = 'channels';
	public const SEARCHES = 'searches';
	public const FOLDERS  = 'folders';

	/** Widgets that are replaced by a menu entry, in the order of the menu */
	private const WIDGETS = [
		self::CIRCLES  => Feature::CIRCLES,
		self::GROUPS   => Feature::GROUPS,
		self::CHANNELS => Feature::CHANNELS,
		self::SEARCHES => Feature::SEARCHES,
		self::FOLDERS  => Feature::FOLDERS,
	];

	public function __construct(
		private readonly L10n $l10n,
		private readonly IManagePersonalConfigValues $pConfig,
	) {}

	/**
	 * Is the menu used instead of the widgets?
	 *
	 * @param int $uid
	 * @return bool
	 */
	public function isActive(int $uid): bool
	{
		return $uid && $this->pConfig->get($uid, 'system', 'widgets_as_menu', false);
	}

	/**
	 * Is the given widget (identified by its feature) replaced by an entry of the menu?
	 *
	 * @param int    $uid
	 * @param string $feature Feature name of the widget
	 * @return bool
	 */
	public function replaces(int $uid, string $feature): bool
	{
		return $this->isActive($uid) && (in_array($feature, self::WIDGETS) || $feature == Feature::TRENDING_TAGS);
	}

	/**
	 * @param int    $uid
	 * @param string $selected Key of the entry of the currently displayed overview page
	 * @return string
	 */
	public function getHTML(int $uid, string $selected = ''): string
	{
		if (!$this->isActive($uid)) {
			return '';
		}

		Nav::setSelected('network');

		$entries = [
			self::CIRCLES  => ['circle',      'ri-bubble-chart-line', $this->l10n->t('Circles')],
			self::CHANNELS => ['channels',    'ri-broadcast-line',    $this->l10n->t('Channels')],
			self::SEARCHES => ['search/saved', 'ri-search-line',      $this->l10n->t('Tags')],
			self::FOLDERS  => ['folders',     'ri-folder-line',       $this->l10n->t('Saved Folders')],
		];

		$menu = [];
		foreach (self::WIDGETS as $key => $feature) {
			if (!isset($entries[$key]) || !Feature::isEnabled($uid, $feature)) {
				continue;
			}

			$menu[] = [
				'url'      => $entries[$key][0],
				'icon'     => $entries[$key][1],
				'label'    => $entries[$key][2],
				'selected' => $key == $selected,
			];
		}

		if (empty($menu)) {
			return '';
		}

		array_unshift($menu, [
			'url'      => 'network',
			'icon'     => 'ri-home-5-line',
			'label'    => $this->l10n->t('Home'),
			'selected' => $selected == self::HOME,
		]);

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('widget/sidebar_menu.tpl'), [
			'$entries' => $menu,
		]);
	}
}
