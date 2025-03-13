<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Content;

use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Event\ArrayFilterEvent;
use Friendica\Util\Strings;

/**
 * ContactSelector class
 */
class ContactSelector
{
	const SVG_DISABLED    = -1;
	const SVG_COLOR_BLACK = 0;
	const SVG_BLACK       = 1;
	const SVG_COLOR_WHITE = 2;
	const SVG_WHITE       = 3;

	public static $serverdata = [];
	public static $server_id  = [];

	/**
	 * @param string  $current  current
	 * @param boolean $disabled optional, default false
	 * @return string
	 */
	public static function pollInterval(string $current, bool $disabled = false): string
	{
		$dis = (($disabled) ? ' disabled="disabled" ' : '');
		$o   = '';
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

	/**
	 * Fetches the server id for a given profile
	 *
	 * @param string $profile
	 * @return integer
	 */
	public static function getServerIdForProfile(string $profile): int
	{
		if (!empty(self::$server_id[$profile])) {
			return self::$server_id[$profile];
		}

		$contact = DBA::selectFirst('contact', ['gsid'], ['uid' => 0, 'nurl' => Strings::normaliseLink($profile)]);
		if (empty($contact['gsid'])) {
			return 0;
		}

		self::$server_id[$profile] = $contact['gsid'];

		return $contact['gsid'];
	}

	/**
	 * Get server array for a given server id
	 *
	 * @param integer $gsid
	 * @return array
	 */
	private static function getServerForId(int $gsid = null): array
	{
		if (empty($gsid)) {
			return [];
		}

		if (!empty(self::$serverdata[$gsid])) {
			return self::$serverdata[$gsid];
		}

		$gserver = DBA::selectFirst('gserver', ['id', 'url', 'platform', 'network'], ['id' => $gsid]);
		if (empty($gserver)) {
			return [];
		}

		self::$serverdata[$gserver['id']] = $gserver;
		return $gserver;
	}

	/**
	 * Determines network name
	 *
	 * @param string $network  network of the contact
	 * @param string $protocol (Optional) Protocol that is used for the transmission
	 * @param int $gsid Server id
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function networkToName(string $network, string $protocol = '', int $gsid = null): string
	{
		$eventDispatcher = DI::eventDispatcher();

		$nets = [
			Protocol::DFRN        => DI::l10n()->t('DFRN'),
			Protocol::OSTATUS     => DI::l10n()->t('OStatus'),
			Protocol::FEED        => DI::l10n()->t('RSS/Atom'),
			Protocol::MAIL        => DI::l10n()->t('Email'),
			Protocol::DIASPORA    => DI::l10n()->t('Diaspora'),
			Protocol::ZOT         => DI::l10n()->t('Zot!'),
			Protocol::LINKEDIN    => DI::l10n()->t('LinkedIn'),
			Protocol::XMPP        => DI::l10n()->t('XMPP/IM'),
			Protocol::MYSPACE     => DI::l10n()->t('MySpace'),
			Protocol::GPLUS       => DI::l10n()->t('Google+'),
			Protocol::PUMPIO      => DI::l10n()->t('pump.io'),
			Protocol::TWITTER     => DI::l10n()->t('Twitter'),
			Protocol::DISCOURSE   => DI::l10n()->t('Discourse'),
			Protocol::DIASPORA2   => DI::l10n()->t('Diaspora Connector'),
			Protocol::STATUSNET   => DI::l10n()->t('GNU Social Connector'),
			Protocol::ACTIVITYPUB => DI::l10n()->t('ActivityPub'),
			Protocol::PNUT        => DI::l10n()->t('pnut'),
			Protocol::TUMBLR      => DI::l10n()->t('Tumblr'),
			Protocol::BLUESKY     => DI::l10n()->t('Bluesky'),
		];

		$nets = $eventDispatcher->dispatch(
			new ArrayFilterEvent(ArrayFilterEvent::NETWORK_TO_NAME, $nets),
		)->getArray();

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$networkname = str_replace($search, $replace, $network);
		$platform    = '';

		if (in_array($network, Protocol::FEDERATED) && !empty($gsid)) {
			$gserver = self::getServerForId($gsid);

			if (!empty($gserver['platform'])) {
				$platform = (string) $gserver['platform'];
			} elseif (!empty($gserver['network']) && ($gserver['network'] != Protocol::ACTIVITYPUB)) {
				$platform = self::networkToName($gserver['network']);
			}

			if ($platform !== '') {
				$networkname = $platform;
			}
		}

		if (!empty($protocol) && ($protocol != $network) && $network != Protocol::DFRN) {
			$networkname = DI::l10n()->t('%s (via %s)', $networkname, self::networkToName($protocol));
		} elseif (in_array($network, ['', $protocol]) && ($network == Protocol::DFRN)) {
			$networkname .= ' (DFRN)';
		} elseif (in_array($network, ['', $protocol]) && ($network == Protocol::DIASPORA) && ($platform !== 'diaspora')) {
			$networkname .= ' (Diaspora)';
		}

		return $networkname;
	}

	/**
	 * Fetch the platform SVG of a given system
	 * @see https://codeberg.org/FediverseIconography/pages
	 * @see https://github.com/simple-icons/simple-icons
	 * @see https://icon-sets.iconify.design
	 *
	 * @param string $network
	 * @param integer|null $gsid
	 * @param string $platform
	 * @param integer $uid
	 * @return string
	 */
	public static function networkToSVG(string $network, int $gsid = null, string $platform = '', int $uid = 0): string
	{
		$platform_icon_style = $uid ? (DI::pConfig()->get($uid, 'accessibility', 'platform_icon_style') ?? self::SVG_COLOR_BLACK) : self::SVG_COLOR_BLACK;

		if ($platform_icon_style == self::SVG_DISABLED) {
			return '';
		}

		$nets = [
			Protocol::ACTIVITYPUB => 'activitypub', // https://commons.wikimedia.org/wiki/File:ActivityPub-logo-symbol.svg
			Protocol::BLUESKY     => 'bluesky', // https://commons.wikimedia.org/wiki/File:Bluesky_Logo.svg
			Protocol::DFRN        => 'friendica',
			Protocol::DIASPORA    => 'diaspora', // https://www.svgrepo.com/svg/362315/diaspora
			Protocol::DIASPORA2   => 'diaspora', // https://www.svgrepo.com/svg/362315/diaspora
			Protocol::DISCOURSE   => 'discourse', // https://commons.wikimedia.org/wiki/File:Discourse_icon.svg
			Protocol::FEED        => 'rss', // https://commons.wikimedia.org/wiki/File:Generic_Feed-icon.svg
			Protocol::MAIL        => 'email', // https://www.svgrepo.com/svg/501173/email
			Protocol::OSTATUS     => '',
			Protocol::PNUT        => '',
			Protocol::PUMPIO      => 'pump-io', // https://commons.wikimedia.org/wiki/File:Pump.io_Logo.svg
			Protocol::STATUSNET   => '',
			Protocol::TUMBLR      => 'tumblr', // https://commons.wikimedia.org/wiki/File:Tumblr.svg
			Protocol::TWITTER     => '',
			Protocol::ZOT         => 'hubzilla', // https://www.svgrepo.com/svg/362219/hubzilla
		];

		$search  = array_keys($nets);
		$replace = array_values($nets);

		$network_svg = str_replace($search, $replace, $network);

		if (in_array($network, Protocol::FEDERATED) && !empty($gsid)) {
			$gserver  = self::getServerForId($gsid);
			$platform = $gserver['platform'];
		}

		$svg = ['aardwolf', 'activitypods', 'activitypub', 'akkoma', 'anfora', 'awakari', 'azorius',
			'bluesky', 'bonfire', 'bookwyrm', 'bridgy_fed', 'brighteon_social', 'brutalinks', 'calckey',
			'castopod', 'catodon', 'chatter_net', 'chuckya', 'clubsall', 'communecter', 'decodon',
			'diaspora', 'discourse', 'dolphin', 'drupal', 'email', 'emissary', 'epicyon', 'f2ap',
			'fedibird', 'fedify', 'firefish', 'flipboard', 'flohmarkt', 'forgefriends', 'forgejo',
			'forte', 'foundkey', 'friendica', 'funkwhale', 'gancio', 'gath.io', 'ghost', 'gitlab',
			'glitch-soc', 'glitchsoc', 'gnu_social', 'gnusocial', 'goblin', 'go-fed', 'gotosocial',
			'greatape', 'guppe', 'hollo', 'hometown', 'honk', 'hubzilla', 'iceshrimp', 'juick', 'kazarma',
			'kbin', 'kepi', 'kitsune', 'kmyblue', 'kookie', 'ktistec', 'lemmy', 'loops', 'mastodon',
			'mbin', 'micro.blog', 'minds', 'misskey', 'mistpark', 'mitra', 'mobilizon', 'neodb',
			'newsmast', 'nextcloud_social', 'nodebb', 'osada', 'owncast', 'peertube', 'piefed', 'pinetta',
			'pixelfed', 'pleroma', 'plume', 'postmarks', 'prismo', 'pump-io', 'rebased', 'redmatrix',
			'reel2bits', 'rss', 'ruffy', 'sakura', 'seppo', 'shadowfacts', 'sharky', 'shuttlecraft',
			'smilodon', 'smithereen', 'snac', 'soapbox', 'socialhome', 'streams', 'sublinks', 'sutty',
			'takahē', 'takesama', 'threads', 'tumblr', 'vernissage', 'vervis', 'vidzy', 'vocata', 'wafrn',
			'wildebeest', 'wordpress', 'write.as', 'writefreely', 'wxwclub', 'xwiki', 'zap'];

		if (in_array($platform_icon_style, [self::SVG_WHITE, self::SVG_COLOR_WHITE])) {
			$svg = ['activitypub', 'akkoma', 'andstatus', 'bluesky', 'bonfire', 'bookwyrm', 'bridgy_fed',
				'calckey', 'castopod', 'diaspora', 'discourse', 'dolphin', 'drupal', 'email', 'firefish',
				'flipboard', 'flohmarkt', 'forgejo', 'friendica', 'funkwhale', 'ghost', 'gitlab',
				'glitch-soc', 'gnusocial', 'gotosocial', 'guppe', 'hollo', 'hubzilla', 'iceshrimp', 'kbin',
				'lemmy', 'loforo', 'loops', 'mastodon', 'mbin', 'microblog', 'minds', 'misskey', 'mobilizon',
				'nextcloud', 'owncast', 'peertube', 'phanpy', 'pixelfed', 'pleroma', 'plume', 'rss', 'shark',
				'soapbox', 'socialhome', 'streams', 'takahē', 'threads', 'tumblr', 'wordpress', 'write.as',
				'writefreely', 'xwiki', 'zap'];
		}

		if (!empty($platform)) {
			$aliases = [
				'brighteon'               => 'brighteon_social',
				'bridgy-fed'              => 'bridgy_fed',
				'friendika'               => 'friendica',
				'gathio'                  => 'gath.io',
				'GNU Social'              => 'gnu_social',
				'gnusocial'               => 'gnu_social',
				'guppe groups'            => 'guppe',
				'microblog'               => 'micro.blog',
				'microblogpub'            => 'micro.blog',
				'nextcloud'               => 'nextcloud_social',
				'red'                     => 'redmatrix',
				'sharkey'                 => 'sharky',
				'sutty-distributed-press' => 'sutty',
			];

			$platform    = str_replace(array_keys($aliases), array_values($aliases), $platform);
			$network_svg = in_array($platform, $svg) ? $platform : $network_svg;
		}

		if (empty($network_svg)) {
			return '';
		}

		$color = ['aardwolf', 'activitypods', 'activitypub', 'akkoma', 'bluesky', 'chuckya', 'decodon',
			'discourse', 'fedify', 'firefish', 'flipboard', 'friendica', 'gitlab', 'gnusocial', 'kookie',
			'loops', 'mastodon', 'mbin', 'misskey', 'neodb', 'newsmast', 'nodebb', 'peertube', 'pixelfed',
			'pleroma', 'rss', 'sharky', 'tumblr', 'vervis', 'vocata', 'wordpress'];

		if (in_array($platform_icon_style, [self::SVG_COLOR_BLACK, self::SVG_COLOR_WHITE]) && in_array($network_svg, $color)) {
			return 'images/platforms/color/' . $network_svg . '.svg';
		} elseif (in_array($platform_icon_style, [self::SVG_WHITE, self::SVG_COLOR_WHITE])) {
			return 'images/platforms/white/' . $network_svg . '.svg';
		} else {
			return 'images/platforms/black/' . $network_svg . '.svg';
		}
	}
}
