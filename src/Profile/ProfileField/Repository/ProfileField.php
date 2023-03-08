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

namespace Friendica\Profile\ProfileField\Repository;

use Friendica\BaseRepository;
use Friendica\Database\Database;
use Friendica\Profile\ProfileField\Exception\ProfileFieldNotFoundException;
use Friendica\Profile\ProfileField\Exception\ProfileFieldPersistenceException;
use Friendica\Profile\ProfileField\Exception\UnexpectedPermissionSetException;
use Friendica\Profile\ProfileField\Factory;
use Friendica\Profile\ProfileField\Entity;
use Friendica\Profile\ProfileField\Collection;
use Friendica\Security\PermissionSet\Repository\PermissionSet as PermissionSetRepository;
use Friendica\Util\DateTimeFormat;
use Psr\Log\LoggerInterface;

class ProfileField extends BaseRepository
{
	/** @var  Factory\ProfileField */
	protected $factory;

	protected static $table_name = 'profile_field';

	protected static $view_name = 'profile_field-view';

	/** @var PermissionSetRepository */
	protected $permissionSetRepository;

	public function __construct(Database $database, LoggerInterface $logger, Factory\ProfileField $factory, PermissionSetRepository $permissionSetRepository)
	{
		parent::__construct($database, $logger, $factory);

		$this->permissionSetRepository = $permissionSetRepository;
	}

	/**
	 * @param array $condition
	 * @param array $params
	 *
	 * @return Entity\ProfileField
	 *
	 * @throws ProfileFieldNotFoundException
	 * @throws UnexpectedPermissionSetException
	 */
	private function selectOne(array $condition, array $params = []): Entity\ProfileField
	{
		$fields = $this->db->selectFirst(static::$view_name, [], $condition, $params);
		if (!$this->db->isResult($fields)) {
			throw new ProfileFieldNotFoundException();
		}

		return $this->factory->createFromTableRow($fields);
	}

	/**
	 * @param array $condition
	 * @param array $params
	 *
	 * @return Collection\ProfileFields
	 *
	 * @throws ProfileFieldPersistenceException In case of underlying persistence exceptions
	 * @throws UnexpectedPermissionSetException
	 */
	private function select(array $condition, array $params = []): Collection\ProfileFields
	{
		$rows = $this->db->selectToArray(static::$view_name, [], $condition, $params);

		$Entities = new Collection\ProfileFields();
		foreach ($rows as $fields) {
			$Entities[] = $this->factory->createFromTableRow($fields);
		}

		return $Entities;
	}

	/**
	 * Converts a given ProfileField into a DB compatible row array
	 *
	 * @param Entity\ProfileField $profileField
	 *
	 * @return array
	 */
	protected function convertToTableRow(Entity\ProfileField $profileField): array
	{
		return [
			'uid'     => $profileField->uid,
			'label'   => $profileField->label,
			'value'   => $profileField->value,
			'order'   => $profileField->order,
			'created' => $profileField->created->format(DateTimeFormat::MYSQL),
			'edited'  => $profileField->edited->format(DateTimeFormat::MYSQL),
			'psid'    => $profileField->permissionSet->id
		];
	}

	/**
	 * Returns all public available ProfileFields for a specific user
	 *
	 * @param int $uid the user id
	 *
	 * @return Collection\ProfileFields
	 *
	 * @throws ProfileFieldPersistenceException In case of underlying persistence exceptions
	 */
	public function selectPublicFieldsByUserId(int $uid): Collection\ProfileFields
	{
		try {
			$publicPermissionSet = $this->permissionSetRepository->selectPublicForUser($uid);

			return $this->select([
				'uid'  => $uid,
				'psid' => $publicPermissionSet->id
			]);
		} catch (\Exception $exception) {
			throw new ProfileFieldPersistenceException(sprintf('Cannot select public ProfileField for user "%d"', $uid), $exception);
		}
	}

	/**
	 * @param int $uid Field owner user Id
	 *
	 * @throws ProfileFieldPersistenceException In case of underlying persistence exceptions
	 */
	public function selectByUserId(int $uid): Collection\ProfileFields
	{
		try {
			return $this->select(
				['uid' => $uid],
				['order' => ['order']]
			);
		} catch (\Exception $exception) {
			throw new ProfileFieldPersistenceException(sprintf('Cannot select ProfileField for user "%d"', $uid), $exception);
		}
	}

	/**
	 * Retrieve all custom profile field a given contact is able to access to, including public profile fields.
	 *
	 * @param int $cid Private contact id, must be owned by $uid
	 * @param int $uid Field owner user id
	 *
	 * @throws \Exception
	 */
	public function selectByContactId(int $cid, int $uid): Collection\ProfileFields
	{
		$permissionSets = $this->permissionSetRepository->selectByContactId($cid, $uid);

		$permissionSetIds = $permissionSets->column('id');

		// Includes public custom fields
		$permissionSetIds[] = $this->permissionSetRepository->selectPublicForUser($uid)->id;

		return $this->select(
			['uid' => $uid, 'psid' => $permissionSetIds],
			['order' => ['order']]
		);
	}

	/**
	 * @param int $id
	 *
	 * @return Entity\ProfileField
	 *
	 * @ProfileFieldNotFoundException In case there is no ProfileField found
	 */
	public function selectOneById(int $id): Entity\ProfileField
	{
		try {
			return $this->selectOne(['id' => $id]);
		} catch (\Exception $exception) {
			throw new ProfileFieldNotFoundException(sprintf('Cannot find Profile "%s"', $id), $exception);
		}
	}

	/**
	 * Delets a whole collection of ProfileFields
	 *
	 * @param Collection\ProfileFields $profileFields
	 *
	 * @return bool
	 * @throws ProfileFieldPersistenceException in case the persistence layer cannot delete the ProfileFields
	 */
	public function deleteCollection(Collection\ProfileFields $profileFields): bool
	{
		try {
			return $this->db->delete(self::$table_name, ['id' => $profileFields->column('id')]);
		} catch (\Exception $exception) {
			throw new ProfileFieldPersistenceException('Cannot delete ProfileFields', $exception);
		}
	}

	/**
	 * @param Entity\ProfileField $profileField
	 *
	 * @return Entity\ProfileField
	 * @throws ProfileFieldPersistenceException in case the persistence layer cannot save the ProfileField
	 */
	public function save(Entity\ProfileField $profileField): Entity\ProfileField
	{
		if ($profileField->permissionSet->id === null) {
			throw new ProfileFieldPersistenceException('PermissionSet needs to be saved first.');
		}

		$fields = $this->convertToTableRow($profileField);

		try {
			if ($profileField->id) {
				$this->db->update(self::$table_name, $fields, ['id' => $profileField->id]);
			} else {
				$this->db->insert(self::$table_name, $fields);

				$profileField = $this->selectOneById($this->db->lastInsertId());
			}
		} catch (\Exception $exception) {
			throw new ProfileFieldPersistenceException(sprintf('Cannot save ProfileField with id "%d" and label "%s"', $profileField->id, $profileField->label), $exception);
		}

		return $profileField;
	}

	public function saveCollectionForUser(int $uid, Collection\ProfileFields $profileFields): Collection\ProfileFields
	{
		$savedProfileFields = new Collection\ProfileFields();

		$profileFieldsOld = $this->selectByUserId($uid);

		// Prunes profile field whose label has been emptied
		$labels                 = $profileFields->column('label');
		$prunedProfileFieldsOld = $profileFieldsOld->filter(function (Entity\ProfileField $profileFieldOld) use ($labels) {
			return array_search($profileFieldOld->label, $labels) === false;
		});
		$this->deleteCollection($prunedProfileFieldsOld);

		// Update the order based on the new Profile Field Collection
		$order                 = 0;
		$labelProfileFieldsOld = array_flip($profileFieldsOld->column('label'));

		foreach ($profileFields as $profileField) {
			// Update existing field (preserve
			if (array_key_exists($profileField->label, $labelProfileFieldsOld)) {
				$profileFieldOldId = $labelProfileFieldsOld[$profileField->label];
				/** @var Entity\ProfileField $foundProfileFieldOld */
				$foundProfileFieldOld = $profileFieldsOld[$profileFieldOldId];
				$foundProfileFieldOld->update(
					$profileField->value,
					$order,
					$profileField->permissionSet
				);

				$savedProfileFields->append($this->save($foundProfileFieldOld));
			} else {
				$profileField->setOrder($order);
				$savedProfileFields->append($this->save($profileField));
			}

			$order++;
		}

		return $savedProfileFields;
	}
}
