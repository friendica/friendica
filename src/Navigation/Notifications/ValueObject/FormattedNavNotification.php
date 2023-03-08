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

namespace Friendica\Navigation\Notifications\ValueObject;

use Friendica\BaseEntity;

/**
 * A view-only object for printing item notifications to the frontend
 */
class FormattedNavNotification extends BaseEntity
{
	/** @var array */
	protected $contact;
	/** @var string */
	protected $timestamp;
	/** @var string */
	protected $plaintext;
	/** @var string */
	protected $html;
	/** @var string */
	protected $href;
	/** @var bool */
	protected $seen;

	/**
	 * @param string $contact_name  Contact display name
	 * @param string $contact_url   Contact profile URL
	 * @param string $contact_photo Contact picture URL
	 * @param string $timestamp     Unix timestamp
	 * @param string $plaintext     Localized notification message with the placeholder replaced by the contact name
	 * @param string $html          Full HTML string of the notification menu element
	 * @param string $href          Absolute URL this notification should send the user to when interacted with
	 * @param bool   $seen          Whether the user interacted with this notification once
	 */
	public function __construct(string $contact_name, string $contact_url, string $contact_photo, string $timestamp, string $plaintext, string $html, string $href, bool $seen)
	{
		// Properties differ from constructor because this structure is used in the "nav-update" Javascript event listener
		$this->contact = [
			'name'  => $contact_name,
			'url'   => $contact_url,
			'photo' => $contact_photo,
		];
		$this->timestamp = $timestamp;
		$this->plaintext = $plaintext;
		$this->html      = $html;
		$this->href      = $href;
		$this->seen      = $seen;
	}
}
