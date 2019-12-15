<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;

class BaseSettingsModule extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$a = DI::app();

		$tpl = Renderer::getMarkupTemplate('settings/head.tpl');
		$a->page['htmlhead'] .= Renderer::replaceMacros($tpl, [
			'$ispublic' => L10n::t('everybody')
		]);

		$tabs = [];

		$tabs[] = [
			'label' => L10n::t('Account'),
			'url' => 'settings',
			'selected' => (($a->argc == 1) && ($a->argv[0] === 'settings') ? 'active' : ''),
			'accesskey' => 'o',
		];

		$tabs[] = [
			'label' => L10n::t('Two-factor authentication'),
			'url' => 'settings/2fa',
			'selected' => (($a->argc > 1) && ($a->argv[1] === '2fa') ? 'active' : ''),
			'accesskey' => 'o',
		];

		$tabs[] = [
			'label' => L10n::t('Profiles'),
			'url' => 'profiles',
			'selected' => (($a->argc == 1) && ($a->argv[0] === 'profiles') ? 'active' : ''),
			'accesskey' => 'p',
		];

		if (Feature::get()) {
			$tabs[] = [
				'label' => L10n::t('Additional features'),
				'url' => 'settings/features',
				'selected' => (($a->argc > 1) && ($a->argv[1] === 'features') ? 'active' : ''),
				'accesskey' => 't',
			];
		}

		$tabs[] = [
			'label' => L10n::t('Display'),
			'url' => 'settings/display',
			'selected' => (($a->argc > 1) && ($a->argv[1] === 'display') ? 'active' : ''),
			'accesskey' => 'i',
		];

		$tabs[] = [
			'label' => L10n::t('Social Networks'),
			'url' => 'settings/connectors',
			'selected' => (($a->argc > 1) && ($a->argv[1] === 'connectors') ? 'active' : ''),
			'accesskey' => 'w',
		];

		$tabs[] = [
			'label' => L10n::t('Addons'),
			'url' => 'settings/addon',
			'selected' => (($a->argc > 1) && ($a->argv[1] === 'addon') ? 'active' : ''),
			'accesskey' => 'l',
		];

		$tabs[] = [
			'label' => L10n::t('Delegations'),
			'url' => 'settings/delegation',
			'selected' => (($a->argc > 1) && ($a->argv[1] === 'delegation') ? 'active' : ''),
			'accesskey' => 'd',
		];

		$tabs[] = [
			'label' => L10n::t('Connected apps'),
			'url' => 'settings/oauth',
			'selected' => (($a->argc > 1) && ($a->argv[1] === 'oauth') ? 'active' : ''),
			'accesskey' => 'b',
		];

		$tabs[] = [
			'label' => L10n::t('Export personal data'),
			'url' => 'settings/userexport',
			'selected' => (($a->argc > 1) && ($a->argv[1] === 'userexport') ? 'active' : ''),
			'accesskey' => 'e',
		];

		$tabs[] = [
			'label' => L10n::t('Remove account'),
			'url' => 'removeme',
			'selected' => (($a->argc == 1) && ($a->argv[0] === 'removeme') ? 'active' : ''),
			'accesskey' => 'r',
		];


		$tabtpl = Renderer::getMarkupTemplate("generic_links_widget.tpl");
		$a->page['aside'] = Renderer::replaceMacros($tabtpl, [
			'$title' => L10n::t('Settings'),
			'$class' => 'settings-widget',
			'$items' => $tabs,
		]);
	}
}
