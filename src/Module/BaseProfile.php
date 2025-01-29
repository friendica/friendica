<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\User;

class BaseProfile extends BaseModule
{
	/**
	 * Returns the HTML for the profile pages tabs
	 *
	 * @param string $current
	 * @param bool   $is_owner
	 * @param string $nickname
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getTabsHTML(string $current, bool $is_owner, string $nickname, bool $hide_friends)
	{
		$baseProfileUrl = DI::baseUrl() . '/profile/' . $nickname;

		$tabs = [
			[
				'label'     => DI::l10n()->t('Profile'),
				'url'       => $baseProfileUrl . '/profile',
				'sel'       => $current == 'profile' ? 'active' : '',
				'title'     => DI::l10n()->t('Profile Details'),
				'id'        => 'profile-tab',
				'accesskey' => 'r',
			],
			[
				'label'     => DI::l10n()->t('Conversations'),
				'url'       => $baseProfileUrl . '/conversations',
				'sel'       => $current == 'status' ? 'active' : '',
				'title'     => DI::l10n()->t('Conversations started'),
				'id'        => 'status-tab',
				'accesskey' => 'm',
			],
			[
				'label'     => DI::l10n()->t('Photos'),
				'url'       => $baseProfileUrl . '/photos',
				'sel'       => $current == 'photos' ? 'active' : '',
				'title'     => DI::l10n()->t('Photo Albums'),
				'id'        => 'photo-tab',
				'accesskey' => 'h',
			],
			[
				'label'     => DI::l10n()->t('Media'),
				'url'       => $baseProfileUrl . '/media',
				'sel'       => $current == 'media' ? 'active' : '',
				'title'     => DI::l10n()->t('Media'),
				'id'        => 'media-tab',
				'accesskey' => 'd',
			],
		];

		// the calendar link for the full-featured events calendar
		if ($is_owner) {
			$tabs[] = [
				'label'     => DI::l10n()->t('Calendar'),
				'url'       => DI::baseUrl() . '/calendar',
				'sel'       => $current == 'calendar' ? 'active' : '',
				'title'     => DI::l10n()->t('Calendar'),
				'id'        => 'calendar-tab',
				'accesskey' => 'c',
			];
		} else {
			$owner = User::getByNickname($nickname, ['uid']);
			if(DI::userSession()->isAuthenticated() || $owner && Feature::isEnabled($owner['uid'], Feature::PUBLIC_CALENDAR)) {
				$tabs[] = [
					'label'     => DI::l10n()->t('Calendar'),
					'url'       => DI::baseUrl() . '/calendar/show/' . $nickname,
					'sel'       => $current == 'calendar' ? 'active' : '',
					'title'     => DI::l10n()->t('Calendar'),
					'id'        => 'calendar-tab',
					'accesskey' => 'c',
				];
			}
		}

		if ($is_owner) {
			$tabs[] = [
				'label'     => DI::l10n()->t('Personal Notes'),
				'url'       => DI::baseUrl() . '/notes',
				'sel'       => $current == 'notes' ? 'active' : '',
				'title'     => DI::l10n()->t('Only You Can See This'),
				'id'        => 'notes-tab',
				'accesskey' => 't',
			];
			$tabs[] = [
				'label'     => DI::l10n()->t('Scheduled Posts'),
				'url'       => $baseProfileUrl . '/schedule',
				'sel'       => $current == 'schedule' ? 'active' : '',
				'title'     => DI::l10n()->t('Posts that are scheduled for publishing'),
				'id'        => 'schedule-tab',
				'accesskey' => 'o',
			];
		}

		if (!$hide_friends) {
			$tabs[] = [
				'label'     => DI::l10n()->t('Contacts'),
				'url'       => $baseProfileUrl . '/contacts',
				'sel'       => $current == 'contacts' ? 'active' : '',
				'title'     => DI::l10n()->t('Contacts'),
				'id'        => 'viewcontacts-tab',
				'accesskey' => 'k',
			];
		}

		if (DI::session()->get('new_member') && $is_owner) {
			$tabs[] = [
				'label' => DI::l10n()->t('Tips for New Members'),
				'url'   => DI::baseUrl() . '/newmember',
				'sel'   => false,
				'title' => DI::l10n()->t('Tips for New Members'),
				'id'    => 'newmember-tab',
			];
		}

		$arr = ['is_owner' => $is_owner, 'nickname' => $nickname, 'tab' => $current, 'tabs' => $tabs];

		Hook::callAll('profile_tabs', $arr);

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');

		return Renderer::replaceMacros($tpl, ['$tabs' => $arr['tabs'], '$more' => DI::l10n()->t('More')]);
	}
}
