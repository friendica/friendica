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

namespace Friendica\Navigation\Notifications\Factory;

use Friendica\BaseFactory;
use Friendica\Core\Renderer;
use Friendica\Model\Contact;
use Friendica\Navigation\Notifications\Entity;
use Friendica\Navigation\Notifications\Exception\NoMessageException;
use Friendica\Navigation\Notifications\ValueObject;
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
	/** @var string */
	private $tpl;

	public function __construct(Notification $notification, \Friendica\App\BaseURL $baseUrl, \Friendica\Core\L10n $l10n, LoggerInterface $logger)
	{
		parent::__construct($logger);

		$this->notification = $notification;
		$this->baseUrl      = $baseUrl;
		$this->l10n         = $l10n;

		$this->tpl = Renderer::getMarkupTemplate('notifications/nav/notify.tpl');
	}

	/**
	 * @param array     $contact A contact array with the following keys: name, url
	 * @param string    $message A notification message with the {0} placeholder for the contact name
	 * @param \DateTime $date
	 * @param Uri       $href
	 * @param bool      $seen
	 * @return ValueObject\FormattedNavNotification
	 * @throws \Friendica\Network\HTTPException\ServiceUnavailableException
	 */
	public function createFromParams(array $contact, string $message, \DateTime $date, Uri $href, bool $seen = false): ValueObject\FormattedNavNotification
	{
		$contact['photo'] = Contact::getAvatarUrlForUrl($contact['url'], local_user(), Proxy::SIZE_MICRO);

		$dateMySQL = $date->format(DateTimeFormat::MYSQL);

		$templateNotify = [
			'contact'   => $contact,
			'href'      => $href->__toString(),
			'message'   => $message,
			'seen'      => $seen,
			'localdate' => DateTimeFormat::local($dateMySQL),
			'ago'       => Temporal::getRelativeDate($dateMySQL),
			'richtext'  => Entity\Notify::formatMessage($contact['name'], $message),
		];

		return new ValueObject\FormattedNavNotification(
			$contact,
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
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 * @throws \Friendica\Network\HTTPException\ServiceUnavailableException
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
			self::$contacts[$notification->actorId],
			$message['notification'],
			$notification->created,
			new Uri($this->baseUrl->get() . '/notification/' . $notification->id),
			$notification->seen,
		);
	}

	public function createFromIntro(\Friendica\Contact\Introduction\Entity\Introduction $intro): ValueObject\FormattedNavNotification
	{
		if (!isset(self::$contacts[$intro->cid])) {
			self::$contacts[$intro->cid] = Contact::getById($intro->cid, ['name', 'url']);
		}

		return $this->createFromParams(
			self::$contacts[$intro->cid],
			$this->l10n->t('{0} wants to follow you'),
			$intro->datetime,
			new Uri($this->baseUrl->get() . '/notifications/intros/' . $intro->id)
		);
	}
}
