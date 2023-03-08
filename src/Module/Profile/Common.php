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
use Friendica\Module;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Module\BaseProfile;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Common extends BaseProfile
{
	/** @var IManageConfigValues */
	private $config;
	/** @var IHandleUserSessions */
	private $userSession;
	/** @var App */
	private $app;

	public function __construct(App $app, IHandleUserSessions $userSession, IManageConfigValues $config, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config = $config;
		$this->userSession = $userSession;
		$this->app = $app;
	}

	protected function content(array $request = []): string
	{
		if ($this->config->get('system', 'block_public') && !$this->userSession->isAuthenticated()) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		Nav::setSelected('home');

		$nickname = $this->parameters['nickname'];

		$profile = Profile::load($this->app, $nickname);
		if (empty($profile)) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		if (!empty($profile['hide-friends'])) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		$displayCommonTab = $this->userSession->isAuthenticated() && $profile['uid'] != $this->userSession->getLocalUserId();

		if (!$displayCommonTab) {
			$this->baseUrl->redirect('profile/' . $nickname . '/contacts');
		};

		$o = self::getTabsHTML('contacts', false, $profile['nickname'], $profile['hide-friends']);

		$tabs = self::getContactFilterTabs('profile/' . $nickname, 'common', $displayCommonTab);

		$sourceId = Contact::getIdForURL($this->userSession->getMyUrl());
		$targetId = Contact::getPublicIdByUserId($profile['uid']);

		$condition = [
			'blocked' => false,
			'deleted' => false,
			'network' => [Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, Protocol::FEED],
		];

		$total = Contact\Relation::countCommon($sourceId, $targetId, $condition);

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 30);

		$commonFollows = Contact\Relation::listCommon($sourceId, $targetId, $condition, $pager->getItemsPerPage(), $pager->getStart());

		// Contact list is obtained from the visited profile user, but the contact display is visitor dependent
		$contacts = array_map(
			function ($contact) {
				$contact = Contact::selectFirst(
					[],
					['uri-id' => $contact['uri-id'], 'uid' => [0, $this->userSession->getLocalUserId()]],
					['order' => ['uid' => 'DESC']]
				);
				return Module\Contact::getContactTemplateVars($contact);
			},
			$commonFollows
		);

		$title = $this->tt('Common contact (%s)', 'Common contacts (%s)', $total);
		$desc = $this->t(
			'Both <strong>%s</strong> and yourself have publicly interacted with these contacts (follow, comment or likes on public posts).',
			htmlentities($profile['name'], ENT_COMPAT, 'UTF-8')
		);

		$tpl = Renderer::getMarkupTemplate('profile/contacts.tpl');
		$o .= Renderer::replaceMacros($tpl, [
			'$title'    => $title,
			'$desc'     => $desc,
			'$tabs'     => $tabs,

			'$noresult_label'  => $this->t('No common contacts.'),

			'$contacts' => $contacts,
			'$paginate' => $pager->renderFull($total),
		]);

		return $o;
	}
}
