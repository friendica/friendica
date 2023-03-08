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
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Index extends BaseModeration
{
	/** @var DomainPatternBlocklist */
	private $blocklist;

	public function __construct(DomainPatternBlocklist $blocklist, App\Page $page, App $app, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $app, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->blocklist = $blocklist;
	}

	protected function post(array $request = [])
	{
		$this->checkModerationAccess();

		if (empty($request['page_blocklist_edit'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/moderation/blocklist/server', 'moderation_blocklist');

		// Edit the entries from blocklist
		$blocklist = [];
		foreach ($request['domain'] as $id => $domain) {
			// Trimming whitespaces as well as any lingering slashes
			$domain = trim($domain);
			$reason = trim($request['reason'][$id]);
			if (empty($request['delete'][$id])) {
				$blocklist[] = [
					'domain' => $domain,
					'reason' => $reason
				];
			}
		}

		$this->blocklist->set($blocklist);

		Worker::add(Worker::PRIORITY_LOW, 'UpdateBlockedServers');

		$this->baseUrl->redirect('moderation/blocklist/server');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$blocklistform = [];
		foreach ($this->blocklist->get() as $id => $b) {
			$blocklistform[] = [
				'domain' => ["domain[$id]", $this->t('Blocked server domain pattern'), $b['domain'], '', $this->t('Required'), '', ''],
				'reason' => ["reason[$id]", $this->t("Reason for the block"), $b['reason'], '', $this->t('Required'), '', ''],
				'delete' => ["delete[$id]", $this->t("Delete server domain pattern") . ' (' . $b['domain'] . ')', false, $this->t("Check to delete this entry from the blocklist")]
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
				'importtitle'    => $this->t('Import server domain pattern blocklist'),
				'addtitle'       => $this->t('Add new entry to the blocklist'),
				'importsubmit'   => $this->t('Upload file'),
				'addsubmit'      => $this->t('Check pattern'),
				'savechanges'    => $this->t('Save changes to the blocklist'),
				'currenttitle'   => $this->t('Current Entries in the Blocklist'),
				'thurl'          => $this->t('Blocked server domain pattern'),
				'threason'       => $this->t('Reason for the block'),
				'delentry'       => $this->t('Delete entry from the blocklist'),
				'confirm_delete' => $this->t('Delete entry from the blocklist?'),
			],
			'$listfile'  => ['listfile', $this->t('Server domain pattern blocklist CSV file'), '', '', $this->t('Required'), '', 'file'],
			'$newdomain' => ['pattern', $this->t('Server Domain Pattern'), '', $this->t('The domain pattern of the new server to add to the blocklist. Do not include the protocol.'), $this->t('Required'), '', ''],
			'$entries'   => $blocklistform,
			'$baseurl'   => $this->baseUrl,

			'$form_security_token'        => self::getFormSecurityToken('moderation_blocklist'),
			'$form_security_token_import' => self::getFormSecurityToken('moderation_blocklist_import'),
		]);
	}
}
