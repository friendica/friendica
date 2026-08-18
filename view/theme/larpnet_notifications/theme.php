<?php
/**
 * Copyright (C) 2010-2024, the Friendica project
 * SPDX-FileCopyrightText: 2010-2024 the Friendica project
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Name: Larpnet Notifications
 * Description: Motyw Larpnet z obsługą Web Push, powiadomień przeglądarkowych i PWA. Wymaga serwera ntfy.
 * Version: 1.3
 * Author: larpnet admin <https://larpnet.pl>
 */

use Friendica\App\Mode;
use Friendica\AppHelper;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\LarpnetPush;
use Friendica\Model\Subscription;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Strings;

defined('LARPNET_SCHEME_ACCENT_BLUE')   || define('LARPNET_SCHEME_ACCENT_BLUE',   '#1e87c2');
defined('LARPNET_SCHEME_ACCENT_RED')    || define('LARPNET_SCHEME_ACCENT_RED',    '#b50404');
defined('LARPNET_SCHEME_ACCENT_PURPLE') || define('LARPNET_SCHEME_ACCENT_PURPLE', '#a54bad');
defined('LARPNET_SCHEME_ACCENT_GREEN')  || define('LARPNET_SCHEME_ACCENT_GREEN',  '#218f39');
defined('LARPNET_SCHEME_ACCENT_PINK')   || define('LARPNET_SCHEME_ACCENT_PINK',   '#d900a9');
defined('LARPNET_DEFAULT_SCHEME')       || define('LARPNET_DEFAULT_SCHEME',       'light');
defined('LARPNET_CUSTOM_SCHEME')        || define('LARPNET_CUSTOM_SCHEME',        '---');

function larpnet_notifications_init(AppHelper $appHelper)
{
	global $larpnet;
	$larpnet = 'view/theme/larpnet_notifications';

	Renderer::setActiveTemplateEngine('smarty3');

	if (DI::mode()->isMobile() || DI::mode()->isMobile()) {
		DI::page()['htmlhead'] .= <<< EOT
			<script type="text/javascript">
				var is_mobile = 1;
			</script>
EOT;
	}
}

function larpnet_notifications_install()
{
	Hook::register('prepare_body_final', 'view/theme/larpnet_notifications/theme.php', 'larpnet_notifications_item_photo_links');
	Hook::register('item_photo_menu',    'view/theme/larpnet_notifications/theme.php', 'larpnet_notifications_item_photo_menu');
	Hook::register('contact_photo_menu', 'view/theme/larpnet_notifications/theme.php', 'larpnet_notifications_contact_photo_menu');
	Hook::register('nav_info',           'view/theme/larpnet_notifications/theme.php', 'larpnet_notifications_remote_nav');
	Hook::register('display_item',       'view/theme/larpnet_notifications/theme.php', 'larpnet_notifications_display_item');
	Hook::register('head',               'view/theme/larpnet_notifications/theme.php', 'larpnet_notifications_head');

	DI::logger()->info('installed theme larpnet_notifications');
}

// ---------------------------------------------------------------------------
// Push notification helpers

function larpnet_notifications_get_or_create_app(): ?array
{
	$app = DBA::selectFirst('application', [], ['name' => 'larpnet_web_push']);
	if (DBA::isResult($app)) {
		return $app;
	}

	DBA::insert('application', [
		'name'          => 'larpnet_web_push',
		'redirect_uri'  => (string) DI::baseUrl(),
		'website'       => (string) DI::baseUrl(),
		'client_id'     => Strings::getRandomHex(32),
		'client_secret' => Strings::getRandomHex(32),
		'scopes'        => 'push',
		'push'          => true,
	]);

	return DBA::selectFirst('application', [], ['name' => 'larpnet_web_push']);
}

function larpnet_notifications_get_or_create_token(int $uid, int $appId): ?string
{
	$row = DBA::selectFirst('application-token', ['access_token'], [
		'application-id' => $appId,
		'uid'            => $uid,
	]);
	if (DBA::isResult($row)) {
		return $row['access_token'];
	}

	$token = Strings::getRandomHex(32);
	DBA::insert('application-token', [
		'application-id' => $appId,
		'uid'            => $uid,
		'code'           => Strings::getRandomHex(32),
		'access_token'   => $token,
		'created_at'     => DateTimeFormat::utcNow(),
		'scopes'         => 'push',
		'push'           => true,
	]);

	return $token;
}

function larpnet_notifications_get_or_create_ntfy_topic(int $uid): string
{
	return LarpnetPush::getOrCreateTopic($uid);
}

function larpnet_notifications_head(string &$b)
{
	if (DI::appHelper()->getCurrentTheme() !== 'larpnet_notifications') {
		return;
	}

	$uid = DI::userSession()->getLocalUserId();
	if (!$uid) {
		return;
	}

	$b .= <<<'JS'
<script>
(function() {
	function isPwa() {
		return window.matchMedia('(display-mode: standalone)').matches
			|| !!navigator.standalone;
	}

	function needsEnable() {
		if (typeof Notification === 'undefined') return false;
		// Need to request browser permission
		if (Notification.permission === 'default') return true;
		// Browser granted permission but app-level disabled:
		// getNotificationPermission() (main.js) returns 'denied' when localStorage
		// is null or 'denied', even though the browser already granted permission.
		if (Notification.permission === 'granted'
			&& typeof getNotificationPermission === 'function'
			&& getNotificationPermission() !== 'granted') return true;
		return false;
	}

	function injectEnableBtn(menu) {
		if (!needsEnable()) return;
		if (document.getElementById('nav-notification-enable')) return;
		var markAll = menu.querySelector('#nav-notifications-mark-all');
		if (!markAll) return;

		var li = document.createElement('li');
		li.id = 'nav-notification-enable';

		var btn = document.createElement('button');
		btn.id = 'nav-notification-enable-btn';
		btn.type = 'button';
		btn.className = 'btn btn-primary btn-sm';
		btn.textContent = isPwa()
			? 'Aktywuj powiadomienia z aplikacji'
			: 'Włącz powiadomienia na pulpicie';

		btn.addEventListener('click', function() {
			if (Notification.permission === 'granted') {
				// Permission already granted — just set localStorage and reload
				// so push.js can complete the ntfy subscription
				localStorage.setItem('notification-permissions', 'granted');
				li.remove();
				window.location.reload();
			} else {
				Notification.requestPermission().then(function(result) {
					if (result === 'granted') {
						localStorage.setItem('notification-permissions', 'granted');
						li.remove();
						if (window.LarpnetPush) { window.location.reload(); }
					}
				});
			}
		});

		li.appendChild(btn);
		markAll.insertAdjacentElement('afterend', li);
	}

	function setup() {
		var menu = document.getElementById('nav-notifications-menu');
		if (!menu) return;
		injectEnableBtn(menu);
		new MutationObserver(function() {
			injectEnableBtn(menu);
		}).observe(menu, { childList: true });
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', setup);
	} else {
		setup();
	}
})();
</script>
JS;

	$ntfyUrl          = DI::config()->get('larpnet_notifications', 'ntfy_url');
	$ntfyVapidKey     = DI::config()->get('larpnet_notifications', 'ntfy_vapid_public_key');
	// Use read-only token for the browser — never expose the write token client-side
	$ntfyToken        = DI::config()->get('larpnet_notifications', 'ntfy_ro_token');

	if (empty($ntfyUrl) || empty($ntfyVapidKey)) {
		return;
	}

	$topic   = larpnet_notifications_get_or_create_ntfy_topic($uid);
	$baseUrl = (string) DI::baseUrl();
	$swUrl   = $baseUrl . '/sw.js';

	$config = json_encode([
		'vapidKey'  => $ntfyVapidKey,
		'ntfyUrl'   => rtrim($ntfyUrl, '/'),
		'ntfyToken' => (string) $ntfyToken,
		'ntfyTopic' => $topic,
		'swUrl'     => $swUrl,
	]);

	$b .= "<script>window.LarpnetPush = {$config};</script>\n";

	DI::page()->registerFooterScript(__DIR__ . '/js/push.js?v=2026.03');
}

// ---------------------------------------------------------------------------
// Hooks (renamed from larpnet_* to larpnet_notifications_*)

function larpnet_notifications_item_photo_links(&$body_info)
{
	$occurence = 0;
	$p         = Plaintext::getBoundariesPosition($body_info['html'], '<a', '>');
	while ($p !== false && ($occurence++ < 500)) {
		$link    = substr($body_info['html'], $p['start'], $p['end'] - $p['start']);
		$matches = [];

		preg_match('/\/photos\/[\w]+\/image\/([\w]+)/', $link, $matches);
		if ($matches) {
			$newlink = str_replace($matches[0], "/photo/{$matches[1]}", $link);
			$newlink = preg_replace('#href="([^"]+)/contact/redir/(\d+)&url=([^"]+)"#', 'href="$1/contact/redir/$2&quiet=1&url=$3"', $newlink);
			$newlink = preg_replace('/\/[?&]zrl=([^&"]+)/', '', $newlink);

			$body_info['html'] = str_replace($link, $newlink, $body_info['html']);
		}

		$p = Plaintext::getBoundariesPosition($body_info['html'], '<a', '>', $occurence);
	}
}

function larpnet_notifications_item_photo_menu(&$arr)
{
	foreach ($arr['menu'] as $k => $v) {
		if (strpos($v, 'message/new/') === 0) {
			$v               = 'javascript:addToModal(\'' . $v . '\'); return false;';
			$arr['menu'][$k] = $v;
		}
	}
}

function larpnet_notifications_contact_photo_menu(&$args)
{
	$cid = $args['contact']['id'];

	$pmlink = $args['menu']['pm'][1] ?? '';

	foreach ($args['menu'] as $k => $v) {
		if ($k === 'status' || $k === 'profile' || $k === 'photos') {
			$v[2]                = (($args['contact']['network'] === 'dfrn') ? false : true);
			$args['menu'][$k][2] = $v[2];
		}
	}

	if (strpos($pmlink, 'message/new/' . $cid) !== false) {
		$args['menu']['pm'][3] = 'modal';
	}
}

function larpnet_notifications_remote_nav(array &$nav_info)
{
	if (DI::mode()->has(Mode::MAINTENANCEDISABLED)) {
		$homelink = DI::userSession()->getMyUrl();
		if (!$homelink) {
			$homelink = DI::session()->get('visitor_home', '');
		}

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
			$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'], DI::l10n()->t('Status'), '', DI::l10n()->t('Your posts and conversations')];
			$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'] . '/profile', DI::l10n()->t('Profile'), '', DI::l10n()->t('Your profile page')];
			$nav_info['nav']['usermenu'][] = [$server_url . '/photos/' . $remoteUser['nick'], DI::l10n()->t('Photos'), '', DI::l10n()->t('Your photos')];
			$nav_info['nav']['usermenu'][] = [$server_url . '/profile/' . $remoteUser['nick'] . '/media', DI::l10n()->t('Media'), '', DI::l10n()->t('Your postings with media')];
			$nav_info['nav']['usermenu'][] = [$server_url . '/calendar/', DI::l10n()->t('Calendar'), '', DI::l10n()->t('Your calendar')];

			$nav_info['nav']['network']  = [$server_url . '/network', DI::l10n()->t('Network'), '', DI::l10n()->t('Conversations from your friends')];
			$nav_info['nav']['calendar'] = [$server_url . '/calendar', DI::l10n()->t('Calendar'), '', DI::l10n()->t('Calendar')];
			$nav_info['nav']['messages'] = [$server_url . '/message', DI::l10n()->t('Messages'), '', DI::l10n()->t('Private mail')];
			$nav_info['nav']['settings'] = [$server_url . '/settings', DI::l10n()->t('Settings'), '', DI::l10n()->t('Account settings')];
			$nav_info['nav']['contacts'] = [$server_url . '/contact', DI::l10n()->t('Contacts'), '', DI::l10n()->t('Manage/edit friends and contacts')];
			$nav_info['nav']['sitename'] = DI::config()->get('config', 'sitename');
		}
	}
}

function larpnet_notifications_display_item(&$arr)
{
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

	$completeThread = [];
	if (
		DI::userSession()->getLocalUserId()
		&& in_array($arr['item']['uid'], [0, DI::userSession()->getLocalUserId()])
		&& $arr['item']['network'] == Protocol::ACTIVITYPUB
		&& $arr['item']['gravity'] == Item::GRAVITY_PARENT
		&& !$arr['item']['self']
	) {
		$completeThread = [
			'menu'   => 'complete_thread',
			'title'  => DI::l10n()->t('Complete Thread'),
			'action' => 'doCompleteThread(' . $arr['item']['uri-id'] . ');',
			'href'   => '#'
		];
	}
	$arr['output']['complete_thread'] = $completeThread;
}
