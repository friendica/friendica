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

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Protocol\Salmon;
use Friendica\Util\Crypto;

/**
 * Prints the metadata for describing this host
 * @see https://tools.ietf.org/html/rfc6415
 */
class HostMeta extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$config = DI::config();

		header('Content-type: text/xml');

		if (!$config->get('system', 'site_pubkey', false)) {
			$res = Crypto::newKeypair(1024);

			$config->set('system', 'site_prvkey', $res['prvkey']);
			$config->set('system', 'site_pubkey', $res['pubkey']);
		}

		$tpl = Renderer::getMarkupTemplate('xrd_host.tpl');
		echo Renderer::replaceMacros($tpl, [
			'$zhost'  => DI::baseUrl()->getHostname(),
			'$zroot'  => DI::baseUrl()->get(),
			'$domain' => DI::baseUrl()->get(),
			'$bigkey' => Salmon::salmonKey($config->get('system', 'site_pubkey'))
		]);

		exit();
	}
}
