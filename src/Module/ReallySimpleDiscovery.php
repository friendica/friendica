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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Util\XML;

/**
 * Prints the rsd.xml
 * @see http://danielberlinger.github.io/rsd/
 */
class ReallySimpleDiscovery extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		header('Content-Type: text/xml');

		$xml = null;
		echo XML::fromArray([
			'rsd' => [
				'@attributes' => [
					'version' => '1.0',
					'xmlns'   => 'http://archipelago.phrasewise.com/rsd',
				],
				'service'     => [
					'engineName' => 'Friendica',
					'engineLink' => 'http://friendica.com',
					'apis'       => [
						'api' => [
							'@attributes' => [
								'name'      => 'Twitter',
								'preferred' => 'true',
								'apiLink'   => DI::baseUrl()->get(),
								'blogID'    => '',
							],
							'settings'    => [
								'docs'    => [
									'http://status.net/wiki/TwitterCompatibleAPI',
								],
								'setting' => [
									'@attributes' => [
										'name' => 'OAuth',
									],
									'false',
								],
							],
						]
					],
				],
			],
		], $xml);
		exit();
	}
}
