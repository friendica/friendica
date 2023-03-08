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

namespace Friendica\Module\Moderation\Blocklist\Server;

use Friendica\App;
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
	/** @var DomainPatternBlocklist */
	private $localBlocklist;

	/** @var array of blocked server domain patterns */
	private $blocklist = [];

	public function __construct(DomainPatternBlocklist $localBlocklist, App\Page $page, App $app, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $app, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->localBlocklist = $localBlocklist;
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
			} catch (\Throwable $e) {
				$this->systemMessages->addNotice($this->t('Error importing pattern file'));
			}

			return;
		}

		if (isset($request['page_blocklist_import'])) {
			$blocklist = json_decode($request['blocklist'], true);
			if ($blocklist === null) {
				$this->systemMessages->addNotice($this->t('Error importing pattern file'));
				return;
			}

			if (($request['mode'] ?? 'append') == 'replace') {
				$this->localBlocklist->set($blocklist);
				$this->systemMessages->addNotice($this->t('Local blocklist replaced with the provided file.'));
			} else {
				$count = $this->localBlocklist->append($blocklist);
				if ($count) {
					$this->systemMessages->addNotice($this->tt('%d pattern was added to the local blocklist.', '%d patterns were added to the local blocklist.', $count));
				} else {
					$this->systemMessages->addNotice($this->t('No pattern was added to the local blocklist.'));
				}
			}

			Worker::add(Worker::PRIORITY_LOW, 'UpdateBlockedServers');

			$this->baseUrl->redirect('/moderation/blocklist/server');
		}
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
			'$baseurl'             => $this->baseUrl,
			'$form_security_token' => self::getFormSecurityToken('moderation_blocklist_import')
		]);
	}
}
