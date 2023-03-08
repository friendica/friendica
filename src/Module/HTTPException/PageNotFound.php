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

namespace Friendica\Module\HTTPException;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Module\Response;
use Friendica\Module\Special\HTTPException as ModuleHTTPException;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class PageNotFound extends BaseModule
{
	/** @var string */
	private $remoteAddress;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, App\Request $request, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->remoteAddress = $request->getRemoteAddress();
	}

	protected function content(array $request = []): string
	{
		throw new HTTPException\NotFoundException($this->t('Page not found.'));
	}

	public function run(ModuleHTTPException $httpException, array $request = []): ResponseInterface
	{
		/* The URL provided does not resolve to a valid module.
		 *
		 * On Dreamhost sites, quite often things go wrong for no apparent reason and they send us to '/internal_error.html'.
		 * We don't like doing this, but as it occasionally accounts for 10-20% or more of all site traffic -
		 * we are going to trap this and redirect back to the requested page. As long as you don't have a critical error on your page
		 * this will often succeed and eventually do the right thing.
		 *
		 * Otherwise we are going to emit a 404 not found.
		 */
		$queryString = $this->server['QUERY_STRING'];
		// Stupid browser tried to pre-fetch our Javascript img template. Don't log the event or return anything - just quietly exit.
		if (!empty($queryString) && preg_match('/{[0-9]}/', $queryString) !== 0) {
			System::exit();
		}

		if (!empty($queryString) && ($queryString === 'q=internal_error.html') && isset($dreamhost_error_hack)) {
			$this->logger->info('index.php: dreamhost_error_hack invoked.', ['Original URI' => $this->server['REQUEST_URI']]);
			$this->baseUrl->redirect($this->server['REQUEST_URI']);
		}

		$this->logger->debug('index.php: page not found.', [
			'request_uri' => $this->server['REQUEST_URI'],
			'address'     => $this->remoteAddress,
			'query'       => $this->server['QUERY_STRING']
		]);

		return parent::run($httpException, $request);
	}
}
