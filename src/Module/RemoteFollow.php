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

namespace Friendica\Module;

use Friendica\App;
use Friendica\App\Page;
use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Search;
use Friendica\Core\System;
use Friendica\Model\Contact;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Network\Probe;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Remotely follow the account on this system by the provided account
 */
class RemoteFollow extends BaseModule
{
	/** @var array */
	protected $owner;
	/** @var Page */
	protected $page;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, App\Page $page, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->owner = User::getOwnerDataByNick($this->parameters['profile']);
		if (!$this->owner) {
			throw new HTTPException\NotFoundException($this->t('User not found.'));
		}

		$this->page    = $page;
	}

	protected function post(array $request = [])
	{
		if (!empty($_POST['cancel']) || empty($_POST['dfrn_url'])) {
			$this->baseUrl->redirect();
		}
	
		if (empty($this->owner)) {
			notice($this->t('Profile unavailable.'));
			return;
		}
		
		$url = Probe::cleanURI($_POST['dfrn_url']);
		if (!strlen($url)) {
			notice($this->t("Invalid locator"));
			return;
		}

		// Detect the network, make sure the provided URL is valid
		$data = Contact::getByURL($url);
		if (!$data) {
			notice($this->t("The provided profile link doesn't seem to be valid"));
			return;
		}

		if (empty($data['subscribe'])) {
			notice($this->t("Remote subscription can't be done for your network. Please subscribe directly on your system."));
			return;
		}

		Logger::notice('Remote request', ['url' => $url, 'follow' => $this->owner['url'], 'remote' => $data['subscribe']]);
		
		// Substitute our user's feed URL into $data['subscribe']
		// Send the subscriber home to subscribe
		// Diaspora needs the uri in the format user@domain.tld
		if ($data['network'] == Protocol::DIASPORA) {
			$uri = urlencode($this->owner['addr']);
		} else {
			$uri = urlencode($this->owner['url']);
		}
	
		$follow_link = str_replace('{uri}', $uri, $data['subscribe']);
		System::externalRedirect($follow_link);
	}

	protected function content(array $request = []): string
	{
		if (empty($this->owner)) {
			return '';
		}

		$this->page['aside'] = Widget\VCard::getHTML($this->owner);

		$target_addr = $this->owner['addr'];
		$target_url = $this->owner['url'];

		$tpl = Renderer::getMarkupTemplate('auto_request.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$header'        => $this->t('Friend/Connection Request'),
			'$page_desc'     => $this->t('Enter your Webfinger address (user@domain.tld) or profile URL here. If this isn\'t supported by your system, you have to subscribe to <strong>%s</strong> or <strong>%s</strong> directly on your system.', $target_addr, $target_url),
			'$invite_desc'   => $this->t('If you are not yet a member of the free social web, <a href="%s">follow this link to find a public Friendica node and join us today</a>.', Search::getGlobalDirectory() . '/servers'),
			'$your_address'  => $this->t('Your Webfinger address or profile URL:'),
			'$pls_answer'    => $this->t('Please answer the following:'),
			'$submit'        => $this->t('Submit Request'),
			'$cancel'        => $this->t('Cancel'),

			'$request'       => 'remote_follow/' . $this->parameters['profile'],
			'$name'          => $this->owner['name'],
			'$myaddr'        => Profile::getMyURL(),
		]);
		return $o;
	}
}
