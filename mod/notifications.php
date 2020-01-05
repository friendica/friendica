<?php
/**
 * @file mod/notifications.php
 * @brief The notifications module
 */

use Friendica\App;
use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\Security\Login;
use Friendica\Model\Contact;
use Friendica\Model\Introduction;

function notifications_post(App $a)
{
	if (!local_user()) {
		DI::baseUrl()->redirect();
	}

	$request_id = (($a->argc > 1) ? $a->argv[1] : 0);

	if ($request_id === 'all') {
		return;
	}

	if ($request_id) {
		$intro = DI::intro()->selectFirst(['id' => $request_id, 'uid' => local_user()]);

		switch ($_POST['submit']) {
			case L10n::t('Discard'):
				$intro->discard();
				break;
			case L10n::t('Ignore'):
				$intro->ignore();
				break;
		}

		DI::baseUrl()->redirect('notifications/intros');
	}
}

function notifications_content(App $a)
{
	if (!local_user()) {
		notice(L10n::t('Permission denied.') . EOL);
		return Login::form();
	}

	$page = ($_REQUEST['page'] ?? 0) ?: 1;
	$show = ($_REQUEST['show'] ?? '') === 'all';

	Nav::setSelected('notifications');

	$json = (($a->argc > 1 && $a->argv[$a->argc - 1] === 'json') ? true : false);

	$nm = DI::notify();

	$o = '';
	// Get the nav tabs for the notification pages
	$tabs = $nm->getTabs();
	$notif_content = [];
	$notif_nocontent = '';

	// Notification results per page
	$perpage = 20;
	$startrec = ($page * $perpage) - $perpage;

	$notif_header = L10n::t('Notifications');

	$all = false;

	// Get introductions
	if ((($a->argc > 1) && ($a->argv[1] == 'intros')) || (($a->argc == 1))) {
		Nav::setSelected('introductions');

		$id = 0;
		if (!empty($a->argv[2]) && intval($a->argv[2]) != 0) {
			$id = (int)$a->argv[2];
		}

		$all = (($a->argc > 2) && ($a->argv[2] == 'all'));

		$notifs = $nm->getIntroList($all, $startrec, $perpage, $id);

	// Get the network notifications
	} elseif (($a->argc > 1) && ($a->argv[1] == 'network')) {
		$notif_header = L10n::t('Network Notifications');
		$notifs = $nm->getNetworkList($show, $startrec, $perpage);

	// Get the system notifications
	} elseif (($a->argc > 1) && ($a->argv[1] == 'system')) {
		$notif_header = L10n::t('System Notifications');
		$notifs = $nm->getSystemList($show, $startrec, $perpage);

	// Get the personal notifications
	} elseif (($a->argc > 1) && ($a->argv[1] == 'personal')) {
		$notif_header = L10n::t('Personal Notifications');
		$notifs = $nm->getPersonalList($show, $startrec, $perpage);

	// Get the home notifications
	} elseif (($a->argc > 1) && ($a->argv[1] == 'home')) {
		$notif_header = L10n::t('Home Notifications');
		$notifs = $nm->getHomeList($show, $startrec, $perpage);
	// fallback - redirect to main page
	} else {
		DI::baseUrl()->redirect('notifications');
	}

	// Set the pager
	$pager = new Pager(DI::args()->getQueryString(), $perpage);

	// Add additional informations (needed for json output)
	$notifs['items_page'] = $pager->getItemsPerPage();
	$notifs['page'] = $pager->getPage();

	// Json output
	if (intval($json) === 1) {
		System::jsonExit($notifs);
	}

	$notif_tpl = Renderer::getMarkupTemplate('notifications.tpl');

	$notif_show_lnk = [
		'href' => ($show ? 'notifications/' . $notifs['ident'] : 'notifications/' . $notifs['ident'] . '?show=all' ),
		'text' => ($show ? L10n::t('Show unread') : L10n::t('Show all')),
	];

	// Process the data for template creation
	if (($notifs['ident'] ?? '') == 'introductions') {
		$sugg = Renderer::getMarkupTemplate('suggestions.tpl');
		$tpl = Renderer::getMarkupTemplate('intros.tpl');

		// The link to switch between ignored and normal connection requests
		$notif_show_lnk = [
			'href' => (!$all ? 'notifications/intros/all' : 'notifications/intros' ),
			'text' => (!$all ? L10n::t('Show Ignored Requests') : L10n::t('Hide Ignored Requests'))
		];

		// Loop through all introduction notifications.This creates an array with the output html for each
		// introduction
		foreach ($notifs['notifications'] as $notif) {

			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.
			switch ($notif['label']) {
				case 'friend_suggestion':
					$notif_content[] = Renderer::replaceMacros($sugg, [
						'$type'       => $notif['label'],
						'$str_notifytype' => L10n::t('Notification type:'),
						'$notify_type'=> $notif['notify_type'],
						'$intro_id'   => $notif['intro_id'],
						'$lbl_madeby' => L10n::t('Suggested by:'),
						'$madeby'     => $notif['madeby'],
						'$madeby_url' => $notif['madeby_url'],
						'$madeby_zrl' => $notif['madeby_zrl'],
						'$madeby_addr'=> $notif['madeby_addr'],
						'$contact_id' => $notif['contact_id'],
						'$photo'      => $notif['photo'],
						'$fullname'   => $notif['name'],
						'$url'        => $notif['url'],
						'$zrl'        => $notif['zrl'],
						'$lbl_url'    => L10n::t('Profile URL'),
						'$addr'       => $notif['addr'],
						'$hidden'     => ['hidden', L10n::t('Hide this contact from others'), ($notif['hidden'] == 1), ''],
						'$knowyou'    => $notif['knowyou'],
						'$approve'    => L10n::t('Approve'),
						'$note'       => $notif['note'],
						'$request'    => $notif['request'],
						'$ignore'     => L10n::t('Ignore'),
						'$discard'    => L10n::t('Discard'),
					]);
					break;

				// Normal connection requests
				default:
					$friend_selected = (($notif['network'] !== Protocol::OSTATUS) ? ' checked="checked" ' : ' disabled ');
					$fan_selected = (($notif['network'] === Protocol::OSTATUS) ? ' checked="checked" disabled ' : '');

					$lbl_knowyou = '';
					$knowyou     = '';
					$helptext    = '';
					$helptext2   = '';
					$helptext3   = '';

					if ($notif['network'] === Protocol::DFRN) {
						$lbl_knowyou = L10n::t('Claims to be known to you: ');
						$knowyou   = (($notif['knowyou']) ? L10n::t('yes') : L10n::t('no'));
						$helptext  = L10n::t('Shall your connection be bidirectional or not?');
						$helptext2 = L10n::t('Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.', $notif['name'], $notif['name']);
						$helptext3 = L10n::t('Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.', $notif['name']);
					} elseif ($notif['network'] === Protocol::DIASPORA) {
						$helptext  = L10n::t('Shall your connection be bidirectional or not?');
						$helptext2 = L10n::t('Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.', $notif['name'], $notif['name']);
						$helptext3 = L10n::t('Accepting %s as a sharer allows them to subscribe to your posts, but you will not receive updates from them in your news feed.', $notif['name']);
					}

					$dfrn_tpl = Renderer::getMarkupTemplate('netfriend.tpl');
					$dfrn_text = Renderer::replaceMacros($dfrn_tpl, [
						'$intro_id'    => $notif['intro_id'],
						'$friend_selected' => $friend_selected,
						'$fan_selected'=> $fan_selected,
						'$approve_as1' => $helptext,
						'$approve_as2' => $helptext2,
						'$approve_as3' => $helptext3,
						'$as_friend'   => L10n::t('Friend'),
						'$as_fan'      => (($notif['network'] == Protocol::DIASPORA) ? L10n::t('Sharer') : L10n::t('Subscriber'))
					]);

					$contact = DBA::selectFirst('contact', ['network', 'protocol'], ['id' => $notif['contact_id']]);

					if (($contact['network'] != Protocol::DFRN) || ($contact['protocol'] == Protocol::ACTIVITYPUB)) {
						$action = 'follow_confirm';
					} else {
						$action = 'dfrn_confirm';
					}

					$header = $notif['name'];

					if ($notif['addr'] != '') {
						$header .= ' <' . $notif['addr'] . '>';
					}

					$header .= ' (' . ContactSelector::networkToName($notif['network'], $notif['url']) . ')';

					if ($notif['network'] != Protocol::DIASPORA) {
						$discard = L10n::t('Discard');
					} else {
						$discard = '';
					}

					$notif_content[] = Renderer::replaceMacros($tpl, [
						'$type'        => $notif['label'],
						'$header'      => $header,
						'$str_notifytype' => L10n::t('Notification type:'),
						'$notify_type' => $notif['notify_type'],
						'$dfrn_text'   => $dfrn_text,
						'$dfrn_id'     => $notif['dfrn_id'],
						'$uid'         => $notif['uid'],
						'$intro_id'    => $notif['intro_id'],
						'$contact_id'  => $notif['contact_id'],
						'$photo'       => $notif['photo'],
						'$fullname'    => $notif['name'],
						'$location'    => $notif['location'],
						'$lbl_location'=> L10n::t('Location:'),
						'$about'       => $notif['about'],
						'$lbl_about'   => L10n::t('About:'),
						'$keywords'    => $notif['keywords'],
						'$lbl_keywords'=> L10n::t('Tags:'),
						'$gender'      => $notif['gender'],
						'$lbl_gender'  => L10n::t('Gender:'),
						'$hidden'      => ['hidden', L10n::t('Hide this contact from others'), ($notif['hidden'] == 1), ''],
						'$url'         => $notif['url'],
						'$zrl'         => $notif['zrl'],
						'$lbl_url'     => L10n::t('Profile URL'),
						'$addr'        => $notif['addr'],
						'$lbl_knowyou' => $lbl_knowyou,
						'$lbl_network' => L10n::t('Network:'),
						'$network'     => ContactSelector::networkToName($notif['network'], $notif['url']),
						'$knowyou'     => $knowyou,
						'$approve'     => L10n::t('Approve'),
						'$note'        => $notif['note'],
						'$ignore'      => L10n::t('Ignore'),
						'$discard'     => $discard,
						'$action'      => $action,
					]);
					break;
			}
		}

		if (count($notifs['notifications']) == 0) {
			info(L10n::t('No introductions.') . EOL);
		}

		// Normal notifications (no introductions)
	} elseif (!empty($notifs['notifications'])) {
		// Loop trough ever notification This creates an array with the output html for each
		// notification and apply the correct template according to the notificationtype (label).
		foreach ($notifs['notifications'] as $notif) {
			$notification_templates = [
				'like'        => 'notifications_likes_item.tpl',
				'dislike'     => 'notifications_dislikes_item.tpl',
				'attend'      => 'notifications_attend_item.tpl',
				'attendno'    => 'notifications_attend_item.tpl',
				'attendmaybe' => 'notifications_attend_item.tpl',
				'friend'      => 'notifications_friends_item.tpl',
				'comment'     => 'notifications_comments_item.tpl',
				'post'        => 'notifications_posts_item.tpl',
				'notify'      => 'notify.tpl',
			];

			$tpl_notif = Renderer::getMarkupTemplate($notification_templates[$notif['label']]);

			$notif_content[] = Renderer::replaceMacros($tpl_notif, [
				'$item_label' => $notif['label'],
				'$item_link'  => $notif['link'],
				'$item_image' => $notif['image'],
				'$item_url'   => $notif['url'],
				'$item_text'  => $notif['text'],
				'$item_when'  => $notif['when'],
				'$item_ago'   => $notif['ago'],
				'$item_seen'  => $notif['seen'],
			]);
		}
	} else {
		$notif_nocontent = L10n::t('No more %s notifications.', $notifs['ident']);
	}

	$o .= Renderer::replaceMacros($notif_tpl, [
		'$notif_header'    => $notif_header,
		'$tabs'            => $tabs,
		'$notif_content'   => $notif_content,
		'$notif_nocontent' => $notif_nocontent,
		'$notif_show_lnk'  => $notif_show_lnk,
		'$notif_paginate'  => $pager->renderMinimal(count($notif_content))
	]);

	return $o;
}
