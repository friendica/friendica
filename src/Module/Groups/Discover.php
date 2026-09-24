<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Groups;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Content\GroupManager;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Plaintext;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * List of known groups the user isn't subscribed to, either on all servers or on a single one
 */
class Discover extends BaseModule
{
	public function __construct(
		private readonly GroupManager $groupManager,
		private readonly IHandleUserSessions $session,
		private readonly Database $database,
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

		$gsid   = (int) ($this->parameters['gsid'] ?? 0);
		$server = [];
		if ($gsid) {
			$server = $this->groupManager->getServers([$gsid])[$gsid] ?? [];
			if (empty($server)) {
				throw new NotFoundException($this->t('Server not found.'));
			}
			$this->groupManager->discoverServerGroups($server);
		}

		$search    = trim($request['search'] ?? '');
		$condition = $this->getCondition($uid, $gsid, $search);

		$pager = new Pager($this->l10n, $this->args->getQueryString());
		$total = $this->database->count('account-view', $condition);

		$contacts = $this->database->selectToArray(
			'account-view',
			['id', 'url', 'addr', 'name', 'about', 'thumb', 'avatar', 'updated', 'gsid', 'last-item', 'manually-approve'],
			$condition,
			['order' => ['last-item' => true], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]],
		);

		$servers = $gsid ? [] : $this->groupManager->getServers(array_column($contacts, 'gsid'));

		$groups = [];
		foreach ($contacts as $contact) {
			$host = parse_url((string) $contact['url'], PHP_URL_HOST) ?: '';

			$groups[] = [
				'id'      => $contact['id'],
				'link'    => $this->groupManager->getLink($contact),
				'name'    => $contact['name'],
				'thumb'   => Contact::getThumb($contact),
				'about'   => Plaintext::shorten(BBCode::toPlaintext($contact['about'], false), 200),
				'server'  => $gsid ? '' : $this->groupManager->getServerDescription($servers[$contact['gsid']] ?? [], $host)['name'],
				'host'    => $host,
				'private' => $contact['manually-approve'],
				'latest'  => $contact['last-item'] > DBA::NULL_DATETIME ? $this->l10n->relativeDateTime($contact['last-item']) : '',
				'follow'  => 'contact/follow?binurl=' . bin2hex((string) $contact['url']),
			];
		}

		if ($gsid) {
			$description = $this->groupManager->getServerDescription($server, parse_url((string) $server['url'], PHP_URL_HOST) ?: '');
			$title       = $this->t('Discover groups on %s', $description['name']);
		} else {
			$description = [];
			$title       = $this->t('Discover groups');
		}

		$tpl = Renderer::getMarkupTemplate('groups_discover.tpl');
		return Renderer::replaceMacros($tpl, [
			'$back'      => $this->t('Back'),
			'$title'     => $title,
			'$action'    => 'groups/discover' . ($gsid ? '/' . $gsid : ''),
			'$search'    => $search,
			'$find'      => $this->t('Find'),
			'$find_desc' => $this->t('Search in name and description'),
			'$info'      => $description['info'] ?? '',
			'$group'     => $this->t('Group'),
			'$latest'    => $this->t('Latest activity'),
			'$join'      => $this->t('Join'),
			'$private'   => $this->t('Membership requires approval'),
			'$no_groups' => $search !== '' ? $this->t('No groups found.') : $this->t('No further groups are known.'),
			'$groups'    => $groups,
			'$paginate'  => $pager->renderFull($total),
		]);
	}

	/**
	 * Known groups that the user is neither subscribed to nor has blocked or ignored
	 *
	 * @param int    $uid    User id
	 * @param int    $gsid   Server id, 0 for all servers
	 * @param string $search Search term for name and description
	 *
	 * @return array
	 */
	private function getCondition(int $uid, int $gsid, string $search): array
	{
		$condition = [
			'contact-type'   => Contact::TYPE_COMMUNITY,
			'network'        => [Protocol::DFRN, Protocol::ACTIVITYPUB],
			'blocked'        => false,
			'deleted'        => false,
			'archive'        => false,
			'failed'         => false,
			'unsearchable'   => false,
			'server-blocked' => false,
		];

		if ($gsid) {
			$condition['gsid'] = $gsid;
		}

		$condition = DBA::mergeConditions($condition, ["`platform` NOT IN (?, ?)", 'peertube', 'wordpress']);

		if ($search !== '') {
			$condition = DBA::mergeConditions($condition, ["(`name` LIKE ? OR `about` LIKE ?)", '%' . $search . '%', '%' . $search . '%']);
		}

		$subscribed = array_column($this->groupManager->getList($uid, false, true, true), 'pid');
		if (!empty($subscribed)) {
			$condition = DBA::mergeConditions($condition, ["NOT `id` IN (" . implode(', ', array_fill(0, count($subscribed), '?')) . ")", ...$subscribed]);
		}

		return DBA::mergeConditions($condition, ["NOT `id` IN (SELECT `cid` FROM `user-contact` WHERE `uid` = ? AND (`blocked` OR `ignored` OR `pending`))", $uid]);
	}
}
