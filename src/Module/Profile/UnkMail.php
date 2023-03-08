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

namespace Friendica\Module\Profile;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\Database;
use Friendica\Model\Mail;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Unknown Mail module
 */
class UnkMail extends \Friendica\BaseModule
{
	/** @var IHandleUserSessions */
	private $userSessions;

	/** @var SystemMessages */
	private $systemMessages;

	/** @var Database */
	private $database;

	/** @var App\Page */
	private $page;

	public function __construct(App\Page $page, Database $database, SystemMessages $systemMessages, IHandleUserSessions $userSessions, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->userSessions   = $userSessions;
		$this->systemMessages = $systemMessages;
		$this->database       = $database;
		$this->page           = $page;
	}

	protected function post(array $request = [])
	{
		$replyto = $this->userSessions->getMyUrl();
		if (!$replyto) {
			$this->systemMessages->addNotice($this->l10n->t('Permission denied.'));
			return;
		}

		$recipient = $this->parameters['nickname'];
		$subject   = trim($request['subject'] ?? '');
		$body      = Strings::escapeHtml(trim($request['body'] ?? ''));

		if (!$body) {
			$this->systemMessages->addNotice($this->l10n->t('Empty message body.'));
			return;
		}

		$user = User::getByNickname($recipient);
		if (empty($user)) {
			return;
		}

		if (!$user['unkmail']) {
			return;
		}

		$total = $this->database->count('mail', ["`uid` = ? AND `created` > ? AND `unknown`", $user['uid'], DateTimeFormat::utc('now - 1 day')]);
		if ($total > $user['cntunkmail']) {
			return;
		}

		$ret = Mail::sendWall($user, $body, $subject, $replyto);

		switch ($ret) {
			case -1:
				$this->systemMessages->addNotice($this->l10n->t('No recipient selected.'));
				break;
			case -2:
				$this->systemMessages->addNotice($this->l10n->t('Unable to check your home location.'));
				break;
			case -3:
				$this->systemMessages->addNotice($this->l10n->t('Message could not be sent.'));
				break;
			case -4:
				$this->systemMessages->addNotice($this->l10n->t('Message collection failure.'));
				break;
		}

		$this->baseUrl->redirect('profile/' . $user['nickname']);
	}

	protected function content(array $request = []): string
	{
		$returnUrl = 'profile/' . $this->parameters['nickname'];

		if (!$this->userSessions->getMyUrl()) {
			$this->systemMessages->addNotice($this->l10n->t('Permission denied.'));
			$this->baseUrl->redirect($returnUrl);
		}

		$user = User::getByNickname($this->parameters['nickname']);
		if (empty($user)) {
			$this->systemMessages->addNotice($this->l10n->t('Recipient not found.'));
			$this->baseUrl->redirect($returnUrl);
		}

		if (!$user['unkmail']) {
			$this->systemMessages->addNotice($this->l10n->t('Permission denied.'));
			$this->baseUrl->redirect($returnUrl);
		}

		$total = $this->database->count('mail', ["`uid` = ? AND `created` > ? AND `unknown`", $user['uid'], DateTimeFormat::utc('now - 1 day')]);
		if ($total > $user['cntunkmail']) {
			$this->systemMessages->addNotice($this->l10n->t('Number of daily wall messages for %s exceeded. Message failed.', $user['username']));
			$this->baseUrl->redirect($returnUrl);
		}

		$tpl = Renderer::getMarkupTemplate('profile/unkmail-header.tpl');
		$this->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$nickname' => $user['nickname'],
			'$linkurl'  => $this->l10n->t('Please enter a link URL:')
		]);

		$tpl = Renderer::getMarkupTemplate('profile/unkmail.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'header'    => $this->l10n->t('Send Private Message'),
				'subheader' => $this->l10n->t('If you wish for %s to respond, please check that the privacy settings on your site allow private mail from unknown senders.', $user['username']),
				'insert'    => $this->l10n->t('Insert web link'),
				'wait'      => $this->l10n->t('Please wait'),
				'submit'    => $this->l10n->t('Submit'),
			],

			'$nickname' => $user['nickname'],

			'$to'      => ['to'     , $this->l10n->t('To')          , $user['username'], '', '', 'disabled'],
			'$subject' => ['subject', $this->l10n->t('Subject')     , $request['subject'] ?? ''],
			'$body'    => ['body'   , $this->l10n->t('Your message'), $request['body'] ?? ''],
		]);
	}
}
