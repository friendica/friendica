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
use Friendica\Module;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Module\BaseProfile;
use Friendica\Network\HTTPException;

class Common extends BaseProfile
{
	protected function content(array $request = []): string
	{
		if (DI::config()->get('system', 'block_public') && !DI::userSession()->isAuthenticated()) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		$a = DI::app();

		Nav::setSelected('home');

		$nickname = $this->parameters['nickname'];

		$profile = Profile::load($a, $nickname);
		if (empty($profile)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('User not found.'));
		}

		if (!empty($profile['hide-friends'])) {
			throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
		}

		$displayCommonTab = DI::userSession()->isAuthenticated() && $profile['uid'] != DI::userSession()->getLocalUserId();

		if (!$displayCommonTab) {
			$a->redirect('profile/' . $nickname . '/contacts');
		};

		$o = self::getTabsHTML('contacts', false, $profile['nickname'], $profile['hide-friends']);

		$tabs = self::getContactFilterTabs('profile/' . $nickname, 'common', $displayCommonTab);

		$sourceId = Contact::getIdForURL(Profile::getMyURL());
		$targetId = Contact::getPublicIdByUserId($profile['uid']);

		$condition = [
			'blocked' => false,
			'deleted' => false,
			'network' => [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED],
		];

		$total = Contact\Relation::countCommon($sourceId, $targetId, $condition);

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 30);

		$commonFollows = Contact\Relation::listCommon($sourceId, $targetId, $condition, $pager->getItemsPerPage(), $pager->getStart());

		$contacts = array_map([Module\Contact::class, 'getContactTemplateVars'], $commonFollows);

		$title = DI::l10n()->tt('Common contact (%s)', 'Common contacts (%s)', $total);
		$desc = DI::l10n()->t(
			'Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).',
			htmlentities($profile['name'], ENT_COMPAT, 'UTF-8')
		);

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $title,
			'$desc'     => $desc,
			'$tabs'     => $tabs,

			'$noresult_label'  => DI::l10n()->t('No common contacts.'),

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
