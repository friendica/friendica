<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Module\BaseAdmin;

class Federation extends BaseAdmin
{
	protected function content(array $request = []): string
	{
		parent::content();

		// get counts on active federation systems this node is knowing
		// We list the more common systems by name. The rest is counted as "other"
		$systems = [
			'friendica'   => ['name' => 'Friendica', 'color' => '#ffc018'], // orange from the logo
			'diaspora'    => ['name' => 'Diaspora', 'color' => '#a1a1a1'], // logo is black and white, makes a gray
			'funkwhale'   => ['name' => 'Funkwhale', 'color' => '#4082B4'], // From the homepage
			'gnusocial'   => ['name' => 'GNU Social/Statusnet', 'color' => '#a22430'], // dark red from the logo
			'hubzilla'    => ['name' => 'Hubzilla/Red Matrix', 'color' => '#43488a'], // blue from the logo
			'mastodon'    => ['name' => 'Mastodon', 'color' => '#1a9df9'], // blue from the Mastodon logo
			'misskey'     => ['name' => 'Misskey', 'color' => '#ccfefd'], // Font color of the homepage
			'mobilizon'   => ['name' => 'Mobilizon', 'color' => '#ffd599'], // Background color of parts of the homepage
			'nextcloud'   => ['name' => 'Nextcloud', 'color' => '#1cafff'], // Logo color
			'peertube'    => ['name' => 'Peertube', 'color' => '#ffad5c'], // One of the logo colors
			'pixelfed'    => ['name' => 'Pixelfed', 'color' => '#11da47'], // One of the logo colors
			'pleroma'     => ['name' => 'Pleroma', 'color' => '#E46F0F'], // Orange from the text that is used on Pleroma instances
			'plume'       => ['name' => 'Plume', 'color' => '#7765e3'], // From the homepage
			'socialhome'  => ['name' => 'SocialHome', 'color' => '#52056b'], // lilac from the Django Image used at the Socialhome homepage
			'wordpress'   => ['name' => 'WordPress', 'color' => '#016087'], // Background color of the homepage
			'writefreely' => ['name' => 'WriteFreely', 'color' => '#292929'], // Font color of the homepage
			'mistpark'    => ['name' => 'Nomad projects (Mistpark, Osada, Roadhouse, Zap)', 'color' => '#348a4a'], // Green like the Mistpark green
			'relay'       => ['name' => 'ActivityPub Relay', 'color' => '#888888'], // Grey like the second color of the ActivityPub logo
			'other'       => ['name' => DI::l10n()->t('Other'), 'color' => '#F1007E'], // ActivityPub main color
		];

		$platforms = array_keys($systems);

		$counts = [];
		foreach ($platforms as $platform) {
			$counts[$platform] = [];
		}

		$total = 0;
		$users = 0;

		$gservers = DBA::p("SELECT COUNT(*) AS `total`, SUM(`registered-users`) AS `users`, `platform`,
			ANY_VALUE(`network`) AS `network`, MAX(`version`) AS `version`
			FROM `gserver` WHERE NOT `failed` GROUP BY `platform`");
		while ($gserver = DBA::fetch($gservers)) {
			$total += $gserver['total'];
			$users += $gserver['users'];

			$versionCounts = [];
			$versions = DBA::p("SELECT COUNT(*) AS `total`, `version` FROM `gserver`
				WHERE NOT `failed` AND `platform` = ?
				GROUP BY `version` ORDER BY `version`", $gserver['platform']);
			while ($version = DBA::fetch($versions)) {
				$version['version'] = str_replace(["\n", "\r", "\t"], " ", $version['version']);

				if (in_array($gserver['platform'], ['Red Matrix', 'redmatrix', 'red'])) {
					$version['version'] = 'Red ' . $version['version'];
				} elseif (in_array($gserver['platform'], ['osada', 'mistpark', 'roadhouse', 'zap'])) {
					$version['version'] = $gserver['platform'] . ' ' . $version['version'];
				} elseif (in_array($gserver['platform'], ['activityrelay', 'pub-relay', 'selective-relay', 'aoderelay'])) {
					$version['version'] = $gserver['platform'] . '-' . $version['version'];
				}

				$versionCounts[] = $version;
			}
			DBA::close($versions);

			$platform = $gserver['platform'] = strtolower($gserver['platform']);

			if ($platform == 'friendika') {
				$platform = 'friendica';
			} elseif (in_array($platform, ['red matrix', 'redmatrix', 'red'])) {
				$platform = 'hubzilla';
			} elseif (in_array($platform, ['mistpark', 'osada', 'roadhouse', 'zap'])) {
				$platform = 'mistpark';
			} elseif(stristr($platform, 'pleroma')) {
				$platform = 'pleroma';
			} elseif(stristr($platform, 'statusnet')) {
				$platform = 'gnusocial';
			} elseif(stristr($platform, 'wordpress')) {
				$platform = 'wordpress';
			} elseif (in_array($platform, ['activityrelay', 'pub-relay', 'selective-relay', 'aoderelay'])) {
				$platform = 'relay';
			} elseif (!in_array($platform, $platforms)) {
				$platform = 'other';
			}

			if ($platform != $gserver['platform']) {
				if ($platform == 'other') {
					$versionCounts = $counts[$platform][1] ?? [];
					$versionCounts[] = ['version' => $gserver['platform'] ?: DI::l10n()->t('unknown'), 'total' => $gserver['total']];
					$gserver['version'] = '';
				} else {
					$versionCounts = array_merge($versionCounts, $counts[$platform][1] ?? []);
				}

				$gserver['platform'] = $platform;
				$gserver['total'] += $counts[$platform][0]['total'] ?? 0;
				$gserver['users'] += $counts[$platform][0]['users'] ?? 0;
			}

			if ($platform == 'friendica') {
				$versionCounts = self::reformaFriendicaVersions($versionCounts);
			} elseif ($platform == 'pleroma') {
				$versionCounts = self::reformaPleromaVersions($versionCounts);
			} elseif ($platform == 'diaspora') {
				$versionCounts = self::reformaDiasporaVersions($versionCounts);
			} elseif ($platform == 'relay') {
				$versionCounts = self::reformatRelayVersions($versionCounts);
			} elseif (in_array($platform, ['funkwhale', 'mastodon', 'mobilizon', 'misskey'])) {
				$versionCounts = self::removeVersionSuffixes($versionCounts);
			}

			if (!in_array($platform, ['other', 'relay', 'mistpark'])) {
				$versionCounts = self::sortVersion($versionCounts);
			} else {
				ksort($versionCounts);
			}

			$gserver['platform'] = $systems[$platform]['name'];

			$counts[$platform] = [$gserver, $versionCounts, str_replace([' ', '%'], '', $platform), $systems[$platform]['color']];
		}
		DBA::close($gserver);

		// some helpful text
		$intro = DI::l10n()->t('This page offers you some numbers to the known part of the federated social network your Friendica node is part of. These numbers are not complete but only reflect the part of the network your node is aware of.');

		// load the template, replace the macros and return the page content
		$t = Renderer::getMarkupTemplate('admin/federation.tpl');
		return Renderer::replaceMacros($t, [
			'$title' => DI::l10n()->t('Administration'),
			'$page' => DI::l10n()->t('Federation Statistics'),
			'$intro' => $intro,
			'$counts' => $counts,
			'$version' => FRIENDICA_VERSION,
			'$legendtext' => DI::l10n()->t('Currently this node is aware of %d nodes with %d registered users from the following platforms:', $total, $users),
		]);
	}

	/**
	 * early friendica versions have the format x.x.xxxx where xxxx is the
	 * DB version stamp; those should be operated out and versions be combined
	 *
	 * @param array $versionCounts list of version numbers
	 * @return array with cleaned version numbers
	 */
	private static function reformaFriendicaVersions(array $versionCounts)
	{
		$newV = [];
		$newVv = [];
		foreach ($versionCounts as $vv) {
			$newVC = $vv['total'];
			$newVV = $vv['version'];
			$lastDot = strrpos($newVV, '.');
			$firstDash = strpos($newVV, '-');
			$len = strlen($newVV) - 1;
			if (($lastDot == $len - 4) && (!strrpos($newVV, '-rc') == $len - 3) && (!$firstDash == $len - 1)) {
				$newVV = substr($newVV, 0, $lastDot);
			}
			if (isset($newV[$newVV])) {
				$newV[$newVV] += $newVC;
			} else {
				$newV[$newVV] = $newVC;
			}
		}
		foreach ($newV as $key => $value) {
			array_push($newVv, ['total' => $value, 'version' => $key]);
		}
		$versionCounts = $newVv;

		return $versionCounts;
	}

	/**
	 * in the DB the Diaspora versions have the format x.x.x.x-xx the last
	 * part (-xx) should be removed to clean up the versions from the "head
	 * commit" information and combined into a single entry for x.x.x.x
	 *
	 * @param array $versionCounts list of version numbers
	 * @return array with cleaned version numbers
	 */
	private static function reformaDiasporaVersions(array $versionCounts)
	{
		$newV = [];
		$newVv = [];
		foreach ($versionCounts as $vv) {
			$newVC = $vv['total'];
			$newVV = $vv['version'];
			$posDash = strpos($newVV, '-');
			if ($posDash) {
				$newVV = substr($newVV, 0, $posDash);
			}
			if (isset($newV[$newVV])) {
				$newV[$newVV] += $newVC;
			} else {
				$newV[$newVV] = $newVC;
			}
		}
		foreach ($newV as $key => $value) {
			array_push($newVv, ['total' => $value, 'version' => $key]);
		}
		$versionCounts = $newVv;

		return $versionCounts;
	}

	/**
	 * Clean up Pleroma version numbers
	 *
	 * @param array $versionCounts list of version numbers
	 * @return array with cleaned version numbers
	 */
	private static function reformaPleromaVersions(array $versionCounts)
	{
		$compacted = [];
		foreach ($versionCounts as $key => $value) {
			$version = $versionCounts[$key]['version'];
			$parts = explode(' ', trim($version));
			do {
				$part = array_pop($parts);
			} while (!empty($parts) && ((strlen($part) >= 40) || (strlen($part) <= 3)));
			// only take the x.x.x part of the version, not the "release" after the dash
			if (!empty($part) && strpos($part, '-')) {
				$part = explode('-', $part)[0];
			}
			if (!empty($part)) {
				if (empty($compacted[$part])) {
					$compacted[$part] = $versionCounts[$key]['total'];
				} else {
					$compacted[$part] += $versionCounts[$key]['total'];
				}
			}
		}

		$versionCounts = [];
		foreach ($compacted as $version => $pl_total) {
			$versionCounts[] = ['version' => $version, 'total' => $pl_total];
		}

		return $versionCounts;
	}

	/**
	 * Clean up version numbers
	 *
	 * @param array $versionCounts list of version numbers
	 * @return array with cleaned version numbers
	 */
	private static function removeVersionSuffixes(array $versionCounts)
	{
		$compacted = [];
		foreach ($versionCounts as $key => $value) {
			$version = $versionCounts[$key]['version'];

			foreach ([' ', '+', '-', '#', '_', '~'] as $delimiter) {
				$parts = explode($delimiter, trim($version));
				$version = array_shift($parts);
			}

			if (empty($compacted[$version])) {
				$compacted[$version] = $versionCounts[$key]['total'];
			} else {
				$compacted[$version] += $versionCounts[$key]['total'];
			}
		}

		$versionCounts = [];
		foreach ($compacted as $version => $pl_total) {
			$versionCounts[] = ['version' => $version, 'total' => $pl_total];
		}

		return $versionCounts;
	}

	/**
	 * Clean up relay version numbers
	 *
	 * @param array $versionCounts list of version numbers
	 * @return array with cleaned version numbers
	 */
	private static function reformatRelayVersions(array $versionCounts)
	{
		$compacted = [];
		foreach ($versionCounts as $key => $value) {
			$version = $versionCounts[$key]['version'];

			$parts = explode(' ', trim($version));
			$version = array_shift($parts);

			if (empty($compacted[$version])) {
				$compacted[$version] = $versionCounts[$key]['total'];
			} else {
				$compacted[$version] += $versionCounts[$key]['total'];
			}
		}

		$versionCounts = [];
		foreach ($compacted as $version => $pl_total) {
			$versionCounts[] = ['version' => $version, 'total' => $pl_total];
		}

		return $versionCounts;
	}

	/**
	 * Reformat, sort and compact version numbers
	 *
	 * @param array $versionCounts list of version numbers
	 * @return array with reformatted version numbers
	 */
	private static function sortVersion(array $versionCounts)
	{
		//
		// clean up version numbers
		//
		// some platforms do not provide version information, add a unkown there
		// to the version string for the displayed list.
		foreach ($versionCounts as $key => $value) {
			if ($versionCounts[$key]['version'] == '') {
				$versionCounts[$key] = ['total' => $versionCounts[$key]['total'], 'version' => DI::l10n()->t('unknown')];
			}
		}

		// Assure that the versions are sorted correctly
		$v2 = [];
		$versions = [];
		foreach ($versionCounts as $vv) {
			$version = trim(strip_tags($vv["version"]));
			$v2[$version] = $vv;
			$versions[] = $version;
		}

		usort($versions, 'version_compare');

		$versionCounts = [];
		foreach ($versions as $version) {
			$versionCounts[] = $v2[$version];
		}

		return $versionCounts;
	}
}
