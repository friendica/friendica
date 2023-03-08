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

namespace Friendica\Module\Debug;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\Response;
use Friendica\Network\HTTPClient\Capability\ICanSendHttpRequests;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Protocol;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Tests a given feed of a contact
 */
class Feed extends BaseModule
{
	/** @var ICanSendHttpRequests */
	protected $httpClient;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, ICanSendHttpRequests $httpClient, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->httpClient = $httpClient;

		if (!DI::userSession()->getLocalUserId()) {
			DI::sysmsg()->addNotice($this->t('You must be logged in to use this module'));
			$baseUrl->redirect();
		}
	}

	protected function content(array $request = []): string
	{
		$result = [];
		if (!empty($_REQUEST['url'])) {
			$url = $_REQUEST['url'];

			$contact = Model\Contact::getByURLForUser($url, DI::userSession()->getLocalUserId(), null);

			$xml = $this->httpClient->fetch($contact['poll'], HttpClientAccept::FEED_XML);

			$import_result = Protocol\Feed::import($xml);

			$result = [
				'input' => $xml,
				'output' => var_export($import_result, true),
			];
		}

		$tpl = Renderer::getMarkupTemplate('feedtest.tpl');
		return Renderer::replaceMacros($tpl, [
			'$url'    => ['url', $this->t('Source URL'), $_REQUEST['url'] ?? '', ''],
			'$result' => $result
		]);
	}
}
