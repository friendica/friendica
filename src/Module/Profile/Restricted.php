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

namespace Friendica\Module\Profile;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Model\Profile;
use Friendica\Module\Response;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Restricted extends BaseModule
{
	/** @var App */
	private $app;

	public function __construct(App $app, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->app = $app;
	}

	protected function content(array $request = []): string
	{
		$profile = Profile::load($this->app, $this->parameters['nickname'] ?? '', false);
		if (!$profile) {
			throw new HTTPException\NotFoundException($this->t('Profile not found.'));
		}

		if (empty($profile['hidewall'])) {
			$this->baseUrl->redirect('profile/' . $profile['nickname']);
		}

		$tpl = Renderer::getMarkupTemplate('exception.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'   => $this->t('Restricted profile'),
			'$message' => $this->t('This profile has been restricted which prevents access to their public content from anonymous visitors.'),
		]);
	}
}
