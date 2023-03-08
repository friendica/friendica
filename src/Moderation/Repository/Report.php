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

namespace Friendica\Moderation\Repository;

use Friendica\BaseEntity;
use Friendica\Core\Logger;
use Friendica\Database\Database;
use Friendica\Model\Post;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class Report extends \Friendica\BaseRepository
{
	protected static $table_name = 'report';

	/**
	 * @var \Friendica\Moderation\Factory\Report
	 */
	protected $factory;

	public function __construct(Database $database, LoggerInterface $logger, \Friendica\Moderation\Factory\Report $factory)
	{
		parent::__construct($database, $logger, $factory);

		$this->factory = $factory;
	}

	public function selectOneById(int $lastInsertId): \Friendica\Moderation\Entity\Report
	{
		return $this->_selectOne(['id' => $lastInsertId]);
	}

	public function save(\Friendica\Moderation\Entity\Report $Report)
	{
		$fields = [
			'uid'         => $Report->uid,
			'reporter-id' => $Report->reporterId,
			'cid'         => $Report->cid,
			'comment'     => $Report->comment,
			'category'    => $Report->category,
			'rules'       => $Report->rules,
			'forward'     => $Report->forward,
		];

		$postUriIds = $Report->postUriIds;

		if ($Report->id) {
			$this->db->update(self::$table_name, $fields, ['id' => $Report->id]);
		} else {
			$fields['created'] = DateTimeFormat::utcNow();
			$this->db->insert(self::$table_name, $fields, Database::INSERT_IGNORE);

			$Report = $this->selectOneById($this->db->lastInsertId());
		}

		$this->db->delete('report-post', ['rid' => $Report->id]);

		foreach ($postUriIds as $uriId) {
			if (Post::exists(['uri-id' => $uriId])) {
				$this->db->insert('report-post', ['rid' => $Report->id, 'uri-id' => $uriId]);
			} else {
				Logger::notice('Post does not exist', ['uri-id' => $uriId, 'report' => $Report]);
			}
		}

		return $Report;
	}

	protected function _selectOne(array $condition, array $params = []): BaseEntity
	{
		$fields = $this->db->selectFirst(static::$table_name, [], $condition, $params);
		if (!$this->db->isResult($fields)) {
			throw new NotFoundException();
		}

		$postUriIds = array_column($this->db->selectToArray('report-post', ['uri-id'], ['rid' => $condition['id'] ?? 0]), 'uri-id');

		return $this->factory->createFromTableRow($fields, $postUriIds);
	}
}
