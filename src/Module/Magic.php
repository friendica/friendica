<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
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

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\User;
use Friendica\Network\HTTPClientOptions;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Strings;

/**
 * Magic Auth (remote authentication) module.
 *
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Module/Magic.php
 */
class Magic extends BaseModule
{
	public static function init(array $parameters = [])
	{
		$a = DI::app();
		$ret = ['success' => false, 'url' => '', 'message' => ''];
		Logger::info('magic mdule: invoked');

		Logger::debug('args', ['request' => $_REQUEST]);

		$addr = $_REQUEST['addr'] ?? '';
		$dest = $_REQUEST['dest'] ?? '';
		$test = (!empty($_REQUEST['test']) ? intval($_REQUEST['test']) : 0);
		$owa  = (!empty($_REQUEST['owa'])  ? intval($_REQUEST['owa'])  : 0);
		$cid  = 0;

		if (!empty($addr)) {
			$cid = Contact::getIdForURL($addr);
		} elseif (!empty($dest)) {
			$cid = Contact::getIdForURL($dest);
		}

		if (!$cid) {
			Logger::info('No contact record found', $_REQUEST);
			// @TODO Finding a more elegant possibility to redirect to either internal or external URL
			$a->redirect($dest);
		}
		$contact = DBA::selectFirst('contact', ['id', 'nurl', 'url'], ['id' => $cid]);

		// Redirect if the contact is already authenticated on this site.
		if ($a->getContactId() && strpos($contact['nurl'], Strings::normaliseLink(DI::baseUrl()->get())) !== false) {
			if ($test) {
				$ret['success'] = true;
				$ret['message'] .= 'Local site - you are already authenticated.' . EOL;
				return $ret;
			}

			Logger::info('Contact is already authenticated');
			System::externalRedirect($dest);
		}

		// OpenWebAuth
		if (local_user() && $owa) {
			$user = User::getById(local_user());

			// Extract the basepath
			// NOTE: we need another solution because this does only work
			// for friendica contacts :-/ . We should have the basepath
			// of a contact also in the contact table.
			$exp = explode('/profile/', $contact['url']);
			$basepath = $exp[0];

			$header = [
				'Accept'		  => ['application/x-dfrn+json', 'application/x-zot+json'],
				'X-Open-Web-Auth' => [Strings::getRandomHex()],
			];

			// Create a header that is signed with the local users private key.
			$header = HTTPSignature::createSig(
				$header,
				$user['prvkey'],
				'acct:' . $user['nickname'] . '@' . DI::baseUrl()->getHostname() . (DI::baseUrl()->getUrlPath() ? '/' . DI::baseUrl()->getUrlPath() : '')
			);

			// Try to get an authentication token from the other instance.
			$curlResult = DI::httpClient()->get($basepath . '/owa', [HTTPClientOptions::HEADERS => $header]);

			if ($curlResult->isSuccess()) {
				$j = json_decode($curlResult->getBody(), true);

				if ($j['success']) {
					$token = '';
					if ($j['encrypted_token']) {
						// The token is encrypted. If the local user is really the one the other instance
						// thinks he/she is, the token can be decrypted with the local users public key.
						openssl_private_decrypt(Strings::base64UrlDecode($j['encrypted_token']), $token, $user['prvkey']);
					} else {
						$token = $j['token'];
					}
					$args = (strpbrk($dest, '?&') ? '&' : '?') . 'owt=' . $token;

					Logger::info('Redirecting', ['path' => $dest . $args]);
					System::externalRedirect($dest . $args);
				}
			}
			System::externalRedirect($dest);
		}

		if ($test) {
			$ret['message'] = 'Not authenticated or invalid arguments' . EOL;
			return $ret;
		}

		// @TODO Finding a more elegant possibility to redirect to either internal or external URL
		$a->redirect($dest);
	}
}
