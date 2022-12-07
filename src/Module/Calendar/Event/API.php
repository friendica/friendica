<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Calendar\Event;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\Event;
use Friendica\Model\Item;
use Friendica\Model\Post;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\BadRequestException;
use Friendica\Network\HTTPException\UnauthorizedException;
use Friendica\Util\ACLFormatter;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Friendica\Worker\Delivery;
use Psr\Log\LoggerInterface;

/**
 * Basic API class for events
 * currently supports create, delete, ignore, unignore
 *
 * @todo: make create/update as REST-call instead of POST
 */
class API extends BaseModule
{
	const ACTION_CREATE   = 'create';
	const ACTION_DELETE   = 'delete';
	const ACTION_IGNORE   = 'ignore';
	const ACTION_UNIGNORE = 'unignore';

	const ALLOWED_ACTIONS = [
		self::ACTION_CREATE,
		self::ACTION_DELETE,
		self::ACTION_IGNORE,
		self::ACTION_UNIGNORE,
	];

	/** @var IHandleUserSessions */
	protected $session;
	/** @var SystemMessages */
	protected $sysMessages;
	/** @var ACLFormatter */
	protected $aclFormatter;
	/** @var string */
	protected $timezone;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IHandleUserSessions $session, SystemMessages $sysMessages, ACLFormatter $aclFormatter, App $app, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->session      = $session;
		$this->sysMessages  = $sysMessages;
		$this->aclFormatter = $aclFormatter;
		$this->timezone     = $app->getTimeZone();

		if (!$this->session->getLocalUserId()) {
			throw new UnauthorizedException($this->t('Permission denied.'));
		}
	}

	protected function post(array $request = [])
	{
		$this->createEvent($request);
	}

	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['action']) || !in_array($this->parameters['action'], self::ALLOWED_ACTIONS)) {
			throw new BadRequestException($this->t('Invalid Request'));
		}

		// CREATE is done per POSt, so nothing to do left
		if ($this->parameters['action'] === static::ACTION_CREATE) {
			return;
		}

		if (empty($this->parameters['id'])) {
			throw new BadRequestException($this->t('Event id is missing.'));
		}

		$returnPath = $request['return_path'] ?? 'calendar';

		switch ($this->parameters['action']) {
			case self::ACTION_IGNORE:
				Event::setIgnore($this->session->getLocalUserId(), $this->parameters['id']);
				break;
			case self::ACTION_UNIGNORE:
				Event::setIgnore($this->session->getLocalUserId(), $this->parameters['id'], false);
				break;
			case self::ACTION_DELETE:
				// Remove an event from the calendar and its related items
				$event = Event::getByIdAndUid($this->session->getLocalUserId(), $this->parameters['id']);

				// Delete only real events (no birthdays)
				if (!empty($event) && $event['type'] == 'event') {
					Item::deleteForUser(['id' => $event['itemid']], $this->session->getLocalUserId());
				}

				if (Post::exists(['id' => $event['itemid']])) {
					$this->sysMessages->addNotice($this->t('Failed to remove event'));
				}
				break;
			default:
				throw new BadRequestException($this->t('Invalid Request'));
		}

		$this->baseUrl->redirect($returnPath);
	}

	protected function createEvent(array $request)
	{
		$eventId = !empty($request['event_id']) ? intval($request['event_id']) : 0;
		$uid     = (int)$this->session->getLocalUserId();
		$cid     = !empty($request['cid']) ? intval($request['cid']) : 0;

		$strStartDateTime  = Strings::escapeHtml($request['start_text'] ?? '');
		$strFinishDateTime = Strings::escapeHtml($request['finish_text'] ?? '');

		$noFinish = intval($request['nofinish'] ?? 0);

		$share     = intval($request['share'] ?? 0);
		$isPreview = intval($request['preview'] ?? 0);

		$start = DateTimeFormat::convert($strStartDateTime ?? DBA::NULL_DATETIME, 'UTC', $this->timezone);
		if (!$noFinish) {
			$finish = DateTimeFormat::convert($strFinishDateTime ?? DBA::NULL_DATETIME, 'UTC', $this->timezone);
		} else {
			$finish = DBA::NULL_DATETIME;
		}

		// Don't allow the event to finish before it begins.
		// It won't hurt anything, but somebody will file a bug report,
		// and we'll waste a bunch of time responding to it. Time that
		// could've been spent doing something else.

		$summary  = trim($request['summary'] ?? '');
		$desc     = trim($request['desc'] ?? '');
		$location = trim($request['location'] ?? '');
		$type     = 'event';

		$params = [
			'summary'  => $summary,
			'desc'     => $desc,
			'location' => $location,
			'start'    => $strStartDateTime,
			'finish'   => $strFinishDateTime,
			'nofinish' => $noFinish,
		];

		$action          = empty($eventId) ? 'new' : 'edit/' . $eventId;
		$redirectOnError = 'calendar/event/' . $action . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

		if (strcmp($finish, $start) < 0 && !$noFinish) {
			if ($isPreview) {
				System::httpExit($this->t('Event can not end before it has started.'));
			} else {
				$this->sysMessages->addNotice($this->t('Event can not end before it has started.'));
				$this->baseUrl->redirect($redirectOnError);
			}
		}

		if (empty($summary) || ($start === DBA::NULL_DATETIME)) {
			if ($isPreview) {
				System::httpExit($this->t('Event title and start time are required.'));
			} else {
				$this->sysMessages->addNotice($this->t('Event title and start time are required.'));
				$this->baseUrl->redirect($redirectOnError);
			}
		}

		$self = Contact::getPublicIdByUserId($uid);

		$aclFormatter = $this->aclFormatter;

		if ($share) {
			$user = User::getById($uid, ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid']);
			if (empty($user)) {
				$this->logger->warning('Cannot find user for an event.', ['uid' => $uid, 'event' => $eventId]);
				$this->response->setStatus(500);
				return;
			}

			$strAclContactAllow = isset($request['contact_allow']) ? $aclFormatter->toString($request['contact_allow']) : $user['allow_cid'] ?? '';
			$strAclGroupAllow   = isset($request['group_allow']) ? $aclFormatter->toString($request['group_allow']) : $user['allow_gid']     ?? '';
			$strContactDeny     = isset($request['contact_deny']) ? $aclFormatter->toString($request['contact_deny']) : $user['deny_cid']    ?? '';
			$strGroupDeny       = isset($request['group_deny']) ? $aclFormatter->toString($request['group_deny']) : $user['deny_gid']        ?? '';

			$visibility = $request['visibility'] ?? '';
			if ($visibility === 'public') {
				// The ACL selector introduced in version 2019.12 sends ACL input data even when the Public visibility is selected
				$strAclContactAllow = $strAclGroupAllow = $strContactDeny = $strGroupDeny = '';
			} elseif ($visibility === 'custom') {
				// Since we know from the visibility parameter the item should be private, we have to prevent the empty ACL
				// case that would make it public. So we always append the author's contact id to the allowed contacts.
				// See https://github.com/friendica/friendica/issues/9672
				$strAclContactAllow .= $aclFormatter->toString($self);
			}
		} else {
			$strAclContactAllow = $aclFormatter->toString($self);
			$strAclGroupAllow   = '';
			$strContactDeny     = '';
			$strGroupDeny       = '';
		}

		$datarray = [
			'start'     => $start,
			'finish'    => $finish,
			'summary'   => $summary,
			'desc'      => $desc,
			'location'  => $location,
			'type'      => $type,
			'nofinish'  => $noFinish,
			'uid'       => $uid,
			'cid'       => $cid,
			'allow_cid' => $strAclContactAllow,
			'allow_gid' => $strAclGroupAllow,
			'deny_cid'  => $strContactDeny,
			'deny_gid'  => $strGroupDeny,
			'id'        => $eventId,
		];

		if (intval($request['preview'])) {
			System::httpExit(Event::getHTML($datarray));
		}

		$eventId = Event::store($datarray);

		$newItem = Event::getItemArrayForId($eventId, [
			'network'   => Protocol::DFRN,
			'protocol'  => Conversation::PARCEL_DIRECT,
			'direction' => Conversation::PUSH
		]);
		if (Item::insert($newItem)) {
			$uriId = (int)$newItem['uri-id'];
		} else {
			$uriId = 0;
		}

		if (!$cid && $uriId) {
			Worker::add(Worker::PRIORITY_HIGH, 'Notifier', Delivery::POST, $uriId, $uid);
		}

		$this->baseUrl->redirect('calendar');
	}
}
