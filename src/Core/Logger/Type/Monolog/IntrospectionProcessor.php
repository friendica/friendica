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

namespace Friendica\Core\Logger\Type\Monolog;

use Friendica\Core\Logger\Util\Introspection;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;

/**
 * Injects line/file//function where the log message came from
 */
class IntrospectionProcessor implements ProcessorInterface
{
	private $level;

	private $introspection;

	/**
	 * @param Introspection $introspection Holds the Introspection of the current call
	 * @param string|int    $level         The minimum logging level at which this Processor will be triggered
	 */
	public function __construct(Introspection $introspection, $level = Logger::DEBUG)
	{
		$this->level = Logger::toMonologLevel($level);
		$introspection->addClasses(['Monolog\\']);
		$this->introspection = $introspection;
	}

	public function __invoke(array $record): array
	{
		// return if the level is not high enough
		if ($record['level'] < $this->level) {
			return $record;
		}
		// we should have the call source now
		$record['extra'] = array_merge(
			$record['extra'],
			$this->introspection->getRecord()
		);

		return $record;
	}
}
