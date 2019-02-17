<?php
/**
 * @file src/Worker/RemoveUser.php
 * @brief Removes orphaned data from deleted users
 */
namespace Friendica\Worker;

use Friendica\Database\DBA;
use Friendica\Model\Item;

class RemoveUser extends AbstractWorker
{
	/**
	 * {@inheritdoc}
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function execute(array $parameters = [])
	{
		if (!$this->checkParameters($parameters, 1)) {
			return;
		}

		list($uid) = $parameters;

		// Only delete if the user is archived
		$condition = ['account_removed' => true, 'uid' => $uid];
		if (!DBA::exists('user', $condition)) {
			return;
		}

		// Now we delete all user items
		$condition = ['uid' => $uid, 'deleted' => false];
		do {
			$items = Item::select(['id'], $condition, ['limit' => 100]);
			while ($item = Item::fetch($items)) {
				Item::deleteById($item['id'], PRIORITY_NEGLIGIBLE);
			}
			DBA::close($items);
		} while (Item::exists($condition));
	}
}
