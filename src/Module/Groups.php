<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\GroupManager;
use Friendica\Content\Nav;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Overview of the groups the user is subscribed to
 */
class Groups extends BaseModule
{
	public function __construct(
		private readonly GroupManager $groupManager,
		private readonly IHandleUserSessions $session,
		L10n $l10n,
		BaseURL $baseUrl,
		Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function content(array $request = []): string
	{
		$uid = $this->session->getLocalUserId();
		if (!$uid) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		Nav::setSelected('groups');

		$contacts = $this->groupManager->getList($uid, true, true, true);

		$stats   = $this->groupManager->getGroupStats($uid, array_column($contacts, 'pid'));
		$threads = $this->groupManager->getLatestThreads($uid, $stats);
		$gravity = [Item::GRAVITY_PARENT, Item::GRAVITY_COMMENT];
		$latest  = $this->groupManager->getLatestPosts($uid, $this->groupManager->getThreadStats($uid, array_values($threads), $gravity), $gravity, ['guid', 'title', 'body', 'author-id', 'author-name', 'author-link', 'author-updated', 'unseen']);

		$groups  = [];
		$servers = $this->groupManager->getServers(array_column($contacts, 'gsid'));
		foreach ($contacts as $contact) {
			$pid  = $contact['pid'];
			$host = parse_url((string) $contact['url'], PHP_URL_HOST) ?: '';

			$group = [
				'id'       => $contact['id'],
				'link'     => $this->groupManager->getLink($contact),
				'profile'  => Contact::magicLinkByContact($contact),
				'name'     => $contact['name'],
				'thumb'    => Contact::getThumb($contact),
				'about'    => Plaintext::shorten(BBCode::toPlaintext($contact['about'], false), 200),
				'threads'  => $stats[$pid]['threads'] ?? 0,
				'unread'   => $stats[$pid]['unread']  ?? 0,
				'received' => '',
				'latest'   => [],
			];

			$uriId = $threads[$pid] ?? 0;
			if (!empty($latest[$uriId])) {
				$group['received'] = $latest[$uriId]['received'];
				$group['latest']   = $this->groupManager->getPostSummary($latest[$uriId]);
			}

			if (!isset($groups[$host])) {
				$groups[$host] = array_merge($this->groupManager->getServerDescription($servers[$contact['gsid']] ?? [], $host), [
					'gsid'   => $contact['gsid'],
					'groups' => [],
				]);
			}
			$groups[$host]['groups'][] = $group;
		}

		foreach ($groups as $host => $server) {
			usort($groups[$host]['groups'], fn ($a, $b): int => strcmp((string) $b['received'], (string) $a['received']));
		}
		usort($groups, fn ($a, $b): int => strcmp((string) $b['groups'][0]['received'], (string) $a['groups'][0]['received']));

		$tpl = Renderer::getMarkupTemplate('groups.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'           => $this->t('Groups'),
			'$threads'         => $this->t('Threads'),
			'$unread'          => $this->t('Unread'),
			'$no_posts'        => $this->t('No posts yet'),
			'$discover'        => $this->t('Discover groups'),
			'$discover_short'  => $this->t('Discover'),
			'$discover_server' => $this->t('Discover more groups on this server'),
			'$no_groups'       => $this->t('You are not subscribed to any groups.'),
			'$groups'          => $groups,
		]);
	}
}
