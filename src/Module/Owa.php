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

use Friendica\BaseModule;
use Friendica\Core\Logger;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\OpenWebAuthToken;
use Friendica\Util\HTTPSignature;
use Friendica\Util\Strings;

/**
 * OpenWebAuth verifier and token generator
 *
 * See https://macgirvin.com/wiki/mike/OpenWebAuth/Home
 * Requests to this endpoint should be signed using HTTP Signatures
 * using the 'Authorization: Signature' authentication method
 * If the signature verifies a token is returned.
 *
 * This token may be exchanged for an authenticated cookie.
 *
 * Ported from Hubzilla: https://framagit.org/hubzilla/core/blob/master/Zotlabs/Module/Owa.php
 */
class Owa extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		$ret = [ 'success' => false ];

		foreach (['REDIRECT_REMOTE_USER', 'HTTP_AUTHORIZATION'] as $head) {
			if (array_key_exists($head, $_SERVER) && substr(trim($_SERVER[$head]), 0, 9) === 'Signature') {
				if ($head !== 'HTTP_AUTHORIZATION') {
					$_SERVER['HTTP_AUTHORIZATION'] = $_SERVER[$head];
					continue;
				}

				$sigblock = HTTPSignature::parseSigheader($_SERVER[$head]);
				if ($sigblock) {
					$keyId = $sigblock['keyId'];

					if ($keyId) {
						// Try to find the public contact entry of the handle.
						$handle = str_replace('acct:', '', $keyId);

						$cid       = Contact::getIdForURL($handle);
						$fields    = ['id', 'url', 'addr', 'pubkey'];
						$condition = ['id' => $cid];

						$contact = DBA::selectFirst('contact', $fields, $condition);

						if (DBA::isResult($contact)) {
							// Try to verify the signed header with the public key of the contact record
							// we have found.
							$verified = HTTPSignature::verifyMagic($contact['pubkey']);

							if ($verified && $verified['header_signed'] && $verified['header_valid']) {
								Logger::debug('OWA header', ['addr' => $contact['addr'], 'data' => $verified]);

								$ret['success'] = true;
								$token = Strings::getRandomHex(32);

								// Store the generated token in the databe.
								OpenWebAuthToken::create('owt', 0, $token, $contact['addr']);

								$result = '';

								// Encrypt the token with the public contacts publik key.
								// Only the specific public contact will be able to encrypt it.
								// At a later time, we will compare weather the token we're getting
								// is really the same token we have stored in the database.
								openssl_public_encrypt($token, $result, $contact['pubkey']);
								$ret['encrypted_token'] = Strings::base64UrlEncode($result);
							} else {
								Logger::info('OWA fail', ['id' => $contact['id'], 'addr' => $contact['addr'], 'url' => $contact['url']]);
							}
						} else {
							Logger::info('Contact not found', ['handle' => $handle]);
						}
					}
				}
			}
		}
		System::jsonExit($ret, 'application/x-zot+json');
	}
}
