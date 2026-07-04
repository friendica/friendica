<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Moderation\Blocklist\Server;

use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\App\Page;
use Friendica\AppHelper;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Moderation\DomainPatternBlocklist;
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Index extends BaseModeration
{
	public function __construct(private readonly DomainPatternBlocklist $blocklist, Page $page, AppHelper $appHelper, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $appHelper, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		$this->checkModerationAccess();
		$search      = trim((string) ($request['search'] ?? ''));
		$redirectUrl = 'moderation/blocklist/server' . ($search !== '' ? '?search=' . rawurlencode($search) : '');

		if (empty($request['page_blocklist_edit'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/moderation/blocklist/server', 'moderation_blocklist');

		// Edit the entries from blocklist
		$blocklist = [];
		foreach ((array) $request['domain'] as $id => $domain) {
			// Trimming whitespaces as well as any lingering slashes
			$domain = trim($domain);
			$reason = trim($request['reason'][$id]);
			if (empty($request['delete'][$id]) && !empty($domain)) {
				$blocklist[] = [
					'domain' => $domain,
					'reason' => $reason,
				];
			}
		}

		$this->blocklist->set($blocklist);

		Worker::add(Worker::PRIORITY_LOW, 'UpdateBlockedServers');

		$this->baseUrl->redirect($redirectUrl);
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$search     = trim((string) ($request['search'] ?? ''));
		$rawEntries = self::filterEntriesBySearch($this->blocklist->get(), $search);

		$blocklistform = [];
		foreach ($rawEntries as $id => $b) {
			$blocklistform[] = [
				'domain' => ["domain[$id]", $this->t('Blocked server domain pattern'), $b['domain'], '', $this->t('Required'), '', ''],
				'reason' => ["reason[$id]", $this->t("Reason for the block"), $b['reason'], '', $this->t('Required'), '', ''],
				'delete' => ["delete[$id]", $this->t("Delete server domain pattern") . ' (' . $b['domain'] . ')', false, $this->t("Check to delete this entry from the blocklist")],
			];
		}

		$t = Renderer::getMarkupTemplate('moderation/blocklist/server/index.tpl');
		return Renderer::replaceMacros($t, [
			'$l10n' => [
				'title'  => $this->t('Moderation'),
				'page'   => $this->t('Server Domain Pattern Blocklist'),
				'intro'  => $this->t('This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'),
				'public' => $this->t('The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'),
				'syntax' => $this->t('<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'),
				'importtitle'         => $this->t('Import server domain pattern blocklist'),
				'addtitle'            => $this->t('Add new entry to the blocklist'),
				'importsubmit'        => $this->t('Upload file'),
				'addsubmit'           => $this->t('Check pattern'),
				'savechanges'         => $this->t('Save changes to the blocklist'),
				'currenttitle'        => $this->t('Current Entries in the Blocklist'),
				'thurl'               => $this->t('Blocked server domain pattern'),
				'threason'            => $this->t('Reason for the block'),
				'delentry'            => $this->t('Delete entry from the blocklist'),
				'confirm_delete'      => $this->t('Delete entry from the blocklist?'),
				'search_label'        => $this->t('Search'),
				'search_submit'       => $this->t('Filter'),
				'search_reset'        => $this->t('Reset'),
				'no_entries'          => $this->t('No server domain pattern is blocked on this node.'),
				'no_entries_filtered' => $this->t('No blocked server domain pattern matches your search.'),
			],
			'$listfile'  => ['listfile', $this->t('Server domain pattern blocklist CSV file'), '', '', $this->t('Required'), '', 'file'],
			'$newdomain' => ['pattern', $this->t('Server Domain Pattern'), '', $this->t('The domain pattern of the new server to add to the blocklist. Do not include the protocol.'), $this->t('Required'), '', ''],
			'$entries'   => $blocklistform,
			'$search'    => $search,

			'$form_security_token'        => self::getFormSecurityToken('moderation_blocklist'),
			'$form_security_token_import' => self::getFormSecurityToken('moderation_blocklist_import'),
		]);
	}

	/**
	 * Filters blocklist entries using a case-insensitive contains match on domain and reason.
	 */
	private static function filterEntriesBySearch(array $entries, string $search): array
	{
		if ($search === '') {
			return $entries;
		}

		return array_filter($entries, static function (array $entry) use ($search): bool {
			$domain = (string) ($entry['domain'] ?? '');
			$reason = (string) ($entry['reason'] ?? '');

			return stripos($domain, $search) !== false || stripos($reason, $search) !== false;
		});
	}
}
