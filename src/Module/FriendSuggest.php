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

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Core\Worker;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\Contact as ContactModel;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Protocol\Delivery;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Suggest friends to a known contact
 */
class FriendSuggest extends BaseModule
{
	/** @var Database */
	protected $dba;
	/** @var \Friendica\Contact\FriendSuggest\Repository\FriendSuggest */
	protected $friendSuggestRepo;
	/** @var \Friendica\Contact\FriendSuggest\Factory\FriendSuggest */
	protected $friendSuggestFac;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, Database $dba, \Friendica\Contact\FriendSuggest\Repository\FriendSuggest $friendSuggestRepo, \Friendica\Contact\FriendSuggest\Factory\FriendSuggest $friendSuggestFac, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		if (!DI::userSession()->getLocalUserId()) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}

		$this->dba               = $dba;
		$this->friendSuggestRepo = $friendSuggestRepo;
		$this->friendSuggestFac  = $friendSuggestFac;
	}

	protected function post(array $request = [])
	{
		$cid = intval($this->parameters['contact']);

		// We do query the "uid" as well to ensure that it is our contact
		if (!$this->dba->exists('contact', ['id' => $cid, 'uid' => DI::userSession()->getLocalUserId()])) {
			throw new NotFoundException($this->t('Contact not found.'));
		}

		$suggest_contact_id = intval($_POST['suggest']);
		if (empty($suggest_contact_id)) {
			return;
		}

		// We do query the "uid" as well to ensure that it is our contact
		$contact = $this->dba->selectFirst('contact', ['name', 'url', 'request', 'avatar'], ['id' => $suggest_contact_id, 'uid' => DI::userSession()->getLocalUserId()]);
		if (empty($contact)) {
			DI::sysmsg()->addNotice($this->t('Suggested contact not found.'));
			return;
		}

		$note = Strings::escapeHtml(trim($_POST['note'] ?? ''));

		$suggest = $this->friendSuggestRepo->save($this->friendSuggestFac->createNew(
			DI::userSession()->getLocalUserId(),
			$cid,
			$contact['name'],
			$contact['url'],
			$contact['request'],
			$contact['avatar'],
			$note
		));

		Worker::add(Worker::PRIORITY_HIGH, 'Notifier', Delivery::SUGGESTION, $suggest->id);

		DI::sysmsg()->addInfo($this->t('Friend suggestion sent.'));
	}

	protected function content(array $request = []): string
	{
		$cid = intval($this->parameters['contact']);

		$contact = $this->dba->selectFirst('contact', [], ['id' => $cid, 'uid' => DI::userSession()->getLocalUserId()]);
		if (empty($contact)) {
			DI::sysmsg()->addNotice($this->t('Contact not found.'));
			$this->baseUrl->redirect();
		}

		$suggestableContacts = ContactModel::selectToArray(['id', 'name'], [
			'`uid` = ? 
			AND `id` != ? 
			AND `network` = ? 
			AND NOT `self` 
			AND NOT `blocked` 
			AND NOT `pending` 
			AND NOT `archive` 
			AND NOT `deleted` 
			AND `notify` != ""',
			DI::userSession()->getLocalUserId(),
			$cid,
			Protocol::DFRN,
		]);

		$formattedContacts = [];

		foreach ($suggestableContacts as $suggestableContact) {
			$formattedContacts[$suggestableContact['id']] = $suggestableContact['name'];
		}

		$tpl = Renderer::getMarkupTemplate('fsuggest.tpl');
		return Renderer::replaceMacros($tpl, [
			'$contact_id'      => $cid,
			'$fsuggest_title'  => $this->t('Suggest Friends'),
			'$fsuggest_select' => [
				'suggest',
				$this->t('Suggest a friend for %s', $contact['name']),
				'',
				'',
				$formattedContacts,
			],
			'$submit'          => $this->t('Submit'),
		]);
	}
}
