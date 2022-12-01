<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Profile;

use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module;
use Friendica\Network\HTTPException;

class Contacts extends Module\BaseProfile
{
	protected function content(array $request = []): string
	{
		if (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated()) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$a = DI::app();

		$nickname = $this->parameters['nickname'];
		$type = $this->parameters['type'] ?? 'all';

		$profile = Model\Profile::load($a, $nickname);
		if (empty($profile)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$is_owner = $profile['uid'] == DI::userSession()->getLocalUserId();

		if ($profile['hide-friends'] && !$is_owner) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		Nav::setSelected('home');

		$o = self::getTabsHTML('contacts', $is_owner, $profile['nickname'], $profile['hide-friends']);

		$tabs = self::getContactFilterTabs('profile/' . $nickname, $type, DI::userSession()->isAuthenticated() && $profile['uid'] != DI::userSession()->getLocalUserId());

		$condition = [
			'uid'     => $profile['uid'],
			'blocked' => false,
			'pending' => false,
			'hidden'  => false,
			'archive' => false,
			'failed'  => false,
			'self'    => false,
			'network' => [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED]
		];

		switch ($type) {
			case 'followers': $condition['rel'] = [Model\Contact::FOLLOWER, Model\Contact::FRIEND]; break;
			case 'following': $condition['rel'] = [Model\Contact::SHARING,  Model\Contact::FRIEND]; break;
			case 'mutuals':   $condition['rel'] = Model\Contact::FRIEND; break;
		}

		$total = DBA::count('contact', $condition);

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 30);

		$params = ['order' => ['name' => false], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

		$contacts = array_map(
			[Module\Contact::class, 'getContactTemplateVars'],
			Model\Contact::selectToArray([], $condition, $params)
		);

		$desc = '';
		switch ($type) {
			case 'followers':
				$title = DI::l10n()->tt('Follower (%s)', 'Followers (%s)', $total);
				break;
			case 'following':
				$title = DI::l10n()->tt('Following (%s)', 'Following (%s)', $total);
				break;
			case 'mutuals':
				$title = DI::l10n()->tt('Mutual friend (%s)', 'Mutual friends (%s)', $total);
				$desc = DI::l10n()->t(
					'These contacts both follow and are followed by <strong>%s</strong>.',
					htmlentities($profile['name'], ENT_COMPAT, 'UTF-8')
				);
				break;
			case 'all':
			default:
				$title = DI::l10n()->tt('Contact (%s)', 'Contacts (%s)', $total);
				break;
		}

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $title,
			'$desc'     => $desc,
			'$tabs'     => $tabs,

			'$noresult_label'  => DI::l10n()->t('No contacts.'),

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
