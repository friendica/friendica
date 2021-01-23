<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Model;

use Friendica\BaseModel;
use Friendica\Content\Text\BBCode;
use Friendica\Database\Database;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Log\LoggerInterface;

/**
 * Model for an entry in the notify table
 *
 * @property string  hash
 * @property integer type
 * @property string  name   Full name of the contact subject
 * @property string  url    Profile page URL of the contact subject
 * @property string  photo  Profile photo URL of the contact subject
 * @property string  date   YYYY-MM-DD hh:mm:ss local server time
 * @property string  msg
 * @property integer uid  	Owner User Id
 * @property string  link   Notification URL
 * @property integer iid  	Item Id
 * @property integer parent Parent Item Id
 * @property boolean seen   Whether the notification was read or not.
 * @property string  verb   Verb URL (@see http://activitystrea.ms)
 * @property string  otype  Subject type ('item', 'intro' or 'mail')
 *
 * @property-read string name_cache Full name of the contact subject
 * @property-read string msg_cache  Plaintext version of the notification text with a placeholder (`{0}`) for the subject contact's name.
 */
class Notification extends BaseModel
{
	/** @var \Friendica\Repository\Notification */
	private $repo;

	public function __construct(Database $dba, LoggerInterface $logger, \Friendica\Repository\Notification $repo, array $data = [])
	{
		parent::__construct($dba, $logger, $data);

		$this->repo = $repo;

		$this->setNameCache();
		$this->setMsgCache();
	}

	/**
	 * Sets the pre-formatted name (caching)
	 */
	private function setNameCache()
	{
		try {
			$this->name_cache = strip_tags(BBCode::convert($this->source_name));
		} catch (InternalServerErrorException $e) {
		}
	}

	/**
	 * Sets the pre-formatted msg (caching)
	 */
	private function setMsgCache()
	{
		try {
			$this->msg_cache = self::formatMessage($this->name_cache, strip_tags(BBCode::convert($this->msg)));
		} catch (InternalServerErrorException $e) {
		}
	}

	public function __set($name, $value)
	{
		parent::__set($name, $value);

		if ($name == 'msg') {
			$this->setMsgCache();
		}

		if ($name == 'source_name') {
			$this->setNameCache();
		}
	}

	/**
	 * Formats a notification message with the notification author
	 *
	 * Replace the name with {0} but ensure to make that only once. The {0} is used
	 * later and prints the name in bold.
	 *
	 * @param string $name
	 * @param string $message
	 *
	 * @return string Formatted message
	 */
	public static function formatMessage($name, $message)
	{
		if ($name != '') {
			$pos = strpos($message, $name);
		} else {
			$pos = false;
		}

		if ($pos !== false) {
			$message = substr_replace($message, '{0}', $pos, strlen($name));
		}

		return $message;
	}
}
