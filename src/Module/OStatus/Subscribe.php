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

namespace Friendica\Module\OStatus;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\APContact;
use Friendica\Model\Contact;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Subscribe extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var ICanSendHttpRequests */
	private $httpClient;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var App\Page */
	private $page;

	public function __construct(App\Page $page, IManagePersonalConfigValues $pConfig, ICanSendHttpRequests $httpClient, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session        = $session;
		$this->systemMessages = $systemMessages;
		$this->httpClient     = $httpClient;
		$this->pConfig        = $pConfig;
		$this->page           = $page;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			$this->systemMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect('login');
		}

		$o = '<h2>' . $this->t('Subscribing to contacts') . '</h2>';

		$uid = $this->session->getLocalUserId();

		$counter = intval($request['counter'] ?? 0);

		if ($this->pConfig->get($uid, 'ostatus', 'legacy_friends') == '') {
			if (empty($request['url'])) {
				$this->pConfig->delete($uid, 'ostatus', 'legacy_contact');
				return $o . $this->t('No contact provided.');
			}

			$contact = Contact::getByURL($request['url']);
			if (!$contact) {
				$this->pConfig->delete($uid, 'ostatus', 'legacy_contact');
				return $o . $this->t('Couldn\'t fetch information for contact.');
			}

			if ($contact['network'] == Protocol::OSTATUS) {
				$api = $contact['baseurl'] . '/api/';

				// Fetching friends
				$curlResult = $this->httpClient->get($api . 'statuses/friends.json?screen_name=' . $contact['nick'], HttpClientAccept::JSON);

				if (!$curlResult->isSuccess()) {
					$this->pConfig->delete($uid, 'ostatus', 'legacy_contact');
					return $o . $this->t('Couldn\'t fetch friends for contact.');
				}

				$friends = $curlResult->getBody();
				if (empty($friends)) {
					$this->pConfig->delete($uid, 'ostatus', 'legacy_contact');
					return $o . $this->t('Couldn\'t fetch following contacts.');
				}
				$this->pConfig->set($uid, 'ostatus', 'legacy_friends', $friends);
			} elseif ($apcontact = APContact::getByURL($contact['url'])) {
				if (empty($apcontact['following'])) {
					$this->pConfig->delete($uid, 'ostatus', 'legacy_contact');
					return $o . $this->t('Couldn\'t fetch remote profile.');
				}
				$followings = ActivityPub::fetchItems($apcontact['following']);
				if (empty($followings)) {
					$this->pConfig->delete($uid, 'ostatus', 'legacy_contact');
					return $o . $this->t('Couldn\'t fetch following contacts.');
				}
				$this->pConfig->set($uid, 'ostatus', 'legacy_friends', json_encode($followings));
			} else {
				$this->pConfig->delete($uid, 'ostatus', 'legacy_contact');
				return $o . $this->t('Unsupported network');
			}
		}

		$friends = json_decode($this->pConfig->get($uid, 'ostatus', 'legacy_friends'));

		if (empty($friends)) {
			$friends = [];
		}

		$total = sizeof($friends);

		if ($counter >= $total) {
			$this->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . $this->baseUrl . '/settings/connectors">';
			$this->pConfig->delete($uid, 'ostatus', 'legacy_friends');
			$this->pConfig->delete($uid, 'ostatus', 'legacy_contact');
			$o .= $this->t('Done');
			return $o;
		}

		$friend = $friends[$counter++];

		$url = $friend->statusnet_profile_url ?? $friend;

		$o .= '<p>' . $counter . '/' . $total . ': ' . $url;

		$probed = Contact::getByURL($url);
		if (!empty($probed['network']) && in_array($probed['network'], Protocol::FEDERATED)) {
			$result = Contact::createFromProbeForUser($this->session->getLocalUserId(), $probed['url']);
			if ($result['success']) {
				$o .= ' - ' . $this->t('success');
			} else {
				$o .= ' - ' . $this->t('failed');
			}
		} else {
			$o .= ' - ' . $this->t('ignored');
		}

		$o .= '</p>';

		$o .= '<p>' . $this->t('Keep this window open until done.') . '</p>';

		$this->page['htmlhead'] = '<meta http-equiv="refresh" content="0; URL=' . $this->baseUrl . '/ostatus/subscribe?counter=' . $counter . '">';

		return $o;
	}
}
