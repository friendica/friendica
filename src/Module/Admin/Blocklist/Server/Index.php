<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Admin\Blocklist\Server;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Library\Moderation\DomainPatternBlocklist;
use Friendica\Module\BaseAdmin;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Index extends BaseAdmin
{
	/** @var DomainPatternBlocklist */
	private $blocklist;

	public function __construct(DomainPatternBlocklist $blocklist, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->blocklist = $blocklist;
	}

	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		if (empty($request['page_blocklist_edit'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/admin/blocklist/server', 'admin_blocklist');

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

		$this->baseUrl->redirect('admin/blocklist/server');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$blocklistform = [];
		foreach ($this->blocklist->get() as $id => $b) {
			$blocklistform[] = [
				'domain' => ["domain[$id]", $this->l10n->t('Blocked server domain pattern'), $b['domain'], '', $this->l10n->t('Required'), '', ''],
				'reason' => ["reason[$id]", $this->l10n->t("Reason for the block"), $b['reason'], '', $this->l10n->t('Required'), '', ''],
				'delete' => ["delete[$id]", $this->l10n->t("Delete server domain pattern") . ' (' . $b['domain'] . ')', false, $this->l10n->t("Check to delete this entry from the blocklist")]
			];
		}

		$t = Renderer::getMarkupTemplate('admin/blocklist/server/index.tpl');
		return Renderer::replaceMacros($t, [
			'$l10n' => [
				'title'  => $this->l10n->t('Administration'),
				'page'   => $this->l10n->t('Server Domain Pattern Blocklist'),
				'intro'  => $this->l10n->t('This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'),
				'public' => $this->l10n->t('The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'),
				'syntax' => $this->l10n->t('<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'),
				'importtitle'    => $this->l10n->t('Import server domain pattern blocklist'),
				'addtitle'       => $this->l10n->t('Add new entry to the blocklist'),
				'importsubmit'   => $this->l10n->t('Upload file'),
				'addsubmit'      => $this->l10n->t('Check pattern'),
				'savechanges'    => $this->l10n->t('Save changes to the blocklist'),
				'currenttitle'   => $this->l10n->t('Current Entries in the Blocklist'),
				'thurl'          => $this->l10n->t('Blocked server domain pattern'),
				'threason'       => $this->l10n->t('Reason for the block'),
				'delentry'       => $this->l10n->t('Delete entry from the blocklist'),
				'confirm_delete' => $this->l10n->t('Delete entry from the blocklist?'),
			],
			'$listfile'            => ['listfile', $this->l10n->t('Server domain pattern blocklist CSV file'), '', '', $this->l10n->t('Required'), '', 'file'],
			'$newdomain'           => ['pattern', $this->l10n->t('Server Domain Pattern'), '', $this->l10n->t('The domain pattern of the new server to add to the blocklist. Do not include the protocol.'), $this->l10n->t('Required'), '', ''],
			'$entries'             => $blocklistform,
			'$baseurl'             => $this->baseUrl->get(true),
			'$form_security_token' => self::getFormSecurityToken('admin_blocklist'),
			'$form_security_token_import' => self::getFormSecurityToken('admin_blocklist_import'),
		]);
	}
}
