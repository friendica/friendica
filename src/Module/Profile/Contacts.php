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

namespace Friendica\Module\Profile;

use Friendica\App;
use Friendica\Content\Nav;
use Friendica\Content\Pager;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model;
use Friendica\Module;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Contacts extends Module\BaseProfile
{
	/** @var IManageConfigValues */
	private $config;
	/** @var IHandleUserSessions */
	private $userSession;
	/** @var App */
	private $app;
	/** @var Database */
	private $database;

	public function __construct(Database $database, App $app, IHandleUserSessions $userSession, IManageConfigValues $config, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config      = $config;
		$this->userSession = $userSession;
		$this->app         = $app;
		$this->database    = $database;
	}

	protected function content(array $request = []): string
	{
		if ($this->config->get('system', 'block_public') && !$this->userSession->isAuthenticated()) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		$nickname = $this->parameters['nickname'];
		$type     = $this->parameters['type'] ?? 'all';

		$profile = Model\Profile::load($this->app, $nickname);
		if (empty($profile)) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		$is_owner = $profile['uid'] == $this->userSession->getLocalUserId();

		if ($profile['hide-friends'] && !$is_owner) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		Nav::setSelected('home');

		$o = self::getTabsHTML('contacts', $is_owner, $profile['nickname'], $profile['hide-friends']);

		$tabs = self::getContactFilterTabs('profile/' . $nickname, $type, $this->userSession->isAuthenticated() && $profile['uid'] != $this->userSession->getLocalUserId());

		$condition = [
			'uid'     => $profile['uid'],
			'blocked' => false,
			'pending' => false,
			'hidden'  => false,
			'archive' => false,
			'failed'  => false,
			'self'    => false,
			'network' => [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS]
		];

		switch ($type) {
			case 'followers':
				$condition['rel'] = [Model\Contact::FOLLOWER, Model\Contact::FRIEND];
				break;
			case 'following':
				$condition['rel'] = [Model\Contact::SHARING, Model\Contact::FRIEND];
				break;
			case 'mutuals':
				$condition['rel'] = Model\Contact::FRIEND;
				break;
		}

		$total = $this->database->count('contact', $condition);

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 30);

		$params = ['order' => ['name' => false], 'limit' => [$pager->getStart(), $pager->getItemsPerPage()]];

		// Contact list is obtained from the visited profile user, but the contact display is visitor dependent
		$contacts = array_map(
			function ($contact) {
				$contact = Model\Contact::selectFirst(
					[],
					['uri-id' => $contact['uri-id'], 'uid' => [0, $this->userSession->getLocalUserId()]],
					['order' => ['uid' => 'DESC']]
				);
				return Module\Contact::getContactTemplateVars($contact);
			},
			Model\Contact::selectToArray(['uri-id'], $condition, $params)
		);

		$desc = '';
		switch ($type) {
			case 'followers':
				$title = $this->tt('Follower (%s)', 'Followers (%s)', $total);
				break;
			case 'following':
				$title = $this->tt('Following (%s)', 'Following (%s)', $total);
				break;
			case 'mutuals':
				$title = $this->tt('Mutual friend (%s)', 'Mutual friends (%s)', $total);
				$desc  = $this->t(
					'These contacts both follow and are followed by <strong>%s</strong>.',
					htmlentities($profile['name'], ENT_COMPAT, 'UTF-8')
				);
				break;
			case 'all':
			default:
				$title = $this->tt('Contact (%s)', 'Contacts (%s)', $total);
				break;
		}

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o   .= Renderer::replaceMacros($tpl, [
			'$title' => $title,
			'$desc'  => $desc,
			'$tabs'  => $tabs,

			'$noresult_label' => $this->t('No contacts.'),

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
