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

use Friendica\App;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\GServer;
use Friendica\Model\Post;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\OStatus;
use Friendica\Protocol\Salmon;
use Friendica\Util\Crypto;
use Friendica\Util\Network;
use Friendica\Util\Strings;

function salmon_post(App $a, $xml = '') {

	if (empty($xml)) {
		$xml = Network::postdata();
	}

	Logger::debug('new salmon ' . $xml);

	$nick = trim(DI::args()->getArgv()[1] ?? '');

	$importer = DBA::selectFirst('user', [], ['nickname' => $nick, 'account_expired' => false, 'account_removed' => false]);
	if (! DBA::isResult($importer)) {
		throw new \Friendica\Network\HTTPException\InternalServerErrorException();
	}

	// parse the xml

	$dom = simplexml_load_string($xml,'SimpleXMLElement',0, ActivityNamespace::SALMON_ME);

	$base = null;

	// figure out where in the DOM tree our data is hiding
	if (!empty($dom->provenance->data))
		$base = $dom->provenance;
	elseif (!empty($dom->env->data))
		$base = $dom->env;
	elseif (!empty($dom->data))
		$base = $dom;

	if (empty($base)) {
		Logger::notice('unable to locate salmon data in xml');
		throw new \Friendica\Network\HTTPException\BadRequestException();
	}

	// Stash the signature away for now. We have to find their key or it won't be good for anything.


	$signature = Strings::base64UrlDecode($base->sig);

	// unpack the  data

	// strip whitespace so our data element will return to one big base64 blob
	$data = str_replace([" ","\t","\r","\n"],["","","",""],$base->data);

	// stash away some other stuff for later

	$type = $base->data[0]->attributes()->type[0];
	$keyhash = $base->sig[0]->attributes()->keyhash[0] ?? '';
	$encoding = $base->encoding;
	$alg = $base->alg;

	// Salmon magic signatures have evolved and there is no way of knowing ahead of time which
	// flavour we have. We'll try and verify it regardless.

	$stnet_signed_data = $data;

	$signed_data = $data  . '.' . Strings::base64UrlEncode($type) . '.' . Strings::base64UrlEncode($encoding) . '.' . Strings::base64UrlEncode($alg);

	$compliant_format = str_replace('=', '', $signed_data);


	// decode the data
	$data = Strings::base64UrlDecode($data);

	$author = OStatus::salmonAuthor($data, $importer);
	$author_link = $author["author-link"];

	if(! $author_link) {
		Logger::notice('Could not retrieve author URI.');
		throw new \Friendica\Network\HTTPException\BadRequestException();
	}

	// Once we have the author URI, go to the web and try to find their public key

	Logger::notice('Fetching key for ' . $author_link);

	$key = Salmon::getKey($author_link, $keyhash);

	if(! $key) {
		Logger::notice('Could not retrieve author key.');
		throw new \Friendica\Network\HTTPException\BadRequestException();
	}

	$key_info = explode('.',$key);

	$m = Strings::base64UrlDecode($key_info[1]);
	$e = Strings::base64UrlDecode($key_info[2]);

	Logger::info('key details', ['info' => $key_info]);

	$pubkey = Crypto::meToPem($m, $e);

	// We should have everything we need now. Let's see if it verifies.

	// Try GNU Social format
	$verify = Crypto::rsaVerify($signed_data, $signature, $pubkey);
	$mode = 1;

	if (! $verify) {
		Logger::notice('message did not verify using protocol. Trying compliant format.');
		$verify = Crypto::rsaVerify($compliant_format, $signature, $pubkey);
		$mode = 2;
	}

	if (! $verify) {
		Logger::notice('message did not verify using padding. Trying old statusnet format.');
		$verify = Crypto::rsaVerify($stnet_signed_data, $signature, $pubkey);
		$mode = 3;
	}

	if (! $verify) {
		Logger::notice('Message did not verify. Discarding.');
		throw new \Friendica\Network\HTTPException\BadRequestException();
	}

	Logger::notice('Message verified with mode '.$mode);


	/*
	*
	* If we reached this point, the message is good. Now let's figure out if the author is allowed to send us stuff.
	*
	*/

	$contact = DBA::selectFirst('contact', [], ["`network` IN (?, ?) AND (`nurl` = ? OR `alias` = ? OR `alias` = ?) AND `uid` = ?",
		Protocol::OSTATUS, Protocol::DFRN, Strings::normaliseLink($author_link), $author_link, Strings::normaliseLink($author_link), $importer['uid']]);

	if (!empty($contact['gsid'])) {
		GServer::setProtocol($contact['gsid'], Post\DeliveryData::OSTATUS);
	}

	// Have we ignored the person?
	// If so we can not accept this post.

	if (!empty($contact['blocked'])) {
		Logger::notice('Ignoring this author.');
		throw new \Friendica\Network\HTTPException\AcceptedException();
	}

	// Placeholder for hub discovery.
	$hub = '';

	$contact = $contact ?: [];

	OStatus::import($data, $importer, $contact, $hub);

	throw new \Friendica\Network\HTTPException\OKException();
}
