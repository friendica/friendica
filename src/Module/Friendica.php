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
use Friendica\Core\Addon;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\PostUpdate;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Network\HTTPException;
use Friendica\Protocol\ActivityPub;

/**
 * Prints information about the current node
 * Either in human readable form or in JSON
 */
class Friendica extends BaseModule
{
	protected function content(array $request = []): string
	{
		$config = DI::config();
		$keyValue = DI::keyValue();

		$visibleAddonList = Addon::getVisibleList();
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
				'title' => DI::l10n()->t('Installed addons/apps:'),
				'list'  => $sortedAddonList,
			];
		} else {
			$addon = [
				'title' => DI::l10n()->t('No installed addons/apps'),
			];
		}

		$tos = ($config->get('system', 'tosdisplay')) ?
			DI::l10n()->t('Read about the <a href="%1$s/tos">Terms of Service</a> of this node.', DI::baseUrl()) :
			'';

		$blockList = $config->get('system', 'blocklist');

		if (!empty($blockList)) {
			$blocked = [
				'title'    => DI::l10n()->t('On this server the following remote servers are blocked.'),
				'header'   => [
					DI::l10n()->t('Blocked domain'),
					DI::l10n()->t('Reason for the block'),
				],
				'download' => DI::l10n()->t('Download this list in CSV format'),
				'list'     => $blockList,
			];
		} else {
			$blocked = null;
		}

		$hooked = '';

		Hook::callAll('about_hook', $hooked);

		$tpl = Renderer::getMarkupTemplate('friendica.tpl');

		return Renderer::replaceMacros($tpl, [
			'about'     => DI::l10n()->t('This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.',
				'<strong>' . App::VERSION . '</strong>',
				DI::baseUrl(),
				'<strong>' . $config->get('system', 'build') . '/' . DB_UPDATE_VERSION . '</strong>',
				'<strong>' . $keyValue->get('post_update_version') . '/' . PostUpdate::VERSION . '</strong>'),
			'friendica' => DI::l10n()->t('Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'),
			'bugs'      => DI::l10n()->t('Bug reports and issues: please visit') . ' ' . '<a href="https://github.com/friendica/friendica/issues?state=open">' . DI::l10n()->t('the bugtracker at github') . '</a>',
			'info'      => DI::l10n()->t('Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'),

			'visible_addons' => $addon,
			'tos'            => $tos,
			'block_list'     => $blocked,
			'hooked'         => $hooked,
		]);
	}

	protected function rawContent(array $request = [])
	{
		// @TODO: Replace with parameter from router
		if (DI::args()->getArgc() <= 1 || (DI::args()->getArgv()[1] !== 'json')) {
			if (!ActivityPub::isRequest()) {
				return;
			}

			try {
				$data = ActivityPub\Transmitter::getProfile(0);
				header('Access-Control-Allow-Origin: *');
				header('Cache-Control: max-age=23200, stale-while-revalidate=23200');
				System::jsonExit($data, 'application/activity+json');
			} catch (HTTPException\NotFoundException $e) {
				System::jsonError(404, ['error' => 'Record not found']);
			}
		}

		$config = DI::config();

		$register_policies = [
			Register::CLOSED  => 'REGISTER_CLOSED',
			Register::APPROVE => 'REGISTER_APPROVE',
			Register::OPEN    => 'REGISTER_OPEN'
		];

		$register_policy_int = intval($config->get('config', 'register_policy'));
		if ($register_policy_int !== Register::CLOSED && $config->get('config', 'invitation_only')) {
			$register_policy = 'REGISTER_INVITATION';
		} else {
			$register_policy = $register_policies[$register_policy_int];
		}

		$admin = [];
		$administrator = User::getFirstAdmin(['username', 'nickname']);
		if (!empty($administrator)) {
			$admin = [
				'name'    => $administrator['username'],
				'profile' => DI::baseUrl() . '/profile/' . $administrator['nickname'],
			];
		}

		$visible_addons = Addon::getVisibleList();

		$config->reload();
		$locked_features = [];
		$featureLocks = $config->get('config', 'feature_lock');
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
			'url'              => DI::baseUrl(),
			'addons'           => $visible_addons,
			'locked_features'  => $locked_features,
			'explicit_content' => intval($config->get('system', 'explicit_content', 0)),
			'language'         => $config->get('system', 'language'),
			'register_policy'  => $register_policy,
			'admin'            => $admin,
			'site_name'        => $config->get('config', 'sitename'),
			'platform'         => strtolower(App::PLATFORM),
			'info'             => $config->get('config', 'info'),
			'no_scrape_url'    => DI::baseUrl() . '/noscrape',
		];

		System::jsonExit($data);
	}
}
