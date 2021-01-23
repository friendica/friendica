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

namespace Friendica\Repository;

use Exception;
use Friendica\BaseRepository;
use Friendica\Collection;
use Friendica\Core\Hook;
use Friendica\Model;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;

class Notification extends BaseRepository
{
	protected static $table_name = 'notify';

	protected static $model_class = Model\Notification::class;

	protected static $collection_class = Collection\Notifications::class;

	/**
	 * {@inheritDoc}
	 *
	 * @return Model\Notification
	 */
	protected function create(array $data)
	{
		return new Model\Notification($this->dba, $this->logger, $this, $data);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return Collection\Notifications
	 */
	public function select(array $condition = [], array $params = [])
	{
		$params['order'] = $params['order'] ?? ['date' => 'DESC'];

		return parent::select($condition, $params);
	}

	/**
	 * Return one notify instance based on ID / UID
	 *
	 * @param int $id The ID of the notify instance
	 * @param int $uid The user ID, bound to this notify instance (= security check)
	 *
	 * @return Model\Notification
	 * @throws NotFoundException
	 */
	public function getByID(int $id, int $uid)
	{
		return $this->selectFirst(['id' => $id, 'uid' => $uid]);
	}

	/**
	 * Set seen state of notifications of the local_user()
	 *
	 * @param bool               $seen   optional true or false. default true
	 * @param Model\Notification $notify optional a notify, which should be set seen (including his parents)
	 *
	 * @return bool true on success, false on error
	 *
	 * @throws Exception
	 */
	public function setSeen(bool $seen = true, Model\Notification $notify = null)
	{
		if (empty($notify)) {
			$conditions = ['uid' => local_user()];
		} else {
			$conditions = ['(`link` = ? OR (`parent` != 0 AND `parent` = ? AND `otype` = ?)) AND `uid` = ?',
				$notify->link,
				$notify->parent,
				$notify->otype,
				local_user()];
		}

		return $this->dba->update('notify', ['seen' => $seen], $conditions);
	}

	/**
	 * @param array $fields
	 *
	 * @return Model\Notification|false
	 *
	 * @throws InternalServerErrorException
	 * @throws Exception
	 */
	public function insert(array $fields)
	{
		$fields['date'] = DateTimeFormat::utcNow();

		Hook::callAll('enotify_store', $fields);

		if (empty($fields)) {
			$this->logger->debug('Abort adding notification entry');
			return false;
		}

		$this->logger->debug('adding notification entry', ['fields' => $fields]);

		return parent::insert($fields);
	}
}
