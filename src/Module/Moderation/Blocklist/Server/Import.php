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
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Moderation\DomainPatternBlocklist;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Import extends \Friendica\Module\BaseModeration
{
	/** @var array of blocked server domain patterns */
	private $blocklist = [];

	public function __construct(private readonly DomainPatternBlocklist $localBlocklist, Page $page, AppHelper $appHelper, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $appHelper, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	/**
	 * @param array $request
	 * @return void
	 * @throws HTTPException\ForbiddenException
	 * @throws HTTPException\FoundException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\MovedPermanentlyException
	 * @throws HTTPException\TemporaryRedirectException
	 */
	protected function post(array $request = [])
	{
		$this->checkModerationAccess();

		if (!isset($request['page_blocklist_upload']) && !isset($request['page_blocklist_import'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/moderation/blocklist/server/import', 'moderation_blocklist_import');

		if (isset($request['page_blocklist_upload'])) {
			try {
				$this->blocklist = $this->localBlocklist::extractFromCSVFile($_FILES['listfile']['tmp_name']);
			} catch (\Throwable) {
				$this->systemMessages->addNotice($this->t('Error importing pattern file'));
				return;
			}
		} elseif (isset($request['page_blocklist_import'])) {
			$this->blocklist = json_decode((string) $request['blocklist'], true);
			if ($this->blocklist === null) {
				$this->systemMessages->addNotice($this->t('Error importing pattern file'));
				return;
			}
		}

		if (($request['mode'] ?? 'append') == 'replace') {
			$this->localBlocklist->set($this->blocklist);
			$this->systemMessages->addNotice($this->t('Local blocklist replaced with the provided file.'));
		} else {
			$count = $this->localBlocklist->append($this->blocklist);
			if ($count) {
				$this->systemMessages->addNotice($this->tt('%d pattern was added to the local blocklist.', '%d patterns were added to the local blocklist.', $count));
			} else {
				$this->systemMessages->addNotice($this->t('No pattern was added to the local blocklist.'));
			}
		}

		Worker::add(Worker::PRIORITY_LOW, 'UpdateBlockedServers');

		$this->baseUrl->redirect('/moderation/blocklist/server');
	}

	/**
	 * @param array $request
	 * @return string
	 * @throws HTTPException\ServiceUnavailableException
	 */
	protected function content(array $request = []): string
	{
		parent::content();

		$t = Renderer::getMarkupTemplate('moderation/blocklist/server/import.tpl');
		return Renderer::replaceMacros($t, [
			'$l10n' => [
				'return_list'    => $this->t('← Return to the list'),
				'title'          => $this->t('Moderation'),
				'page'           => $this->t('Import a Server Domain Pattern Blocklist'),
				'download'       => $this->t('<p>This file can be downloaded from the <code>/friendica</code> path of any Friendica server.</p>'),
				'upload'         => $this->t('Upload file'),
				'patterns'       => $this->t('Patterns to import'),
				'domain_pattern' => $this->t('Domain Pattern'),
				'block_reason'   => $this->t('Block Reason'),
				'mode'           => $this->t('Import Mode'),
				'import'         => $this->t('Import Patterns'),
				'pattern_count'  => $this->tt('%d total pattern', '%d total patterns', count($this->blocklist)),
			],
			'$listfile'            => ['listfile', $this->t('Server domain pattern blocklist CSV file'), '', '', $this->t('Required'), '', 'file'],
			'$mode_append'         => ['mode', $this->t('Append'), 'append', $this->t('Imports patterns from the file that weren\'t already existing in the current blocklist.'), 'checked="checked"'],
			'$mode_replace'        => ['mode', $this->t('Replace'), 'replace', $this->t('Replaces the current blocklist by the imported patterns.')],
			'$blocklist'           => $this->blocklist,
			'$form_security_token' => self::getFormSecurityToken('moderation_blocklist_import'),
		]);
	}
}
