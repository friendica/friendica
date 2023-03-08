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

namespace Friendica\Object\Api\Mastodon;

use Friendica\BaseDataTransferObject;

/**
 * Class StatusSource
 *
 * @see https://docs.joinmastodon.org/entities/StatusSource/
 */
class StatusSource extends BaseDataTransferObject
{
	/** @var string */
	protected $id;
	/** @var string */
	protected $text;
	/** @var string */
	protected $spoiler_text = "";

	/**
	 * Creates a source record from an post array.
	 *
	 * @param integer $id
	 * @param string $text
	 * @param string $spoiler_text
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public function __construct(int $id, string $text, string $spoiler_text)
	{
		$this->id           = (string)$id;
		$this->text         = $text;
		$this->spoiler_text = $spoiler_text;
	}
}
