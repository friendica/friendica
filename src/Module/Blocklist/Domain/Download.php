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

namespace Friendica\Module\Blocklist\Domain;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\System;
use Friendica\Moderation\DomainPatternBlocklist;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Download extends \Friendica\BaseModule
{
	/** @var DomainPatternBlocklist */
	private $blocklist;

	public function __construct(DomainPatternBlocklist $blocklist, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->blocklist = $blocklist;
	}

	/**
	 * @param array $request
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function rawContent(array $request = [])
	{
		$hash = md5(json_encode($this->blocklist->get(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

		$etag = 'W/"' . $hash . '"';
		if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') == $etag) {
			header('HTTP/1.1 304 Not Modified');
			System::exit();
		}

		header('Content-Type: text/csv');
		header('Content-Transfer-Encoding: Binary');
		header('Content-disposition: attachment; filename="' . $this->baseUrl->getHost() . '_domain_blocklist_' . substr($hash, 0, 6) . '.csv"');
		header("Etag: $etag");

		$this->blocklist->exportToFile('php://output');

		System::exit();
	}
}
