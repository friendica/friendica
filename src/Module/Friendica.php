<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App;
use Friendica\App\Arguments;
use Friendica\App\BaseURL;
use Friendica\BaseModule;
use Friendica\Core\Addon\AddonHelper;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\KeyValueStorage\Capability\IManageKeyValuePairs;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Database\PostUpdate;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Prints information about the current node
 * Either in human-readable form or in JSON
 */
class Friendica extends BaseModule
{
	private AddonHelper $addonHelper;
	/** @var IManageConfigValues */
	private $config;
	/** @var IManageKeyValuePairs */
	private $keyValue;
	/** @var IHandleUserSessions */
	private $session;

	public function __construct(AddonHelper $addonHelper, IHandleUserSessions $session, IManageKeyValuePairs $keyValue, IManageConfigValues $config, L10n $l10n, BaseURL $baseUrl, Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config      = $config;
		$this->keyValue    = $keyValue;
		$this->session     = $session;
		$this->addonHelper = $addonHelper;
	}

	protected function content(array $request = []): string
	{
		$visibleAddonList = $this->addonHelper->getVisibleEnabledAddons();
		if (!empty($visibleAddonList)) {

			$sorted = $visibleAddonList;
			sort($sorted);

			$sortedAddonList = '';

			foreach ($sorted as $addon) {
				if (strlen($addon)) {
					if (strlen($sortedAddonList)) {
						$sortedAddonList .= ', ';
					}
					$sortedAddonList .= $addon;
				}
			}
			$addon = [
				'title' => $this->t('Installed addons/apps:'),
				'list'  => $sortedAddonList,
			];
		} else {
			$addon = [
				'title' => $this->t('No installed addons/apps'),
			];
		}

		$tos = ($this->config->get('system', 'tosdisplay')) ?
			$this->t('Read about the <a href="%1$s/tos">Terms of Service</a> of this node.', $this->baseUrl) :
			'';

		$blockList = $this->config->get('system', 'blocklist') ?? [];

		if (!empty($blockList) && ($this->config->get('blocklist', 'public') || $this->session->isAuthenticated())) {
			$blocked = [
				'title'  => $this->t('On this server the following remote servers are blocked.'),
				'header' => [
					$this->t('Blocked domain'),
					$this->t('Reason for the block'),
				],
				'download' => $this->t('Download this list in CSV format'),
				'list'     => $blockList,
			];
		} else {
			$blocked = null;
		}

		$hooked = '';

		Hook::callAll('about_hook', $hooked);

		$tpl = Renderer::getMarkupTemplate('friendica.tpl');

		return Renderer::replaceMacros($tpl, [
			'about' => $this->t(
				'This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.',
				'<strong>' . App::VERSION . '</strong>',
				$this->baseUrl,
				'<strong>' . $this->config->get('system', 'build') . '/' . DB_UPDATE_VERSION . '</strong>',
				'<strong>' . $this->keyValue->get('post_update_version') . '/' . PostUpdate::VERSION . '</strong>'
			),
			'friendica' => $this->t('Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'),
			'bugs'      => $this->t('Bug reports and issues: please visit') . ' ' . '<a href="https://github.com/friendica/friendica/issues?state=open">' . $this->t('the bugtracker at github') . '</a>',
			'info'      => $this->t('Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'),

			'visible_addons' => $addon,
			'tos'            => $tos,
			'block_list'     => $blocked,
			'hooked'         => $hooked,
		]);
	}

	protected function rawContent(array $request = [])
	{
		if (empty($this->parameters['format']) || $this->parameters['format'] !== 'json') {
			if (!ActivityPub::isRequest()) {
				return;
			}

			try {
				$data = ActivityPub\Transmitter::getProfile(0);
				header('Access-Control-Allow-Origin: *');
				header('Cache-Control: max-age=23200, stale-while-revalidate=23200');
				$this->jsonExit($data, 'application/activity+json');
			} catch (HTTPException\NotFoundException $e) {
				$this->jsonError(404, ['error' => 'Record not found']);
			}
		}

		$register_policies = [
			Register::CLOSED  => 'REGISTER_CLOSED',
			Register::APPROVE => 'REGISTER_APPROVE',
			Register::OPEN    => 'REGISTER_OPEN'
		];

		$register_policy_int = Register::getPolicy();
		if ($register_policy_int !== Register::CLOSED && $this->config->get('config', 'invitation_only')) {
			$register_policy = 'REGISTER_INVITATION';
		} else {
			$register_policy = $register_policies[$register_policy_int];
		}

		$admin         = [];
		$administrator = User::getFirstAdmin(['username', 'nickname']);
		if (!empty($administrator)) {
			$admin = [
				'name'    => $administrator['username'],
				'profile' => $this->baseUrl . '/profile/' . $administrator['nickname'],
			];
		}

		$visible_addons = $this->addonHelper->getVisibleEnabledAddons();

		$this->config->reload();
		$locked_features = [];
		$featureLocks    = $this->config->get('config', 'feature_lock');
		if (isset($featureLocks)) {
			foreach ($featureLocks as $feature => $lock) {
				if ($feature === 'config_loaded') {
					continue;
				}

				$locked_features[$feature] = intval($lock);
			}
		}

		$data = [
			'version'          => App::VERSION,
			'url'              => (string)$this->baseUrl,
			'addons'           => $visible_addons,
			'locked_features'  => $locked_features,
			'explicit_content' => intval($this->config->get('system', 'explicit_content', 0)),
			'language'         => $this->config->get('system', 'language'),
			'register_policy'  => $register_policy,
			'admin'            => $admin,
			'site_name'        => $this->config->get('config', 'sitename'),
			'platform'         => strtolower(App::PLATFORM),
			'info'             => $this->config->get('config', 'info'),
			'no_scrape_url'    => $this->baseUrl . '/noscrape',
		];

		$this->jsonExit($data);
	}
}
