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
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Asynchronous HTML fragment provider for frio contact hovercards
 */
class Hovercard extends BaseModule
{
	/** @var IManageConfigValues */
	private $config;
	/** @var IHandleUserSessions */
	private $userSession;

	public function __construct(IHandleUserSessions $userSession, IManageConfigValues $config, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config      = $config;
		$this->userSession = $userSession;
	}

	protected function rawContent(array $request = [])
	{
		$contact_url = $request['url'] ?? '';

		// Get out if the system doesn't have public access allowed
		if ($this->config->get('system', 'block_public') && !$this->userSession->isAuthenticated()) {
			throw new HTTPException\ForbiddenException();
		}

		/* Possible formats for relative URLs that need to be converted to the absolute contact URL:
		 * - contact/redir/123456
		 * - contact/123456/conversations
		 */
		if (strpos($contact_url, 'contact/') === 0 && preg_match('/(\d+)/', $contact_url, $matches)) {
			$remote_contact = Contact::selectFirst(['nurl'], ['id' => $matches[1]]);
			$contact_url    = $remote_contact['nurl'] ?? '';
		}

		if (!$contact_url) {
			throw new HTTPException\BadRequestException();
		}

		// Search for contact data
		// Look if the local user has got the contact
		if ($this->userSession->isAuthenticated()) {
			$contact = Contact::getByURLForUser($contact_url, $this->userSession->getLocalUserId());
		} else {
			$contact = Contact::getByURL($contact_url, false);
		}

		if (!count($contact)) {
			throw new HTTPException\NotFoundException();
		}

		// Get the photo_menu - the menu if possible contact actions
		if ($this->userSession->isAuthenticated()) {
			$actions = Contact::photoMenu($contact, $this->userSession->getLocalUserId());
		} else {
			$actions = [];
		}

		// Move the contact data to the profile array so we can deliver it to
		$tpl = Renderer::getMarkupTemplate('hovercard.tpl');
		$o   = Renderer::replaceMacros($tpl, [
			'$profile' => [
				'name'         => $contact['name'],
				'nick'         => $contact['nick'],
				'addr'         => $contact['addr'] ?: $contact['url'],
				'thumb'        => Contact::getThumb($contact),
				'url'          => Contact::magicLinkByContact($contact),
				'nurl'         => $contact['nurl'],
				'location'     => $contact['location'],
				'about'        => $contact['about'],
				'network_link' => Strings::formatNetworkName($contact['network'], $contact['url']),
				'tags'         => $contact['keywords'],
				'bd'           => $contact['bd'] <= DBA::NULL_DATE ? '' : $contact['bd'],
				'account_type' => Contact::getAccountType($contact['contact-type']),
				'actions'      => $actions,
			],
		]);

		System::httpExit($o);
	}
}
