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

namespace Friendica\Module\Contact;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model;
use Friendica\Model\User;
use Friendica\Module;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Contacts extends BaseModule
{
	/** @var IHandleUserSessions */
	private $userSession;
	/** @var App\Page */
	private $page;

	public function __construct(App\Page $page, IHandleUserSessions $userSession, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->userSession = $userSession;
		$this->page = $page;
	}

	protected function content(array $request = []): string
	{
		if (!$this->userSession->getLocalUserId()) {
			throw new HTTPException\ForbiddenException();
		}

		$cid = $this->parameters['id'];
		$type = $this->parameters['type'] ?? 'all';
		$accounttype = $request['accounttype'] ?? '';
		$accounttypeid = User::getAccountTypeByString($accounttype);

		if (!$cid) {
			throw new HTTPException\BadRequestException($this->t('Invalid contact.'));
		}

		$contact = Model\Contact::getById($cid, []);
		if (empty($contact)) {
			throw new HTTPException\NotFoundException($this->t('Contact not found.'));
		}

		$localContactId = Model\Contact::getPublicIdByUserId($this->userSession->getLocalUserId());

		$this->page['aside'] = Widget\VCard::getHTML($contact);

		$condition = [
			'blocked' => false,
			'self'    => false,
			'hidden'  => false,
			'failed'  => false,
		];

		if (isset($accounttypeid)) {
			$condition['contact-type'] = $accounttypeid;
		}

		$noresult_label = $this->t('No known contacts.');

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
				$noresult_label = $this->t('No common contacts.');
				break;
			default:
				$total = Model\Contact\Relation::countAll($cid, $condition);
		}

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 30);
		$desc = '';

		switch ($type) {
			case 'followers':
				$friends = Model\Contact\Relation::listFollowers($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = $this->tt('Follower (%s)', 'Followers (%s)', $total);
				break;
			case 'following':
				$friends = Model\Contact\Relation::listFollows($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = $this->tt('Following (%s)', 'Following (%s)', $total);
				break;
			case 'mutuals':
				$friends = Model\Contact\Relation::listMutuals($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = $this->tt('Mutual friend (%s)', 'Mutual friends (%s)', $total);
				$desc = $this->t(
					'These contacts both follow and are followed by <strong>%s</strong>.',
					htmlentities($contact['name'], ENT_COMPAT, 'UTF-8')
				);
				break;
			case 'common':
				$friends = Model\Contact\Relation::listCommon($localContactId, $cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = $this->tt('Common contact (%s)', 'Common contacts (%s)', $total);
				$desc = $this->t(
					'Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).',
					htmlentities($contact['name'], ENT_COMPAT, 'UTF-8')
				);
				break;
			default:
				$friends = Model\Contact\Relation::listAll($cid, $condition, $pager->getItemsPerPage(), $pager->getStart());
				$title = $this->tt('Contact (%s)', 'Contacts (%s)', $total);
		}

		$o = Module\Contact::getTabsHTML($contact, Module\Contact::TAB_CONTACTS);

		$tabs = self::getContactFilterTabs('contact/' . $cid, $type, true);

		// Contact list is obtained from the visited contact, but the contact display is visitor dependent
		$contacts = array_map(
			function ($contact) {
				$contact = Model\Contact::selectFirst(
					[],
					['uri-id' => $contact['uri-id'], 'uid' => [0, $this->userSession->getLocalUserId()]],
					['order' => ['uid' => 'DESC']]
				);
				return Module\Contact::getContactTemplateVars($contact);
			},
			$friends
		);

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $title,
			'$desc'     => $desc,
			'$tabs'     => $tabs,

			'$noresult_label'  => $noresult_label,

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		$this->page['aside'] .= Widget::accountTypes($_SERVER['REQUEST_URI'], $accounttype);

		return $o;
	}
}
