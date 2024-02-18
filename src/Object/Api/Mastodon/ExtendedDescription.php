<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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
use Friendica\Util\DateTimeFormat;

/**
 * Class ExtendedDescription
 *
 * @see https://docs.joinmastodon.org/entities/ExtendedDescription/
 */
class ExtendedDescription extends BaseDataTransferObject
{
	/** @var string (Datetime) */
	protected $updated_at;
	/** @var string */
	protected $content;

	public function __construct(\DateTime $updated_at, string $content)
	{
		$this->updated_at = $updated_at->format(DateTimeFormat::JSON);
		$this->content    = $content;
	}
}
