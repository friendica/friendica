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

namespace Friendica\Object\Api\Friendica;

use Friendica\BaseDataTransferObject;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Navigation\Notifications\Entity\Notify;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Temporal;

/**
 * Friendica Notification
 *
 * @see https://github.com/friendica/friendica/blob/develop/doc/API-Entities.md#notification
 */
class Notification extends BaseDataTransferObject
{
	/** @var integer */
	protected $id;
	/** @var integer */
	protected $type;
	/** @var string Full name of the contact subject */
	protected $name;
	/** @var string Profile page URL of the contact subject */
	protected $url;
	/** @var string Profile photo URL of the contact subject */
	protected $photo;
	/** @var string YYYY-MM-DD hh:mm:ss local server time */
	protected $date;
	/** @var string The message (BBCode) */
	protected $msg;
	/** @var integer Owner User Id */
	protected $uid;
	/** @var string Notification URL */
	protected $link;
	/** @var integer Item Id */
	protected $iid;
	/** @var integer Parent Item Id */
	protected $parent;
	/** @var boolean  Whether the notification was read or not. */
	protected $seen;
	/** @var string Verb URL @see http://activitystrea.ms */
	protected $verb;
	/** @var string Subject type ('item', 'intro' or 'mail') */
	protected $otype;
	/** @var string Full name of the contact subject (HTML) */
	protected $name_cache;
	/** @var string Plaintext version of the notification text with a placeholder (`{0}`) for the subject contact's name. (Plaintext) */
	protected $msg_cache;
	/** @var integer  Unix timestamp */
	protected $timestamp;
	/** @var string Time since the note was posted, eg "1 hour ago" */
	protected $date_rel;
	/** @var string Message (HTML) */
	protected $msg_html;
	/** @var string Message (Plaintext) */
	protected $msg_plain;

	public function __construct(Notify $Notify)
	{
		$this->id         = $Notify->id;
		$this->type       = $Notify->type;
		$this->name       = $Notify->name;
		$this->url        = $Notify->url->__toString();
		$this->photo      = $Notify->photo->__toString();
		$this->date       = DateTimeFormat::local($Notify->date->format(DateTimeFormat::MYSQL));
		$this->msg        = $Notify->msg;
		$this->uid        = $Notify->uid;
		$this->link       = $Notify->link->__toString();
		$this->iid        = $Notify->itemId;
		$this->parent     = $Notify->parent;
		$this->seen       = $Notify->seen;
		$this->verb       = $Notify->verb;
		$this->otype      = $Notify->otype;
		$this->name_cache = $Notify->name_cache;
		$this->msg_cache  = $Notify->msg_cache;
		$this->timestamp  = $Notify->date->format('U');
		$this->date_rel   = Temporal::getRelativeDate($this->date);

		try {
			$this->msg_html  = BBCode::convert($this->msg, false);
		} catch (\Exception $e) {
			$this->msg_html  = '';
		}

		try {
			$this->msg_plain = explode("\n", trim(HTML::toPlaintext($this->msg_html, 0)))[0];
		} catch (\Exception $e) {
			$this->msg_plain  = '';
		}
	}
}
