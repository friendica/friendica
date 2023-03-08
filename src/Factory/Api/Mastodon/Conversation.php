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

namespace Friendica\Factory\Api\Mastodon;

use Friendica\BaseFactory;
use Friendica\Database\Database;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use ImagickException;
use Psr\Log\LoggerInterface;

class Conversation extends BaseFactory
{
	/** @var Database */
	private $dba;
	/** @var Status */
	private $mstdnStatusFactory;
	/** @var Account */
	private $mstdnAccountFactory;

	public function __construct(LoggerInterface $logger, Database $dba, Status $mstdnStatusFactory, Account $mstdnAccountFactoryFactory)
	{
		parent::__construct($logger);
		$this->dba                 = $dba;
		$this->mstdnStatusFactory  = $mstdnStatusFactory;
		$this->mstdnAccountFactory = $mstdnAccountFactoryFactory;
	}

	/**
	 * @param int $id Conversation id
	 *
	 * @return \Friendica\Object\Api\Mastodon\Conversation
	 * @throws ImagickException|HTTPException\InternalServerErrorException|HTTPException\NotFoundException
	 */
	public function createFromConvId(int $id): \Friendica\Object\Api\Mastodon\Conversation
	{
		$accounts    = [];
		$unread      = false;
		$last_status = null;

		$ids = [];

		$mails = $this->dba->select('mail', ['id', 'from-url', 'uid', 'seen'], ['convid' => $id], ['order' => ['id' => true]]);
		while ($mail = $this->dba->fetch($mails)) {
			if (!$mail['seen']) {
				$unread = true;
			}

			$id = Contact::getIdForURL($mail['from-url'], 0, false);
			if (in_array($id, $ids)) {
				continue;
			}

			$ids[] = $id;

			if (empty($last_status)) {
				$last_status = $this->mstdnStatusFactory->createFromMailId($mail['id']);
			}

			$accounts[] = $this->mstdnAccountFactory->createFromContactId($id, 0);
		}

		return new \Friendica\Object\Api\Mastodon\Conversation($id, $accounts, $unread, $last_status);
	}
}
