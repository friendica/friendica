<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Module\Notifications;

use Friendica\Content\ContactSelector;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseNotifications;
use Friendica\Object\Notification\Introduction;

/**
 * Prints notifications about introduction
 */
class Introductions extends BaseNotifications
{
	/**
	 * @inheritDoc
	 */
	public static function getNotifications()
	{
		$id  = (int)DI::args()->get(2, 0);
		$all = DI::args()->get(2) == 'all';

		$notifications = [
			'ident'         => 'introductions',
			'notifications' => DI::notificationIntro()->getList($all, self::$firstItemNum, self::ITEMS_PER_PAGE, $id),
		];

		return [
			'header'        => DI::l10n()->t('Notifications'),
			'notifications' => $notifications,
		];
	}

	public static function content(array $parameters = [])
	{
		Nav::setSelected('introductions');

		$all = DI::args()->get(2) == 'all';

		$notificationContent   = [];
		$notificationNoContent = '';

		$notificationResult = self::getNotifications();
		$notifications      = $notificationResult['notifications'] ?? [];
		$notificationHeader = $notificationResult['header'] ?? '';

		$notificationSuggestions = Renderer::getMarkupTemplate('notifications/suggestions.tpl');
		$notificationTemplate    = Renderer::getMarkupTemplate('notifications/intros.tpl');

		// The link to switch between ignored and normal connection requests
		$notificationShowLink = [
			'href' => (!$all ? 'notifications/intros/all' : 'notifications/intros'),
			'text' => (!$all ? DI::l10n()->t('Show Ignored Requests') : DI::l10n()->t('Hide Ignored Requests')),
		];

		// Loop through all introduction notifications.This creates an array with the output html for each
		// introduction
		/** @var Introduction $notification */
		foreach ($notifications['notifications'] as $notification) {

			// There are two kind of introduction. Contacts suggested by other contacts and normal connection requests.
			// We have to distinguish between these two because they use different data.
			switch ($notification->getLabel()) {
				case 'friend_suggestion':
					$notificationContent[] = Renderer::replaceMacros($notificationSuggestions, [
						'$type'                  => $notification->getLabel(),
						'$str_notification_type' => DI::l10n()->t('Notification type:'),
						'$str_type'              => $notification->getType(),
						'$intro_id'              => $notification->getIntroId(),
						'$lbl_madeby'            => DI::l10n()->t('Suggested by:'),
						'$madeby'                => $notification->getMadeBy(),
						'$madeby_url'            => $notification->getMadeByUrl(),
						'$madeby_zrl'            => $notification->getMadeByZrl(),
						'$madeby_addr'           => $notification->getMadeByAddr(),
						'$contact_id'            => $notification->getContactId(),
						'$photo'                 => $notification->getPhoto(),
						'$fullname'              => $notification->getName(),
						'$url'                   => $notification->getUrl(),
						'$zrl'                   => $notification->getZrl(),
						'$lbl_url'               => DI::l10n()->t('Profile URL'),
						'$addr'                  => $notification->getAddr(),
						'$hidden'                => ['hidden', DI::l10n()->t('Hide this contact from others'), $notification->isHidden(), ''],
						'$knowyou'               => $notification->getKnowYou(),
						'$approve'               => DI::l10n()->t('Approve'),
						'$note'                  => $notification->getNote(),
						'$request'               => $notification->getRequest(),
						'$ignore'                => DI::l10n()->t('Ignore'),
						'$discard'               => DI::l10n()->t('Discard'),
					]);
					break;

				// Normal connection requests
				default:
					if ($notification->getNetwork() === Protocol::DFRN) {
						$lbl_knowyou = DI::l10n()->t('Claims to be known to you: ');
						$knowyou     = ($notification->getKnowYou() ? DI::l10n()->t('Yes') : DI::l10n()->t('No'));
					} else {
						$lbl_knowyou = '';
						$knowyou = '';
					}

					$convertedName = BBCode::convert($notification->getName());

					$helptext  = DI::l10n()->t('Shall your connection be bidirectional or not?');
					$helptext2 = DI::l10n()->t('Accepting %s as a friend allows %s to subscribe to your posts, and you will also receive updates from them in your news feed.', $convertedName, $convertedName);
					$helptext3 = DI::l10n()->t('Accepting %s as a subscriber allows them to subscribe to your posts, but you will not receive updates from them in your news feed.', $convertedName);

					$friend = ['duplex', DI::l10n()->t('Friend'), '1', $helptext2, true];
					$follower = ['duplex', DI::l10n()->t('Subscriber'), '0', $helptext3, false];

					$contact = DBA::selectFirst('contact', ['network', 'protocol'], ['id' => $notification->getContactId()]);

					if (($contact['network'] != Protocol::DFRN) || ($contact['protocol'] == Protocol::ACTIVITYPUB)) {
						$action = 'follow_confirm';
					} else {
						$action = 'dfrn_confirm';
					}

					$header = $notification->getName();

					if ($notification->getAddr() != '') {
						$header .= ' <' . $notification->getAddr() . '>';
					}

					$header .= ' (' . ContactSelector::networkToName($notification->getNetwork(), $notification->getUrl()) . ')';

					if ($notification->getNetwork() != Protocol::DIASPORA) {
						$discard = DI::l10n()->t('Discard');
					} else {
						$discard = '';
					}

					$notificationContent[] = Renderer::replaceMacros($notificationTemplate, [
						'$type'                  => $notification->getLabel(),
						'$header'                => $header,
						'$str_notification_type' => DI::l10n()->t('Notification type:'),
						'$str_type'              => $notification->getType(),
						'$dfrn_id'               => $notification->getDfrnId(),
						'$uid'                   => $notification->getUid(),
						'$intro_id'              => $notification->getIntroId(),
						'$contact_id'            => $notification->getContactId(),
						'$photo'                 => $notification->getPhoto(),
						'$fullname'              => $notification->getName(),
						'$location'              => $notification->getLocation(),
						'$lbl_location'          => DI::l10n()->t('Location:'),
						'$about'                 => $notification->getAbout(),
						'$lbl_about'             => DI::l10n()->t('About:'),
						'$keywords'              => $notification->getKeywords(),
						'$lbl_keywords'          => DI::l10n()->t('Tags:'),
						'$hidden'                => ['hidden', DI::l10n()->t('Hide this contact from others'), $notification->isHidden(), ''],
						'$lbl_connection_type'   => $helptext,
						'$friend'                => $friend,
						'$follower'              => $follower,
						'$url'                   => $notification->getUrl(),
						'$zrl'                   => $notification->getZrl(),
						'$lbl_url'               => DI::l10n()->t('Profile URL'),
						'$addr'                  => $notification->getAddr(),
						'$lbl_knowyou'           => $lbl_knowyou,
						'$lbl_network'           => DI::l10n()->t('Network:'),
						'$network'               => ContactSelector::networkToName($notification->getNetwork(), $notification->getUrl()),
						'$knowyou'               => $knowyou,
						'$approve'               => DI::l10n()->t('Approve'),
						'$note'                  => $notification->getNote(),
						'$ignore'                => DI::l10n()->t('Ignore'),
						'$discard'               => $discard,
						'$action'                => $action,
					]);
					break;
			}
		}

		if (count($notifications['notifications']) == 0) {
			notice(DI::l10n()->t('No introductions.'));
			$notificationNoContent = DI::l10n()->t('No more %s notifications.', $notifications['ident']);
		}

		return self::printContent($notificationHeader, $notificationContent, $notificationNoContent, $notificationShowLink);
	}
}
