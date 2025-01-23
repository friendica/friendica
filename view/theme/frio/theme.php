<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Name: frio
 * Description: Responsive theme based on a modern HTML/CSS/JavaScript framework.
 * Version: V.1.0
 * Author: Rabuzarus <https://friendica.kommune4.de/profile/rabuzarus>
 * Maintainer: Hypolite Petovan <https://friendica.mrpetovan.com/profile/hypolite>
 */

use Friendica\App\Mode;
use Friendica\AppHelper;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;

const FRIO_SCHEME_ACCENT_BLUE   = '#1e87c2';
const FRIO_SCHEME_ACCENT_RED    = '#b50404';
const FRIO_SCHEME_ACCENT_PURPLE = '#a54bad';
const FRIO_SCHEME_ACCENT_GREEN  = '#218f39';
const FRIO_SCHEME_ACCENT_PINK   = '#d900a9';

const FRIO_DEFAULT_SCHEME = 'light';
const FRIO_CUSTOM_SCHEME  = '---';

/*
 * This script can be included even when the app is in maintenance mode which requires us to avoid any config call
 */

function frio_init(AppHelper $appHelper)
{
	global $frio;
	$frio = 'view/theme/frio';

	$appHelper->setThemeInfoValue('videowidth', 622);

	Renderer::setActiveTemplateEngine('smarty3');

	// if the device is a mobile device set js is_mobile
	// variable so the js scripts can use this information
	if (DI::mode()->isMobile() || DI::mode()->isMobile()) {
		DI::page()['htmlhead'] .= <<< EOT
			<script type="text/javascript">
				var is_mobile = 1;
			</script>
EOT;
	}
}

function frio_install()
{
	Hook::register('prepare_body_final', 'view/theme/frio/theme.php', 'frio_item_photo_links');
	Hook::register('item_photo_menu', 'view/theme/frio/theme.php', 'frio_item_photo_menu');
	Hook::register('contact_photo_menu', 'view/theme/frio/theme.php', 'frio_contact_photo_menu');
	Hook::register('nav_info', 'view/theme/frio/theme.php', 'frio_remote_nav');
	Hook::register('display_item', 'view/theme/frio/theme.php', 'frio_display_item');

	DI::logger()->info('installed theme frio');
}

/**
 * Replace friendica photo links hook
 *
 *  This function does replace the links to photos
 *  of other friendica users. Original the photos are
 *  linked to the photo page. Now they will linked directly
 *  to the photo file. This function is necessary to use colorbox
 *  in the network stream
 *
 * @param array $body_info The item and its html output
 */
function frio_item_photo_links(&$body_info)
{
	$occurence = 0;
	$p         = Plaintext::getBoundariesPosition($body_info['html'], '<a', '>');
	while ($p !== false && ($occurence++ < 500)) {
		$link    = substr($body_info['html'], $p['start'], $p['end'] - $p['start']);
		$matches = [];

		preg_match('/\/photos\/[\w]+\/image\/([\w]+)/', $link, $matches);
		if ($matches) {
			// Replace the link for the photo's page with a direct link to the photo itself
			$newlink = str_replace($matches[0], "/photo/{$matches[1]}", $link);

			// Add a "quiet" parameter to any redir links to prevent the "XX welcomes YY" info boxes
			$newlink = preg_replace('#href="([^"]+)/contact/redir/(\d+)&url=([^"]+)"#', 'href="$1/contact/redir/$2&quiet=1&url=$3"', $newlink);

			// Having any arguments to the link for Colorbox causes it to fetch base64 code instead of the image
			$newlink = preg_replace('/\/[?&]zrl=([^&"]+)/', '', $newlink);

			$body_info['html'] = str_replace($link, $newlink, $body_info['html']);
		}

		$p = Plaintext::getBoundariesPosition($body_info['html'], '<a', '>', $occurence);
	}
}

/**
 * Replace links of the item_photo_menu hook
 *
 *  This function replaces the original message links
 *  to call the addToModal javascript function so this pages can
 *  be loaded in a bootstrap modal
 *
 * @param array $arr Contains item data and the original photo_menu
 */
function frio_item_photo_menu(&$arr)
{
	foreach ($arr['menu'] as $k => $v) {
		if (strpos($v, 'message/new/') === 0) {
			$v               = 'javascript:addToModal(\'' . $v . '\'); return false;';
			$arr['menu'][$k] = $v;
		}
	}
}

/**
 * Replace links of the contact_photo_menu
 *
 *  This function replaces the original message link
 *  to call the addToModal javascript function so this pages can
 *  be loaded in a bootstrap modal
 *  Additionally the profile, status and photo page links  will be changed
 *  to don't open in a new tab if the contact is a friendica contact.
 *
 * @param array $args Contains contact data and the original photo_menu
 */
function frio_contact_photo_menu(&$args)
{
	$cid = $args['contact']['id'];

	if (!empty($args['menu']['pm'])) {
		$pmlink = $args['menu']['pm'][1];
	} else {
		$pmlink = '';
	}

	// Set the indicator for opening the status, profile and photo pages
	// in a new tab to false if the contact a dfrn (friendica) contact
	// We do this because we can go back on foreign friendica pages through
	// friendicas "magic-link" which indicates a friendica user on foreign
	// friendica servers as remote user or visitor
	//
	// The value for opening in a new tab is e.g. when
	// $args['menu']['status'][2] is true. If the value of the [2] key is true
	// and if it's a friendica contact we set it to false
	foreach ($args['menu'] as $k => $v) {
		if ($k === 'status' || $k === 'profile' || $k === 'photos') {
			$v[2]                = (($args['contact']['network'] === 'dfrn') ? false : true);
			$args['menu'][$k][2] = $v[2];
		}
	}

	// Add to pm link a new key with the value 'modal'.
	// Later we can make conditions in the corresponding templates (e.g.
	// contact/entry.tpl)
	if (strpos($pmlink, 'message/new/' . $cid) !== false) {
		$args['menu']['pm'][3] = 'modal';
	}
}

/**
 * Construct remote nav menu
 *
 *  It creates a remote baseurl form $_SESSION for remote users and friendica
 *  visitors. This url will be added to some of the nav links. With this behaviour
 *  the user will come back to her/his own pages on his/her friendica server.
 *  Not all possible links are available (notifications, administrator, manage,
 *  notes aren't available because we have no way the check remote permissions)..
 *  Some links will point to the local pages because the user would expect
 *  local page (these pages are: search, community, help, apps, directory).
 *
 * @param array $nav_info The original nav info array: nav, banner, userinfo, sitelocation
 * @throws Exception
 */
function frio_remote_nav(array &$nav_info)
{
	if (DI::mode()->has(Mode::MAINTENANCEDISABLED)) {
		// get the homelink from $_SESSION
		$homelink = DI::userSession()->getMyUrl();
		if (!$homelink) {
			$homelink = DI::session()->get('visitor_home', '');
		}

		// since $userinfo isn't available for the hook we write it to the nav array
		// this isn't optimal because the contact query will be done now twice
		$fields = ['id', 'url', 'avatar', 'micro', 'name', 'nick', 'baseurl', 'updated'];
		if (DI::userSession()->isAuthenticated()) {
			$remoteUser = Contact::selectFirst($fields, ['uid' => DI::userSession()->getLocalUserId(), 'self' => true]);
		} elseif (!DI::userSession()->getLocalUserId() && DI::userSession()->getRemoteUserId()) {
			$remoteUser                = Contact::getById(DI::userSession()->getRemoteUserId(), $fields);
			$nav_info['nav']['remote'] = DI::l10n()->t('Guest');
		} elseif (DI::userSession()->getMyUrl()) {
			$remoteUser                = Contact::getByURL($homelink, null, $fields);
			$nav_info['nav']['remote'] = DI::l10n()->t('Visitor');
		} else {
			$remoteUser = null;
		}

		if (DBA::isResult($remoteUser)) {
			$nav_info['userinfo'] = [
				'icon' => Contact::getMicro($remoteUser),
				'name' => $remoteUser['name'],
			];
			$server_url = $remoteUser['baseurl'];
		}

		if (!DI::userSession()->getLocalUserId() && !empty($server_url) && !is_null($remoteUser)) {
			// user menu
			$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'], DI::l10n()->t('Status'), '', DI::l10n()->t('Your posts and conversations')];
			$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'] . '/profile', DI::l10n()->t('Profile'), '', DI::l10n()->t('Your profile page')];
			// Kept for backwards-compatibility reasons, the remote server may not have updated to version 2022.12 yet
			// @TODO Switch with the new routes by version 2023.12
			//$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'] . '/photos', DI::l10n()->t('Photos'), '', DI::l10n()->t('Your photos')];
			$nav_info['nav']['usermenu'][] = [$server_url . '/photos/' . $remoteUser['nick'], DI::l10n()->t('Photos'), '', DI::l10n()->t('Your photos')];
			$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'] . '/media', DI::l10n()->t('Media'), '', DI::l10n()->t('Your postings with media')];
			$nav_info['nav']['usermenu'][] = [$server_url . '/calendar/', DI::l10n()->t('Calendar'), '', DI::l10n()->t('Your calendar')];

			// navbar links
			$nav_info['nav']['network']  = [$server_url . '/network', DI::l10n()->t('Network'), '', DI::l10n()->t('Conversations from your friends')];
			$nav_info['nav']['calendar'] = [$server_url . '/calendar', DI::l10n()->t('Calendar'), '', DI::l10n()->t('Calendar')];
			$nav_info['nav']['messages'] = [$server_url . '/message', DI::l10n()->t('Messages'), '', DI::l10n()->t('Private mail')];
			$nav_info['nav']['settings'] = [$server_url . '/settings', DI::l10n()->t('Settings'), '', DI::l10n()->t('Account settings')];
			$nav_info['nav']['contacts'] = [$server_url . '/contact', DI::l10n()->t('Contacts'), '', DI::l10n()->t('Manage/edit friends and contacts')];
			$nav_info['nav']['sitename'] = DI::config()->get('config', 'sitename');
		}
	}
}

function frio_display_item(&$arr)
{
	// Add follow to the item menu
	$followThread = [];
	if (
		DI::userSession()->getLocalUserId()
		&& in_array($arr['item']['uid'], [0, DI::userSession()->getLocalUserId()])
		&& $arr['item']['gravity'] == Item::GRAVITY_PARENT
		&& !$arr['item']['self']
		&& !$arr['item']['mention']
	) {
		$followThread = [
			'menu'   => 'follow_thread',
			'title'  => DI::l10n()->t('Follow Thread'),
			'action' => 'doFollowThread(' . $arr['item']['id'] . ');',
			'href'   => '#'
		];
	}
	$arr['output']['follow_thread'] = $followThread;
}
