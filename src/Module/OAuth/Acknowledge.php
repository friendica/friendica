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

namespace Friendica\Module\OAuth;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseApi;
use Friendica\Module\Special\HTTPException;
use Psr\Http\Message\ResponseInterface;

/**
 * Acknowledgement of OAuth requests
 */
class Acknowledge extends BaseApi
{
	public function run(HTTPException $httpException, array $request = [], bool $scopecheck = true): ResponseInterface
	{
		return parent::run($httpException, $request, false);
	}

	protected function post(array $request = [])
	{
		DI::session()->set('oauth_acknowledge', true);
		DI::app()->redirect(DI::session()->get('return_path'));
	}

	protected function content(array $request = []): string
	{
		DI::session()->set('return_path', $_REQUEST['return_path'] ?? '');

		$o = Renderer::replaceMacros(Renderer::getMarkupTemplate('oauth_authorize.tpl'), [
			'$title'     => DI::l10n()->t('Authorize application connection'),
			'$app'       => ['name' => $_REQUEST['application'] ?? ''],
			'$authorize' => DI::l10n()->t('Do you want to authorize this application to access your posts and contacts, and/or create new posts for you?'),
			'$yes'       => DI::l10n()->t('Yes'),
			'$no'        => DI::l10n()->t('No'),
		]);

		return $o;
	}
}
