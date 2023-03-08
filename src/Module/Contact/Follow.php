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
use Friendica\Content\Widget\VCard;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\Probe;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

class Follow extends BaseModule
{
	/** @var IHandleUserSessions */
	protected $session;
	/** @var SystemMessages */
	protected $sysMessages;
	/** @var IManageConfigValues */
	protected $config;
	/** @var App\Page */
	protected $page;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, SystemMessages $sysMessages, IManageConfigValues $config, App\Page $page, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session     = $session;
		$this->sysMessages = $sysMessages;
		$this->config      = $config;
		$this->page        = $page;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			throw new ForbiddenException($this->t('Access denied.'));
		}

		if (isset($request['cancel']) || empty($request['url'])) {
			$this->baseUrl->redirect('contact');
		}

		$url = Probe::cleanURI($request['url']);

		$this->process($url);
	}

	protected function content(array $request = []): string
	{
		$returnPath = 'contact';

		if (!$this->session->getLocalUserId()) {
			$this->sysMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect($returnPath);
		}

		$uid = $this->session->getLocalUserId();

		// uri is used by the /authorize_interaction Mastodon route
		$url = Probe::cleanURI(trim($request['uri'] ?? $request['url'] ?? ''));

		// Issue 6874: Allow remote following from Peertube
		if (strpos($url, 'acct:') === 0) {
			$url = str_replace('acct:', '', $url);
		}

		if (empty($url)) {
			$this->baseUrl->redirect($returnPath);
		}

		$submit = $this->t('Submit Request');

		// Don't try to add a pending contact
		$userContact = Contact::selectFirst(['pending'], [
			"`uid` = ? AND ((`rel` != ?) OR (`network` = ?)) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)",
			$uid, Contact::FOLLOWER, Protocol::DFRN,
			Strings::normaliseLink($url),
			Strings::normaliseLink($url), $url]);

		if (!empty($userContact['pending'])) {
			$this->sysMessages->addNotice($this->t('You already added this contact.'));
			$submit = '';
		}

		$contact = Contact::getByURL($url, true);

		// Possibly it is a mail contact
		if (empty($contact)) {
			$contact = Probe::uri($url, Protocol::MAIL, $uid);
		}

		if (empty($contact) || ($contact['network'] == Protocol::PHANTOM)) {
			// Possibly it is a remote item and not an account
			$this->followRemoteItem($url);

			$this->sysMessages->addNotice($this->t('The network type couldn\'t be detected. Contact can\'t be added.'));
			$submit  = '';
			$contact = ['url' => $url, 'network' => Protocol::PHANTOM, 'name' => $url, 'keywords' => ''];
		}

		$protocol = Contact::getProtocol($contact['url'], $contact['network']);

		if (($protocol == Protocol::DIASPORA) && !$this->config->get('system', 'diaspora_enabled')) {
			$this->sysMessages->addNotice($this->t('Diaspora support isn\'t enabled. Contact can\'t be added.'));
			$submit = '';
		}

		if (($protocol == Protocol::OSTATUS) && $this->config->get('system', 'ostatus_disabled')) {
			$this->sysMessages->addNotice($this->t("OStatus support is disabled. Contact can't be added."));
			$submit = '';
		}

		if ($protocol == Protocol::MAIL) {
			$contact['url'] = $contact['addr'];
		}

		if (!empty($request['auto'])) {
			$this->process($contact['url']);
		}

		$requestUrl = $this->baseUrl . '/contact/follow';
		$tpl        = Renderer::getMarkupTemplate('auto_request.tpl');

		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			$this->sysMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect($returnPath);
		}

		$myaddr = $owner['url'];

		$output = Renderer::replaceMacros($tpl, [
			'$header'         => $this->t('Connect/Follow'),
			'$pls_answer'     => $this->t('Please answer the following:'),
			'$your_address'   => $this->t('Your Identity Address:'),
			'$url_label'      => $this->t('Profile URL'),
			'$keywords_label' => $this->t('Tags:'),
			'$submit'         => $submit,
			'$cancel'         => $this->t('Cancel'),

			'$action'   => $requestUrl,
			'$name'     => $contact['name'],
			'$url'      => $contact['url'],
			'$zrl'      => Profile::zrl($contact['url']),
			'$myaddr'   => $myaddr,
			'$keywords' => $contact['keywords'],

			'$does_know_you' => ['knowyou', $this->t('%s knows you', $contact['name'])],
			'$addnote_field' => ['dfrn-request-message', $this->t('Add a personal note:')],
		]);

		$this->page['aside'] = '';

		if (!in_array($protocol, [Protocol::PHANTOM, Protocol::MAIL])) {
			$this->page['aside'] = VCard::getHTML($contact);

			$output .= Renderer::replaceMacros(Renderer::getMarkupTemplate('section_title.tpl'),
				['$title' => $this->t('Posts and Replies')]
			);

			// Show last public posts
			$output .= Contact::getPostsFromUrl($contact['url']);
		}

		return $output;
	}

	protected function process(string $url)
	{
		$returnPath = 'contact/follow?url=' . urlencode($url);

		$result = Contact::createFromProbeForUser($this->session->getLocalUserId(), $url);

		if (!$result['success']) {
			// Possibly it is a remote item and not an account
			$this->followRemoteItem($url);

			if (!empty($result['message'])) {
				$this->sysMessages->addNotice($result['message']);
			}

			$this->baseUrl->redirect($returnPath);
		} elseif (!empty($result['cid'])) {
			$this->baseUrl->redirect('contact/' . $result['cid']);
		}

		$this->sysMessages->addNotice($this->t('The contact could not be added.'));
		$this->baseUrl->redirect($returnPath);
	}

	protected function followRemoteItem(string $url)
	{
		$itemId = Item::fetchByLink($url, $this->session->getLocalUserId());
		if (!$itemId) {
			// If the user-specific search failed, we search and probe a public post
			$itemId = Item::fetchByLink($url);
		}

		if (!empty($itemId)) {
			$item = Post::selectFirst(['guid'], ['id' => $itemId]);
			if (!empty($item['guid'])) {
				$this->baseUrl->redirect('display/' . $item['guid']);
			}
		}
	}
}
