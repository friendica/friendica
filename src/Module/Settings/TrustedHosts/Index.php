<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings\TrustedHosts;

use Friendica\App;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Index extends BaseSettings
{
	public function __construct(
		private readonly IManagePersonalConfigValues $pConfig,
		IHandleUserSessions $session,
		App\Page $page,
		L10n $l10n,
		App\BaseURL $baseUrl,
		App\Arguments $args,
		LoggerInterface $logger,
		Profiler $profiler,
		Response $response,
		array $server,
		array $parameters = [],
	) {
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);
	}

	protected function post(array $request = [])
	{
		self::checkFormSecurityTokenRedirectOnError('/settings/trustedhosts', 'settings-trustedhosts');

		$uid     = $this->session->getLocalUserId();
		$trusted = $this->pConfig->get($uid, 'system', 'trusted_iframe_hosts', []);

		if (array_key_exists('delete', $request) && is_array($request['delete'])) {
			foreach ($request['delete'] as $host => $delete) {
				if ($delete) {
					$trusted = array_diff($trusted, [$host]);
				}
			}
		}

		$this->pConfig->set($uid, 'system', 'trusted_iframe_hosts', array_values($trusted));

		$this->baseUrl->redirect($this->args->getQueryString());
	}

	protected function content(array $request = []): string
	{
		parent::content();

		$uid     = $this->session->getLocalUserId();
		$trusted = $this->pConfig->get($uid, 'system', 'trusted_iframe_hosts', []);
		sort($trusted);

		$pager = new Pager($this->l10n, $this->args->getQueryString(), 30);
		$total = count($trusted);
		$page  = array_slice($trusted, $pager->getStart(), $pager->getItemsPerPage());

		$deleteCheckboxes = array_map(function (string $host) {
			return ['delete[' . $host . ']'];
		}, $page);

		$tpl = Renderer::getMarkupTemplate('settings/trustedhosts.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'title'  => $this->t('External content'),
				'desc'   => $this->t('These are the hostnames you chose to always show embedded content from without being asked again. Remove an entry to be asked again the next time.'),
				'host'   => $this->t('Hostname'),
				'delete' => $this->t('Delete'),
				'submit' => $this->t('Save changes'),
			],

			'$count'    => $total,
			'$no_hosts' => $this->t("You haven't marked any hostname to always show its embedded content."),

			'$hosts' => $page,

			'$form_security_token' => self::getFormSecurityToken('settings-trustedhosts'),

			'$deleteCheckboxes' => $deleteCheckboxes,

			'$paginate' => $pager->renderFull($total),
		]);
	}
}
