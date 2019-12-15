<?php

namespace Friendica\Module\Admin;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Module\BaseAdminModule;

class Tos extends BaseAdminModule
{
	public static function post(array $parameters = [])
	{
		parent::post($parameters);

		parent::checkFormSecurityTokenRedirectOnError('/admin/tos', 'admin_tos');

		if (empty($_POST['page_tos'])) {
			return;
		}

		$displaytos = !empty($_POST['displaytos']);
		$displayprivstatement = !empty($_POST['displayprivstatement']);
		$tostext = (!empty($_POST['tostext']) ? strip_tags(trim($_POST['tostext'])) : '');

		Config::set('system', 'tosdisplay', $displaytos);
		Config::set('system', 'tosprivstatement', $displayprivstatement);
		Config::set('system', 'tostext', $tostext);

		info(L10n::t('The Terms of Service settings have been updated.'));

		DI::baseUrl()->redirect('admin/tos');
	}

	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$tos = new \Friendica\Module\Tos();
		$t = Renderer::getMarkupTemplate('admin/tos.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => L10n::t('Administration'),
			'$page' => L10n::t('Terms of Service'),
			'$displaytos' => ['displaytos', L10n::t('Display Terms of Service'), Config::get('system', 'tosdisplay'), L10n::t('Enable the Terms of Service page. If this is enabled a link to the terms will be added to the registration form and the general information page.')],
			'$displayprivstatement' => ['displayprivstatement', L10n::t('Display Privacy Statement'), Config::get('system', 'tosprivstatement'), L10n::t('Show some informations regarding the needed information to operate the node according e.g. to <a href="%s" target="_blank">EU-GDPR</a>.', 'https://en.wikipedia.org/wiki/General_Data_Protection_Regulation')],
			'$preview' => L10n::t('Privacy Statement Preview'),
			'$privtext' => $tos->privacy_complete,
			'$tostext' => ['tostext', L10n::t('The Terms of Service'), Config::get('system', 'tostext'), L10n::t('Enter the Terms of Service for your node here. You can use BBCode. Headers of sections should be [h2] and below.')],
			'$form_security_token' => parent::getFormSecurityToken('admin_tos'),
			'$submit' => L10n::t('Save Settings'),
		]);
	}
}
