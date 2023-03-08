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
use Friendica\Contact\LocalRelationship\Repository\LocalRelationship;
use Friendica\Content\Nav;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\DBA;
use Friendica\Model;
use Friendica\Module\Contact;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 *  Show a contact posts and comments
 */
class Posts extends BaseModule
{
	/**
	 * @var LocalRelationship
	 */
	private $localRelationship;
	/**
	 * @var App\Page
	 */
	private $page;
	/**
	 * @var IHandleUserSessions
	 */
	private $userSession;

	public function __construct(L10n $l10n, LocalRelationship $localRelationship, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, App\Page $page, IHandleUserSessions $userSession, $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->localRelationship = $localRelationship;
		$this->page              = $page;
		$this->userSession       = $userSession;
	}

	protected function content(array $request = []): string
	{
		if (!$this->userSession->getLocalUserId()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		// Backward compatibility: Ensure to use the public contact when the user contact is provided
		// Remove by version 2022.03
		$data = Model\Contact::getPublicAndUserContactID(intval($this->parameters['id']), $this->userSession->getLocalUserId());
		if (empty($data)) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$contact = Model\Contact::getById($data['public']);
		if (!DBA::isResult($contact)) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		// Don't display contacts that are about to be deleted
		if (DBA::isResult($contact) && (!empty($contact['deleted']) || !empty($contact['network']) && $contact['network'] == Protocol::PHANTOM)) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$localRelationship = $this->localRelationship->getForUserContact($this->userSession->getLocalUserId(), $contact['id']);
		if ($localRelationship->rel === Model\Contact::SELF) {
			$this->baseUrl->redirect('profile/' . $contact['nick']);
		}

		$this->page['aside'] .= Widget\VCard::getHTML($contact);

		Nav::setSelected('contact');

		$o = Contact::getTabsHTML($contact, Contact::TAB_POSTS);

		$o .= Model\Contact::getPostsFromId($contact['id']);

		return $o;
	}
}
