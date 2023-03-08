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
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class Unfollow extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $userSession;

	/** @var SystemMessages */
	private $systemMessages;

	/** @var Database */
	private $database;

	/** @var App\Page */
	private $page;

	public function __construct(App\Page $page, Database $database, SystemMessages $systemMessages, IHandleUserSessions $userSession, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->userSession    = $userSession;
		$this->systemMessages = $systemMessages;
		$this->database       = $database;
		$this->page           = $page;
	}

	protected function post(array $request = [])
	{
		if (!$this->userSession->getLocalUserId()) {
			$this->systemMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect('login');
		}

		$url = trim($request['url'] ?? '');

		$this->process($url);
	}

	protected function content(array $request = []): string
	{
		$base_return_path = 'contact';

		if (!$this->userSession->getLocalUserId()) {
			$this->systemMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect('login');
		}

		$uid = $this->userSession->getLocalUserId();
		$url = trim($request['url']);

		$condition = [
			"`uid` = ?
			AND (`rel` = ? OR `rel` = ?)
			AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
			$this->userSession->getLocalUserId(),
			Contact::SHARING, Contact::FRIEND,
			Strings::normaliseLink($url), Strings::normaliseLink($url), $url,
		];

		$contact = $this->database->selectFirst('contact', ['url', 'id', 'uid', 'network', 'addr', 'name'], $condition);
		if (!$this->database->isResult($contact)) {
			$this->systemMessages->addNotice($this->t("You aren't following this contact."));
			$this->baseUrl->redirect($base_return_path);
		}

		if (!Protocol::supportsFollow($contact['network'])) {
			$this->systemMessages->addNotice($this->t('Unfollowing is currently not supported by your network.'));
			$this->baseUrl->redirect($base_return_path . '/' . $contact['id']);
		}

		$tpl = Renderer::getMarkupTemplate('auto_request.tpl');

		$self = $this->database->selectFirst('contact', ['url'], ['uid' => $uid, 'self' => true]);

		if (!$this->database->isResult($self)) {
			$this->systemMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect($base_return_path);
		}

		if (!empty($request['auto'])) {
			$this->process($contact['url']);
		}

		$o = Renderer::replaceMacros($tpl, [
			'$header'         => $this->t('Disconnect/Unfollow'),
			'$page_desc'      => '',
			'$your_address'   => $this->t('Your Identity Address:'),
			'$invite_desc'    => '',
			'$submit'         => $this->t('Submit Request'),
			'$cancel'         => $this->t('Cancel'),
			'$url'            => $contact['url'],
			'$zrl'            => Contact::magicLinkByContact($contact),
			'$url_label'      => $this->t('Profile URL'),
			'$myaddr'         => $self['url'],
			'$action'         => $this->baseUrl . '/contact/unfollow',
			'$keywords'       => '',
			'$keywords_label' => ''
		]);

		$this->page['aside'] = Widget\VCard::getHTML(Contact::getByURL($contact['url'], false));

		$o .= Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'), ['$title' => $this->t('Posts and Replies')]);

		// Show last public posts
		$o .= Contact::getPostsFromUrl($contact['url']);

		return $o;
	}

	private function process(string $url): void
	{
		$base_return_path = 'contact';

		$uid = $this->userSession->getLocalUserId();

		$owner = User::getOwnerDataById($uid);
		if (!$owner) {
			throw new \Friendica\Network\HTTPException\NotFoundException();
		}

		$condition = [
			"`uid` = ?
			AND (`rel` = ? OR `rel` = ?)
			AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
			$uid, Contact::SHARING, Contact::FRIEND,
			Strings::normaliseLink($url), Strings::normaliseLink($url), $url,
		];
		$contact = $this->database->selectFirst('contact', [], $condition);

		if (!$this->database->isResult($contact)) {
			$this->systemMessages->addNotice($this->t("You aren't following this contact."));
			$this->baseUrl->redirect($base_return_path);
		}

		$return_path = $base_return_path . '/' . $contact['id'];

		try {
			Contact::unfollow($contact);
			$notice_message = $this->t('Contact was successfully unfollowed');
		} catch (\Exception $e) {
			$this->logger->error($e->getMessage(), ['contact' => $contact]);
			$notice_message = $this->t('Unable to unfollow this contact, please contact your administrator');
		}

		$this->systemMessages->addNotice($notice_message);
		$this->baseUrl->redirect($return_path);
	}
}
