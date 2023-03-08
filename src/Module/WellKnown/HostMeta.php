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

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Module\Response;
use Friendica\Protocol\Salmon;
use Friendica\Util\Crypto;
use Friendica\Util\XML;

/**
 * Prints the metadata for describing this host
 * @see https://tools.ietf.org/html/rfc6415
 */
class HostMeta extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$config = DI::config();

		if (!$config->get('system', 'site_pubkey', false)) {
			$res = Crypto::newKeypair(1024);

			$config->set('system', 'site_prvkey', $res['prvkey']);
			$config->set('system', 'site_pubkey', $res['pubkey']);
		}

		$domain = DI::baseUrl();

		XML::fromArray([
			'XRD' => [
				'@attributes' => [
					'xmlns'    => 'http://docs.oasis-open.org/ns/xri/xrd-1.0',
				],
				'hm:Host' => DI::baseUrl()->getHost(),
				'1:link' => [
					'@attributes' => [
						'rel'      => 'lrdd',
						'type'     => 'application/xrd+xml',
						'template' => $domain . '/xrd?uri={uri}'
					]
				],
				'2:link' => [
					'@attributes' => [
						'rel'      => 'lrdd',
						'type'     => 'application/json',
						'template' => $domain . '/.well-known/webfinger?resource={uri}'
					]
				],
				'3:link' => [
					'@attributes' => [
						'rel'  => 'acct-mgmt',
						'href' => $domain . '/amcd'
					]
				],
				'4:link' => [
					'@attributes' => [
						'rel'  => 'http://services.mozilla.com/amcd/0.1',
						'href' => $domain . '/amcd'
					]
				],
				'Property' => [
					'@attributes' => [
						'type'      => 'http://salmon-protocol.org/ns/magic-key',
						'mk:key_id' => '1'
					],
					Salmon::salmonKey($config->get('system', 'site_pubkey'))
				]
			],
		], $xml, false, ['hm' => 'http://host-meta.net/xrd/1.0', 'mk' => 'http://salmon-protocol.org/ns/magic-key']);

		System::httpExit($xml->saveXML(), Response::TYPE_XML, 'application/xrd+xml');
	}
}
