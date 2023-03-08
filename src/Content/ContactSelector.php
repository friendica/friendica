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

namespace Friendica\Content;

use Friendica\Core\Hook;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Util\Network;
use Friendica\Util\Strings;

/**
 * ContactSelector class
 */
class ContactSelector
{
	static $serverdata = [];
	static $server_url = [];

	/**
	 * @param string  $current  current
	 * @param boolean $disabled optional, default false
	 * @return string
	 */
	public static function pollInterval(string $current, bool $disabled = false): string
	{
		$dis = (($disabled) ? ' disabled="disabled" ' : '');
		$o = '';
		$o .= "<select id=\"contact-poll-interval\" name=\"poll\" $dis />" . "\r\n";

		$rep = [
			0 => DI::l10n()->t('Frequently'),
			1 => DI::l10n()->t('Hourly'),
			2 => DI::l10n()->t('Twice daily'),
			3 => DI::l10n()->t('Daily'),
			4 => DI::l10n()->t('Weekly'),
			5 => DI::l10n()->t('Monthly')
		];

		foreach ($rep as $k => $v) {
			$selected = (($k == $current) ? " selected=\"selected\" " : "");
			$o .= "<option value=\"$k\" $selected >$v</option>\r\n";
		}
		$o .= "</select>\r\n";
		return $o;
	}

	private static function getServerForProfile(string $profile)
	{
		$server_url = self::getServerURLForProfile($profile);

		if (!empty(self::$serverdata[$server_url])) {
			return self::$serverdata[$server_url];
		}

		// Now query the GServer for the platform name
		$gserver = DBA::selectFirst('gserver', ['platform', 'network'], ['nurl' => $server_url]);

		self::$serverdata[$server_url] = $gserver;
		return $gserver;
	}

	/**
	 * @param string $profile Profile URL
	 * @return string Server URL
	 * @throws \Exception
	 */
	private static function getServerURLForProfile(string $profile): string
	{
		if (!empty(self::$server_url[$profile])) {
			return self::$server_url[$profile];
		}

		$server_url = '';

		// Fetch the server url from the contact table
		$contact = DBA::selectFirst('contact', ['baseurl'], ['uid' => 0, 'nurl' => Strings::normaliseLink($profile)]);
		if (DBA::isResult($contact) && !empty($contact['baseurl'])) {
			$server_url = Strings::normaliseLink($contact['baseurl']);
		}

		if (empty($server_url)) {
			// Create the server url out of the profile url
			$parts = parse_url($profile);
			unset($parts['path']);
			$server_url = Strings::normaliseLink(Network::unparseURL($parts));
		}

		self::$server_url[$profile] = $server_url;

		return $server_url;
	}

	/**
	 * Determines network name
	 *
	 * @param string $network  network of the contact
	 * @param string $profile  optional, default empty
	 * @param string $protocol (Optional) Protocol that is used for the transmission
	 * @param int $gsid Server id
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function networkToName(string $network, string $profile = '', string $protocol = '', int $gsid = null): string
	{
		$nets = [
			Protocol::DFRN      =>   DI::l10n()->t('DFRN'),
			Protocol::OSTATUS   =>   DI::l10n()->t('OStatus'),
			Protocol::FEED      =>   DI::l10n()->t('RSS/Atom'),
			Protocol::MAIL      =>   DI::l10n()->t('Email'),
			Protocol::DIASPORA  =>   DI::l10n()->t('Diaspora'),
			Protocol::ZOT       =>   DI::l10n()->t('Zot!'),
			Protocol::LINKEDIN  =>   DI::l10n()->t('LinkedIn'),
			Protocol::XMPP      =>   DI::l10n()->t('XMPP/IM'),
			Protocol::MYSPACE   =>   DI::l10n()->t('MySpace'),
			Protocol::GPLUS     =>   DI::l10n()->t('Google+'),
			Protocol::PUMPIO    =>   DI::l10n()->t('pump.io'),
			Protocol::TWITTER   =>   DI::l10n()->t('Twitter'),
			Protocol::DISCOURSE =>   DI::l10n()->t('Discourse'),
			Protocol::DIASPORA2 =>   DI::l10n()->t('Diaspora Connector'),
			Protocol::STATUSNET =>   DI::l10n()->t('GNU Social Connector'),
			Protocol::ACTIVITYPUB => DI::l10n()->t('ActivityPub'),
			Protocol::PNUT      =>   DI::l10n()->t('pnut'),
		];

		Hook::callAll('network_to_name', $nets);

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$networkname = str_replace($search, $replace, $network);

		if ((in_array($network, Protocol::FEDERATED)) && ($profile != "")) {
			if (!empty($gsid) && !empty(self::$serverdata[$gsid])) {
				$gserver = self::$serverdata[$gsid];
			} elseif (!empty($gsid)) {
				$gserver = DBA::selectFirst('gserver', ['platform', 'network'], ['id' => $gsid]);
				self::$serverdata[$gsid] = $gserver;
			} else {
				$gserver = self::getServerForProfile($profile);
			}

			if (!empty($gserver['platform'])) {
				$platform = $gserver['platform'];
			} elseif (!empty($gserver['network']) && ($gserver['network'] != Protocol::ACTIVITYPUB)) {
				$platform = self::networkToName($gserver['network']);
			}

			if (!empty($platform)) {
				$networkname = $platform;

				if ($network == Protocol::ACTIVITYPUB) {
					$networkname .= ' (AP)';
				}
			}
		}

		if (!empty($protocol) && ($protocol != $network)) {
			$networkname = DI::l10n()->t('%s (via %s)', $networkname, self::networkToName($protocol));
		}

		return $networkname;
	}

	/**
	 * Determines network's icon name
	 *
	 * @param string $network network
	 * @param string $profile optional, default empty
	 * @param int $gsid Server id
	 * @return string Name for network icon
	 * @throws \Exception
	 */
	public static function networkToIcon(string $network, string $profile = "", int $gsid = null): string
	{
		$nets = [
			Protocol::DFRN      =>   'friendica',
			Protocol::OSTATUS   =>   'gnu-social', // There is no generic OStatus icon
			Protocol::FEED      =>   'rss',
			Protocol::MAIL      =>   'inbox',
			Protocol::DIASPORA  =>   'diaspora',
			Protocol::ZOT       =>   'hubzilla',
			Protocol::LINKEDIN  =>   'linkedin',
			Protocol::XMPP      =>   'xmpp',
			Protocol::MYSPACE   =>   'file-text-o', /// @todo
			Protocol::GPLUS     =>   'google-plus',
			Protocol::PUMPIO    =>   'file-text-o', /// @todo
			Protocol::TWITTER   =>   'twitter',
			Protocol::DISCOURSE =>   'dot-circle-o', /// @todo
			Protocol::DIASPORA2 =>   'diaspora',
			Protocol::STATUSNET =>   'gnu-social',
			Protocol::ACTIVITYPUB => 'activitypub',
			Protocol::PNUT      =>   'file-text-o', /// @todo
		];

		$platform_icons = ['diaspora' => 'diaspora', 'friendica' => 'friendica', 'friendika' => 'friendica',
			'GNU Social' => 'gnu-social', 'gnusocial' => 'gnu-social', 'hubzilla' => 'hubzilla',
			'mastodon' => 'mastodon', 'peertube' => 'peertube', 'pixelfed' => 'pixelfed',
			'pleroma' => 'pleroma', 'red' => 'hubzilla', 'redmatrix' => 'hubzilla',
			'socialhome' => 'social-home', 'wordpress' => 'wordpress'];

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$network_icon = str_replace($search, $replace, $network);

		if ((in_array($network, Protocol::FEDERATED)) && ($profile != "")) {
			if (!empty($gsid) && !empty(self::$serverdata[$gsid])) {
				$gserver = self::$serverdata[$gsid];
			} elseif (!empty($gsid)) {
				$gserver = DBA::selectFirst('gserver', ['platform', 'network'], ['id' => $gsid]);
				self::$serverdata[$gsid] = $gserver;
			} else {
				$gserver = self::getServerForProfile($profile);
			}
			if (!empty($gserver['platform'])) {
				$network_icon = $platform_icons[strtolower($gserver['platform'])] ?? $network_icon;
			}
		}

		return $network_icon;
	}
}
