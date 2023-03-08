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

namespace Friendica\Model\Post;

use BadMethodCallException;
use Friendica\Database\DBA;
use Friendica\Database\DBStructure;
use Friendica\DI;

class QuestionOption
{
	/**
	 * Update a post question-option entry
	 *
	 * @param integer $uri_id
	 * @param integer $id
	 * @param array   $data
	 * @param bool    $insert_if_missing
	 * @return bool
	 * @throws \Exception
	 */
	public static function update(int $uri_id, int $id, array $data = [], bool $insert_if_missing = true)
	{
		if (empty($uri_id)) {
			throw new BadMethodCallException('Empty URI_id');
		}

		$fields = DI::dbaDefinition()->truncateFieldsForTable('post-question-option', $data);

		// Remove the key fields
		unset($fields['uri-id']);
		unset($fields['id']);

		if (empty($fields)) {
			return true;
		}

		return DBA::update('post-question-option', $fields, ['uri-id' => $uri_id, 'id' => $id], $insert_if_missing ? true : []);
	}

	/**
	 * Retrieves the question options associated with the provided item ID.
	 *
	 * @param int $uri_id
	 * @return array
	 * @throws \Exception
	 */
	public static function getByURIId(int $uri_id)
	{
		$condition = ['uri-id' => $uri_id];

		return DBA::selectToArray('post-question-option', [], $condition, ['order' => ['id']]);
	}
}
