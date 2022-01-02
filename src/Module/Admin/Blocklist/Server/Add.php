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

use Friendica\Content\ContactSelector;
use Friendica\Core\Renderer;
use Friendica\Core\Worker;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Module\BaseAdmin;
use GuzzleHttp\Psr7\Uri;

class Add extends BaseAdmin
{
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		if (empty($_POST['page_blocklist_add'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/admin/blocklist/server/add', 'admin_blocklist_add');

		//  Add new item to blocklist
		$domain = trim($_POST['pattern']);

		$blocklist   = DI::config()->get('system', 'blocklist');
		$blocklist[] = [
			'domain' => $domain,
			'reason' => trim($_POST['reason']),
		];
		DI::config()->set('system', 'blocklist', $blocklist);

		info(DI::l10n()->t('Server domain pattern added to the blocklist.'));

		if (!empty($_POST['purge'])) {
			$gservers = GServer::listByDomainPattern($domain);
			foreach (Contact::selectToArray(['id'], ['gsid' => array_column($gservers, 'id')]) as $contact) {
				Worker::add(PRIORITY_LOW, 'Contact\RemoveContent', $contact['id']);
			}

			info(DI::l10n()->tt('%s server scheduled to be purged.', '%s servers scheduled to be purged.', count($gservers)));
		}

		DI::baseUrl()->redirect('admin/blocklist/server');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$gservers = [];

		if ($pattern = trim($_REQUEST['pattern'] ?? '')) {
			$gservers = GServer::listByDomainPattern($pattern);
		}

		array_walk($gservers, function (array &$gserver) {
			$gserver['domain'] = (new Uri($gserver['url']))->getHost();
			$gserver['network_icon'] = ContactSelector::networkToIcon($gserver['network']);
			$gserver['network_name'] = ContactSelector::networkToName($gserver['network']);
		});

		$t = Renderer::getMarkupTemplate('admin/blocklist/server/add.tpl');
		return Renderer::replaceMacros($t, [
			'$l10n' => [
				'return_list' => DI::l10n()->t('← Return to the list'),
				'title'       => DI::l10n()->t('Administration'),
				'page'        => DI::l10n()->t('Block A New Server Domain Pattern'),
				'syntax'      => DI::l10n()->t('<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'),
				'submit'           => DI::l10n()->t('Check pattern'),
				'matching_servers' => DI::l10n()->t('Matching known servers'),
				'server_name'      => DI::l10n()->t('Server Name'),
				'server_domain'    => DI::l10n()->t('Server Domain'),
				'known_contacts'   => DI::l10n()->t('Known Contacts'),
				'server_count'     => DI::l10n()->tt('%d known server', '%d known servers', count($gservers)),
				'add_pattern'      => DI::l10n()->t('Add pattern to the blocklist'),
			],
			'$newdomain'           => ['pattern', DI::l10n()->t('Server Domain Pattern'), $pattern, DI::l10n()->t('The domain pattern of the new server to add to the blocklist. Do not include the protocol.'), DI::l10n()->t('Required'), '', ''],
			'$newpurge'            => ['purge', DI::l10n()->t('Purge server'), $_REQUEST['purge'] ?? false, DI::l10n()->tt('Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.', 'Also purges all the locally stored content authored by the known contacts registered on these servers. Keeps the contacts and the servers records. This action cannot be undone.', count($gservers))],
			'$newreason'           => ['reason', DI::l10n()->t('Block reason'), $_REQUEST['reason'] ?? '', DI::l10n()->t('The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'), DI::l10n()->t('Required'), '', ''],
			'$pattern'             => $pattern,
			'$gservers'            => $gservers,
			'$baseurl'             => DI::baseUrl()->get(true),
			'$form_security_token' => self::getFormSecurityToken('admin_blocklist_add')
		]);
	}
}
