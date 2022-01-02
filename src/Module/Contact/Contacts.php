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

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\User;
use Friendica\Module;
use Friendica\Network\HTTPException;

class Contacts extends BaseModule
{
	protected function content(array $request = []): string
	{
		$app = DI::app();

		if (!local_user()) {
			throw new HTTPException\ForbiddenException();
		}

		$cid = $this->parameters['id'];
		$type = $this->parameters['type'] ?? 'all';
		$accounttype = $_GET['accounttype'] ?? '';
		$accounttypeid = User::getAccountTypeByString($accounttype);

		if (!$cid) {
			throw new HTTPException\BadRequestException(DI::l10n()->t('Invalid contact.'));
		}

		$contact = Model\Contact::getById($cid, []);
		if (empty($contact)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Contact not found.'));
		}

		$localContactId = Model\Contact::getPublicIdByUserId(local_user());

		DI::page()['aside'] = Widget\VCard::getHTML($contact);

		$condition = [
			'blocked' => false,
			'self' => false,
			'hidden' => false,
			'failed' => false,
		];

		if (isset($accounttypeid)) {
			$condition['contact-type'] = $accounttypeid;
		}

		$noresult_label = DI::l10n()->t('No known contacts.');

		switch ($type) {
			case 'followers':
				$total = Model\Contact\Relation::countFollowers($cid, $condition);
				break;
			case 'following':
				$total = Model\Contact\Relation::countFollows($cid, $condition);
				break;
			case 'mutuals':
				$total = Model\Contact\Relation::countMutuals($cid, $condition);
				break;
			case 'common':
				$total = Model\Contact\Relation::countCommon($localContactId, $cid, $condition);
				$noresult_label = DI::l10n()->t('No common contacts.');
				break;
			default:
				$total = Model\Contact\Relation::countAll($cid, $condition);
		}

		$pager = new Pager(DI::l10n(), DI::args()->getQueryString(), 30);
		$desc = '';

		switch ($type) {
			case 'followers':
				$friends = Model\Contact\Relation::listFollowers($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Follower (%s)', 'Followers (%s)', $total);
				break;
			case 'following':
				$friends = Model\Contact\Relation::listFollows($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Following (%s)', 'Following (%s)', $total);
				break;
			case 'mutuals':
				$friends = Model\Contact\Relation::listMutuals($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Mutual friend (%s)', 'Mutual friends (%s)', $total);
				$desc = DI::l10n()->t(
					'These contacts both follow and are followed by <strong>%s</strong>.',
					htmlentities($contact['name'], ENT_COMPAT, 'UTF-8')
				);
				break;
			case 'common':
				$friends = Model\Contact\Relation::listCommon($localContactId, $cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Common contact (%s)', 'Common contacts (%s)', $total);
				$desc = DI::l10n()->t(
					'Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).',
					htmlentities($contact['name'], ENT_COMPAT, 'UTF-8')
				);
				break;
			default:
				$friends = Model\Contact\Relation::listAll($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = DI::l10n()->tt('Contact (%s)', 'Contacts (%s)', $total);
		}

		$o = Module\Contact::getTabsHTML($contact, Module\Contact::TAB_CONTACTS);

		$tabs = self::getContactFilterTabs('contact/' . $cid, $type, true);

		$contacts = array_map([Module\Contact::class, 'getContactTemplateVars'], $friends);

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $title,
			'$desc'     => $desc,
			'$tabs'     => $tabs,

			'$noresult_label'  => $noresult_label,

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		DI::page()['aside'] .= Widget::accounttypes($_SERVER['REQUEST_URI'], $accounttype);

		return $o;
	}
}
