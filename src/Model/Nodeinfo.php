<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Model;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\DBA;
use Friendica\DI;
use stdClass;

/**
 * Model interaction for the nodeinfo
 */
class Nodeinfo
{
	/**
	 * Updates the info about the current node
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function update()
	{
		$config      = DI::config();
		$logger      = DI::logger();
		$addonHelper = DI::addonHelper();

		// If the addon 'statistics_json' is enabled then disable it and activate nodeinfo.
		if ($addonHelper->isAddonEnabled('statistics_json')) {
			$config->set('system', 'nodeinfo', true);
			$addonHelper->uninstallAddon('statistics_json');
		}

		if (empty($config->get('system', 'nodeinfo'))) {
			return;
		}

		$logger->info('User statistics - start');

		$userStats = User::getStatistics();

		DI::keyValue()->set('nodeinfo_total_users', $userStats['total_users']);
		DI::keyValue()->set('nodeinfo_active_users_halfyear', $userStats['active_users_halfyear']);
		DI::keyValue()->set('nodeinfo_active_users_monthly', $userStats['active_users_monthly']);
		DI::keyValue()->set('nodeinfo_active_users_weekly', $userStats['active_users_weekly']);

		$logger->info('user statistics - done', $userStats);

		$posts    = DBA::count('post-thread', ["`uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE NOT `deleted` AND `origin`)"]);
		$comments = DBA::count('post', ["NOT `deleted` AND `gravity` = ? AND `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin`)", Item::GRAVITY_COMMENT]);
		DI::keyValue()->set('nodeinfo_local_posts', $posts);
		DI::keyValue()->set('nodeinfo_local_comments', $comments);

		$posts    = DBA::count('post', ['deleted' => false, 'gravity' => Item::GRAVITY_COMMENT]);
		$comments = DBA::count('post', ['deleted' => false, 'gravity' => Item::GRAVITY_COMMENT]);
		DI::keyValue()->set('nodeinfo_total_posts', $posts);
		DI::keyValue()->set('nodeinfo_total_comments', $comments);

		$logger->info('Post statistics - done', ['posts' => $posts, 'comments' => $comments]);
	}

	/**
	 * Return the supported services
	 *
	 * @return stdClass with supported services
	 */
	public static function getUsage(bool $version2 = false): stdClass
	{
		$config = DI::config();

		$usage        = new stdClass();
		$usage->users = new stdClass();

		if (!empty($config->get('system', 'nodeinfo'))) {
			$usage->users->total          = intval(DI::keyValue()->get('nodeinfo_total_users'));
			$usage->users->activeHalfyear = intval(DI::keyValue()->get('nodeinfo_active_users_halfyear'));
			$usage->users->activeMonth    = intval(DI::keyValue()->get('nodeinfo_active_users_monthly'));
			$usage->localPosts            = intval(DI::keyValue()->get('nodeinfo_local_posts'));
			$usage->localComments         = intval(DI::keyValue()->get('nodeinfo_local_comments'));

			if ($version2) {
				$usage->users->activeWeek = intval(DI::keyValue()->get('nodeinfo_active_users_weekly'));
			}
		}

		return $usage;
	}

	/**
	 * Return the supported services
	 *
	 * @return array with supported services
	 */
	public static function getServices(): array
	{
		$addonHelper = DI::addonHelper();

		$services = [
			'inbound'  => [],
			'outbound' => [],
		];

		if ($addonHelper->isAddonEnabled('bluesky')) {
			$services['inbound'][]  = 'bluesky';
			$services['outbound'][] = 'bluesky';
		}
		if ($addonHelper->isAddonEnabled('dwpost')) {
			$services['outbound'][] = 'dreamwidth';
		}
		if ($addonHelper->isAddonEnabled('statusnet')) {
			$services['inbound'][]  = 'gnusocial';
			$services['outbound'][] = 'gnusocial';
		}
		if ($addonHelper->isAddonEnabled('ijpost')) {
			$services['outbound'][] = 'insanejournal';
		}
		if ($addonHelper->isAddonEnabled('libertree')) {
			$services['outbound'][] = 'libertree';
		}
		if ($addonHelper->isAddonEnabled('ljpost')) {
			$services['outbound'][] = 'livejournal';
		}
		if ($addonHelper->isAddonEnabled('pumpio')) {
			$services['inbound'][]  = 'pumpio';
			$services['outbound'][] = 'pumpio';
		}

		$services['outbound'][] = 'smtp';

		if ($addonHelper->isAddonEnabled('tumblr')) {
			$services['outbound'][] = 'tumblr';
		}
		if ($addonHelper->isAddonEnabled('twitter')) {
			$services['outbound'][] = 'twitter';
		}
		if ($addonHelper->isAddonEnabled('wppost')) {
			$services['outbound'][] = 'wordpress';
		}

		return $services;
	}

	/**
	 * Gathers organization information and returns it as an array
	 *
	 * @param IManageConfigValues $config Configuration instance
	 * @return array Organization information
	 * @throws \Exception
	 */
	public static function getOrganization(IManageConfigValues $config): array
	{
		$administrator = User::getFirstAdmin(['username', 'email', 'nickname']);

		return [
			'name'    => $administrator['username'] ?? null,
			'contact' => $administrator['email']    ?? null,
			'account' => $administrator['nickname'] ?? '' ? DI::baseUrl() . '/profile/' . $administrator['nickname'] : null,
		];
	}
}
