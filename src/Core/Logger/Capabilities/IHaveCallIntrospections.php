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

namespace Friendica\Core\Logger\Capabilities;

interface IHaveCallIntrospections
{
	/**
	 * A list of classes, which shouldn't get logged
	 *
	 * @var string[]
	 */
	public const IGNORE_CLASS_LIST = [
		\Friendica\Core\Logger::class,
		\Friendica\Core\Logger\Factory\Logger::class,
		'Friendica\\Core\\Logger\\Type',
		\Friendica\Util\Profiler::class,
	];

	/**
	 * Adds new classes to get skipped
	 *
	 * @param array $classNames
	 */
	public function addClasses(array $classNames): void;

	/**
	 * Returns the introspection record of the current call
	 *
	 * @return array
	 */
	public function getRecord(): array;
}
