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
use Friendica\App\Page;
use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\Contact;
use Friendica\Module\Response;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * GUI for advanced contact details manipulation
 */
class Advanced extends BaseModule
{
	/** @var Database */
	protected $dba;
	/** @var Page */
	protected $page;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, App\Page $page, LoggerInterface $logger, Profiler $profiler, Response $response, Database $dba, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba  = $dba;
		$this->page = $page;

		if (!DI::userSession()->isAuthenticated()) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}
	}

	protected function post(array $request = [])
	{
		$cid = $this->parameters['id'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid, 'uid' => DI::userSession()->getLocalUserId()]);
		if (empty($contact)) {
			throw new BadRequestException($this->t('Contact not found.'));
		}

		$name        = ($_POST['name'] ?? '') ?: $contact['name'];
		$nick        = $_POST['nick'] ?? '';
		$url         = $_POST['url'] ?? '';
		$poll        = $_POST['poll'] ?? '';
		$photo       = $_POST['photo'] ?? '';
		$nurl        = Strings::normaliseLink($url);

		$r = $this->dba->update(
			'contact',
			[
				'name'        => $name,
				'nick'        => $nick,
				'url'         => $url,
				'nurl'        => $nurl,
				'poll'        => $poll,
			],
			['id' => $contact['id'], 'uid' => DI::userSession()->getLocalUserId()]
		);

		if ($photo) {
			$this->logger->notice('Updating photo.', ['photo' => $photo]);

			Model\Contact::updateAvatar($contact['id'], $photo, true);
		}

		if (!$r) {
			DI::sysmsg()->addNotice($this->t('Contact update failed.'));
		}
	}

	protected function content(array $request = []): string
	{
		$cid = $this->parameters['id'];

		$contact = Model\Contact::selectFirst([], ['id' => $cid, 'uid' => DI::userSession()->getLocalUserId()]);
		if (empty($contact)) {
			throw new BadRequestException($this->t('Contact not found.'));
		}

		$this->page['aside'] = Widget\VCard::getHTML($contact);

		$returnaddr = "contact/$cid";

		// This data is fetched automatically for most networks.
		// Editing does only makes sense for mail and feed contacts.
		if (!in_array($contact['network'], [Protocol::FEED, Protocol::MAIL])) {
			$readonly = 'readonly';
		} else {
			$readonly = '';
		}

		$tab_str = Contact::getTabsHTML($contact, Contact::TAB_ADVANCED);

		$tpl = Renderer::getMarkupTemplate('contact/advanced.tpl');
		return Renderer::replaceMacros($tpl, [
			'$tab_str'           => $tab_str,
			'$returnaddr'        => $returnaddr,
			'$return'            => $this->t('Return to contact editor'),
			'$contact_id'        => $contact['id'],
			'$lbl_submit'        => $this->t('Submit'),

			'$name'    => ['name', $this->t('Name'), $contact['name'], '', '', $readonly],
			'$nick'    => ['nick', $this->t('Account Nickname'), $contact['nick'], '', '', 'readonly'],
			'$url'     => ['url', $this->t('Account URL'), $contact['url'], '', '', 'readonly'],
			'poll'     => ['poll', $this->t('Poll/Feed URL'), $contact['poll'], '', '', ($contact['network'] == Protocol::FEED) ? '' : 'readonly'],
			'photo'    => ['photo', $this->t('New photo from this URL'), '', '', '', $readonly],
		]);
	}
}
