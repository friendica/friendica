<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Library\Api\Mastodon\Factory;

use Friendica\Library\BaseFactory;
use Friendica\Library\Api\Mastodon\Collection\Fields;
use Friendica\Library\Profile\ProfileField\Collection\ProfileFields;
use Friendica\Content\Text\BBCode;
use Friendica\Library\Profile\ProfileField\Entity\ProfileField;
use Friendica\Network\HTTPException;

class Field extends BaseFactory
{
	/**
	 * @param ProfileField $profileField
	 *
	 * @return \Friendica\Library\Api\Mastodon\Object\Field
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromProfileField(ProfileField $profileField): \Friendica\Library\Api\Mastodon\Object\Field
	{
		return new \Friendica\Library\Api\Mastodon\Object\Field($profileField->label, BBCode::convert($profileField->value, false, BBCode::ACTIVITYPUB));
	}

	/**
	 * @param ProfileFields $profileFields
	 *
	 * @return Fields
	 * @throws HTTPException\InternalServerErrorException
	 */
	public function createFromProfileFields(ProfileFields $profileFields): Fields
	{
		$fields = [];

		foreach ($profileFields as $profileField) {
			$fields[] = $this->createFromProfileField($profileField);
		}

		return new Fields($fields);
	}
}
