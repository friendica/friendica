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

namespace Friendica\Module\Api\Mastodon;

use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Util\Network;
use Psr\Http\Message\ResponseInterface;

/**
 * Apps class to register new OAuth clients
 */
class Apps extends BaseApi
{
	public function run(array $request = [], bool $scopecheck = true): ResponseInterface
	{
		return parent::run($request, false);
	}

	/**
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function post(array $request = [])
	{
		$request = $this->getRequest([
			'client_name'   => '',
			'redirect_uris' => '',
			'scopes'        => 'read',
			'website'       => '',
		], $request);

		// Workaround for AndStatus, see issue https://github.com/andstatus/andstatus/issues/538
		$postdata = Network::postdata();
		if (!empty($postdata)) {
			$postrequest = json_decode($postdata, true);
			if (!empty($postrequest) && is_array($postrequest)) {
				$request = array_merge($request, $postrequest);
			}
		}
			
		if (empty($request['client_name']) || empty($request['redirect_uris'])) {
			DI::mstdnError()->UnprocessableEntity(DI::l10n()->t('Missing parameters'));
		}

		$client_id     = bin2hex(random_bytes(32));
		$client_secret = bin2hex(random_bytes(32));

		$fields = ['client_id' => $client_id, 'client_secret' => $client_secret, 'name' => $request['client_name'], 'redirect_uri' => $request['redirect_uris']];

		if (!empty($request['scopes'])) {
			$fields['scopes'] = $request['scopes'];
		}

		$fields['read']   = (stripos($request['scopes'], self::SCOPE_READ) !== false);
		$fields['write']  = (stripos($request['scopes'], self::SCOPE_WRITE) !== false);
		$fields['follow'] = (stripos($request['scopes'], self::SCOPE_FOLLOW) !== false);
		$fields['push']   = (stripos($request['scopes'], self::SCOPE_PUSH) !== false);

		if (!empty($request['website'])) {
			$fields['website'] = $request['website'];
		}

		if (!DBA::insert('application', $fields)) {
			DI::mstdnError()->InternalError();
		}

		System::jsonExit(DI::mstdnApplication()->createFromApplicationId(DBA::lastInsertId())->toArray());
	}
}
