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

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Content\Nav;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\System;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class BaseSettings extends BaseModule
{
	/** @var App\Page */
	protected $page;
	/** @var IHandleUserSessions */
	protected $session;

	public function __construct(IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->page    = $page;
		$this->session = $session;

		if ($this->session->getSubManagedUserId()) {
			throw new ForbiddenException($this->t('Permission denied.'));
		}
	}

	protected function content(array $request = []): string
	{
		Nav::setSelected('settings');

		if (!$this->session->getLocalUserId()) {
			$this->session->set('return_path', $this->args->getCommand());
			$this->baseUrl->redirect('login');
		}

		$this->createAside();

		return '';
	}

	public function createAside()
	{
		$tpl = Renderer::getMarkupTemplate('settings/head.tpl');
		$this->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$ispublic' => $this->t('everybody')
		]);

		$tabs = [];

		$tabs[] = [
			'label'     => $this->t('Account'),
			'url'       => 'settings',
			'selected'  => static::class == Settings\Account::class ? 'active' : '',
			'accesskey' => 'o',
		];

		$tabs[] = [
			'label'     => $this->t('Two-factor authentication'),
			'url'       => 'settings/2fa',
			'selected'  => in_array(static::class, [
				Settings\TwoFactor\AppSpecific::class,
				Settings\TwoFactor\Index::class,
				Settings\TwoFactor\Recovery::class,
				Settings\TwoFactor\Trusted::class,
				Settings\TwoFactor\Verify::class
			]) ? 'active' : '',
			'accesskey' => '2',
		];

		$tabs[] = [
			'label'     => $this->t('Profile'),
			'url'       => 'settings/profile',
			'selected'  => in_array(static::class, [
				Settings\Profile\Index::class,
				Settings\Profile\Photo\Crop::class,
				Settings\Profile\Photo\Index::class,
			]) ? 'active' : '',
			'accesskey' => 'p',
		];

		if (Feature::get()) {
			$tabs[] = [
				'label'     => $this->t('Additional features'),
				'url'       => 'settings/features',
				'selected'  => static::class == Settings\Features::class ? 'active' : '',
				'accesskey' => 't',
			];
		}

		$tabs[] = [
			'label'     => $this->t('Display'),
			'url'       => 'settings/display',
			'selected'  => static::class == Settings\Display::class ? 'active' : '',
			'accesskey' => 'i',
		];

		$tabs[] = [
			'label'     => $this->t('Social Networks'),
			'url'       => 'settings/connectors',
			'selected'  => static::class == Settings\Connectors::class ? 'active' : '',
			'accesskey' => 'w',
		];

		$tabs[] = [
			'label'     => $this->t('Addons'),
			'url'       => 'settings/addons',
			'selected'  => static::class == Settings\Addons::class ? 'active' : '',
			'accesskey' => 'l',
		];

		$tabs[] = [
			'label'     => $this->t('Manage Accounts'),
			'url'       => 'settings/delegation',
			'selected'  => static::class == Settings\Delegation::class ? 'active' : '',
			'accesskey' => 'd',
		];

		$tabs[] = [
			'label'     => $this->t('Connected apps'),
			'url'       => 'settings/oauth',
			'selected'  => static::class == Settings\OAuth::class ? 'active' : '',
			'accesskey' => 'b',
		];

		$tabs[] = [
			'label'     => $this->t('Export personal data'),
			'url'       => 'settings/userexport',
			'selected'  => static::class == Settings\UserExport::class ? 'active' : '',
			'accesskey' => 'e',
		];

		$tabs[] = [
			'label'     => $this->t('Remove account'),
			'url'       => 'settings/removeme',
			'selected'  => static::class === Settings\RemoveMe::class ? 'active' : '',
			'accesskey' => 'r',
		];

		$tabtpl              = Renderer::getMarkupTemplate('generic_links_widget.tpl');
		$this->page['aside'] = Renderer::replaceMacros($tabtpl, [
			'$title' => $this->t('Settings'),
			'$class' => 'settings-widget',
			'$items' => $tabs,
		]);
	}
}
