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

namespace Friendica\Module\Search;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Search;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Saved extends BaseModule
{
	/** @var Database */
	protected $dba;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Database $dba, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba = $dba;
	}

	protected function rawContent(array $request = [])
	{
		$action = $this->args->get(2, 'none');
		$search = trim(rawurldecode($_GET['term'] ?? ''));

		$return_url = $_GET['return_url'] ?? Search::getSearchPath($search);

		if (DI::userSession()->getLocalUserId() && $search) {
			switch ($action) {
				case 'add':
					$fields = ['uid' => DI::userSession()->getLocalUserId(), 'term' => $search];
					if (!$this->dba->exists('search', $fields)) {
						if (!$this->dba->insert('search', $fields)) {
							DI::sysmsg()->addNotice($this->t('Search term was not saved.'));
						}
					} else {
						DI::sysmsg()->addNotice($this->t('Search term already saved.'));
					}
					break;

				case 'remove':
					if (!$this->dba->delete('search', ['uid' => DI::userSession()->getLocalUserId(), 'term' => $search])) {
						DI::sysmsg()->addNotice($this->t('Search term was not removed.'));
					}
					break;
			}
		}

		$this->baseUrl->redirect($return_url);
	}
}
