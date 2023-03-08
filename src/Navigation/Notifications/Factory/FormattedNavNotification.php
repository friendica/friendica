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

namespace Friendica\Navigation\Notifications\Factory;

use Friendica\BaseFactory;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Model\Contact;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Navigation\Notifications\Exception\NoMessageException;
use Friendica\Navigation\Notifications\ValueObject;
use Friendica\Network\HTTPException;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Proxy;
use Friendica\Util\Temporal;
use GuzzleHttp\Psr7\Uri;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating notification objects based on items
 */
class FormattedNavNotification extends BaseFactory
{
	private static $contacts = [];

	/** @var Notification */
	private $notification;
	/** @var \Friendica\App\BaseURL */
	private $baseUrl;
	/** @var \Friendica\Core\L10n */
	private $l10n;
	/** @var IHandleUserSessions */
	private $userSession;
	/** @var string */
	private $tpl;

	public function __construct(Notification $notification, \Friendica\App\BaseURL $baseUrl, \Friendica\Core\L10n $l10n, LoggerInterface $logger, IHandleUserSessions $userSession)
	{
		parent::__construct($logger);

		$this->notification = $notification;
		$this->baseUrl      = $baseUrl;
		$this->l10n         = $l10n;
		$this->userSession  = $userSession;

		$this->tpl = Renderer::getMarkupTemplate('notifications/nav/notify.tpl');
	}

	/**
	 * @param string    $contact_name
	 * @param string    $contact_url
	 * @param string    $message A notification message with the {0} placeholder for the contact name
	 * @param \DateTime $date
	 * @param Uri       $href
	 * @param bool      $seen
	 * @return ValueObject\FormattedNavNotification
	 * @throws HTTPException\ServiceUnavailableException
	 */
	public function createFromParams(string $contact_name, string $contact_url, string $message, \DateTime $date, Uri $href, bool $seen = false): ValueObject\FormattedNavNotification
	{
		$contact_photo = Contact::getAvatarUrlForUrl($contact_url, $this->userSession->getLocalUserId(), Proxy::SIZE_MICRO);

		// Removing the RTL Override character to prevent a garbled notification message
		// See https://github.com/friendica/friendica/issues/12084
		$contact_name = str_replace("\xE2\x80\xAE", '', $contact_name);

		$dateMySQL = $date->format(DateTimeFormat::MYSQL);

		$templateNotify = [
			'contact' => [
				'name'  => $contact_name,
				'url'   => $contact_url,
				'photo' => $contact_photo,
			],
			'href'      => $href->__toString(),
			'message'   => $message,
			'seen'      => $seen,
			'localdate' => DateTimeFormat::local($dateMySQL),
			'ago'       => Temporal::getRelativeDate($dateMySQL),
			'richtext'  => Entity\Notify::formatMessage($contact_name, $message),
		];

		return new ValueObject\FormattedNavNotification(
			$contact_name,
			$contact_url,
			$contact_photo,
			$date->getTimestamp(),
			strip_tags($templateNotify['richtext']),
			Renderer::replaceMacros($this->tpl, ['notify' => $templateNotify]),
			$href,
			$seen,
		);
	}

	/**
	 * @param Entity\Notification $notification
	 * @return ValueObject\FormattedNavNotification
	 * @throws NoMessageException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws HTTPException\ServiceUnavailableException
	 */
	public function createFromNotification(Entity\Notification $notification): ValueObject\FormattedNavNotification
	{
		$message = $this->notification->getMessageFromNotification($notification);

		if (empty($message)) {
			throw new NoMessageException();
		}

		if (!isset(self::$contacts[$notification->actorId])) {
			self::$contacts[$notification->actorId] = Contact::getById($notification->actorId, ['name', 'url']);
		}

		return $this->createFromParams(
			self::$contacts[$notification->actorId]['name'],
			self::$contacts[$notification->actorId]['url'],
			$message['notification'],
			$notification->created,
			new Uri($this->baseUrl . '/notification/' . $notification->id),
			$notification->seen,
		);
	}

	/**
	 * @param \Friendica\Contact\Introduction\Entity\Introduction $intro
	 * @return ValueObject\FormattedNavNotification
	 * @throws HTTPException\NotFoundException when the contact record couldn't be located
	 * @throws HTTPException\ServiceUnavailableException
	 */
	public function createFromIntro(\Friendica\Contact\Introduction\Entity\Introduction $intro): ValueObject\FormattedNavNotification
	{
		if (empty(self::$contacts[$intro->cid])) {
			if ($contact = Contact::getById($intro->cid, ['name', 'url', 'pending'])) {
				self::$contacts[$intro->cid] = $contact;
			} else {
				throw new HTTPException\NotFoundException('Contact not found with id' . $intro->cid);
			}
		}

		if (self::$contacts[$intro->cid]['pending']) {
			$msg = $this->l10n->t('{0} wants to follow you');
		} else {
			$msg = $this->l10n->t('{0} has started following you');
		}

		return $this->createFromParams(
			self::$contacts[$intro->cid]['name'],
			self::$contacts[$intro->cid]['url'],
			$msg,
			$intro->datetime,
			new Uri($this->baseUrl . '/notifications/intros/' . $intro->id)
		);
	}
}
