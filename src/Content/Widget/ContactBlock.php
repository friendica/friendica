<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Content\Widget;

use Friendica\Content\Text\HTML;
use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\User;

/**
 * ContactBlock widget
 *
 * @author Hypolite Petovan
 */
class ContactBlock
{
	/**
	 * Get HTML for contact block
	 *
	 * @template widget/contacts.tpl
	 * @hook contact_block_end (contacts=>array, output=>string)
	 * @return string Formatted HTML code or empty string
	 */
	public static function getHTML(array $profile, int $visitor_uid = null): string
	{
		$o = '';

		if (is_null($visitor_uid) || ($visitor_uid == $profile['uid'])) {
			$contact_uid = $profile['uid'];
		} else {
			$contact_uid = 0;
		}

		$shown = DI::pConfig()->get($profile['uid'], 'system', 'display_friend_count', 24);
		if ($shown == 0) {
			return $o;
		}

		if (!empty($profile['hide-friends'])) {
			return $o;
		}

		$contacts = [];

		$total = DBA::count('contact', [
			'uid'     => $profile['uid'],
			'self'    => false,
			'blocked' => false,
			'pending' => false,
			'hidden'  => false,
			'archive' => false,
			'failed'  => false,
			'network' => [Protocol::DFRN, Protocol::ACTIVITYPUB, Protocol::OSTATUS, Protocol::DIASPORA, Protocol::FEED],
		]);

		$contacts_title = DI::l10n()->t('No contacts');

		$micropro = [];

		if ($total) {
			// Only show followed for personal accounts, followers for pages
			if ((($profile['account-type'] ?? '') ?: User::ACCOUNT_TYPE_PERSON) == User::ACCOUNT_TYPE_PERSON) {
				$rel = [Contact::SHARING, Contact::FRIEND];
			} else {
				$rel = [Contact::FOLLOWER, Contact::FRIEND];
			}

			$personal_contacts = DBA::selectToArray('contact', ['uri-id'], [
				'uid'     => $profile['uid'],
				'self'    => false,
				'blocked' => false,
				'pending' => false,
				'hidden'  => false,
				'archive' => false,
				'rel'     => $rel,
				'network' => Protocol::FEDERATED,
			], [
				'limit' => $shown,
			]);

			$contact_uriids = array_column($personal_contacts, 'uri-id');

			if (!empty($contact_uriids)) {
				$contacts_stmt = DBA::select('contact', ['id', 'uid', 'addr', 'url', 'name', 'thumb', 'avatar', 'network'], ['uri-id' => $contact_uriids, 'uid' => $contact_uid]);

				if (DBA::isResult($contacts_stmt)) {
					$contacts_title = DI::l10n()->tt('%d Contact', '%d Contacts', $total);
					$micropro = [];

					while ($contact = DBA::fetch($contacts_stmt)) {
						$contacts[] = $contact;
						$micropro[] = HTML::micropro($contact, true, 'mpfriend');
					}
				}

				DBA::close($contacts_stmt);
			}
		}

		$tpl = Renderer::getMarkupTemplate('widget/contacts.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$contacts' => $contacts_title,
			'$nickname' => $profile['nickname'],
			'$viewcontacts' => DI::l10n()->t('View Contacts'),
			'$micropro' => $micropro,
		]);

		$arr = ['contacts' => $contacts, 'output' => $o];

		Hook::callAll('contact_block_end', $arr);

		return $o;
	}
}
