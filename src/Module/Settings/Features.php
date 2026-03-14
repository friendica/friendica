<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Content\Conversation;
use Friendica\Content\Feature;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Features extends BaseSettings
{
	/** @var IManagePersonalConfigValues */
	private $pConfig;

	public function __construct(IManagePersonalConfigValues $pConfig, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->pConfig = $pConfig;
	}

	protected function post(array $request = [])
	{
		BaseSettings::checkFormSecurityTokenRedirectOnError('/settings/features', 'settings_features');
		foreach ($request as $k => $v) {
			if (strpos($k, 'feature_') === 0) {
				$key = substr($k, 8);
				if ($key == 'widgetorder') { // not boolean, stringified array
					$this->pConfig->set($this->session->getLocalUserId(), 'feature', 'widgetorder', $v);
				} elseif ($key == 'resetorder' && $v) {
					$this->pConfig->delete($this->session->getLocalUserId(), 'feature', 'widgetorder');
				} else {
					$this->pConfig->set($this->session->getLocalUserId(), 'feature', $key, $v);
				}
			}
		}
	}

	protected function content(array $request = []): string
	{
		parent::content($request);

		$arr = [];
		foreach (Feature::get() as $name => $feature) {
			$arr[$name]    = [];
			$arr[$name][0] = $feature[0];
			foreach (array_slice($feature, 1) as $f) {
				$arr[$name][1][] = ['feature_' . $f[0], $f[1], Feature::isEnabled($this->session->getLocalUserId(), $f[0]), $f[2]];
			}
		}
		// try to get widget order preference
		$widgetorder = json_decode($this->pConfig->get($this->session->getLocalUserId(), 'feature', 'widgetorder'));
		if (!empty($widgetorder)) {
			$tmp = [];
			// iterate through widgetorder and network items
			foreach ($widgetorder as $widget) {
				foreach ($arr['network'][1] as $list_item) {
					if ($list_item[0] == 'feature_' . $widget) {
						$tmp[] = $list_item;
					}
				}
			}
			// replace network order with temp array order
			$arr['network'][1] = $tmp;
		};

		$tpl = Renderer::getMarkupTemplate('settings/features.tpl');
		return Renderer::replaceMacros($tpl, [
			'$form_security_token' => BaseSettings::getFormSecurityToken('settings_features'),
			'$title'               => $this->t('Additional Features'),
			'$sortable'            => $this->t('Drag to reorder, use arrow buttons on each item, or tab to item with keyboard and move up/down with arrow keys'),
			'$network_mode'        => Conversation::MODE_NETWORK,
			'$reset'               => [
				'0' => 'feature_resetorder',
				'1' => $this->t('Reset order'),
			],
			'$features' => $arr,
			'$submit'   => $this->t('Save Settings'),
		]);
	}
}
