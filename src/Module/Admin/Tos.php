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

namespace Friendica\Module\Admin;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Module\BaseAdmin;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Tos extends BaseAdmin
{
	/** @var \Friendica\Module\Tos */
	protected $tos;
	/** @var IManageConfigValues */
	protected $config;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->tos     = new \Friendica\Module\Tos($l10n, $baseUrl, $args, $logger, $profiler, $response, $config, $server, $parameters);
		$this->config  = $config;
	}

	protected function post(array $request = [])
	{
		self::checkAdminAccess();

		if (empty($_POST['page_tos'])) {
			return;
		}

		self::checkFormSecurityTokenRedirectOnError('/admin/tos', 'admin_tos');

		$displaytos = !empty($_POST['displaytos']);
		$displayprivstatement = !empty($_POST['displayprivstatement']);
		$tostext  = (!empty($_POST['tostext']) ? strip_tags(trim($_POST['tostext'])) : '');
		$tosrules = (!empty($_POST['tosrules']) ? strip_tags(trim($_POST['tosrules'])) : '');

		$this->config->set('system', 'tosdisplay', $displaytos);
		$this->config->set('system', 'tosprivstatement', $displayprivstatement);
		$this->config->set('system', 'tostext', $tostext);
		$this->config->set('system', 'tosrules', $tosrules);

		$this->baseUrl->redirect('admin/tos');
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$t = Renderer::getMarkupTemplate('admin/tos.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => $this->t('Administration'),
			'$page' => $this->t('Terms of Service'),
			'$displaytos' => ['displaytos', $this->t('Display Terms of Service'), $this->config->get('system', 'tosdisplay'), $this->t('Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.')],
			'$displayprivstatement' => ['displayprivstatement', $this->t('Display Privacy Statement'), $this->config->get('system', 'tosprivstatement'), $this->t('Show some informations regarding the needed information to operate the node according e.g. to <a href="%s" target="_blank" rel="noopener noreferrer">EU-GDPR</a>.', 'https://en.wikipedia.org/wiki/General_Data_Protection_Regulation')],
			'$preview' => $this->t('Privacy Statement Preview'),
			'$privtext' => $this->tos->privacy_complete,
			'$tostext' => ['tostext', $this->t('The Terms of Service'), $this->config->get('system', 'tostext'), $this->t('Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.')],
			'$tosrules' => ['tosrules', $this->t('The rules'), $this->config->get('system', 'tosrules'), $this->t('Enter your system rules here. Each line represents one rule.')],
			'$form_security_token' => self::getFormSecurityToken('admin_tos'),
			'$submit' => $this->t('Save Settings'),
		]);
	}
}
