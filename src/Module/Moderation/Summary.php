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

namespace Friendica\Module\Moderation;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Register;
use Friendica\Module\BaseModeration;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Summary extends BaseModeration
{
	/** @var Database */
	private $database;

	public function __construct(Database $database, App\Page $page, App $app, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($page, $app, $systemMessages, $session, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$accounts = [
			[$this->t('Normal Account'), 0],
			[$this->t('Automatic Follower Account'), 0],
			[$this->t('Public Group Account'), 0],
			[$this->t('Automatic Friend Account'), 0],
			[$this->t('Blog Account'), 0],
			[$this->t('Private Group Account'), 0]
		];

		$users = 0;

		$pageFlagsCountStmt = $this->database->p('SELECT `page-flags`, COUNT(`uid`) AS `count` FROM `user` WHERE `uid` != ? GROUP BY `page-flags`', 0);
		while ($pageFlagsCount = $this->database->fetch($pageFlagsCountStmt)) {
			$accounts[$pageFlagsCount['page-flags']][1] = $pageFlagsCount['count'];
			$users += $pageFlagsCount['count'];
		}
		$this->database->close($pageFlagsCountStmt);

		$this->logger->debug('accounts', ['accounts' => $accounts]);

		$pending = Register::getPendingCount();

		$t = Renderer::getMarkupTemplate('moderation/summary.tpl');
		return Renderer::replaceMacros($t, [
			'$title'       => $this->t('Moderation'),
			'$page'        => $this->t('Summary'),
			'$users'       => [$this->t('Registered users'), $users],
			'$accounts'    => $accounts,
			'$pending'     => [$this->t('Pending registrations'), $pending],
			'$warningtext' => [],
		]);
	}
}
