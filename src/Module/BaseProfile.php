<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Model\Contact;
use Friendica\Model\User;

class BaseProfile extends BaseModule
{
	/** Tabs that are displayed as filters below the "Posts" tab */
	public const POSTS_TABS = ['status', 'original', 'reshares', 'quotes', 'posts', 'activities', 'media'];

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

		$owner = $is_owner ? null : User::getByNickname($nickname, ['uid']);

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
				'label'     => DI::l10n()->t('Posts'),
				'url'       => $baseProfileUrl . '/conversations',
				'sel'       => in_array($current, self::POSTS_TABS) ? 'active' : '',
				'title'     => DI::l10n()->t('All posts'),
				'id'        => 'status-tab',
				'accesskey' => 'm',
			],
		];

		if (!$hide_friends) {
			$tabs[] = [
				'label'     => DI::l10n()->t('Contacts'),
				'url'       => $baseProfileUrl . '/contacts',
				'sel'       => $current == 'contacts' ? 'active' : '',
				'title'     => DI::l10n()->t('View all known contacts'),
				'id'        => 'viewcontacts-tab',
				'accesskey' => 'k',
			];
		}

		$tabs[] = [
			'label'     => DI::l10n()->t('Photos'),
			'url'       => $baseProfileUrl . '/photos',
			'sel'       => $current == 'photos' ? 'active' : '',
			'title'     => DI::l10n()->t('Photo Albums'),
			'id'        => 'photo-tab',
			'accesskey' => 'h',
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
		} elseif (DI::userSession()->isAuthenticated() || $owner && Feature::isEnabled($owner['uid'], Feature::PUBLIC_CALENDAR)) {
			$tabs[] = [
				'label'     => DI::l10n()->t('Calendar'),
				'url'       => DI::baseUrl() . '/calendar/show/' . $nickname,
				'sel'       => $current == 'calendar' ? 'active' : '',
				'title'     => DI::l10n()->t('Calendar'),
				'id'        => 'calendar-tab',
				'accesskey' => 'c',
			];
		}

		if ($is_owner) {
			$tabs[] = [
				'label'     => DI::l10n()->t('Personal notes'),
				'url'       => DI::baseUrl() . '/notes',
				'sel'       => $current == 'notes' ? 'active' : '',
				'title'     => DI::l10n()->t('Only you can see these'),
				'id'        => 'notes-tab',
				'accesskey' => 't',
			];
			$tabs[] = [
				'label'     => DI::l10n()->t('Scheduled posts'),
				'url'       => $baseProfileUrl . '/schedule',
				'sel'       => $current == 'schedule' ? 'active' : '',
				'title'     => DI::l10n()->t('Posts that are scheduled for publishing'),
				'id'        => 'schedule-tab',
				'accesskey' => 'o',
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

		$hook_data = ['is_owner' => $is_owner, 'nickname' => $nickname, 'tab' => $current, 'tabs' => $tabs];

		$eventDispatcher = DI::eventDispatcher();

		$hook_data = $eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::PROFILE_TABS, $hook_data),
		)->getArray();

		$tpl = Renderer::getMarkupTemplate('common_tabs.tpl');

		$o = Renderer::replaceMacros($tpl, ['$tabs' => $hook_data['tabs'], '$more' => DI::l10n()->t('More')]);

		if (in_array($current, self::POSTS_TABS)) {
			$pcid     = $is_owner ? DI::userSession()->getPublicContactId() : Contact::getPublicIdByUserId($owner['uid'] ?? 0);
			$pcontact = $pcid ? Contact::getById($pcid, ['contact-type', 'network']) : [];

			$o .= self::getPostsFilterHTML($baseProfileUrl . '/conversations', $baseProfileUrl, $current, $pcontact ?: []);
		}

		return $o;
	}

	/**
	 * Returns the HTML for the filters below the "Posts" tab
	 *
	 * Only the filters that make sense for the type and the network of the account are displayed.
	 *
	 * @param string $allUrl     URL of the unfiltered posts
	 * @param string $contactUrl URL prefix of the contact based filter pages
	 * @param string $current    Currently selected filter
	 * @param array  $contact    Contact array with the fields "contact-type" and "network"
	 * @return string
	 */
	public static function getPostsFilterHTML(string $allUrl, string $contactUrl, string $current, array $contact): string
	{
		$network = $contact['network'] ?? '';
		$isGroup = ($contact['contact-type'] ?? null) == Contact::TYPE_COMMUNITY;

		// Feeds and mails only have plain posts, there are no reshares, quotes or likes
		$isPlain = in_array($network, [Protocol::FEED, Protocol::MAIL]);

		$filters = [
			['label' => DI::l10n()->t('All'), 'url' => $allUrl, 'sel' => $current == 'status' ? 'active' : '', 'title' => DI::l10n()->t('All posts'), 'id' => 'status-filter'],
		];

		if (!$isPlain) {
			$filters[] = ['label' => DI::l10n()->t('Original'), 'url' => $contactUrl . '/original', 'sel' => $current == 'original' ? 'active' : '', 'title' => DI::l10n()->t('Posts without reshares and quotes'), 'id' => 'original-filter'];
		}

		// Groups transmit all their posts as reshares
		if (!$isPlain && !$isGroup) {
			$filters[] = ['label' => DI::l10n()->t('Reshares'), 'url' => $contactUrl . '/reshares', 'sel' => $current == 'reshares' ? 'active' : '', 'title' => DI::l10n()->t('Posts reshared'), 'id' => 'reshares-filter'];
		}

		// Diaspora reshares are quotes, there are no other quotes
		if (!$isPlain && ($network != Protocol::DIASPORA)) {
			$filters[] = ['label' => DI::l10n()->t('Quotes'), 'url' => $contactUrl . '/quotes', 'sel' => $current == 'quotes' ? 'active' : '', 'title' => DI::l10n()->t('Posts quoting other posts'), 'id' => 'quotes-filter'];
		}

		if ($network != Protocol::FEED) {
			$filters[] = ['label' => DI::l10n()->t('Comments'), 'url' => $contactUrl . '/posts', 'sel' => $current == 'posts' ? 'active' : '', 'title' => DI::l10n()->t('Individual Posts and Replies'), 'id' => 'posts-filter'];
		}

		// Groups don't like or react to posts
		if (!$isPlain && !$isGroup) {
			$filters[] = ['label' => DI::l10n()->t('Activities'), 'url' => $contactUrl . '/activities', 'sel' => $current == 'activities' ? 'active' : '', 'title' => DI::l10n()->t('Posts liked, disliked or reacted to'), 'id' => 'activities-filter'];
		}

		$filters[] = ['label' => DI::l10n()->t('Media'), 'url' => $contactUrl . '/media', 'sel' => $current == 'media' ? 'active' : '', 'title' => DI::l10n()->t('Posts containing media'), 'id' => 'media-filter'];

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('posts_filter.tpl'), ['$filters' => $filters]);
	}
}
