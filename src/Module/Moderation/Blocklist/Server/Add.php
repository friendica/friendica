<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation\Blocklist\Server;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Content\ContactSelector;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Moderation\DomainPatternBlocklist;
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

class Add extends BaseModeration
{
	public function __construct(private readonly DomainPatternBlocklist $blocklist, Page $page, AppHelper $app, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $app, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * @param array $request
	 * @return void
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\FoundException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\MovedPermanentlyException
	 * @throws HTTPException\TemporaryRedirectException
	 * @throws \Exception
	 */
	protected function post(array $request = [])
	{
		$this->checkModerationAccess();

		if (empty($request['page_blocklist_add'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/moderation/blocklist/server/add', 'moderation_blocklist_add');

		$pattern = trim((string) $request['pattern']);

		//  Add new item to blocklist
		$this->blocklist->addPattern($pattern, trim((string) $request['reason']));

		Worker::add(Worker::PRIORITY_LOW, 'UpdateBlockedServers');

		$this->systemMessages->addInfo($this->t('Server domain pattern added to the blocklist.'));

		if (!empty($request['purge'])) {
			$gservers = GServer::listByDomainPattern($pattern);
			foreach (Contact::selectToArray(['id'], ['gsid' => array_column($gservers, 'id')]) as $contact) {
				Worker::add(Worker::PRIORITY_LOW, 'Contact\RemoveContent', $contact['id']);
			}

			$this->systemMessages->addInfo($this->tt('%s server scheduled to be purged.', '%s servers scheduled to be purged.', count($gservers)));
		}

		$this->baseUrl->redirect('moderation/blocklist/server');
	}

	/**
	 * @param array $request
	 * @return string
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\ServiceUnavailableException
	 * @throws \Exception
	 */
	protected function content(array $request = []): string
	{
		parent::content();

		$gservers = [];

		if ($pattern = trim($request['pattern'] ?? '')) {
			$gservers = GServer::listByDomainPattern($pattern);
		}

		array_walk($gservers, function (array &$gserver): void {
			$gserver['domain']       = (new Uri($gserver['url']))->getHost();
			$gserver['network_svg']  = ContactSelector::networkToSVG($gserver['network']);
			$gserver['network_name'] = ContactSelector::networkToName($gserver['network']);
		});

		$t = Renderer::getMarkupTemplate('moderation/blocklist/server/add.tpl');
		return Renderer::replaceMacros($t, [
			'$l10n' => [
				'return_list' => $this->t('← Return to the list'),
				'title'       => $this->t('Moderation'),
				'page'        => $this->t('Block A New Server Domain Pattern'),
				'syntax'      => $this->t('<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'),
				'submit'           => $this->t('Check pattern'),
				'matching_servers' => $this->t('Matching known servers'),
				'server_name'      => $this->t('Server Name'),
				'server_domain'    => $this->t('Server Domain'),
				'known_contacts'   => $this->t('Known Contacts'),
				'server_count'     => $this->tt('%d known server', '%d known servers', count($gservers)),
				'add_pattern'      => $this->t('Add pattern to the blocklist'),
			],
			'$newdomain'           => ['pattern', $this->t('Server Domain Pattern'), $pattern, $this->t('The domain pattern of the new server to add to the blocklist. Do not include the protocol.'), $this->t('Required'), '', ''],
			'$newpurge'            => ['purge', $this->t('Purge server'), $request['purge'] ?? false, $this->tt('Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.', 'Also purges all the locally stored content authored by the known contacts registered on these servers. Keeps the contacts and the servers records. This action cannot be undone.', count($gservers))],
			'$newreason'           => ['reason', $this->t('Block reason'), $request['reason'] ?? '', $this->t('The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'), $this->t('Required'), '', ''],
			'$pattern'             => $pattern,
			'$gservers'            => $gservers,
			'$form_security_token' => self::getFormSecurityToken('moderation_blocklist_add'),
		]);
	}
}
