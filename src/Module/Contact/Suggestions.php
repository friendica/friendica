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
use Friendica\Core\L10n;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Model\Contact;
use Friendica\Module\Contact as ModuleContact;
use Friendica\Network\HTTPException;

class Suggestions extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $session;
	/** @var App\Page */
	private $page;

	public function __construct(App\Page $page, IHandleUserSessions $session, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session = $session;
		$this->page    = $page;
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\ForbiddenException($this->t('Permission denied.'));
		}

		$this->page['aside'] .= Widget::findPeople();
		$this->page['aside'] .= Widget::follow();

		$contacts = Contact\Relation::getCachedSuggestions($this->session->getLocalUserId());
		if (!$contacts) {
			return $this->t('No suggestions available. If this is a new site, please try again in 24 hours.');
		}

		$entries = [];
		foreach ($contacts as $contact) {
			$entries[] = ModuleContact::getContactTemplateVars($contact);
		}

		$tpl = Renderer::getMarkupTemplate('contact/list.tpl');

		return Renderer::replaceMacros($tpl, [
			'$title'    => $this->t('Friend Suggestions'),
			'$contacts' => $entries,
		]);
	}
}
