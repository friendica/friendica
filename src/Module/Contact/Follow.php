<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\Probe;
use Friendica\Security\OpenWebAuth;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use GuzzleHttp\Psr7\Uri;
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

		if (!empty($request['follow-url'])) {
			$this->baseUrl->redirect('contact/follow?binurl=' . bin2hex($request['follow-url']));
		}

		$url = $this->getUrl($request);

		if (isset($request['cancel']) || empty($url)) {
			$this->baseUrl->redirect('contact');
		}

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
		$url = $this->getUrl($request);

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
			$contact = ['url' => $url, 'network' => Protocol::PHANTOM, 'name' => $url, 'alias' => '', 'keywords' => ''];
		}

		$protocol = Contact::getProtocol($contact['url'], $contact['network']);

		if (($protocol == Protocol::DIASPORA) && !$this->config->get('system', 'diaspora_enabled')) {
			$this->sysMessages->addNotice($this->t('Diaspora support isn\'t enabled. Contact can\'t be added.'));
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
			'$url'      => $contact['alias'] ?: $contact['url'],
			'$zrl'      => OpenWebAuth::getZrlUrl($contact['alias'] ?: $contact['url']),
			'$myaddr'   => $myaddr,
			'$keywords' => $contact['keywords'],

			'$does_know_you' => ['knowyou', $this->t('%s knows you', $contact['name'])],
			'$addnote_field' => ['dfrn-request-message', $this->t('Add a personal note:')],
		]);

		$this->page['aside'] = '';

		if (!in_array($protocol, [Protocol::PHANTOM, Protocol::MAIL])) {
			$this->page['aside'] = VCard::getHTML($contact, false, true);

			$output .= Renderer::replaceMacros(
				Renderer::getMarkupTemplate('section_title.tpl'),
				['$title' => $this->t('Posts and Replies')]
			);

			// Show last public posts
			$output .= Contact::getPostsFromUrl($contact['url'], $this->session->getLocalUserId());
		}

		return $output;
	}

	protected function process(string $url)
	{
		$returnPath = 'contact/follow?binurl=' . bin2hex($url);

		$result = Contact::createFromProbeForUser($this->session->getLocalUserId(), $url);

		if (!$result['success']) {
			// Possibly it is a remote item and not an account
			$this->followRemoteItem($url);

			if (!empty($result['message'])) {
				$this->sysMessages->addNotice($result['message']);
			}

			$this->baseUrl->redirect($returnPath);
		} elseif (!empty($result['cid'])) {
			$this->baseUrl->redirect('contact/' . Contact::getPublicContactId($result['cid'], $this->session->getLocalUserId()));
		}

		$this->sysMessages->addNotice($this->t('The contact could not be added.'));
		$this->baseUrl->redirect($returnPath);
	}

	protected function followRemoteItem(string $url)
	{
		try {
			$uri = new Uri($url);
			if (!$uri->getScheme()) {
				return;
			}

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
		} catch (\InvalidArgumentException $e) {
			return;
		}
	}

	private function getUrl(array $request): string
	{
		if (!empty($request['binurl']) && Strings::isHex($request['binurl'])) {
			$url = hex2bin($request['binurl']);
		} else {
			$url = $request['url'] ?? '';
		}
		return Probe::cleanURI($url);
	}
}
