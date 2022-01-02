<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
 *
 * @license   GNU AGPL version 3 or any later version
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

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdmin;

class Index extends BaseAdmin
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		if (empty($_POST['page_blocklist_edit'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/admin/blocklist/server', 'admin_blocklist');

		// Edit the entries from blocklist
		$blocklist = [];
		foreach ($_POST['domain'] as $id => $domain) {
			// Trimming whitespaces as well as any lingering slashes
			$domain = trim($domain);
			$reason = trim($_POST['reason'][$id]);
			if (empty($_POST['delete'][$id])) {
				$blocklist[] = [
					'domain' => $domain,
					'reason' => $reason
				];
			}
		}

		DI::config()->set('system', 'blocklist', $blocklist);

		DI::baseUrl()->redirect('admin/blocklist/server');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$blocklist     = DI::config()->get('system', 'blocklist');
		$blocklistform = [];
		if (is_array($blocklist)) {
			foreach ($blocklist as $id => $b) {
				$blocklistform[] = [
					'domain' => ["domain[$id]", DI::l10n()->t('Blocked server domain pattern'), $b['domain'], '', DI::l10n()->t('Required'), '', ''],
					'reason' => ["reason[$id]", DI::l10n()->t("Reason for the block"), $b['reason'], '', DI::l10n()->t('Required'), '', ''],
					'delete' => ["delete[$id]", DI::l10n()->t("Delete server domain pattern") . ' (' . $b['domain'] . ')', false, DI::l10n()->t("Check to delete this entry from the blocklist")]
				];
			}
		}

		$t = Renderer::getMarkupTemplate('admin/blocklist/server/index.tpl');
		return Renderer::replaceMacros($t, [
			'$l10n' => [
				'title'  => DI::l10n()->t('Administration'),
				'page'   => DI::l10n()->t('Server Domain Pattern Blocklist'),
				'intro'  => DI::l10n()->t('This page can be used to define a blocklist of server domain patterns from the federated network that are not allowed to interact with your node. For each domain pattern you should also provide the reason why you block it.'),
				'public' => DI::l10n()->t('The list of blocked server domain patterns will be made publically available on the <a href="/friendica">/friendica</a> page so that your users and people investigating communication problems can find the reason easily.'),
				'syntax' => DI::l10n()->t('<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'),
				'addtitle'       => DI::l10n()->t('Add new entry to the blocklist'),
				'submit'         => DI::l10n()->t('Check pattern'),
				'savechanges'    => DI::l10n()->t('Save changes to the blocklist'),
				'currenttitle'   => DI::l10n()->t('Current Entries in the Blocklist'),
				'thurl'          => DI::l10n()->t('Blocked server domain pattern'),
				'threason'       => DI::l10n()->t('Reason for the block'),
				'delentry'       => DI::l10n()->t('Delete entry from the blocklist'),
				'confirm_delete' => DI::l10n()->t('Delete entry from the blocklist?'),
			],
			'$newdomain'           => ['pattern', DI::l10n()->t('Server Domain Pattern'), '', DI::l10n()->t('The domain pattern of the new server to add to the blocklist. Do not include the protocol.'), DI::l10n()->t('Required'), '', ''],
			'$entries'             => $blocklistform,
			'$baseurl'             => DI::baseUrl()->get(true),
			'$form_security_token' => self::getFormSecurityToken('admin_blocklist')
		]);
	}
}
