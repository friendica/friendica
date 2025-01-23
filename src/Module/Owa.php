<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\BaseModule;
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
								$this->logger->debug('OWA header', ['addr' => $contact['addr'], 'data' => $verified]);

								$ret['success'] = true;
								$token          = Strings::getRandomHex(32);

								// Store the generated token in the database.
								OpenWebAuthToken::create('owt', 0, $token, $contact['addr']);

								$result = '';

								// Encrypt the token with the public contacts public key.
								// Only the specific public contact will be able to encrypt it.
								// At a later time, we will compare weather the token we're getting
								// is really the same token we have stored in the database.
								openssl_public_encrypt($token, $result, $contact['pubkey']);
								$ret['encrypted_token'] = Strings::base64UrlEncode($result);
							} else {
								$this->logger->info('OWA fail', ['id' => $contact['id'], 'addr' => $contact['addr'], 'url' => $contact['url']]);
							}
						} else {
							$this->logger->info('Contact not found', ['handle' => $handle]);
						}
					}
				}
			}
		}
		$this->jsonExit($ret, 'application/x-zot+json');
	}
}
