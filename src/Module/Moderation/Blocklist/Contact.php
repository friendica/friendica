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

namespace Friendica\Module\Moderation\Blocklist;

use Friendica\App;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\Model;
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Contact extends BaseModeration
{
	/** @var Database */
	private $database;

	public function __construct(Database $database, App\Page $page, App $app, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $app, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	protected function post(array $request = [])
	{
		$this->checkModerationAccess();

		self::checkFormSecurityTokenRedirectOnError('/moderation/blocklist/contact', 'moderation_contactblock');

		$contact_url  = $request['contact_url']          ?? '';
		$block_reason = $request['contact_block_reason'] ?? '';
		$block_purge  = $request['contact_block_purge']  ?? false;
		$contacts     = $request['contacts']             ?? [];

		if (!empty($request['page_contactblock_block'])) {
			$contact = Model\Contact::getByURL($contact_url, null, ['id', 'nurl']);
			if (empty($contact)) {
				$this->systemMessages->addNotice($this->t('Could not find any contact entry for this URL (%s)', $contact_url));
				$this->baseUrl->redirect('moderation/blocklist/contact');
			}

			if (Network::isLocalLink($contact['nurl'])) {
				$this->systemMessages->addNotice($this->t('You can\'t block a local contact, please block the user instead'));
				$this->baseUrl->redirect('moderation/blocklist/contact');
			}

			Model\Contact::block($contact['id'], $block_reason);

			if ($block_purge) {
				foreach (Model\Contact::selectToArray(['id'], ['nurl' => $contact['nurl']]) as $contact) {
					Worker::add(Worker::PRIORITY_LOW, 'Contact\RemoveContent', $contact['id']);
				}
			}

			$this->systemMessages->addInfo($this->t('The contact has been blocked from the node'));
		}

		if (!empty($request['page_contactblock_unblock'])) {
			foreach ($contacts as $uid) {
				Model\Contact::unblock($uid);
			}
			$this->systemMessages->addInfo($this->tt('%s contact unblocked', '%s contacts unblocked', count($contacts)));
		}

		$this->baseUrl->redirect('moderation/blocklist/contact');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$condition = ['uid' => 0, 'blocked' => true];

		$total = $this->database->count('contact', $condition);

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 30);

		$contacts = Model\Contact::selectToArray([], $condition, ['limit' => [$pager->getStart(), $pager->getItemsPerPage()]]);

		$t = Renderer::getMarkupTemplate('moderation/blocklist/contact.tpl');
		return Renderer::replaceMacros($t, [
			// strings //
			'$title'       => $this->t('Moderation'),
			'$page'        => $this->t('Remote Contact Blocklist'),
			'$description' => $this->t('This page allows you to prevent any message from a remote contact to reach your node.'),
			'$submit'      => $this->t('Block Remote Contact'),
			'$select_all'  => $this->t('select all'),
			'$select_none' => $this->t('select none'),
			'$block'       => $this->t('Block'),
			'$unblock'     => $this->t('Unblock'),
			'$no_data'     => $this->t('No remote contact is blocked from this node.'),

			'$h_contacts'  => $this->t('Blocked Remote Contacts'),
			'$h_newblock'  => $this->t('Block New Remote Contact'),
			'$th_contacts' => [$this->t('Photo'), $this->t('Name'), $this->t('Reason')],

			'$form_security_token' => self::getFormSecurityToken('moderation_contactblock'),

			// values //
			'$baseurl' => $this->baseUrl,

			'$contacts'       => $contacts,
			'$total_contacts' => $this->tt('%s total blocked contact', '%s total blocked contacts', $total),
			'$paginate'       => $pager->renderFull($total),

			'$contacturl'           => ['contact_url', $this->t('Profile URL'), '', $this->t('URL of the remote contact to block.')],
			'$contact_block_purge'  => ['contact_block_purge', $this->t('Also purge contact'), false, $this->t('Removes all content related to this contact from the node. Keeps the contact record. This action cannot be undone.')],
			'$contact_block_reason' => ['contact_block_reason', $this->t('Block Reason')],
		]);
	}
}
