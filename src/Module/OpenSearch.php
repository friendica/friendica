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

namespace Friendica\Module;

use DOMDocument;
use DOMElement;
use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Util\XML;

/**
 * Prints the opensearch description document
 * @see https://github.com/dewitt/opensearch/blob/master/opensearch-1-1-draft-6.md#opensearch-description-document
 */
class OpenSearch extends BaseModule
{
	/**
	 * @throws \Exception
	 */
	protected function rawContent(array $request = [])
	{
		$hostname = DI::baseUrl()->getHost();
		$baseUrl  = DI::baseUrl();

		/** @var DOMDocument $xml */
		XML::fromArray([
			'OpenSearchDescription' => [
				'@attributes' => [
					'xmlns' => 'http://a9.com/-/spec/opensearch/1.1',
				],
				'ShortName'      => "Friendica $hostname",
				'Description'    => "Search in Friendica $hostname",
				'Contact'        => 'https://github.com/friendica/friendica/issues',
				'InputEncoding'  => 'UTF-8',
				'OutputEncoding' => 'UTF-8',
				'Developer'      => 'Friendica Developer Team',
			],
		], $xml);

		/** @var DOMElement $parent */
		$parent = $xml->getElementsByTagName('OpenSearchDescription')[0];

		XML::addElement($xml, $parent, 'Image',
			"$baseUrl/images/friendica-16.png", [
				'height' => 16,
				'width'  => 16,
				'type'   => 'image/png',
			]);

		XML::addElement($xml, $parent, 'Image',
			"$baseUrl/images/friendica-64.png", [
				'height' => 64,
				'width'  => 64,
				'type'   => 'image/png',
			]);

		XML::addElement($xml, $parent, 'Url', '', [
			'type'     => 'text/html',
			'template' => "$baseUrl/search?search={searchTerms}",
		]);

		XML::addElement($xml, $parent, 'Url', '', [
			'type'     => 'application/opensearchdescription+xml',
			'rel'      => 'self',
			'template' => "$baseUrl/opensearch",
		]);

		System::httpExit($xml->saveXML(), Response::TYPE_XML, 'application/opensearchdescription+xml');
	}
}
