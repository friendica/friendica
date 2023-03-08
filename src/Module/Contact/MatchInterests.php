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

namespace Friendica\Module\Contact;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Module\Contact as ModuleContact;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * It takes keywords from your profile and queries the directory server for
 * matching keywords from other profiles.
 */
class MatchInterests extends BaseModule
{
	const FETCH_PER_PAGE = 100;

	/** @var IHandleUserSessions */
	protected $session;
	/** @var Database */
	protected $database;
	/** @var SystemMessages */
	protected $systemMessages;
	/** @var App\Page */
	protected $page;
	/** @var App\Mode */
	protected $mode;
	/** @var IManageConfigValues */
	protected $config;
	/** @var IManagePersonalConfigValues */
	protected $pConfig;
	/** @var ICanSendHttpRequests */
	protected $httpClient;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, Database $database, SystemMessages $systemMessages, App\Page $page, App\Mode $mode, IManageConfigValues $config, IManagePersonalConfigValues $pConfig, ICanSendHttpRequests $httpClient, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session        = $session;
		$this->database       = $database;
		$this->systemMessages = $systemMessages;
		$this->page           = $page;
		$this->mode           = $mode;
		$this->config         = $config;
		$this->pConfig        = $pConfig;
		$this->httpClient     = $httpClient;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			$this->systemMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect('login&return_path=match');
		}

		$profile = Profile::getByUID($this->session->getLocalUserId());

		if (empty($profile)) {
			$this->logger->warning('Couldn\'t find Profile for user id in session.', ['uid' => $this->session->getLocalUserId()]);
			throw new InternalServerErrorException($this->t('Invalid request.'));
		}

		$this->page['aside'] .= Widget::findPeople();
		$this->page['aside'] .= Widget::follow();

		if (empty($profile['pub_keywords']) && empty($profile['prv_keywords'])) {
			$this->systemMessages->addNotice($this->t('No keywords to match. Please add keywords to your profile.'));
			return '';
		}

		if ($this->mode->isMobile()) {
			$limit = $this->pConfig->get($this->session->getLocalUserId(), 'system', 'itemspage_mobile_network')
					 ?? $this->config->get('system', 'itemspage_network_mobile');
		} else {
			$limit = $this->pConfig->get($this->session->getLocalUserId(), 'system', 'itemspage_network')
					 ?? $this->config->get('system', 'itemspage_network');
		}

		$searchParameters = [
			's' => trim($profile['pub_keywords'] . ' ' . $profile['prv_keywords']),
			'n' => self::FETCH_PER_PAGE,
		];

		$entries = [];

		foreach ([Search::getGlobalDirectory(), $this->baseUrl] as $server) {
			if (empty($server)) {
				continue;
			}

			$result = $this->httpClient->post($server . '/search/user/tags', $searchParameters);
			if (!$result->isSuccess()) {
				// try legacy endpoint
				$result = $this->httpClient->post($server . '/msearch', $searchParameters);
				if (!$result->isSuccess()) {
					$this->logger->notice('Search-Endpoint not available for server.', ['server' => $server]);
					continue;
				}
			}

			$entries = $this->parseContacts(json_decode($result->getBody()), $entries, $limit);
		}

		if (empty($entries)) {
			$this->systemMessages->addNotice($this->t('No matches'));
		}

		$tpl = Renderer::getMarkupTemplate('contact/list.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'    => $this->t('Profile Match'),
			'$contacts' => array_slice($entries, 0, $limit),
		]);
	}

	/**
	 * parses the JSON result and adds the new entries until the limit is reached
	 *
	 * @param       $jsonResult
	 * @param array $entries
	 * @param int   $limit
	 *
	 * @return array the new entries array
	 */
	protected function parseContacts($jsonResult, array $entries, int $limit): array
	{
		if (empty($jsonResult->results)) {
			return $entries;
		}

		foreach ($jsonResult->results as $profile) {
			if (!$profile) {
				continue;
			}

			// Already known contact
			$contact = Contact::getByURL($profile->url, null, ['rel'], $this->session->getLocalUserId());
			if (!empty($contact) && in_array($contact['rel'], [Contact::FRIEND, Contact::SHARING])) {
				continue;
			}

			$contact = Contact::getByURLForUser($profile->url, $this->session->getLocalUserId());
			if (!empty($contact)) {
				$entries[$contact['id']] = ModuleContact::getContactTemplateVars($contact);
			}

			if (count($entries) == $limit) {
				break;
			}
		}
		return $entries;
	}
}
