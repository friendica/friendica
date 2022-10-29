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
use Friendica\Content\ContactSelector;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Worker;
use Friendica\Model\Contact;
use Friendica\Model\GServer;
use Friendica\Library\Moderation\DomainPatternBlocklist;
use Friendica\Module\BaseAdmin;
use Friendica\Module\Response;
use Friendica\Library\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

class Add extends BaseAdmin
{
	/** @var SystemMessages */
	private $sysmsg;

	/** @var DomainPatternBlocklist */
	private $blocklist;

	public function __construct(SystemMessages $sysmsg, DomainPatternBlocklist $blocklist, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->sysmsg    = $sysmsg;
		$this->blocklist = $blocklist;
	}

	/**
	 * @param array $request
	 * @return void
	 * @throws \Friendica\Network\HTTPException\ForbiddenException
	 * @throws \Friendica\Network\HTTPException\FoundException
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\MovedPermanentlyException
	 * @throws \Friendica\Network\HTTPException\TemporaryRedirectException
	 * @throws \Exception
	 */
	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		if (empty($request['page_blocklist_add'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/admin/blocklist/server/add', 'admin_blocklist_add');

		$pattern = trim($request['pattern']);

		//  Add new item to blocklist
		$this->blocklist->addPattern($pattern, trim($request['reason']));

		$this->sysmsg->addInfo($this->l10n->t('Server domain pattern added to the blocklist.'));

		if (!empty($request['purge'])) {
			$gservers = GServer::listByDomainPattern($pattern);
			foreach (Contact::selectToArray(['id'], ['gsid' => array_column($gservers, 'id')]) as $contact) {
				Worker::add(Worker::PRIORITY_LOW, 'Contact\RemoveContent', $contact['id']);
			}

			$this->sysmsg->addInfo($this->l10n->tt('%s server scheduled to be purged.', '%s servers scheduled to be purged.', count($gservers)));
		}

		$this->baseUrl->redirect('admin/blocklist/server');
	}

	/**
	 * @param array $request
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\ServiceUnavailableException
	 * @throws \Exception
	 */
	protected function content(array $request = []): string
	{
		parent::content();

		$gservers = [];

		if ($pattern = trim($request['pattern'] ?? '')) {
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
				'return_list' => $this->l10n->t('← Return to the list'),
				'title'       => $this->l10n->t('Administration'),
				'page'        => $this->l10n->t('Block A New Server Domain Pattern'),
				'syntax'      => $this->l10n->t('<p>The server domain pattern syntax is case-insensitive shell wildcard, comprising the following special characters:</p>
<ul>
	<li><code>*</code>: Any number of characters</li>
	<li><code>?</code>: Any single character</li>
</ul>'),
				'submit'           => $this->l10n->t('Check pattern'),
				'matching_servers' => $this->l10n->t('Matching known servers'),
				'server_name'      => $this->l10n->t('Server Name'),
				'server_domain'    => $this->l10n->t('Server Domain'),
				'known_contacts'   => $this->l10n->t('Known Contacts'),
				'server_count'     => $this->l10n->tt('%d known server', '%d known servers', count($gservers)),
				'add_pattern'      => $this->l10n->t('Add pattern to the blocklist'),
			],
			'$newdomain'           => ['pattern', $this->l10n->t('Server Domain Pattern'), $pattern, $this->l10n->t('The domain pattern of the new server to add to the blocklist. Do not include the protocol.'), $this->l10n->t('Required'), '', ''],
			'$newpurge'            => ['purge', $this->l10n->t('Purge server'), $request['purge'] ?? false, $this->l10n->tt('Also purges all the locally stored content authored by the known contacts registered on that server. Keeps the contacts and the server records. This action cannot be undone.', 'Also purges all the locally stored content authored by the known contacts registered on these servers. Keeps the contacts and the servers records. This action cannot be undone.', count($gservers))],
			'$newreason'           => ['reason', $this->l10n->t('Block reason'), $request['reason'] ?? '', $this->l10n->t('The reason why you blocked this server domain pattern. This reason will be shown publicly in the server information page.'), $this->l10n->t('Required'), '', ''],
			'$pattern'             => $pattern,
			'$gservers'            => $gservers,
			'$baseurl'             => $this->baseUrl->get(true),
			'$form_security_token' => self::getFormSecurityToken('admin_blocklist_add')
		]);
	}
}
