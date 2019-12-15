<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Addon;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model\User;

/**
 * Prints information about the current node
 * Either in human readable form or in JSON
 */
class Friendica extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$app = DI::app();
		$config = DI::config();

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
				'title' => L10n::t('Installed addons/apps:'),
				'list'  => $sortedAddonList,
			];
		} else {
			$addon = [
				'title' => L10n::t('No installed addons/apps'),
			];
		}

		$tos = ($config->get('system', 'tosdisplay')) ?
			L10n::t('Read about the <a href="%1$s/tos">Terms of Service</a> of this node.', $app->getBaseURL()) :
			'';

		$blockList = $config->get('system', 'blocklist');

		if (!empty($blockList)) {
			$blocked = [
				'title'  => L10n::t('On this server the following remote servers are blocked.'),
				'header' => [
					L10n::t('Blocked domain'),
					L10n::t('Reason for the block'),
				],
				'list'   => $blockList,
			];
		} else {
			$blocked = null;
		}

		$hooked = '';

		Hook::callAll('about_hook', $hooked);

		$tpl = Renderer::getMarkupTemplate('friendica.tpl');

		return Renderer::replaceMacros($tpl, [
			'about'     => L10n::t('This is Friendica, version %s that is running at the web location %s. The database version is %s, the post update version is %s.',
				'<strong>' . FRIENDICA_VERSION . '</strong>',
				$app->getBaseURL(),
				'<strong>' . DB_UPDATE_VERSION . '</strong>',
				'<strong>' . $config->get('system', 'post_update_version') . '</strong>'),
			'friendica' => L10n::t('Please visit <a href="https://friendi.ca">Friendi.ca</a> to learn more about the Friendica project.'),
			'bugs'      => L10n::t('Bug reports and issues: please visit') . ' ' . '<a href="https://github.com/friendica/friendica/issues?state=open">' . L10n::t('the bugtracker at github') . '</a>',
			'info'      => L10n::t('Suggestions, praise, etc. - please email "info" at "friendi - dot - ca'),

			'visible_addons' => $addon,
			'tos'            => $tos,
			'block_list'     => $blocked,
			'hooked'         => $hooked,
		]);
	}

	public static function rawContent(array $parameters = [])
	{
		$app = DI::app();

		// @TODO: Replace with parameter from router
		if ($app->argc <= 1 || ($app->argv[1] !== 'json')) {
			return;
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

		$condition = [];
		$admin = false;
		if (!empty($config->get('config', 'admin_nickname'))) {
			$condition['nickname'] = $config->get('config', 'admin_nickname');
		}
		if (!empty($config->get('config', 'admin_email'))) {
			$adminList = explode(',', str_replace(' ', '', $config->get('config', 'admin_email')));
			$condition['email'] = $adminList[0];
			$administrator = User::getByEmail($adminList[0], ['username', 'nickname']);
			if (!empty($administrator)) {
				$admin = [
					'name'    => $administrator['username'],
					'profile' => $app->getBaseURL() . '/profile/' . $administrator['nickname'],
				];
			}
		}

		$visible_addons = Addon::getVisibleList();

		$config->load('feature_lock');
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
			'version'          => FRIENDICA_VERSION,
			'url'              => $app->getBaseURL(),
			'addons'           => $visible_addons,
			'locked_features'  => $locked_features,
			'explicit_content' => intval($config->get('system', 'explicit_content', 0)),
			'language'         => $config->get('system', 'language'),
			'register_policy'  => $register_policy,
			'admin'            => $admin,
			'site_name'        => $config->get('config', 'sitename'),
			'platform'         => FRIENDICA_PLATFORM,
			'info'             => $config->get('config', 'info'),
			'no_scrape_url'    => $app->getBaseURL() . '/noscrape',
		];

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($data);
		exit();
	}
}
