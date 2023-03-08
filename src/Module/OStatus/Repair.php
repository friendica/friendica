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
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Repair extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var SystemMessages */
	private $systemMessages;
	/** @var Database */
	private $database;
	/** @var App\Page */
	private $page;

	public function __construct(App\Page $page, Database $database, SystemMessages $systemMessages, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session        = $session;
		$this->systemMessages = $systemMessages;
		$this->database       = $database;
		$this->page           = $page;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			$this->systemMessages->addNotice($this->t('Permission denied.'));
			$this->baseUrl->redirect('login');
		}

		$uid = $this->session->getLocalUserId();

		$counter = intval($request['counter'] ?? 0);

		$condition = ['uid' => $uid, 'network' => Protocol::OSTATUS, 'rel' => [Contact::FRIEND, Contact::SHARING]];
		$total     = $this->database->count('contact', $condition);
		if ($total) {
			$contacts = Contact::selectToArray(['url'], $condition, ['order' => ['url'], 'limit' => [$counter++, 1]]);
			if ($contacts) {
				Contact::createFromProbeForUser($this->session->getLocalUserId(), $contacts[0]['url']);

				$this->page['htmlhead'] .= '<meta http-equiv="refresh" content="5; url=ostatus/repair?counter=' . $counter . '">';
			}
		}

		$tpl = Renderer::getMarkupTemplate('ostatus/repair.tpl');

		return Renderer::replaceMacros($tpl, [
			'$l10n'    => [
				'title'      => $this->t('Resubscribing to OStatus contacts'),
				'keep'       => $this->t('Keep this window open until done.'),
				'done'       => $this->t('✔ Done'),
				'nocontacts' => $this->t('No OStatus contacts to resubscribe to.'),
			],
			'$total'   => $total,
			'$counter' => $counter,
			'$contact' => $contacts[0] ?? null,
		]);
	}
}
