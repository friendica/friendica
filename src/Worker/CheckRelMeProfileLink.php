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

namespace Friendica\Worker;

use DOMDocument;
use Friendica\Content\Text\HTML;
use Friendica\Core\Logger;
use Friendica\DI;
use Friendica\Model\Profile;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientOptions;
use GuzzleHttp\Psr7\Uri;

/* This class is used to verify the homepage link of a user profile.
 * To do so, we look for rel="me" links in the given homepage, if one
 * of them points to the Friendica profile of the user, a verification
 * mark is added to the link.
 *
 * To reverse the process, if a homepage link is given, it is displayed
 * with the rel="me" attribute as well, so that 3rd party tools can
 * verify the connection between the two pages.
 *
 * This task will be performed by the worker on a daily basis _and_ every
 * time the user changes their homepage link. In the first case the priority
 * of the task is set to LOW, with the second case it is MEDIUM.
 *
 * rel-me microformat docs https://microformats.org/wiki/rel-me
 */
class CheckRelMeProfileLink
{
	/* Cheks the homepage of a profile for a rel-me link back to the user profile
	 *
	 * @param $uid (int) the UID of the user
	 */
	public static function execute(int $uid)
	{
		Logger::notice('Verifying the homepage', ['uid' => $uid]);
		Profile::update(['homepage_verified' => false], $uid);

		$owner = User::getOwnerDataById($uid);
		if (empty($owner['homepage'])) {
			Logger::notice('The user has no homepage link.', ['uid' => $uid]);
			return;
		}

		$xrd_timeout = DI::config()->get('system', 'xrd_timeout');
		$curlResult  = DI::httpClient()->get($owner['homepage'], HttpClientAccept::HTML, [HttpClientOptions::TIMEOUT => $xrd_timeout]);
		if (!$curlResult->isSuccess()) {
			Logger::notice('Could not cURL the homepage URL', ['owner homepage' => $owner['homepage']]);
			return;
		}

		$content = $curlResult->getBody();
		if (!$content) {
			Logger::notice('Empty body of the fetched homepage link). Cannot verify the relation to profile of UID %s.', ['uid' => $uid, 'owner homepage' => $owner['homepage']]);
			return;
		}

		$doc = new DOMDocument();
		if (!@$doc->loadHTML($content)) {
			Logger::notice('Could not parse the content');
			return;
		}

		if (HTML::checkRelMeLink($doc, new Uri($owner['url']))) {
			Profile::update(['homepage_verified' => true], $uid);
			Logger::notice('Homepage URL verified', ['uid' => $uid, 'owner homepage' => $owner['homepage']]);
		} else {
			Logger::notice('Homepage URL could not be verified', ['uid' => $uid, 'owner homepage' => $owner['homepage']]);
		}
	}
}
