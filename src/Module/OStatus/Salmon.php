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

namespace Friendica\Module\OStatus;

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Database\Database;
use Friendica\Model\GServer;
use Friendica\Model\Post;
use Friendica\Module\Response;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\OStatus;
use Friendica\Util\Crypto;
use Friendica\Util\Network;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Salmon as SalmonProtocol;
use Friendica\Util\Profiler;
use Friendica\Util\Strings;
use Psr\Log\LoggerInterface;

/**
 * Technical endpoint for the Salmon protocol
 */
class Salmon extends \Friendica\BaseModule
{
	/** @var Database */
	private $database;

	public function __construct(Database $database, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->database = $database;
	}

	/**
	 * @param array $request
	 * @return void
	 * @throws HTTPException\AcceptedException
	 * @throws HTTPException\BadRequestException
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\OKException
	 * @throws \ImagickException
	 */
	protected function post(array $request = [])
	{
		$xml = Network::postdata();
		$this->logger->debug('Got request data.', ['request' => $request]);

		$nickname = $this->parameters['nickname'] ?? '';
		if (empty($nickname)) {
			throw new HTTPException\BadRequestException('nickname parameter is mandatory');
		}

		$this->logger->debug('New Salmon', ['nickname' => $nickname, 'xml' => $xml]);

		$importer = $this->database->selectFirst('user', [], ['nickname' => $nickname, 'account_expired' => false, 'account_removed' => false]);
		if (!$this->database->isResult($importer)) {
			throw new HTTPException\InternalServerErrorException();
		}

		// parse the xml
		$dom = simplexml_load_string($xml, 'SimpleXMLElement', 0, ActivityNamespace::SALMON_ME);

		$base = null;

		// figure out where in the DOM tree our data is hiding
		if (!empty($dom->provenance->data)) {
			$base = $dom->provenance;
		} elseif (!empty($dom->env->data)) {
			$base = $dom->env;
		} elseif (!empty($dom->data)) {
			$base = $dom;
		}

		if (empty($base)) {
			$this->logger->notice('unable to locate salmon data in xml');
			throw new HTTPException\BadRequestException();
		}

		// Stash the signature away for now. We have to find their key or it won't be good for anything.
		$signature = Strings::base64UrlDecode($base->sig);

		// unpack the  data

		// strip whitespace so our data element will return to one big base64 blob
		$data = str_replace([" ", "\t", "\r", "\n"], ["", "", "", ""], $base->data);

		// stash away some other stuff for later

		$type     = $base->data[0]->attributes()->type[0];
		$keyhash  = $base->sig[0]->attributes()->keyhash[0] ?? '';
		$encoding = $base->encoding;
		$alg      = $base->alg;

		// Salmon magic signatures have evolved and there is no way of knowing ahead of time which
		// flavour we have. We'll try and verify it regardless.

		$stnet_signed_data = $data;

		$signed_data = $data . '.' . Strings::base64UrlEncode($type) . '.' . Strings::base64UrlEncode($encoding) . '.' . Strings::base64UrlEncode($alg);

		$compliant_format = str_replace('=', '', $signed_data);


		// decode the data
		$data = Strings::base64UrlDecode($data);

		$author      = OStatus::salmonAuthor($data, $importer);
		$author_link = $author["author-link"];
		if (!$author_link) {
			$this->logger->notice('Could not retrieve author URI.');
			throw new HTTPException\BadRequestException();
		}

		// Once we have the author URI, go to the web and try to find their public key

		$this->logger->notice('Fetching key for ' . $author_link);

		$key = SalmonProtocol::getKey($author_link, $keyhash);

		if (!$key) {
			$this->logger->notice('Could not retrieve author key.');
			throw new HTTPException\BadRequestException();
		}

		$this->logger->info('Key details', ['info' => $key]);

		$pubkey = SalmonProtocol::magicKeyToPem($key);

		// We should have everything we need now. Let's see if it verifies.

		// Try GNU Social format
		$verify = Crypto::rsaVerify($signed_data, $signature, $pubkey);
		$mode   = 1;

		if (!$verify) {
			$this->logger->notice('Message did not verify using protocol. Trying compliant format.');
			$verify = Crypto::rsaVerify($compliant_format, $signature, $pubkey);
			$mode   = 2;
		}

		if (!$verify) {
			$this->logger->notice('Message did not verify using padding. Trying old statusnet format.');
			$verify = Crypto::rsaVerify($stnet_signed_data, $signature, $pubkey);
			$mode   = 3;
		}

		if (!$verify) {
			$this->logger->notice('Message did not verify. Discarding.');
			throw new HTTPException\BadRequestException();
		}

		$this->logger->notice('Message verified with mode ' . $mode);


		/*
		*
		* If we reached this point, the message is good. Now let's figure out if the author is allowed to send us stuff.
		*
		*/

		$contact = $this->database->selectFirst(
			'contact',
			[],
			[
				"`network` IN (?, ?)
		        AND (`nurl` = ? OR `alias` = ? OR `alias` = ?)
		        AND `uid` = ?",
				Protocol::OSTATUS, Protocol::DFRN,
				Strings::normaliseLink($author_link), $author_link, Strings::normaliseLink($author_link),
				$importer['uid']
			]
		);

		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::OSTATUS);
		}

		// Have we ignored the person?
		// If so we can not accept this post.

		if (!empty($contact['blocked'])) {
			$this->logger->notice('Ignoring this author.');
			throw new HTTPException\AcceptedException();
		}

		// Placeholder for hub discovery.
		$hub = '';

		$contact = $contact ?: [];

		OStatus::import($data, $importer, $contact, $hub);

		throw new HTTPException\OKException();
	}
}
