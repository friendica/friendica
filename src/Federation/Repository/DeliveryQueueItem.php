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

namespace Friendica\Federation\Repository;

use Friendica\Database\Database;
use Friendica\Database\DBA;
use Friendica\Federation\Collection;
use Friendica\Federation\Entity;
use Friendica\Federation\Factory;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

final class DeliveryQueueItem extends \Friendica\BaseRepository
{
	protected static $table_name = 'delivery-queue';

	public function __construct(Database $database, LoggerInterface $logger, Factory\DeliveryQueueItem $factory)
	{
		parent::__construct($database, $logger, $factory);
	}

	public function selectByServerId(int $gsid, int $maxFailedCount): Collection\DeliveryQueueItems
	{
		$Entities = new Collection\DeliveryQueueItems();

		$deliveryQueueItems = $this->db->select(
			self::$table_name,
			[],
			["`gsid` = ? AND `failed` < ?", $gsid, $maxFailedCount],
			['order' => ['created']]
		);
		while ($deliveryQueueItem = $this->db->fetch($deliveryQueueItems)) {
			$Entities[] = $this->factory->createFromTableRow($deliveryQueueItem);
		}

		$this->db->close($deliveryQueueItems);

		return $Entities;
	}

	public function selectAggregateByServerId(): Collection\DeliveryQueueAggregates
	{
		$Entities = new Collection\DeliveryQueueAggregates();

		$deliveryQueueAggregates = $this->db->p("SELECT `gsid`, MAX(`failed`) AS `failed` FROM " . DBA::buildTableString([self::$table_name]) . " GROUP BY `gsid` ORDER BY RAND()");
		while ($deliveryQueueAggregate = $this->db->fetch($deliveryQueueAggregates)) {
			$Entities[] = new Entity\DeliveryQueueAggregate($deliveryQueueAggregate['gsid'], $deliveryQueueAggregate['failed']);
		}

		$this->db->close($deliveryQueueAggregates);

		return $Entities;
	}

	public function save(Entity\DeliveryQueueItem $deliveryQueueItem)
	{
		$fields = [
			'gsid'    => $deliveryQueueItem->targetServerId,
			'uri-id'  => $deliveryQueueItem->postUriId,
			'created' => $deliveryQueueItem->created->format(DateTimeFormat::MYSQL),
			'command' => $deliveryQueueItem->command,
			'cid'     => $deliveryQueueItem->targetContactId,
			'uid'     => $deliveryQueueItem->senderUserId,
			'failed'  => $deliveryQueueItem->failed,
		];

		$this->db->insert(self::$table_name, $fields, Database::INSERT_UPDATE);
	}

	public function remove(Entity\DeliveryQueueItem $deliveryQueueItem): bool
	{
		return $this->db->delete(self::$table_name, ['uri-id' => $deliveryQueueItem->postUriId, 'gsid' => $deliveryQueueItem->targetServerId]);
	}

	public function removeFailedByServerId(int $gsid, int $failedThreshold): bool
	{
		return $this->db->delete(self::$table_name, ["`gsid` = ? AND `failed` >= ?", $gsid, $failedThreshold]);
	}

	public function incrementFailed(Entity\DeliveryQueueItem $deliveryQueueItem): bool
	{
		return $this->db->e("
			UPDATE " . DBA::buildTableString([self::$table_name]) . "
			SET `failed` = `failed` + 1
			WHERE `uri-id` = ? AND `gsid` = ?",
			$deliveryQueueItem->postUriId, $deliveryQueueItem->targetServerId
		);
	}

	public function optimizeStorage(): bool
	{
		return $this->db->e("OPTIMIZE TABLE " . DBA::buildTableString([self::$table_name]));
	}
}
