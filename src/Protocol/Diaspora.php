<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Protocol;

use Friendica\Content\Feature;
use Friendica\Content\PageInfo;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Cache\Duration;
use Friendica\Core\Logger;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\FContact;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Mail;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\Probe;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;
use Friendica\Util\Network;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use Friendica\Worker\Delivery;
use SimpleXMLElement;

/**
 * This class contain functions to create and send Diaspora XML files
 */
class Diaspora
{
	/**
	 * Return a list of participating contacts for a thread
	 *
	 * This is used for the participation feature.
	 * One of the parameters is a contact array.
	 * This is done to avoid duplicates.
	 *
	 * @param array $item     Item that is about to be delivered
	 * @param array $contacts The previously fetched contacts
	 *
	 * @return array of relay servers
	 * @throws \Exception
	 */
	public static function participantsForThread(array $item, array $contacts)
	{
		if (!in_array($item['private'], [Item::PUBLIC, Item::UNLISTED]) || in_array($item["verb"], [Activity::FOLLOW, Activity::TAG])) {
			Logger::info('Item is private or a participation request. It will not be relayed', ['guid' => $item['guid'], 'private' => $item['private'], 'verb' => $item['verb']]);
			return $contacts;
		}

		$items = Item::select(['author-id', 'author-link', 'parent-author-link', 'parent-guid', 'guid'],
			['parent' => $item['parent'], 'gravity' => [GRAVITY_COMMENT, GRAVITY_ACTIVITY]]);
		while ($item = DBA::fetch($items)) {
			$contact = DBA::selectFirst('contact', ['id', 'url', 'name', 'protocol', 'batch', 'network'],
				['id' => $item['author-id']]);
			if (!DBA::isResult($contact) || empty($contact['batch']) ||
				($contact['network'] != Protocol::DIASPORA) ||
				Strings::compareLink($item['parent-author-link'], $item['author-link'])) {
				continue;
			}

			$exists = false;
			foreach ($contacts as $entry) {
				if ($entry['batch'] == $contact['batch']) {
					$exists = true;
				}
			}

			if (!$exists) {
				Logger::info('Add participant to receiver list', ['parent' => $item['parent-guid'], 'item' => $item['guid'], 'participant' => $contact['url']]);
				$contacts[] = $contact;
			}
		}
		DBA::close($items);

		return $contacts;
	}

	/**
	 * verify the envelope and return the verified data
	 *
	 * @param string $envelope The magic envelope
	 *
	 * @return string verified data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function verifyMagicEnvelope($envelope)
	{
		$basedom = XML::parseString($envelope, true);

		if (!is_object($basedom)) {
			Logger::log("Envelope is no XML file");
			return false;
		}

		$children = $basedom->children('http://salmon-protocol.org/ns/magic-env');

		if (sizeof($children) == 0) {
			Logger::log("XML has no children");
			return false;
		}

		$handle = "";

		$data = Strings::base64UrlDecode($children->data);
		$type = $children->data->attributes()->type[0];

		$encoding = $children->encoding;

		$alg = $children->alg;

		$sig = Strings::base64UrlDecode($children->sig);
		$key_id = $children->sig->attributes()->key_id[0];
		if ($key_id != "") {
			$handle = Strings::base64UrlDecode($key_id);
		}

		$b64url_data = Strings::base64UrlEncode($data);
		$msg = str_replace(["\n", "\r", " ", "\t"], ["", "", "", ""], $b64url_data);

		$signable_data = $msg.".".Strings::base64UrlEncode($type).".".Strings::base64UrlEncode($encoding).".".Strings::base64UrlEncode($alg);

		if ($handle == '') {
			Logger::log('No author could be decoded. Discarding. Message: ' . $envelope);
			return false;
		}

		$key = self::key($handle);
		if ($key == '') {
			Logger::log("Couldn't get a key for handle " . $handle . ". Discarding.");
			return false;
		}

		$verify = Crypto::rsaVerify($signable_data, $sig, $key);
		if (!$verify) {
			Logger::log('Message from ' . $handle . ' did not verify. Discarding.');
			return false;
		}

		return $data;
	}

	/**
	 * encrypts data via AES
	 *
	 * @param string $key  The AES key
	 * @param string $iv   The IV (is used for CBC encoding)
	 * @param string $data The data that is to be encrypted
	 *
	 * @return string encrypted data
	 */
	private static function aesEncrypt($key, $iv, $data)
	{
		return openssl_encrypt($data, 'aes-256-cbc', str_pad($key, 32, "\0"), OPENSSL_RAW_DATA, str_pad($iv, 16, "\0"));
	}

	/**
	 * decrypts data via AES
	 *
	 * @param string $key       The AES key
	 * @param string $iv        The IV (is used for CBC encoding)
	 * @param string $encrypted The encrypted data
	 *
	 * @return string decrypted data
	 */
	private static function aesDecrypt($key, $iv, $encrypted)
	{
		return openssl_decrypt($encrypted, 'aes-256-cbc', str_pad($key, 32, "\0"), OPENSSL_RAW_DATA, str_pad($iv, 16, "\0"));
	}

	/**
	 * Decodes incoming Diaspora message in the new format
	 *
	 * @param string  $raw      raw post message
	 * @param string  $privKey   The private key of the importer
	 * @param boolean $no_exit  Don't do an http exit on error
	 *
	 * @return array
	 * 'message' -> decoded Diaspora XML message
	 * 'author' -> author diaspora handle
	 * 'key' -> author public key (converted to pkcs#8)
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function decodeRaw(string $raw, string $privKey = '', bool $no_exit = false)
	{
		$data = json_decode($raw);

		// Is it a private post? Then decrypt the outer Salmon
		if (is_object($data)) {
			$encrypted_aes_key_bundle = base64_decode($data->aes_key);
			$ciphertext = base64_decode($data->encrypted_magic_envelope);

			$outer_key_bundle = '';
			@openssl_private_decrypt($encrypted_aes_key_bundle, $outer_key_bundle, $privKey);
			$j_outer_key_bundle = json_decode($outer_key_bundle);

			if (!is_object($j_outer_key_bundle)) {
				Logger::log('Outer Salmon did not verify. Discarding.');
				if ($no_exit) {
					return false;
				} else {
					throw new \Friendica\Network\HTTPException\BadRequestException();
				}
			}

			$outer_iv = base64_decode($j_outer_key_bundle->iv);
			$outer_key = base64_decode($j_outer_key_bundle->key);

			$xml = self::aesDecrypt($outer_key, $outer_iv, $ciphertext);
		} else {
			$xml = $raw;
		}

		$basedom = XML::parseString($xml, true);

		if (!is_object($basedom)) {
			Logger::log('Received data does not seem to be an XML. Discarding. '.$xml);
			if ($no_exit) {
				return false;
			} else {
				throw new \Friendica\Network\HTTPException\BadRequestException();
			}
		}

		$base = $basedom->children(ActivityNamespace::SALMON_ME);

		// Not sure if this cleaning is needed
		$data = str_replace([" ", "\t", "\r", "\n"], ["", "", "", ""], $base->data);

		// Build the signed data
		$type = $base->data[0]->attributes()->type[0];
		$encoding = $base->encoding;
		$alg = $base->alg;
		$signed_data = $data.'.'.Strings::base64UrlEncode($type).'.'.Strings::base64UrlEncode($encoding).'.'.Strings::base64UrlEncode($alg);

		// This is the signature
		$signature = Strings::base64UrlDecode($base->sig);

		// Get the senders' public key
		$key_id = $base->sig[0]->attributes()->key_id[0];
		$author_addr = base64_decode($key_id);
		if ($author_addr == '') {
			Logger::log('No author could be decoded. Discarding. Message: ' . $xml);
			if ($no_exit) {
				return false;
			} else {
				throw new \Friendica\Network\HTTPException\BadRequestException();
			}
		}

		$key = self::key($author_addr);
		if ($key == '') {
			Logger::log("Couldn't get a key for handle " . $author_addr . ". Discarding.");
			if ($no_exit) {
				return false;
			} else {
				throw new \Friendica\Network\HTTPException\BadRequestException();
			}
		}

		$verify = Crypto::rsaVerify($signed_data, $signature, $key);
		if (!$verify) {
			Logger::log('Message did not verify. Discarding.');
			if ($no_exit) {
				return false;
			} else {
				throw new \Friendica\Network\HTTPException\BadRequestException();
			}
		}

		return ['message' => (string)Strings::base64UrlDecode($base->data),
				'author' => XML::unescape($author_addr),
				'key' => (string)$key];
	}

	/**
	 * Decodes incoming Diaspora message in the deprecated format
	 *
	 * @param string $xml      urldecoded Diaspora salmon
	 * @param string $privKey  The private key of the importer
	 *
	 * @return array
	 * 'message' -> decoded Diaspora XML message
	 * 'author' -> author diaspora handle
	 * 'key' -> author public key (converted to pkcs#8)
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function decode(string $xml, string $privKey = '')
	{
		$public = false;
		$basedom = XML::parseString($xml);

		if (!is_object($basedom)) {
			Logger::notice('XML is not parseable.');
			return false;
		}
		$children = $basedom->children('https://joindiaspora.com/protocol');

		$inner_aes_key = null;
		$inner_iv = null;

		if ($children->header) {
			$public = true;
			$author_link = str_replace('acct:', '', $children->header->author_id);
		} else {
			// This happens with posts from a relais
			if (empty($privKey)) {
				Logger::info('This is no private post in the old format');
				return false;
			}

			$encrypted_header = json_decode(base64_decode($children->encrypted_header));

			$encrypted_aes_key_bundle = base64_decode($encrypted_header->aes_key);
			$ciphertext = base64_decode($encrypted_header->ciphertext);

			$outer_key_bundle = '';
			openssl_private_decrypt($encrypted_aes_key_bundle, $outer_key_bundle, $privKey);

			$j_outer_key_bundle = json_decode($outer_key_bundle);

			$outer_iv = base64_decode($j_outer_key_bundle->iv);
			$outer_key = base64_decode($j_outer_key_bundle->key);

			$decrypted = self::aesDecrypt($outer_key, $outer_iv, $ciphertext);

			Logger::info('decrypted', ['data' => $decrypted]);
			$idom = XML::parseString($decrypted);

			$inner_iv = base64_decode($idom->iv);
			$inner_aes_key = base64_decode($idom->aes_key);

			$author_link = str_replace('acct:', '', $idom->author_id);
		}

		$dom = $basedom->children(ActivityNamespace::SALMON_ME);

		// figure out where in the DOM tree our data is hiding

		$base = null;
		if ($dom->provenance->data) {
			$base = $dom->provenance;
		} elseif ($dom->env->data) {
			$base = $dom->env;
		} elseif ($dom->data) {
			$base = $dom;
		}

		if (!$base) {
			Logger::log('unable to locate salmon data in xml');
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}


		// Stash the signature away for now. We have to find their key or it won't be good for anything.
		$signature = Strings::base64UrlDecode($base->sig);

		// unpack the  data

		// strip whitespace so our data element will return to one big base64 blob
		$data = str_replace([" ", "\t", "\r", "\n"], ["", "", "", ""], $base->data);


		// stash away some other stuff for later

		$type = $base->data[0]->attributes()->type[0];
		$keyhash = $base->sig[0]->attributes()->keyhash[0];
		$encoding = $base->encoding;
		$alg = $base->alg;


		$signed_data = $data.'.'.Strings::base64UrlEncode($type).'.'.Strings::base64UrlEncode($encoding).'.'.Strings::base64UrlEncode($alg);


		// decode the data
		$data = Strings::base64UrlDecode($data);


		if ($public) {
			$inner_decrypted = $data;
		} else {
			// Decode the encrypted blob
			$inner_encrypted = base64_decode($data);
			$inner_decrypted = self::aesDecrypt($inner_aes_key, $inner_iv, $inner_encrypted);
		}

		if (!$author_link) {
			Logger::log('Could not retrieve author URI.');
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}
		// Once we have the author URI, go to the web and try to find their public key
		// (first this will look it up locally if it is in the fcontact cache)
		// This will also convert diaspora public key from pkcs#1 to pkcs#8

		Logger::log('Fetching key for '.$author_link);
		$key = self::key($author_link);

		if (!$key) {
			Logger::log('Could not retrieve author key.');
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		$verify = Crypto::rsaVerify($signed_data, $signature, $key);

		if (!$verify) {
			Logger::log('Message did not verify. Discarding.');
			throw new \Friendica\Network\HTTPException\BadRequestException();
		}

		Logger::log('Message verified.');

		return ['message' => (string)$inner_decrypted,
				'author' => XML::unescape($author_link),
				'key' => (string)$key];
	}


	/**
	 * Dispatches public messages and find the fitting receivers
	 *
	 * @param array $msg     The post that will be dispatched
	 * @param bool  $fetched The message had been fetched (default "false")
	 *
	 * @return int The message id of the generated message, "true" or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function dispatchPublic($msg, bool $fetched = false)
	{
		$enabled = intval(DI::config()->get("system", "diaspora_enabled"));
		if (!$enabled) {
			Logger::log("diaspora is disabled");
			return false;
		}

		if (!($fields = self::validPosting($msg))) {
			Logger::log("Invalid posting");
			return false;
		}

		$importer = ["uid" => 0, "page-flags" => User::PAGE_FLAGS_FREELOVE];
		$success = self::dispatch($importer, $msg, $fields, $fetched);

		return $success;
	}

	/**
	 * Dispatches the different message types to the different functions
	 *
	 * @param array            $importer Array of the importer user
	 * @param array            $msg      The post that will be dispatched
	 * @param SimpleXMLElement $fields   SimpleXML object that contains the message
	 * @param bool             $fetched  The message had been fetched (default "false")
	 *
	 * @return int The message id of the generated message, "true" or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function dispatch(array $importer, $msg, SimpleXMLElement $fields = null, bool $fetched = false)
	{
		// The sender is the handle of the contact that sent the message.
		// This will often be different with relayed messages (for example "like" and "comment")
		$sender = $msg["author"];

		// This is only needed for private postings since this is already done for public ones before
		if (is_null($fields)) {
			$private = true;
			if (!($fields = self::validPosting($msg))) {
				Logger::log("Invalid posting");
				return false;
			}
		} else {
			$private = false;
		}

		$type = $fields->getName();

		Logger::info('Received message', ['type' => $type, 'sender' => $sender, 'user' => $importer["uid"]]);

		switch ($type) {
			case "account_migration":
				if (!$private) {
					Logger::log('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveAccountMigration($importer, $fields);

			case "account_deletion":
				return self::receiveAccountDeletion($fields);

			case "comment":
				return self::receiveComment($importer, $sender, $fields, $msg["message"], $fetched);

			case "contact":
				if (!$private) {
					Logger::log('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveContactRequest($importer, $fields);

			case "conversation":
				if (!$private) {
					Logger::log('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveConversation($importer, $msg, $fields);

			case "like":
				return self::receiveLike($importer, $sender, $fields);

			case "message":
				if (!$private) {
					Logger::log('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveMessage($importer, $fields);

			case "participation":
				if (!$private) {
					Logger::log('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveParticipation($importer, $fields);

			case "photo": // Not implemented
				return self::receivePhoto($importer, $fields);

			case "poll_participation": // Not implemented
				return self::receivePollParticipation($importer, $fields);

			case "profile":
				if (!$private) {
					Logger::log('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveProfile($importer, $fields);

			case "reshare":
				return self::receiveReshare($importer, $fields, $msg["message"]);

			case "retraction":
				return self::receiveRetraction($importer, $sender, $fields);

			case "status_message":
				return self::receiveStatusMessage($importer, $fields, $msg["message"], $fetched);

			default:
				Logger::log("Unknown message type ".$type);
				return false;
		}
	}

	/**
	 * Checks if a posting is valid and fetches the data fields.
	 *
	 * This function does not only check the signature.
	 * It also does the conversion between the old and the new diaspora format.
	 *
	 * @param array $msg Array with the XML, the sender handle and the sender signature
	 *
	 * @return bool|SimpleXMLElement If the posting is valid then an array with an SimpleXML object is returned
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function validPosting($msg)
	{
		$data = XML::parseString($msg["message"]);

		if (!is_object($data)) {
			Logger::info('No valid XML', ['message' => $msg['message']]);
			return false;
		}

		// Is this the new or the old version?
		if ($data->getName() == "XML") {
			$oldXML = true;
			foreach ($data->post->children() as $child) {
				$element = $child;
			}
		} else {
			$oldXML = false;
			$element = $data;
		}

		$type = $element->getName();
		$orig_type = $type;

		Logger::log("Got message type ".$type.": ".$msg["message"], Logger::DATA);

		// All retractions are handled identically from now on.
		// In the new version there will only be "retraction".
		if (in_array($type, ["signed_retraction", "relayable_retraction"]))
			$type = "retraction";

		if ($type == "request") {
			$type = "contact";
		}

		$fields = new SimpleXMLElement("<".$type."/>");

		$signed_data = "";
		$author_signature = null;
		$parent_author_signature = null;

		foreach ($element->children() as $fieldname => $entry) {
			if ($oldXML) {
				// Translation for the old XML structure
				if ($fieldname == "diaspora_handle") {
					$fieldname = "author";
				}
				if ($fieldname == "participant_handles") {
					$fieldname = "participants";
				}
				if (in_array($type, ["like", "participation"])) {
					if ($fieldname == "target_type") {
						$fieldname = "parent_type";
					}
				}
				if ($fieldname == "sender_handle") {
					$fieldname = "author";
				}
				if ($fieldname == "recipient_handle") {
					$fieldname = "recipient";
				}
				if ($fieldname == "root_diaspora_id") {
					$fieldname = "root_author";
				}
				if ($type == "status_message") {
					if ($fieldname == "raw_message") {
						$fieldname = "text";
					}
				}
				if ($type == "retraction") {
					if ($fieldname == "post_guid") {
						$fieldname = "target_guid";
					}
					if ($fieldname == "type") {
						$fieldname = "target_type";
					}
				}
			}

			if (($fieldname == "author_signature") && ($entry != "")) {
				$author_signature = base64_decode($entry);
			} elseif (($fieldname == "parent_author_signature") && ($entry != "")) {
				$parent_author_signature = base64_decode($entry);
			} elseif (!in_array($fieldname, ["author_signature", "parent_author_signature", "target_author_signature"])) {
				if ($signed_data != "") {
					$signed_data .= ";";
				}

				$signed_data .= $entry;
			}
			if (!in_array($fieldname, ["parent_author_signature", "target_author_signature"])
				|| ($orig_type == "relayable_retraction")
			) {
				XML::copy($entry, $fields, $fieldname);
			}
		}

		// This is something that shouldn't happen at all.
		if (in_array($type, ["status_message", "reshare", "profile"])) {
			if ($msg["author"] != $fields->author) {
				Logger::log("Message handle is not the same as envelope sender. Quitting this message.");
				return false;
			}
		}

		// Only some message types have signatures. So we quit here for the other types.
		if (!in_array($type, ["comment", "like"])) {
			return $fields;
		}
		// No author_signature? This is a must, so we quit.
		if (!isset($author_signature)) {
			Logger::log("No author signature for type ".$type." - Message: ".$msg["message"], Logger::DEBUG);
			return false;
		}

		if (isset($parent_author_signature)) {
			$key = self::key($msg["author"]);
			if (empty($key)) {
				Logger::info('No key found for parent', ['author' => $msg["author"]]);
				return false;
			}

			if (!Crypto::rsaVerify($signed_data, $parent_author_signature, $key, "sha256")) {
				Logger::log("No valid parent author signature for parent author ".$msg["author"]. " in type ".$type." - signed data: ".$signed_data." - Message: ".$msg["message"]." - Signature ".$parent_author_signature, Logger::DEBUG);
				return false;
			}
		}

		$key = self::key($fields->author);
		if (empty($key)) {
			Logger::info('No key found', ['author' => $fields->author]);
			return false;
		}

		if (!Crypto::rsaVerify($signed_data, $author_signature, $key, "sha256")) {
			Logger::log("No valid author signature for author ".$fields->author. " in type ".$type." - signed data: ".$signed_data." - Message: ".$msg["message"]." - Signature ".$author_signature, Logger::DEBUG);
			return false;
		} else {
			return $fields;
		}
	}

	/**
	 * Fetches the public key for a given handle
	 *
	 * @param string $handle The handle
	 *
	 * @return string The public key
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function key($handle)
	{
		$handle = strval($handle);

		Logger::log("Fetching diaspora key for: ".$handle);

		$r = FContact::getByURL($handle);
		if ($r) {
			return $r["pubkey"];
		}

		return "";
	}

	/**
	 * get a handle (user@domain.tld) from a given contact id
	 *
	 * @param int $contact_id  The id in the contact table
	 * @param int $pcontact_id The id in the contact table (Used for the public contact)
	 *
	 * @return string the handle
	 * @throws \Exception
	 */
	private static function handleFromContact($contact_id, $pcontact_id = 0)
	{
		$handle = false;

		Logger::log("contact id is ".$contact_id." - pcontact id is ".$pcontact_id, Logger::DEBUG);

		if ($pcontact_id != 0) {
			$contact = DBA::selectFirst('contact', ['addr'], ['id' => $pcontact_id]);

			if (DBA::isResult($contact) && !empty($contact["addr"])) {
				return strtolower($contact["addr"]);
			}
		}

		$r = q(
			"SELECT `network`, `addr`, `self`, `url`, `nick` FROM `contact` WHERE `id` = %d",
			intval($contact_id)
		);

		if (DBA::isResult($r)) {
			$contact = $r[0];

			Logger::log("contact 'self' = ".$contact['self']." 'url' = ".$contact['url'], Logger::DEBUG);

			if ($contact['addr'] != "") {
				$handle = $contact['addr'];
			} else {
				$baseurl_start = strpos($contact['url'], '://') + 3;
				// allows installations in a subdirectory--not sure how Diaspora will handle
				$baseurl_length = strpos($contact['url'], '/profile') - $baseurl_start;
				$baseurl = substr($contact['url'], $baseurl_start, $baseurl_length);
				$handle = $contact['nick'].'@'.$baseurl;
			}
		}

		return strtolower($handle);
	}

	/**
	 * Get a contact id for a given handle
	 *
	 * @todo  Move to Friendica\Model\Contact
	 *
	 * @param int    $uid    The user id
	 * @param string $handle The handle in the format user@domain.tld
	 *
	 * @return array Contact data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function contactByHandle($uid, $handle)
	{
		return Contact::getByURL($handle, null, [], $uid);
	}

	/**
	 * Checks if the given contact url does support ActivityPub
	 *
	 * @param string  $url    profile url
	 * @param boolean $update true = always update, false = never update, null = update when not found or outdated
	 * @return boolean
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSupportedByContactUrl($url, $update = null)
	{
		return !empty(FContact::getByURL($url, $update));
	}

	/**
	 * Check if posting is allowed for this contact
	 *
	 * @param array $importer   Array of the importer user
	 * @param array $contact    The contact that is checked
	 * @param bool  $is_comment Is the check for a comment?
	 *
	 * @return bool is the contact allowed to post?
	 */
	private static function postAllow(array $importer, array $contact, $is_comment = false)
	{
		/*
		 * Perhaps we were already sharing with this person. Now they're sharing with us.
		 * That makes us friends.
		 * Normally this should have handled by getting a request - but this could get lost
		 */
		// It is deactivated by now, due to side effects. See issue https://github.com/friendica/friendica/pull/4033
		// It is not removed by now. Possibly the code is needed?
		//if (!$is_comment && $contact["rel"] == Contact::FOLLOWER && in_array($importer["page-flags"], array(User::PAGE_FLAGS_FREELOVE))) {
		//	DBA::update(
		//		'contact',
		//		array('rel' => Contact::FRIEND, 'writable' => true),
		//		array('id' => $contact["id"], 'uid' => $contact["uid"])
		//	);
		//
		//	$contact["rel"] = Contact::FRIEND;
		//	Logger::log("defining user ".$contact["nick"]." as friend");
		//}

		// Contact server is blocked
		if (Network::isUrlBlocked($contact['url'])) {
			return false;
			// We don't seem to like that person
		} elseif ($contact["blocked"]) {
			// Maybe blocked, don't accept.
			return false;
			// We are following this person?
		} elseif (($contact["rel"] == Contact::SHARING) || ($contact["rel"] == Contact::FRIEND)) {
			// Yes, then it is fine.
			return true;
			// Is it a post to a community?
		} elseif (($contact["rel"] == Contact::FOLLOWER) && in_array($importer["page-flags"], [User::PAGE_FLAGS_COMMUNITY, User::PAGE_FLAGS_PRVGROUP])) {
			// That's good
			return true;
			// Is the message a global user or a comment?
		} elseif (($importer["uid"] == 0) || $is_comment) {
			// Messages for the global users and comments are always accepted
			return true;
		}

		return false;
	}

	/**
	 * Fetches the contact id for a handle and checks if posting is allowed
	 *
	 * @param array  $importer   Array of the importer user
	 * @param string $handle     The checked handle in the format user@domain.tld
	 * @param bool   $is_comment Is the check for a comment?
	 *
	 * @return array The contact data
	 * @throws \Exception
	 */
	private static function allowedContactByHandle(array $importer, $handle, $is_comment = false)
	{
		$contact = self::contactByHandle($importer["uid"], $handle);
		if (!$contact) {
			Logger::log("A Contact for handle ".$handle." and user ".$importer["uid"]." was not found");
			// If a contact isn't found, we accept it anyway if it is a comment
			if ($is_comment && ($importer["uid"] != 0)) {
				return self::contactByHandle(0, $handle);
			} elseif ($is_comment) {
				return $importer;
			} else {
				return false;
			}
		}

		if (!self::postAllow($importer, $contact, $is_comment)) {
			Logger::log("The handle: ".$handle." is not allowed to post to user ".$importer["uid"]);
			return false;
		}
		return $contact;
	}

	/**
	 * Does the message already exists on the system?
	 *
	 * @param int    $uid  The user id
	 * @param string $guid The guid of the message
	 *
	 * @return int|bool message id if the message already was stored into the system - or false.
	 * @throws \Exception
	 */
	private static function messageExists($uid, $guid)
	{
		$item = Item::selectFirst(['id'], ['uid' => $uid, 'guid' => $guid]);
		if (DBA::isResult($item)) {
			Logger::log("message ".$guid." already exists for user ".$uid);
			return $item["id"];
		}

		return false;
	}

	/**
	 * Checks for links to posts in a message
	 *
	 * @param array $item The item array
	 * @return void
	 */
	private static function fetchGuid(array $item)
	{
		$expression = "=diaspora://.*?/post/([0-9A-Za-z\-_@.:]{15,254}[0-9A-Za-z])=ism";
		preg_replace_callback(
			$expression,
			function ($match) use ($item) {
				self::fetchGuidSub($match, $item);
			},
			$item["body"]
		);

		preg_replace_callback(
			"&\[url=/?posts/([^\[\]]*)\](.*)\[\/url\]&Usi",
			function ($match) use ($item) {
				self::fetchGuidSub($match, $item);
			},
			$item["body"]
		);
	}

	/**
	 * Checks for relative /people/* links in an item body to match local
	 * contacts or prepends the remote host taken from the author link.
	 *
	 * @param string $body        The item body to replace links from
	 * @param string $author_link The author link for missing local contact fallback
	 *
	 * @return string the replaced string
	 */
	public static function replacePeopleGuid($body, $author_link)
	{
		$return = preg_replace_callback(
			"&\[url=/people/([^\[\]]*)\](.*)\[\/url\]&Usi",
			function ($match) use ($author_link) {
				// $match
				// 0 => '[url=/people/0123456789abcdef]Foo Bar[/url]'
				// 1 => '0123456789abcdef'
				// 2 => 'Foo Bar'
				$handle = FContact::getUrlByGuid($match[1]);

				if ($handle) {
					$return = '@[url='.$handle.']'.$match[2].'[/url]';
				} else {
					// No local match, restoring absolute remote URL from author scheme and host
					$author_url = parse_url($author_link);
					$return = '[url='.$author_url['scheme'].'://'.$author_url['host'].'/people/'.$match[1].']'.$match[2].'[/url]';
				}

				return $return;
			},
			$body
		);

		return $return;
	}

	/**
	 * sub function of "fetchGuid" which checks for links in messages
	 *
	 * @param array $match array containing a link that has to be checked for a message link
	 * @param array $item  The item array
	 * @return void
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function fetchGuidSub($match, $item)
	{
		if (!self::storeByGuid($match[1], $item["author-link"])) {
			self::storeByGuid($match[1], $item["owner-link"]);
		}
	}

	/**
	 * Fetches an item with a given guid from a given server
	 *
	 * @param string $guid   the message guid
	 * @param string $server The server address
	 * @param int    $uid    The user id of the user
	 *
	 * @return int the message id of the stored message or false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function storeByGuid($guid, $server, $uid = 0)
	{
		$serverparts = parse_url($server);

		if (empty($serverparts["host"]) || empty($serverparts["scheme"])) {
			return false;
		}

		$server = $serverparts["scheme"]."://".$serverparts["host"];

		Logger::log("Trying to fetch item ".$guid." from ".$server, Logger::DEBUG);

		$msg = self::message($guid, $server);

		if (!$msg) {
			return false;
		}

		Logger::log("Successfully fetched item ".$guid." from ".$server, Logger::DEBUG);

		// Now call the dispatcher
		return self::dispatchPublic($msg, true);
	}

	/**
	 * Fetches a message from a server
	 *
	 * @param string $guid   message guid
	 * @param string $server The url of the server
	 * @param int    $level  Endless loop prevention
	 *
	 * @return array
	 *      'message' => The message XML
	 *      'author' => The author handle
	 *      'key' => The public key of the author
	 * @throws \Exception
	 */
	private static function message($guid, $server, $level = 0)
	{
		if ($level > 5) {
			return false;
		}

		// This will work for new Diaspora servers and Friendica servers from 3.5
		$source_url = $server."/fetch/post/".urlencode($guid);

		Logger::log("Fetch post from ".$source_url, Logger::DEBUG);

		$envelope = DI::httpRequest()->fetch($source_url);
		if ($envelope) {
			Logger::log("Envelope was fetched.", Logger::DEBUG);
			$x = self::verifyMagicEnvelope($envelope);
			if (!$x) {
				Logger::log("Envelope could not be verified.", Logger::DEBUG);
			} else {
				Logger::log("Envelope was verified.", Logger::DEBUG);
			}
		} else {
			$x = false;
		}

		if (!$x) {
			return false;
		}

		$source_xml = XML::parseString($x);

		if (!is_object($source_xml)) {
			return false;
		}

		if ($source_xml->post->reshare) {
			// Reshare of a reshare - old Diaspora version
			Logger::log("Message is a reshare", Logger::DEBUG);
			return self::message($source_xml->post->reshare->root_guid, $server, ++$level);
		} elseif ($source_xml->getName() == "reshare") {
			// Reshare of a reshare - new Diaspora version
			Logger::log("Message is a new reshare", Logger::DEBUG);
			return self::message($source_xml->root_guid, $server, ++$level);
		}

		$author = "";

		// Fetch the author - for the old and the new Diaspora version
		if ($source_xml->post->status_message && $source_xml->post->status_message->diaspora_handle) {
			$author = (string)$source_xml->post->status_message->diaspora_handle;
		} elseif ($source_xml->author && ($source_xml->getName() == "status_message")) {
			$author = (string)$source_xml->author;
		}

		// If this isn't a "status_message" then quit
		if (!$author) {
			Logger::log("Message doesn't seem to be a status message", Logger::DEBUG);
			return false;
		}

		$msg = ["message" => $x, "author" => $author];

		$msg["key"] = self::key($msg["author"]);

		return $msg;
	}

	/**
	 * Fetches an item with a given URL
	 *
	 * @param string $url the message url
	 *
	 * @return int the message id of the stored message or false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fetchByURL($url, $uid = 0)
	{
		// Check for Diaspora (and Friendica) typical paths
		if (!preg_match("=(https?://.+)/(?:posts|display|objects)/([a-zA-Z0-9-_@.:%]+[a-zA-Z0-9])=i", $url, $matches)) {
			Logger::info('Invalid url', ['url' => $url]);
			return false;
		}

		$guid = urldecode($matches[2]);

		$item = Item::selectFirst(['id'], ['guid' => $guid, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			Logger::info('Found', ['id' => $item['id']]);
			return $item['id'];
		}

		Logger::info('Fetch GUID from origin', ['guid' => $guid, 'server' => $matches[1]]);
		$ret = self::storeByGuid($guid, $matches[1], $uid);
		Logger::info('Result', ['ret' => $ret]);

		$item = Item::selectFirst(['id'], ['guid' => $guid, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			Logger::info('Found', ['id' => $item['id']]);
			return $item['id'];
		} else {
			Logger::info('Not found', ['guid' => $guid, 'uid' => $uid]);
			return false;
		}
	}

	/**
	 * Fetches the item record of a given guid
	 *
	 * @param int    $uid     The user id
	 * @param string $guid    message guid
	 * @param string $author  The handle of the item
	 * @param array  $contact The contact of the item owner
	 *
	 * @return array the item record
	 * @throws \Exception
	 */
	private static function parentItem($uid, $guid, $author, array $contact)
	{
		$fields = ['id', 'parent', 'body', 'wall', 'uri', 'guid', 'private', 'origin',
			'author-name', 'author-link', 'author-avatar', 'gravity', 'parent-uri',
			'owner-name', 'owner-link', 'owner-avatar'];
		$condition = ['uid' => $uid, 'guid' => $guid];
		$item = Item::selectFirst($fields, $condition);

		if (!DBA::isResult($item)) {
			$person = FContact::getByURL($author);
			$result = self::storeByGuid($guid, $person["url"], $uid);

			// We don't have an url for items that arrived at the public dispatcher
			if (!$result && !empty($contact["url"])) {
				$result = self::storeByGuid($guid, $contact["url"], $uid);
			}

			if ($result) {
				Logger::log("Fetched missing item ".$guid." - result: ".$result, Logger::DEBUG);

				$item = Item::selectFirst($fields, $condition);
			}
		}

		if (!DBA::isResult($item)) {
			Logger::log("parent item not found: parent: ".$guid." - user: ".$uid);
			return false;
		} else {
			Logger::log("parent item found: parent: ".$guid." - user: ".$uid);
			return $item;
		}
	}

	/**
	 * returns contact details
	 *
	 * @param array $def_contact The default contact if the person isn't found
	 * @param array $person      The record of the person
	 * @param int   $uid         The user id
	 *
	 * @return array
	 *      'cid' => contact id
	 *      'network' => network type
	 * @throws \Exception
	 */
	private static function authorContactByUrl($def_contact, $person, $uid)
	{
		$condition = ['nurl' => Strings::normaliseLink($person["url"]), 'uid' => $uid];
		$contact = DBA::selectFirst('contact', ['id', 'network'], $condition);
		if (DBA::isResult($contact)) {
			$cid = $contact["id"];
			$network = $contact["network"];
		} else {
			$cid = $def_contact["id"];
			$network = Protocol::DIASPORA;
		}

		return ["cid" => $cid, "network" => $network];
	}

	/**
	 * Is the profile a hubzilla profile?
	 *
	 * @param string $url The profile link
	 *
	 * @return bool is it a hubzilla server?
	 */
	private static function isHubzilla($url)
	{
		return(strstr($url, '/channel/'));
	}

	/**
	 * Generate a post link with a given handle and message guid
	 *
	 * @param string $addr        The user handle
	 * @param string $guid        message guid
	 * @param string $parent_guid optional parent guid
	 *
	 * @return string the post link
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function plink($addr, $guid, $parent_guid = '')
	{
		$contact = Contact::getByURL($addr);
		if (empty($contact)) {
			Logger::info('No contact data for address', ['addr' => $addr]);
			return '';
		}

		if (empty($contact['baseurl'])) {
			$contact['baseurl'] = 'https://' . substr($addr, strpos($addr, '@') + 1);
			Logger::info('Create baseurl from address', ['baseurl' => $contact['baseurl'], 'url' => $contact['url']]);
		}

		$platform = '';
		$gserver = DBA::selectFirst('gserver', ['platform'], ['nurl' => Strings::normaliseLink($contact['baseurl'])]);
		if (!empty($gserver['platform'])) {
			$platform = strtolower($gserver['platform']);
			Logger::info('Detected platform', ['platform' => $platform, 'url' => $contact['url']]);
		}

		if (!in_array($platform, ['diaspora', 'friendica', 'hubzilla', 'socialhome'])) {
			if (self::isHubzilla($contact['url'])) {
				Logger::info('Detected unknown platform as Hubzilla', ['platform' => $platform, 'url' => $contact['url']]);
				$platform = 'hubzilla';
			} elseif ($contact['network'] == Protocol::DFRN) {
				Logger::info('Detected unknown platform as Friendica', ['platform' => $platform, 'url' => $contact['url']]);
				$platform = 'friendica';
			}
		}

		if ($platform == 'friendica') {
			return str_replace('/profile/' . $contact['nick'] . '/', '/display/' . $guid, $contact['url'] . '/');
		}

		if ($platform == 'hubzilla') {
			return $contact['baseurl'] . '/item/' . $guid;
		}

		if ($platform == 'socialhome') {
			return $contact['baseurl'] . '/content/' . $guid;
		}

		if ($platform != 'diaspora') {
			Logger::info('Unknown platform', ['platform' => $platform, 'url' => $contact['url']]);
			return '';
		}

		if ($parent_guid != '') {
			return $contact['baseurl'] . '/posts/' . $parent_guid . '#' . $guid;
		} else {
			return $contact['baseurl'] . '/posts/' . $guid;
		}
	}

	/**
	 * Receives account migration
	 *
	 * @param array  $importer Array of the importer user
	 * @param object $data     The message object
	 *
	 * @return bool Success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveAccountMigration(array $importer, $data)
	{
		$old_handle = Strings::escapeTags(XML::unescape($data->author));
		$new_handle = Strings::escapeTags(XML::unescape($data->profile->author));
		$signature = Strings::escapeTags(XML::unescape($data->signature));

		$contact = self::contactByHandle($importer["uid"], $old_handle);
		if (!$contact) {
			Logger::log("cannot find contact for sender: ".$old_handle." and user ".$importer["uid"]);
			return false;
		}

		Logger::log("Got migration for ".$old_handle.", to ".$new_handle." with user ".$importer["uid"]);

		// Check signature
		$signed_text = 'AccountMigration:'.$old_handle.':'.$new_handle;
		$key = self::key($old_handle);
		if (!Crypto::rsaVerify($signed_text, $signature, $key, "sha256")) {
			Logger::log('No valid signature for migration.');
			return false;
		}

		// Update the profile
		self::receiveProfile($importer, $data->profile);

		// change the technical stuff in contact
		$data = Probe::uri($new_handle);
		if ($data['network'] == Protocol::PHANTOM) {
			Logger::log('Account for '.$new_handle." couldn't be probed.");
			return false;
		}

		$fields = ['url' => $data['url'], 'nurl' => Strings::normaliseLink($data['url']),
				'name' => $data['name'], 'nick' => $data['nick'],
				'addr' => $data['addr'], 'batch' => $data['batch'],
				'notify' => $data['notify'], 'poll' => $data['poll'],
				'network' => $data['network']];

		DBA::update('contact', $fields, ['addr' => $old_handle]);

		Logger::log('Contacts are updated.');

		return true;
	}

	/**
	 * Processes an account deletion
	 *
	 * @param object $data The message object
	 *
	 * @return bool Success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function receiveAccountDeletion($data)
	{
		$author = Strings::escapeTags(XML::unescape($data->author));

		$contacts = DBA::select('contact', ['id'], ['addr' => $author]);
		while ($contact = DBA::fetch($contacts)) {
			Contact::remove($contact["id"]);
		}
		DBA::close($contacts);

		Logger::log('Removed contacts for ' . $author);

		return true;
	}

	/**
	 * Fetch the uri from our database if we already have this item (maybe from ourselves)
	 *
	 * @param string  $author    Author handle
	 * @param string  $guid      Message guid
	 * @param boolean $onlyfound Only return uri when found in the database
	 *
	 * @return string The constructed uri or the one from our database
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function getUriFromGuid($author, $guid, $onlyfound = false)
	{
		$item = Item::selectFirst(['uri'], ['guid' => $guid]);
		if (DBA::isResult($item)) {
			return $item["uri"];
		} elseif (!$onlyfound) {
			$person = FContact::getByURL($author);

			$parts = parse_url($person['url']);
			unset($parts['path']);
			$host_url = Network::unparseURL($parts);

			return $host_url . '/objects/' . $guid;
		}

		return "";
	}

	/**
	 * Fetch the guid from our database with a given uri
	 *
	 * @param string $uri Message uri
	 * @param string $uid Author handle
	 *
	 * @return string The post guid
	 * @throws \Exception
	 */
	private static function getGuidFromUri($uri, $uid)
	{
		$item = Item::selectFirst(['guid'], ['uri' => $uri, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			return $item["guid"];
		} else {
			return false;
		}
	}

	/**
	 * Store the mentions in the tag table
	 *
	 * @param integer $uriid
	 * @param string $text
	 */
	private static function storeMentions(int $uriid, string $text)
	{
		preg_match_all('/([@!]){(?:([^}]+?); ?)?([^} ]+)}/', $text, $matches, PREG_SET_ORDER);
		if (empty($matches)) {
			return;
		}

		/*
		 * Matching values for the preg match
		 * [1] = mention type (@ or !)
		 * [2] = name (optional)
		 * [3] = profile URL
		 */

		foreach ($matches as $match) {
			if (empty($match)) {
				continue;
			}

			$person = FContact::getByURL($match[3]);
			if (empty($person)) {
				continue;
			}

			Tag::storeByHash($uriid, $match[1], $person['name'] ?: $person['nick'], $person['url']);
		}
	}

	/**
	 * Processes an incoming comment
	 *
	 * @param array  $importer Array of the importer user
	 * @param string $sender   The sender of the message
	 * @param object $data     The message object
	 * @param string $xml      The original XML of the message
	 * @param bool   $fetched  The message had been fetched and not pushed
	 *
	 * @return int The message id of the generated comment or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveComment(array $importer, $sender, $data, $xml, bool $fetched)
	{
		$author = Strings::escapeTags(XML::unescape($data->author));
		$guid = Strings::escapeTags(XML::unescape($data->guid));
		$parent_guid = Strings::escapeTags(XML::unescape($data->parent_guid));
		$text = XML::unescape($data->text);

		if (isset($data->created_at)) {
			$created_at = DateTimeFormat::utc(Strings::escapeTags(XML::unescape($data->created_at)));
		} else {
			$created_at = DateTimeFormat::utcNow();
		}

		if (isset($data->thread_parent_guid)) {
			$thread_parent_guid = Strings::escapeTags(XML::unescape($data->thread_parent_guid));
			$thr_parent = self::getUriFromGuid("", $thread_parent_guid, true);
		} else {
			$thr_parent = "";
		}

		$contact = self::allowedContactByHandle($importer, $sender, true);
		if (!$contact) {
			return false;
		}

		$message_id = self::messageExists($importer["uid"], $guid);
		if ($message_id) {
			return true;
		}

		$toplevel_parent_item = self::parentItem($importer["uid"], $parent_guid, $author, $contact);
		if (!$toplevel_parent_item) {
			return false;
		}

		$person = FContact::getByURL($author);
		if (!is_array($person)) {
			Logger::log("unable to find author details");
			return false;
		}

		// Fetch the contact id - if we know this contact
		$author_contact = self::authorContactByUrl($contact, $person, $importer["uid"]);

		$datarray = [];

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $author_contact["cid"];
		$datarray["network"]  = $author_contact["network"];

		$datarray["author-link"] = $person["url"];
		$datarray["author-id"] = Contact::getIdForURL($person["url"], 0);

		$datarray["owner-link"] = $contact["url"];
		$datarray["owner-id"] = Contact::getIdForURL($contact["url"], 0);

		// Will be overwritten for sharing accounts in Item::insert
		if ($fetched) {
			$datarray["post-type"] = Item::PT_FETCHED;
		} elseif ($datarray["uid"] == 0) {
			$datarray["post-type"] = Item::PT_GLOBAL;
		} else {
			$datarray["post-type"] = Item::PT_COMMENT;
		}

		$datarray["guid"] = $guid;
		$datarray["uri"] = self::getUriFromGuid($author, $guid);
		$datarray['uri-id'] = ItemURI::insert(['uri' => $datarray['uri'], 'guid' => $datarray['guid']]);

		$datarray["verb"] = Activity::POST;
		$datarray["gravity"] = GRAVITY_COMMENT;

		$datarray['thr-parent'] = $thr_parent ?: $toplevel_parent_item['uri'];
		$datarray['parent-uri'] = $toplevel_parent_item['uri'];

		$datarray["object-type"] = Activity\ObjectType::COMMENT;

		$datarray["protocol"] = Conversation::PARCEL_DIASPORA;
		$datarray["source"] = $xml;

		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = $created_at;

		$datarray["plink"] = self::plink($author, $guid, $toplevel_parent_item['guid']);
		$body = Markdown::toBBCode($text);

		$datarray["body"] = self::replacePeopleGuid($body, $person["url"]);

		self::storeMentions($datarray['uri-id'], $text);
		Tag::storeRawTagsFromBody($datarray['uri-id'], $datarray["body"]);

		self::fetchGuid($datarray);

		// If we are the origin of the parent we store the original data.
		// We notify our followers during the item storage.
		if ($toplevel_parent_item["origin"]) {
			$datarray['diaspora_signed_text'] = json_encode($data);
		}

		$message_id = Item::insert($datarray);

		if ($message_id <= 0) {
			return false;
		}

		if ($message_id) {
			Logger::log("Stored comment ".$datarray["guid"]." with message id ".$message_id, Logger::DEBUG);
			if ($datarray['uid'] == 0) {
				Item::distribute($message_id, json_encode($data));
			}
		}

		return true;
	}

	/**
	 * processes and stores private messages
	 *
	 * @param array  $importer     Array of the importer user
	 * @param array  $contact      The contact of the message
	 * @param object $data         The message object
	 * @param array  $msg          Array of the processed message, author handle and key
	 * @param object $mesg         The private message
	 * @param array  $conversation The conversation record to which this message belongs
	 *
	 * @return bool "true" if it was successful
	 * @throws \Exception
	 */
	private static function receiveConversationMessage(array $importer, array $contact, $data, $msg, $mesg, $conversation)
	{
		$author = Strings::escapeTags(XML::unescape($data->author));
		$guid = Strings::escapeTags(XML::unescape($data->guid));
		$subject = Strings::escapeTags(XML::unescape($data->subject));

		// "diaspora_handle" is the element name from the old version
		// "author" is the element name from the new version
		if ($mesg->author) {
			$msg_author = Strings::escapeTags(XML::unescape($mesg->author));
		} elseif ($mesg->diaspora_handle) {
			$msg_author = Strings::escapeTags(XML::unescape($mesg->diaspora_handle));
		} else {
			return false;
		}

		$msg_guid = Strings::escapeTags(XML::unescape($mesg->guid));
		$msg_conversation_guid = Strings::escapeTags(XML::unescape($mesg->conversation_guid));
		$msg_text = XML::unescape($mesg->text);
		$msg_created_at = DateTimeFormat::utc(Strings::escapeTags(XML::unescape($mesg->created_at)));

		if ($msg_conversation_guid != $guid) {
			Logger::log("message conversation guid does not belong to the current conversation.");
			return false;
		}

		$body = Markdown::toBBCode($msg_text);
		$message_uri = $msg_author.":".$msg_guid;

		$person = FContact::getByURL($msg_author);

		return Mail::insert([
			'uid'        => $importer['uid'],
			'guid'       => $msg_guid,
			'convid'     => $conversation['id'],
			'from-name'  => $person['name'],
			'from-photo' => $person['photo'],
			'from-url'   => $person['url'],
			'contact-id' => $contact['id'],
			'title'      => $subject,
			'body'       => $body,
			'uri'        => $message_uri,
			'parent-uri' => $author . ':' . $guid,
			'created'    => $msg_created_at
		]);
	}

	/**
	 * Processes new private messages (answers to private messages are processed elsewhere)
	 *
	 * @param array  $importer Array of the importer user
	 * @param array  $msg      Array of the processed message, author handle and key
	 * @param object $data     The message object
	 *
	 * @return bool Success
	 * @throws \Exception
	 */
	private static function receiveConversation(array $importer, $msg, $data)
	{
		$author = Strings::escapeTags(XML::unescape($data->author));
		$guid = Strings::escapeTags(XML::unescape($data->guid));
		$subject = Strings::escapeTags(XML::unescape($data->subject));
		$created_at = DateTimeFormat::utc(Strings::escapeTags(XML::unescape($data->created_at)));
		$participants = Strings::escapeTags(XML::unescape($data->participants));

		$messages = $data->message;

		if (!count($messages)) {
			Logger::log("empty conversation");
			return false;
		}

		$contact = self::allowedContactByHandle($importer, $msg["author"], true);
		if (!$contact) {
			return false;
		}

		$conversation = DBA::selectFirst('conv', [], ['uid' => $importer["uid"], 'guid' => $guid]);
		if (!DBA::isResult($conversation)) {
			$r = q(
				"INSERT INTO `conv` (`uid`, `guid`, `creator`, `created`, `updated`, `subject`, `recips`)
				VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s')",
				intval($importer["uid"]),
				DBA::escape($guid),
				DBA::escape($author),
				DBA::escape($created_at),
				DBA::escape(DateTimeFormat::utcNow()),
				DBA::escape($subject),
				DBA::escape($participants)
			);
			if ($r) {
				$conversation = DBA::selectFirst('conv', [], ['uid' => $importer["uid"], 'guid' => $guid]);
			}
		}
		if (!$conversation) {
			Logger::log("unable to create conversation.");
			return false;
		}

		foreach ($messages as $mesg) {
			self::receiveConversationMessage($importer, $contact, $data, $msg, $mesg, $conversation);
		}

		return true;
	}

	/**
	 * Processes "like" messages
	 *
	 * @param array  $importer Array of the importer user
	 * @param string $sender   The sender of the message
	 * @param object $data     The message object
	 *
	 * @return int The message id of the generated like or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveLike(array $importer, $sender, $data)
	{
		$author = Strings::escapeTags(XML::unescape($data->author));
		$guid = Strings::escapeTags(XML::unescape($data->guid));
		$parent_guid = Strings::escapeTags(XML::unescape($data->parent_guid));
		$parent_type = Strings::escapeTags(XML::unescape($data->parent_type));
		$positive = Strings::escapeTags(XML::unescape($data->positive));

		// likes on comments aren't supported by Diaspora - only on posts
		// But maybe this will be supported in the future, so we will accept it.
		if (!in_array($parent_type, ["Post", "Comment"])) {
			return false;
		}

		$contact = self::allowedContactByHandle($importer, $sender, true);
		if (!$contact) {
			return false;
		}

		$message_id = self::messageExists($importer["uid"], $guid);
		if ($message_id) {
			return true;
		}

		$toplevel_parent_item = self::parentItem($importer["uid"], $parent_guid, $author, $contact);
		if (!$toplevel_parent_item) {
			return false;
		}

		$person = FContact::getByURL($author);
		if (!is_array($person)) {
			Logger::log("unable to find author details");
			return false;
		}

		// Fetch the contact id - if we know this contact
		$author_contact = self::authorContactByUrl($contact, $person, $importer["uid"]);

		// "positive" = "false" would be a Dislike - wich isn't currently supported by Diaspora
		// We would accept this anyhow.
		if ($positive == "true") {
			$verb = Activity::LIKE;
		} else {
			$verb = Activity::DISLIKE;
		}

		$datarray = [];

		$datarray["protocol"] = Conversation::PARCEL_DIASPORA;

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $author_contact["cid"];
		$datarray["network"]  = $author_contact["network"];

		$datarray["owner-link"] = $datarray["author-link"] = $person["url"];
		$datarray["owner-id"] = $datarray["author-id"] = Contact::getIdForURL($person["url"], 0);

		$datarray["guid"] = $guid;
		$datarray["uri"] = self::getUriFromGuid($author, $guid);

		$datarray["verb"] = $verb;
		$datarray["gravity"] = GRAVITY_ACTIVITY;
		$datarray['thr-parent'] = $toplevel_parent_item['uri'];

		$datarray["object-type"] = Activity\ObjectType::NOTE;

		$datarray["body"] = $verb;

		// Diaspora doesn't provide a date for likes
		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = DateTimeFormat::utcNow();

		// like on comments have the comment as parent. So we need to fetch the toplevel parent
		if ($toplevel_parent_item['gravity'] != GRAVITY_PARENT) {
			$toplevel = Item::selectFirst(['origin'], ['id' => $toplevel_parent_item['parent']]);
			$origin = $toplevel["origin"];
		} else {
			$origin = $toplevel_parent_item["origin"];
		}

		// If we are the origin of the parent we store the original data.
		// We notify our followers during the item storage.
		if ($origin) {
			$datarray['diaspora_signed_text'] = json_encode($data);
		}

		$message_id = Item::insert($datarray);

		if ($message_id <= 0) {
			return false;
		}

		if ($message_id) {
			Logger::log("Stored like ".$datarray["guid"]." with message id ".$message_id, Logger::DEBUG);
			if ($datarray['uid'] == 0) {
				Item::distribute($message_id, json_encode($data));
			}
		}

		return true;
	}

	/**
	 * Processes private messages
	 *
	 * @param array  $importer Array of the importer user
	 * @param object $data     The message object
	 *
	 * @return bool Success?
	 * @throws \Exception
	 */
	private static function receiveMessage(array $importer, $data)
	{
		$author = Strings::escapeTags(XML::unescape($data->author));
		$guid = Strings::escapeTags(XML::unescape($data->guid));
		$conversation_guid = Strings::escapeTags(XML::unescape($data->conversation_guid));
		$text = XML::unescape($data->text);
		$created_at = DateTimeFormat::utc(Strings::escapeTags(XML::unescape($data->created_at)));

		$contact = self::allowedContactByHandle($importer, $author, true);
		if (!$contact) {
			return false;
		}

		$conversation = null;

		$condition = ['uid' => $importer["uid"], 'guid' => $conversation_guid];
		$conversation = DBA::selectFirst('conv', [], $condition);

		if (!DBA::isResult($conversation)) {
			Logger::log("conversation not available.");
			return false;
		}

		$message_uri = $author.":".$guid;

		$person = FContact::getByURL($author);
		if (!$person) {
			Logger::log("unable to find author details");
			return false;
		}

		$body = Markdown::toBBCode($text);

		$body = self::replacePeopleGuid($body, $person["url"]);

		return Mail::insert([
			'uid'        => $importer['uid'],
			'guid'       => $guid,
			'convid'     => $conversation['id'],
			'from-name'  => $person['name'],
			'from-photo' => $person['photo'],
			'from-url'   => $person['url'],
			'contact-id' => $contact['id'],
			'title'      => $conversation['subject'],
			'body'       => $body,
			'reply'      => 1,
			'uri'        => $message_uri,
			'parent-uri' => $author.":".$conversation['guid'],
			'created'    => $created_at
		]);
	}

	/**
	 * Processes participations - unsupported by now
	 *
	 * @param array  $importer Array of the importer user
	 * @param object $data     The message object
	 *
	 * @return bool success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveParticipation(array $importer, $data)
	{
		$author = strtolower(Strings::escapeTags(XML::unescape($data->author)));
		$guid = Strings::escapeTags(XML::unescape($data->guid));
		$parent_guid = Strings::escapeTags(XML::unescape($data->parent_guid));

		$contact = self::allowedContactByHandle($importer, $author, true);
		if (!$contact) {
			return false;
		}

		if (self::messageExists($importer["uid"], $guid)) {
			return true;
		}

		$toplevel_parent_item = self::parentItem($importer["uid"], $parent_guid, $author, $contact);
		if (!$toplevel_parent_item) {
			return false;
		}

		if (!$toplevel_parent_item['origin']) {
			Logger::info('Not our origin. Participation is ignored', ['parent_guid' => $parent_guid, 'guid' => $guid, 'author' => $author]);
		}

		if (!in_array($toplevel_parent_item['private'], [Item::PUBLIC, Item::UNLISTED])) {
			Logger::info('Item is not public, participation is ignored', ['parent_guid' => $parent_guid, 'guid' => $guid, 'author' => $author]);
			return false;
		}

		$person = FContact::getByURL($author);
		if (!is_array($person)) {
			Logger::log("Person not found: ".$author);
			return false;
		}

		$author_contact = self::authorContactByUrl($contact, $person, $importer["uid"]);

		// Store participation
		$datarray = [];

		$datarray["protocol"] = Conversation::PARCEL_DIASPORA;

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $author_contact["cid"];
		$datarray["network"]  = $author_contact["network"];

		$datarray["owner-link"] = $datarray["author-link"] = $person["url"];
		$datarray["owner-id"] = $datarray["author-id"] = Contact::getIdForURL($person["url"], 0);

		$datarray["guid"] = $guid;
		$datarray["uri"] = self::getUriFromGuid($author, $guid);

		$datarray["verb"] = Activity::FOLLOW;
		$datarray["gravity"] = GRAVITY_ACTIVITY;
		$datarray['thr-parent'] = $toplevel_parent_item['uri'];
		$datarray['parent-uri'] = $toplevel_parent_item['parent-uri'];

		$datarray["object-type"] = Activity\ObjectType::NOTE;

		$datarray["body"] = Activity::FOLLOW;

		// Diaspora doesn't provide a date for a participation
		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = DateTimeFormat::utcNow();

		$message_id = Item::insert($datarray);

		Logger::info('Participation stored', ['id' => $message_id, 'guid' => $guid, 'parent_guid' => $parent_guid, 'author' => $author]);

		// Send all existing comments and likes to the requesting server
		$comments = Item::select(['id', 'uri-id', 'parent-author-network', 'author-network', 'verb'],
			['parent' => $toplevel_parent_item['id'], 'gravity' => [GRAVITY_COMMENT, GRAVITY_ACTIVITY]]);
		while ($comment = Item::fetch($comments)) {
			if (in_array($comment['verb'], [Activity::FOLLOW, Activity::TAG])) {
				Logger::info('participation messages are not relayed', ['item' => $comment['id']]);
				continue;
			}

			if ($comment['author-network'] == Protocol::ACTIVITYPUB) {
				Logger::info('Comments from ActivityPub authors are not relayed', ['item' => $comment['id']]);
				continue;
			}

			if ($comment['parent-author-network'] == Protocol::ACTIVITYPUB) {
				Logger::info('Comments to comments from ActivityPub authors are not relayed', ['item' => $comment['id']]);
				continue;
			}

			Logger::info('Deliver participation', ['item' => $comment['id'], 'contact' => $author_contact["cid"]]);
			if (Worker::add(PRIORITY_HIGH, 'Delivery', Delivery::POST, $comment['id'], $author_contact["cid"])) {
				Post\DeliveryData::incrementQueueCount($comment['uri-id'], 1);
			}
		}
		DBA::close($comments);

		return true;
	}

	/**
	 * Processes photos - unneeded
	 *
	 * @param array  $importer Array of the importer user
	 * @param object $data     The message object
	 *
	 * @return bool always true
	 */
	private static function receivePhoto(array $importer, $data)
	{
		// There doesn't seem to be a reason for this function,
		// since the photo data is transmitted in the status message as well
		return true;
	}

	/**
	 * Processes poll participations - unssupported
	 *
	 * @param array  $importer Array of the importer user
	 * @param object $data     The message object
	 *
	 * @return bool always true
	 */
	private static function receivePollParticipation(array $importer, $data)
	{
		// We don't support polls by now
		return true;
	}

	/**
	 * Processes incoming profile updates
	 *
	 * @param array  $importer Array of the importer user
	 * @param object $data     The message object
	 *
	 * @return bool Success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveProfile(array $importer, $data)
	{
		$author = strtolower(Strings::escapeTags(XML::unescape($data->author)));

		$contact = self::contactByHandle($importer["uid"], $author);
		if (!$contact) {
			return false;
		}

		$name = XML::unescape($data->first_name).((strlen($data->last_name)) ? " ".XML::unescape($data->last_name) : "");
		$image_url = XML::unescape($data->image_url);
		$birthday = XML::unescape($data->birthday);
		$about = Markdown::toBBCode(XML::unescape($data->bio));
		$location = Markdown::toBBCode(XML::unescape($data->location));
		$searchable = (XML::unescape($data->searchable) == "true");
		$nsfw = (XML::unescape($data->nsfw) == "true");
		$tags = XML::unescape($data->tag_string);

		$tags = explode("#", $tags);

		$keywords = [];
		foreach ($tags as $tag) {
			$tag = trim(strtolower($tag));
			if ($tag != "") {
				$keywords[] = $tag;
			}
		}

		$keywords = implode(", ", $keywords);

		$handle_parts = explode("@", $author);
		$nick = $handle_parts[0];

		if ($name === "") {
			$name = $handle_parts[0];
		}

		if (preg_match("|^https?://|", $image_url) === 0) {
			$image_url = "http://".$handle_parts[1].$image_url;
		}

		Contact::updateAvatar($contact["id"], $image_url);

		// Generic birthday. We don't know the timezone. The year is irrelevant.

		$birthday = str_replace("1000", "1901", $birthday);

		if ($birthday != "") {
			$birthday = DateTimeFormat::utc($birthday, "Y-m-d");
		}

		// this is to prevent multiple birthday notifications in a single year
		// if we already have a stored birthday and the 'm-d' part hasn't changed, preserve the entry, which will preserve the notify year

		if (substr($birthday, 5) === substr($contact["bd"], 5)) {
			$birthday = $contact["bd"];
		}

		$fields = ['name' => $name, 'location' => $location,
			'name-date' => DateTimeFormat::utcNow(), 'about' => $about,
			'addr' => $author, 'nick' => $nick, 'keywords' => $keywords,
			'unsearchable' => !$searchable, 'sensitive' => $nsfw];

		if (!empty($birthday)) {
			$fields['bd'] = $birthday;
		}

		DBA::update('contact', $fields, ['id' => $contact['id']]);

		Logger::log("Profile of contact ".$contact["id"]." stored for user ".$importer["uid"], Logger::DEBUG);

		return true;
	}

	/**
	 * Processes incoming friend requests
	 *
	 * @param array $importer Array of the importer user
	 * @param array $contact  The contact that send the request
	 * @return void
	 * @throws \Exception
	 */
	private static function receiveRequestMakeFriend(array $importer, array $contact)
	{
		if ($contact["rel"] == Contact::SHARING) {
			DBA::update(
				'contact',
				['rel' => Contact::FRIEND, 'writable' => true],
				['id' => $contact["id"], 'uid' => $importer["uid"]]
			);
		}
	}

	/**
	 * Processes incoming sharing notification
	 *
	 * @param array  $importer Array of the importer user
	 * @param object $data     The message object
	 *
	 * @return bool Success
	 * @throws \Exception
	 */
	private static function receiveContactRequest(array $importer, $data)
	{
		$author = XML::unescape($data->author);
		$recipient = XML::unescape($data->recipient);

		if (!$author || !$recipient) {
			return false;
		}

		// the current protocol version doesn't know these fields
		// That means that we will assume their existance
		if (isset($data->following)) {
			$following = (XML::unescape($data->following) == "true");
		} else {
			$following = true;
		}

		if (isset($data->sharing)) {
			$sharing = (XML::unescape($data->sharing) == "true");
		} else {
			$sharing = true;
		}

		$contact = self::contactByHandle($importer["uid"], $author);

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends.
		if ($contact) {
			if ($following) {
				Logger::log("Author ".$author." (Contact ".$contact["id"].") wants to follow us.", Logger::DEBUG);
				self::receiveRequestMakeFriend($importer, $contact);

				// refetch the contact array
				$contact = self::contactByHandle($importer["uid"], $author);

				// If we are now friends, we are sending a share message.
				// Normally we needn't to do so, but the first message could have been vanished.
				if (in_array($contact["rel"], [Contact::FRIEND])) {
					$user = DBA::selectFirst('user', [], ['uid' => $importer["uid"]]);
					if (DBA::isResult($user)) {
						Logger::log("Sending share message to author ".$author." - Contact: ".$contact["id"]." - User: ".$importer["uid"], Logger::DEBUG);
						self::sendShare($user, $contact);
					}
				}
				return true;
			} else {
				Logger::log("Author ".$author." doesn't want to follow us anymore.", Logger::DEBUG);
				Contact::removeFollower($importer, $contact);
				return true;
			}
		}

		if (!$following && $sharing && in_array($importer["page-flags"], [User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_NORMAL])) {
			Logger::log("Author ".$author." wants to share with us - but doesn't want to listen. Request is ignored.", Logger::DEBUG);
			return false;
		} elseif (!$following && !$sharing) {
			Logger::log("Author ".$author." doesn't want anything - and we don't know the author. Request is ignored.", Logger::DEBUG);
			return false;
		} elseif (!$following && $sharing) {
			Logger::log("Author ".$author." wants to share with us.", Logger::DEBUG);
		} elseif ($following && $sharing) {
			Logger::log("Author ".$author." wants to have a bidirectional conection.", Logger::DEBUG);
		} elseif ($following && !$sharing) {
			Logger::log("Author ".$author." wants to listen to us.", Logger::DEBUG);
		}

		$ret = FContact::getByURL($author);

		if (!$ret || ($ret["network"] != Protocol::DIASPORA)) {
			Logger::log("Cannot resolve diaspora handle ".$author." for ".$recipient);
			return false;
		}

		$cid = Contact::getIdForURL($ret['url'], $importer['uid']);
		if (!empty($cid)) {
			$contact = DBA::selectFirst('contact', [], ['id' => $cid, 'network' => Protocol::NATIVE_SUPPORT]);
		} else {
			$contact = [];
		}

		$item = ['author-id' => Contact::getIdForURL($ret['url']),
			'author-link' => $ret['url']];

		$result = Contact::addRelationship($importer, $contact, $item, false);
		if ($result === true) {
			$contact_record = self::contactByHandle($importer['uid'], $author);
			if (!$contact_record) {
				Logger::info('unable to locate newly created contact record.');
				return;
			}

			$user = DBA::selectFirst('user', [], ['uid' => $importer['uid']]);
			if (DBA::isResult($user)) {
				self::sendShare($user, $contact_record);

				// Send the profile data, maybe it weren't transmitted before
				self::sendProfile($importer['uid'], [$contact_record]);
			}
		}

		return true;
	}

	/**
	 * Fetches a message with a given guid
	 *
	 * @param string $guid        message guid
	 * @param string $orig_author handle of the original post
	 * @return array The fetched item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function originalItem($guid, $orig_author)
	{
		if (empty($guid)) {
			Logger::log('Empty guid. Quitting.');
			return false;
		}

		// Do we already have this item?
		$fields = ['body', 'title', 'app', 'created', 'object-type', 'uri', 'guid',
			'author-name', 'author-link', 'author-avatar', 'plink', 'uri-id'];
		$condition = ['guid' => $guid, 'visible' => true, 'deleted' => false, 'private' => [Item::PUBLIC, Item::UNLISTED]];
		$item = Item::selectFirst($fields, $condition);

		if (DBA::isResult($item)) {
			Logger::log("reshared message ".$guid." already exists on system.");

			// Maybe it is already a reshared item?
			// Then refetch the content, if it is a reshare from a reshare.
			// If it is a reshared post from another network then reformat to avoid display problems with two share elements
			if (self::isReshare($item["body"], true)) {
				$item = [];
			} elseif (self::isReshare($item["body"], false) || strstr($item["body"], "[share")) {
				$item["body"] = Markdown::toBBCode(BBCode::toMarkdown($item["body"]));

				$item["body"] = self::replacePeopleGuid($item["body"], $item["author-link"]);

				// Add OEmbed and other information to the body
				$item["body"] = PageInfo::searchAndAppendToBody($item["body"], false, true);

				return $item;
			} else {
				return $item;
			}
		}

		if (!DBA::isResult($item)) {
			if (empty($orig_author)) {
				Logger::log('Empty author for guid ' . $guid . '. Quitting.');
				return false;
			}

			$server = "https://".substr($orig_author, strpos($orig_author, "@") + 1);
			Logger::log("1st try: reshared message ".$guid." will be fetched via SSL from the server ".$server);
			$stored = self::storeByGuid($guid, $server);

			if (!$stored) {
				$server = "http://".substr($orig_author, strpos($orig_author, "@") + 1);
				Logger::log("2nd try: reshared message ".$guid." will be fetched without SSL from the server ".$server);
				$stored = self::storeByGuid($guid, $server);
			}

			if ($stored) {
				$fields = ['body', 'title', 'app', 'created', 'object-type', 'uri', 'guid',
					'author-name', 'author-link', 'author-avatar', 'plink', 'uri-id'];
				$condition = ['guid' => $guid, 'visible' => true, 'deleted' => false, 'private' => [Item::PUBLIC, Item::UNLISTED]];
				$item = Item::selectFirst($fields, $condition);

				if (DBA::isResult($item)) {
					// If it is a reshared post from another network then reformat to avoid display problems with two share elements
					if (self::isReshare($item["body"], false)) {
						$item["body"] = Markdown::toBBCode(BBCode::toMarkdown($item["body"]));
						$item["body"] = self::replacePeopleGuid($item["body"], $item["author-link"]);
					}

					return $item;
				}
			}
		}
		return false;
	}

	/**
	 * Stores a reshare activity
	 *
	 * @param array   $item              Array of reshare post
	 * @param integer $parent_message_id Id of the parent post
	 * @param string  $guid              GUID string of reshare action
	 * @param string  $author            Author handle
	 */
	private static function addReshareActivity($item, $parent_message_id, $guid, $author)
	{
		$parent = Item::selectFirst(['uri', 'guid', 'parent-uri'], ['id' => $parent_message_id]);

		$datarray = [];

		$datarray['uid'] = $item['uid'];
		$datarray['contact-id'] = $item['contact-id'];
		$datarray['network'] = $item['network'];

		$datarray['author-link'] = $item['author-link'];
		$datarray['author-id'] = $item['author-id'];

		$datarray['owner-link'] = $datarray['author-link'];
		$datarray['owner-id'] = $datarray['author-id'];

		$datarray['guid'] = $parent['guid'] . '-' . $guid;
		$datarray['uri'] = self::getUriFromGuid($author, $datarray['guid']);
		$datarray['thr-parent'] = $parent['uri'];
		$datarray['parent-uri'] = $parent['parent-uri'];

		$datarray['verb'] = $datarray['body'] = Activity::ANNOUNCE;
		$datarray['gravity'] = GRAVITY_ACTIVITY;
		$datarray['object-type'] = Activity\ObjectType::NOTE;

		$datarray['protocol'] = $item['protocol'];

		$datarray['plink'] = self::plink($author, $datarray['guid']);
		$datarray['private'] = $item['private'];
		$datarray['changed'] = $datarray['created'] = $datarray['edited'] = $item['created'];

		$message_id = Item::insert($datarray);

		if ($message_id) {
			Logger::info('Stored reshare activity.', ['guid' => $guid, 'id' => $message_id]);
			if ($datarray['uid'] == 0) {
				Item::distribute($message_id);
			}
		}
	}

	/**
	 * Processes a reshare message
	 *
	 * @param array  $importer Array of the importer user
	 * @param object $data     The message object
	 * @param string $xml      The original XML of the message
	 *
	 * @return int the message id
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveReshare(array $importer, $data, $xml)
	{
		$author = Strings::escapeTags(XML::unescape($data->author));
		$guid = Strings::escapeTags(XML::unescape($data->guid));
		$created_at = DateTimeFormat::utc(Strings::escapeTags(XML::unescape($data->created_at)));
		$root_author = Strings::escapeTags(XML::unescape($data->root_author));
		$root_guid = Strings::escapeTags(XML::unescape($data->root_guid));
		/// @todo handle unprocessed property "provider_display_name"
		$public = Strings::escapeTags(XML::unescape($data->public));

		$contact = self::allowedContactByHandle($importer, $author, false);
		if (!$contact) {
			return false;
		}

		$message_id = self::messageExists($importer["uid"], $guid);
		if ($message_id) {
			return true;
		}

		$original_item = self::originalItem($root_guid, $root_author);
		if (!$original_item) {
			return false;
		}

		$datarray = [];

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $contact["id"];
		$datarray["network"]  = Protocol::DIASPORA;

		$datarray["author-link"] = $contact["url"];
		$datarray["author-id"] = Contact::getIdForURL($contact["url"], 0);

		$datarray["owner-link"] = $datarray["author-link"];
		$datarray["owner-id"] = $datarray["author-id"];

		$datarray["guid"] = $guid;
		$datarray["uri"] = $datarray["thr-parent"] = self::getUriFromGuid($author, $guid);
		$datarray['uri-id'] = ItemURI::insert(['uri' => $datarray['uri'], 'guid' => $datarray['guid']]);

		$datarray["verb"] = Activity::POST;
		$datarray["gravity"] = GRAVITY_PARENT;

		$datarray["protocol"] = Conversation::PARCEL_DIASPORA;
		$datarray["source"] = $xml;

		/// @todo Copy tag data from original post

		$prefix = BBCode::getShareOpeningTag(
			$original_item["author-name"],
			$original_item["author-link"],
			$original_item["author-avatar"],
			$original_item["plink"],
			$original_item["created"],
			$original_item["guid"]
		);

		if (!empty($original_item['title'])) {
			$prefix .= '[h3]' . $original_item['title'] . "[/h3]\n";
		}

		$datarray["body"] = $prefix.$original_item["body"]."[/share]";

		Tag::storeFromBody($datarray['uri-id'], $datarray["body"]);

		Post\Media::copy($original_item['uri-id'], $datarray['uri-id']);
		$datarray["app"]  = $original_item["app"];

		$datarray["plink"] = self::plink($author, $guid);
		$datarray["private"] = (($public == "false") ? Item::PRIVATE : Item::PUBLIC);
		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = $created_at;

		$datarray["object-type"] = $original_item["object-type"];

		self::fetchGuid($datarray);
		$message_id = Item::insert($datarray);

		self::sendParticipation($contact, $datarray);

		$root_message_id = self::messageExists($importer["uid"], $root_guid);
		if ($root_message_id) {
			self::addReshareActivity($datarray, $root_message_id, $guid, $author);
		}

		if ($message_id) {
			Logger::log("Stored reshare ".$datarray["guid"]." with message id ".$message_id, Logger::DEBUG);
			if ($datarray['uid'] == 0) {
				Item::distribute($message_id);
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Processes retractions
	 *
	 * @param array  $importer Array of the importer user
	 * @param array  $contact  The contact of the item owner
	 * @param object $data     The message object
	 *
	 * @return bool success
	 * @throws \Exception
	 */
	private static function itemRetraction(array $importer, array $contact, $data)
	{
		$author = Strings::escapeTags(XML::unescape($data->author));
		$target_guid = Strings::escapeTags(XML::unescape($data->target_guid));
		$target_type = Strings::escapeTags(XML::unescape($data->target_type));

		$person = FContact::getByURL($author);
		if (!is_array($person)) {
			Logger::log("unable to find author detail for ".$author);
			return false;
		}

		if (empty($contact["url"])) {
			$contact["url"] = $person["url"];
		}

		// Fetch items that are about to be deleted
		$fields = ['uid', 'id', 'parent', 'author-link', 'file'];

		// When we receive a public retraction, we delete every item that we find.
		if ($importer['uid'] == 0) {
			$condition = ['guid' => $target_guid, 'deleted' => false];
		} else {
			$condition = ['guid' => $target_guid, 'deleted' => false, 'uid' => $importer['uid']];
		}

		$r = Item::select($fields, $condition);
		if (!DBA::isResult($r)) {
			Logger::log("Target guid ".$target_guid." was not found on this system for user ".$importer['uid'].".");
			return false;
		}

		while ($item = Item::fetch($r)) {
			if (strstr($item['file'], '[')) {
				Logger::log("Target guid " . $target_guid . " for user " . $item['uid'] . " is filed. So it won't be deleted.", Logger::DEBUG);
				continue;
			}

			// Fetch the parent item
			$parent = Item::selectFirst(['author-link'], ['id' => $item['parent']]);

			// Only delete it if the parent author really fits
			if (!Strings::compareLink($parent["author-link"], $contact["url"]) && !Strings::compareLink($item["author-link"], $contact["url"])) {
				Logger::log("Thread author ".$parent["author-link"]." and item author ".$item["author-link"]." don't fit to expected contact ".$contact["url"], Logger::DEBUG);
				continue;
			}

			Item::markForDeletion(['id' => $item['id']]);

			Logger::log("Deleted target ".$target_guid." (".$item["id"].") from user ".$item["uid"]." parent: ".$item['parent'], Logger::DEBUG);
		}

		return true;
	}

	/**
	 * Receives retraction messages
	 *
	 * @param array  $importer Array of the importer user
	 * @param string $sender   The sender of the message
	 * @param object $data     The message object
	 *
	 * @return bool Success
	 * @throws \Exception
	 */
	private static function receiveRetraction(array $importer, $sender, $data)
	{
		$target_type = Strings::escapeTags(XML::unescape($data->target_type));

		$contact = self::contactByHandle($importer["uid"], $sender);
		if (!$contact && (in_array($target_type, ["Contact", "Person"]))) {
			Logger::log("cannot find contact for sender: ".$sender." and user ".$importer["uid"]);
			return false;
		}

		if (!$contact) {
			$contact = [];
		}

		Logger::log("Got retraction for ".$target_type.", sender ".$sender." and user ".$importer["uid"], Logger::DEBUG);

		switch ($target_type) {
			case "Comment":
			case "Like":
			case "Post":
			case "Reshare":
			case "StatusMessage":
				return self::itemRetraction($importer, $contact, $data);

			case "PollParticipation":
			case "Photo":
				// Currently unsupported
				break;

			default:
				Logger::log("Unknown target type ".$target_type);
				return false;
		}
		return true;
	}

	/**
	 * Checks if an incoming message is wanted
	 *
	 * @param string $url
	 * @param integer $uriid
	 * @param string $author
	 * @param string $body
	 * @return boolean Is the message wanted?
	 */
	private static function isSolicitedMessage(string $url, int $uriid, string $author, string $body)
	{
		$contact = Contact::getByURL($author);
		if (DBA::exists('contact', ["`nurl` = ? AND `uid` != ? AND `rel` IN (?, ?)",
			$contact['nurl'], 0, Contact::FRIEND, Contact::SHARING])) {
			Logger::info('Author has got followers - accepted', ['url' => $url, 'author' => $author]);
			return true;
		}

		$taglist = Tag::getByURIId($uriid, [Tag::HASHTAG]);
		$tags = array_column($taglist, 'name');
		return Relay::isSolicitedPost($tags, $body, $contact['id'], $url, Protocol::DIASPORA);
	}

	/**
	 * Store an attached photo in the post-media table
	 *
	 * @param int $uriid
	 * @param object $photo
	 * @return void
	 */
	private static function storePhotoAsMedia(int $uriid, $photo)
	{
		$data = [];
		$data['uri-id'] = $uriid;
		$data['type'] = Post\Media::IMAGE;
		$data['url'] = XML::unescape($photo->remote_photo_path) . XML::unescape($photo->remote_photo_name);
		$data['height'] = (int)XML::unescape($photo->height ?? 0);
		$data['width'] = (int)XML::unescape($photo->width ?? 0);
		$data['description'] = XML::unescape($photo->text ?? '');

		Post\Media::insert($data);
	}

	/**
	 * Receives status messages
	 *
	 * @param array            $importer Array of the importer user
	 * @param SimpleXMLElement $data     The message object
	 * @param string           $xml      The original XML of the message
	 * @param bool             $fetched  The message had been fetched and not pushed
	 * @return int The message id of the newly created item
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveStatusMessage(array $importer, SimpleXMLElement $data, $xml, bool $fetched)
	{
		$author = Strings::escapeTags(XML::unescape($data->author));
		$guid = Strings::escapeTags(XML::unescape($data->guid));
		$created_at = DateTimeFormat::utc(Strings::escapeTags(XML::unescape($data->created_at)));
		$public = Strings::escapeTags(XML::unescape($data->public));
		$text = XML::unescape($data->text);
		$provider_display_name = Strings::escapeTags(XML::unescape($data->provider_display_name));

		$contact = self::allowedContactByHandle($importer, $author, false);
		if (!$contact) {
			return false;
		}

		$message_id = self::messageExists($importer["uid"], $guid);
		if ($message_id) {
			return true;
		}

		$address = [];
		if ($data->location) {
			foreach ($data->location->children() as $fieldname => $data) {
				$address[$fieldname] = Strings::escapeTags(XML::unescape($data));
			}
		}

		$raw_body = $body = Markdown::toBBCode($text);

		$datarray = [];

		$datarray["guid"] = $guid;
		$datarray["uri"] = $datarray["thr-parent"] = self::getUriFromGuid($author, $guid);
		$datarray['uri-id'] = ItemURI::insert(['uri' => $datarray['uri'], 'guid' => $datarray['guid']]);

		// Attach embedded pictures to the body
		if ($data->photo) {
			foreach ($data->photo as $photo) {
				self::storePhotoAsMedia($datarray['uri-id'], $photo);
				$body = "[img]".XML::unescape($photo->remote_photo_path).
					XML::unescape($photo->remote_photo_name)."[/img]\n".$body;
			}

			$datarray["object-type"] = Activity\ObjectType::IMAGE;
		} else {
			$datarray["object-type"] = Activity\ObjectType::NOTE;

			// Add OEmbed and other information to the body
			if (!self::isHubzilla($contact["url"])) {
				$body = PageInfo::searchAndAppendToBody($body, false, true);
			}
		}

		/// @todo enable support for polls
		//if ($data->poll) {
		//	foreach ($data->poll AS $poll)
		//		print_r($poll);
		//	die("poll!\n");
		//}

		/// @todo enable support for events

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $contact["id"];
		$datarray["network"] = Protocol::DIASPORA;

		$datarray["author-link"] = $contact["url"];
		$datarray["author-id"] = Contact::getIdForURL($contact["url"], 0);

		$datarray["owner-link"] = $datarray["author-link"];
		$datarray["owner-id"] = $datarray["author-id"];

		$datarray["verb"] = Activity::POST;
		$datarray["gravity"] = GRAVITY_PARENT;

		$datarray["protocol"] = Conversation::PARCEL_DIASPORA;
		$datarray["source"] = $xml;

		if ($fetched) {
			$datarray["post-type"] = Item::PT_FETCHED;
		} elseif ($datarray["uid"] == 0) {
			$datarray["post-type"] = Item::PT_GLOBAL;
		}

		$datarray["body"] = self::replacePeopleGuid($body, $contact["url"]);
		$datarray["raw-body"] = self::replacePeopleGuid($raw_body, $contact["url"]);

		self::storeMentions($datarray['uri-id'], $text);
		Tag::storeRawTagsFromBody($datarray['uri-id'], $datarray["body"]);

		if (!$fetched && !self::isSolicitedMessage($datarray["uri"], $datarray['uri-id'], $author, $body)) {
			DBA::delete('item-uri', ['uri' => $datarray['uri']]);
			return false;
		}

		if ($provider_display_name != "") {
			$datarray["app"] = $provider_display_name;
		}

		$datarray["plink"] = self::plink($author, $guid);
		$datarray["private"] = (($public == "false") ? Item::PRIVATE : Item::PUBLIC);
		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = $created_at;

		if (isset($address["address"])) {
			$datarray["location"] = $address["address"];
		}

		if (isset($address["lat"]) && isset($address["lng"])) {
			$datarray["coord"] = $address["lat"]." ".$address["lng"];
		}

		self::fetchGuid($datarray);
		$message_id = Item::insert($datarray);

		self::sendParticipation($contact, $datarray);

		if ($message_id) {
			Logger::log("Stored item ".$datarray["guid"]." with message id ".$message_id, Logger::DEBUG);
			if ($datarray['uid'] == 0) {
				Item::distribute($message_id);
			}
			return true;
		} else {
			return false;
		}
	}

	/* ************************************************************************************** *
	 * Here are all the functions that are needed to transmit data with the Diaspora protocol *
	 * ************************************************************************************** */

	/**
	 * returnes the handle of a contact
	 *
	 * @param array $contact contact array
	 *
	 * @return string the handle in the format user@domain.tld
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function myHandle(array $contact)
	{
		if (!empty($contact["addr"])) {
			return $contact["addr"];
		}

		// Normally we should have a filled "addr" field - but in the past this wasn't the case
		// So - just in case - we build the the address here.
		if ($contact["nickname"] != "") {
			$nick = $contact["nickname"];
		} else {
			$nick = $contact["nick"];
		}

		return $nick . "@" . substr(DI::baseUrl(), strpos(DI::baseUrl(), "://") + 3);
	}


	/**
	 * Creates the data for a private message in the new format
	 *
	 * @param string $msg     The message that is to be transmitted
	 * @param array  $user    The record of the sender
	 * @param array  $contact Target of the communication
	 * @param string $prvkey  The private key of the sender
	 * @param string $pubkey  The public key of the receiver
	 *
	 * @return string The encrypted data
	 * @throws \Exception
	 */
	public static function encodePrivateData($msg, array $user, array $contact, $prvkey, $pubkey)
	{
		Logger::log("Message: ".$msg, Logger::DATA);

		// without a public key nothing will work
		if (!$pubkey) {
			Logger::log("pubkey missing: contact id: ".$contact["id"]);
			return false;
		}

		$aes_key = openssl_random_pseudo_bytes(32);
		$b_aes_key = base64_encode($aes_key);
		$iv = openssl_random_pseudo_bytes(16);
		$b_iv = base64_encode($iv);

		$ciphertext = self::aesEncrypt($aes_key, $iv, $msg);

		$json = json_encode(["iv" => $b_iv, "key" => $b_aes_key]);

		$encrypted_key_bundle = "";
		if (!@openssl_public_encrypt($json, $encrypted_key_bundle, $pubkey)) {
			return false;
		}

		$json_object = json_encode(
			["aes_key" => base64_encode($encrypted_key_bundle),
					"encrypted_magic_envelope" => base64_encode($ciphertext)]
		);

		return $json_object;
	}

	/**
	 * Creates the envelope for the "fetch" endpoint and for the new format
	 *
	 * @param string $msg  The message that is to be transmitted
	 * @param array  $user The record of the sender
	 *
	 * @return string The envelope
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function buildMagicEnvelope($msg, array $user)
	{
		$b64url_data = Strings::base64UrlEncode($msg);
		$data = str_replace(["\n", "\r", " ", "\t"], ["", "", "", ""], $b64url_data);

		$key_id = Strings::base64UrlEncode(self::myHandle($user));
		$type = "application/xml";
		$encoding = "base64url";
		$alg = "RSA-SHA256";
		$signable_data = $data.".".Strings::base64UrlEncode($type).".".Strings::base64UrlEncode($encoding).".".Strings::base64UrlEncode($alg);

		// Fallback if the private key wasn't transmitted in the expected field
		if ($user['uprvkey'] == "") {
			$user['uprvkey'] = $user['prvkey'];
		}

		$signature = Crypto::rsaSign($signable_data, $user["uprvkey"]);
		$sig = Strings::base64UrlEncode($signature);

		$xmldata = ["me:env" => ["me:data" => $data,
							"@attributes" => ["type" => $type],
							"me:encoding" => $encoding,
							"me:alg" => $alg,
							"me:sig" => $sig,
							"@attributes2" => ["key_id" => $key_id]]];

		$namespaces = ["me" => "http://salmon-protocol.org/ns/magic-env"];

		return XML::fromArray($xmldata, $xml, false, $namespaces);
	}

	/**
	 * Create the envelope for a message
	 *
	 * @param string $msg     The message that is to be transmitted
	 * @param array  $user    The record of the sender
	 * @param array  $contact Target of the communication
	 * @param string $prvkey  The private key of the sender
	 * @param string $pubkey  The public key of the receiver
	 * @param bool   $public  Is the message public?
	 *
	 * @return string The message that will be transmitted to other servers
	 * @throws \Exception
	 */
	public static function buildMessage($msg, array $user, array $contact, $prvkey, $pubkey, $public = false)
	{
		// The message is put into an envelope with the sender's signature
		$envelope = self::buildMagicEnvelope($msg, $user);

		// Private messages are put into a second envelope, encrypted with the receivers public key
		if (!$public) {
			$envelope = self::encodePrivateData($envelope, $user, $contact, $prvkey, $pubkey);
		}

		return $envelope;
	}

	/**
	 * Creates a signature for a message
	 *
	 * @param array $owner   the array of the owner of the message
	 * @param array $message The message that is to be signed
	 *
	 * @return string The signature
	 */
	private static function signature($owner, $message)
	{
		$sigmsg = $message;
		unset($sigmsg["author_signature"]);
		unset($sigmsg["parent_author_signature"]);

		$signed_text = implode(";", $sigmsg);

		return base64_encode(Crypto::rsaSign($signed_text, $owner["uprvkey"], "sha256"));
	}

	/**
	 * Transmit a message to a target server
	 *
	 * @param array  $owner        the array of the item owner
	 * @param array  $contact      Target of the communication
	 * @param string $envelope     The message that is to be transmitted
	 * @param bool   $public_batch Is it a public post?
	 * @param string $guid         message guid
	 *
	 * @return int Result of the transmission
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function transmit(array $owner, array $contact, $envelope, $public_batch, $guid = "")
	{
		$enabled = intval(DI::config()->get("system", "diaspora_enabled"));
		if (!$enabled) {
			return 200;
		}

		$logid = Strings::getRandomHex(4);

		// We always try to use the data from the fcontact table.
		// This is important for transmitting data to Friendica servers.
		if (!empty($contact['addr'])) {
			$fcontact = FContact::getByURL($contact['addr']);
			if (!empty($fcontact)) {
				$dest_url = ($public_batch ? $fcontact["batch"] : $fcontact["notify"]);
			}
		}

		if (empty($dest_url)) {
			$dest_url = ($public_batch ? $contact["batch"] : $contact["notify"]);
		}

		if (!$dest_url) {
			Logger::log("no url for contact: ".$contact["id"]." batch mode =".$public_batch);
			return 0;
		}

		Logger::log("transmit: ".$logid."-".$guid." ".$dest_url);

		if (!intval(DI::config()->get("system", "diaspora_test"))) {
			$content_type = (($public_batch) ? "application/magic-envelope+xml" : "application/json");

			$postResult = DI::httpRequest()->post($dest_url . "/", $envelope, ["Content-Type: " . $content_type]);
			$return_code = $postResult->getReturnCode();
		} else {
			Logger::log("test_mode");
			return 200;
		}

		Logger::log("transmit: ".$logid."-".$guid." to ".$dest_url." returns: ".$return_code);

		return $return_code ? $return_code : -1;
	}


	/**
	 * Build the post xml
	 *
	 * @param string $type    The message type
	 * @param array  $message The message data
	 *
	 * @return string The post XML
	 */
	public static function buildPostXml($type, $message)
	{
		$data = [$type => $message];

		return XML::fromArray($data, $xml);
	}

	/**
	 * Builds and transmit messages
	 *
	 * @param array  $owner        the array of the item owner
	 * @param array  $contact      Target of the communication
	 * @param string $type         The message type
	 * @param array  $message      The message data
	 * @param bool   $public_batch Is it a public post?
	 * @param string $guid         message guid
	 *
	 * @return int Result of the transmission
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function buildAndTransmit(array $owner, array $contact, $type, $message, $public_batch = false, $guid = "")
	{
		$msg = self::buildPostXml($type, $message);

		Logger::log('message: '.$msg, Logger::DATA);
		Logger::log('send guid '.$guid, Logger::DEBUG);

		// Fallback if the private key wasn't transmitted in the expected field
		if (empty($owner['uprvkey'])) {
			$owner['uprvkey'] = $owner['prvkey'];
		}

		$envelope = self::buildMessage($msg, $owner, $contact, $owner['uprvkey'], $contact['pubkey'], $public_batch);

		$return_code = self::transmit($owner, $contact, $envelope, $public_batch, $guid);

		Logger::log("guid: ".$guid." result ".$return_code, Logger::DEBUG);

		return $return_code;
	}

	/**
	 * sends a participation (Used to get all further updates)
	 *
	 * @param array $contact Target of the communication
	 * @param array $item    Item array
	 *
	 * @return int The result of the transmission
	 * @throws \Exception
	 */
	private static function sendParticipation(array $contact, array $item)
	{
		// Don't send notifications for private postings
		if ($item['private'] == Item::PRIVATE) {
			return;
		}

		$cachekey = "diaspora:sendParticipation:".$item['guid'];

		$result = DI::cache()->get($cachekey);
		if (!is_null($result)) {
			return;
		}

		// Fetch some user id to have a valid handle to transmit the participation.
		// In fact it doesn't matter which user sends this - but it is needed by the protocol.
		// If the item belongs to a user, we take this user id.
		if ($item['uid'] == 0) {
			$condition = ['verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false];
			$first_user = DBA::selectFirst('user', ['uid'], $condition);
			$owner = User::getOwnerDataById($first_user['uid']);
		} else {
			$owner = User::getOwnerDataById($item['uid']);
		}

		$author = self::myHandle($owner);

		$message = ["author" => $author,
				"guid" => System::createUUID(),
				"parent_type" => "Post",
				"parent_guid" => $item["guid"]];

		Logger::log("Send participation for ".$item["guid"]." by ".$author, Logger::DEBUG);

		// It doesn't matter what we store, we only want to avoid sending repeated notifications for the same item
		DI::cache()->set($cachekey, $item["guid"], Duration::QUARTER_HOUR);

		return self::buildAndTransmit($owner, $contact, "participation", $message);
	}

	/**
	 * sends an account migration
	 *
	 * @param array $owner   the array of the item owner
	 * @param array $contact Target of the communication
	 * @param int   $uid     User ID
	 *
	 * @return int The result of the transmission
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function sendAccountMigration(array $owner, array $contact, $uid)
	{
		$old_handle = DI::pConfig()->get($uid, 'system', 'previous_addr');
		$profile = self::createProfileData($uid);

		$signed_text = 'AccountMigration:'.$old_handle.':'.$profile['author'];
		$signature = base64_encode(Crypto::rsaSign($signed_text, $owner["uprvkey"], "sha256"));

		$message = ["author" => $old_handle,
				"profile" => $profile,
				"signature" => $signature];

		Logger::info('Send account migration', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, "account_migration", $message);
	}

	/**
	 * Sends a "share" message
	 *
	 * @param array $owner   the array of the item owner
	 * @param array $contact Target of the communication
	 *
	 * @return int The result of the transmission
	 * @throws \Exception
	 */
	public static function sendShare(array $owner, array $contact)
	{
		/**
		 * @todo support the different possible combinations of "following" and "sharing"
		 * Currently, Diaspora only interprets the "sharing" field
		 *
		 * Before switching this code productive, we have to check all "sendShare" calls if "rel" is set correctly
		 */

		/*
		switch ($contact["rel"]) {
			case Contact::FRIEND:
				$following = true;
				$sharing = true;

			case Contact::SHARING:
				$following = false;
				$sharing = true;

			case Contact::FOLLOWER:
				$following = true;
				$sharing = false;
		}
		*/

		$message = ["author" => self::myHandle($owner),
				"recipient" => $contact["addr"],
				"following" => "true",
				"sharing" => "true"];

		Logger::info('Send share', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, "contact", $message);
	}

	/**
	 * sends an "unshare"
	 *
	 * @param array $owner   the array of the item owner
	 * @param array $contact Target of the communication
	 *
	 * @return int The result of the transmission
	 * @throws \Exception
	 */
	public static function sendUnshare(array $owner, array $contact)
	{
		$message = ["author" => self::myHandle($owner),
				"recipient" => $contact["addr"],
				"following" => "false",
				"sharing" => "false"];

		Logger::info('Send unshare', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, "contact", $message);
	}

	/**
	 * Checks a message body if it is a reshare
	 *
	 * @param string $body     The message body that is to be check
	 * @param bool   $complete Should it be a complete check or a simple check?
	 *
	 * @return array|bool Reshare details or "false" if no reshare
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isReshare($body, $complete = true)
	{
		$body = trim($body);

		$reshared = Item::getShareArray(['body' => $body]);
		if (empty($reshared)) {
			return false;
		}

		// Skip if it isn't a pure repeated messages
		// Does it start with a share?
		if (!empty($reshared['comment']) && $complete) {
			return false;
		}

		if (!empty($reshared['guid']) && $complete) {
			$condition = ['guid' => $reshared['guid'], 'network' => [Protocol::DFRN, Protocol::DIASPORA]];
			$item = Item::selectFirst(['contact-id'], $condition);
			if (DBA::isResult($item)) {
				$ret = [];
				$ret["root_handle"] = self::handleFromContact($item["contact-id"]);
				$ret["root_guid"] = $reshared['guid'];
				return $ret;
			} elseif ($complete) {
				// We are resharing something that isn't a DFRN or Diaspora post.
				// So we have to return "false" on "$complete" to not trigger a reshare.
				return false;
			}
		} elseif (empty($reshared['guid']) && $complete) {
			return false;
		}

		$ret = [];

		if (!empty($reshared['profile']) && ($cid = Contact::getIdForURL($reshared['profile']))) {
			$contact = DBA::selectFirst('contact', ['addr'], ['id' => $cid]);
			if (!empty($contact['addr'])) {
				$ret['root_handle'] = $contact['addr'];
			}
		}

		if (empty($ret) && !$complete) {
			return true;
		}

		return $ret;
	}

	/**
	 * Create an event array
	 *
	 * @param integer $event_id The id of the event
	 *
	 * @return array with event data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function buildEvent($event_id)
	{
		$r = q("SELECT `guid`, `uid`, `start`, `finish`, `nofinish`, `summary`, `desc`, `location`, `adjust` FROM `event` WHERE `id` = %d", intval($event_id));
		if (!DBA::isResult($r)) {
			return [];
		}

		$event = $r[0];

		$eventdata = [];

		$r = q("SELECT `timezone` FROM `user` WHERE `uid` = %d", intval($event['uid']));
		if (!DBA::isResult($r)) {
			return [];
		}

		$user = $r[0];

		$r = q("SELECT `addr`, `nick` FROM `contact` WHERE `uid` = %d AND `self`", intval($event['uid']));
		if (!DBA::isResult($r)) {
			return [];
		}

		$owner = $r[0];

		$eventdata['author'] = self::myHandle($owner);

		if ($event['guid']) {
			$eventdata['guid'] = $event['guid'];
		}

		$mask = DateTimeFormat::ATOM;

		/// @todo - establish "all day" events in Friendica
		$eventdata["all_day"] = "false";

		$eventdata['timezone'] = 'UTC';
		if (!$event['adjust'] && $user['timezone']) {
			$eventdata['timezone'] = $user['timezone'];
		}

		if ($event['start']) {
			$eventdata['start'] = DateTimeFormat::convert($event['start'], "UTC", $eventdata['timezone'], $mask);
		}
		if ($event['finish'] && !$event['nofinish']) {
			$eventdata['end'] = DateTimeFormat::convert($event['finish'], "UTC", $eventdata['timezone'], $mask);
		}
		if ($event['summary']) {
			$eventdata['summary'] = html_entity_decode(BBCode::toMarkdown($event['summary']));
		}
		if ($event['desc']) {
			$eventdata['description'] = html_entity_decode(BBCode::toMarkdown($event['desc']));
		}
		if ($event['location']) {
			$event['location'] = preg_replace("/\[map\](.*?)\[\/map\]/ism", '$1', $event['location']);
			$coord = Map::getCoordinates($event['location']);

			$location = [];
			$location["address"] = html_entity_decode(BBCode::toMarkdown($event['location']));
			if (!empty($coord['lat']) && !empty($coord['lon'])) {
				$location["lat"] = $coord['lat'];
				$location["lng"] = $coord['lon'];
			} else {
				$location["lat"] = 0;
				$location["lng"] = 0;
			}
			$eventdata['location'] = $location;
		}

		return $eventdata;
	}

	/**
	 * Create a post (status message or reshare)
	 *
	 * @param array $item  The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array
	 * 'type' -> Message type ("status_message" or "reshare")
	 * 'message' -> Array of XML elements of the status
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function buildStatus(array $item, array $owner)
	{
		$cachekey = "diaspora:buildStatus:".$item['guid'];

		$result = DI::cache()->get($cachekey);
		if (!is_null($result)) {
			return $result;
		}

		$myaddr = self::myHandle($owner);

		$public = ($item["private"] == Item::PRIVATE ? "false" : "true");
		$created = DateTimeFormat::utc($item['received'], DateTimeFormat::ATOM);
		$edited = DateTimeFormat::utc($item["edited"] ?? $item["created"], DateTimeFormat::ATOM);

		// Detect a share element and do a reshare
		if (($item['private'] != Item::PRIVATE) && ($ret = self::isReshare($item["body"]))) {
			$message = ["author" => $myaddr,
					"guid" => $item["guid"],
					"created_at" => $created,
					"root_author" => $ret["root_handle"],
					"root_guid" => $ret["root_guid"],
					"provider_display_name" => $item["app"],
					"public" => $public];

			$type = "reshare";
		} else {
			$title = $item["title"];
			$body = $item["body"];

			// Fetch the title from an attached link - if there is one
			if (empty($item["title"]) && DI::pConfig()->get($owner['uid'], 'system', 'attach_link_title')) {
				$page_data = BBCode::getAttachmentData($item['body']);
				if (!empty($page_data['type']) && !empty($page_data['title']) && ($page_data['type'] == 'link')) {
					$title = $page_data['title'];
				}
			}

			if ($item['author-link'] != $item['owner-link']) {
				require_once 'mod/share.php';
				$body = BBCode::getShareOpeningTag($item['author-name'], $item['author-link'], $item['author-avatar'],
					$item['plink'], $item['created']) . $body . '[/share]';
			}

			// convert to markdown
			$body = html_entity_decode(BBCode::toMarkdown($body));

			// Adding the title
			if (strlen($title)) {
				$body = "### ".html_entity_decode($title)."\n\n".$body;
			}

			$attachments = Post\Media::getByURIId($item['uri-id'], [Post\Media::DOCUMENT, Post\Media::TORRENT, Post\Media::UNKNOWN]);
			if (!empty($attachments)) {
				$body .= "\n".DI::l10n()->t("Attachments:")."\n";
				foreach ($attachments as $attachment) {
					$body .= "[" . $attachment['description'] . "](" . $attachment['url'] . ")\n";
				}
			}

			$location = [];

			if ($item["location"] != "")
				$location["address"] = $item["location"];

			if ($item["coord"] != "") {
				$coord = explode(" ", $item["coord"]);
				$location["lat"] = $coord[0];
				$location["lng"] = $coord[1];
			}

			$message = ["author" => $myaddr,
					"guid" => $item["guid"],
					"created_at" => $created,
					"edited_at" => $edited,
					"public" => $public,
					"text" => $body,
					"provider_display_name" => $item["app"],
					"location" => $location];

			// Diaspora rejects messages when they contain a location without "lat" or "lng"
			if (!isset($location["lat"]) || !isset($location["lng"])) {
				unset($message["location"]);
			}

			if ($item['event-id'] > 0) {
				$event = self::buildEvent($item['event-id']);
				if (count($event)) {
					$message['event'] = $event;

					if (!empty($event['location']['address']) &&
						!empty($event['location']['lat']) &&
						!empty($event['location']['lng'])) {
						$message['location'] = $event['location'];
					}

					/// @todo Once Diaspora supports it, we will remove the body and the location hack above
					// $message['text'] = '';
				}
			}

			$type = "status_message";
		}

		$msg = ["type" => $type, "message" => $message];

		DI::cache()->set($cachekey, $msg, Duration::QUARTER_HOUR);

		return $msg;
	}

	private static function prependParentAuthorMention($body, $profile_url)
	{
		$profile = Contact::getByURL($profile_url, false, ['addr', 'name', 'contact-type']);
		if (!empty($profile['addr'])
			&& $profile['contact-type'] != Contact::TYPE_COMMUNITY
			&& !strstr($body, $profile['addr'])
			&& !strstr($body, $profile_url)
		) {
			$body = '@[url=' . $profile_url . ']' . $profile['name'] . '[/url] ' . $body;
		}

		return $body;
	}

	/**
	 * Sends a post
	 *
	 * @param array $item         The item that will be exported
	 * @param array $owner        the array of the item owner
	 * @param array $contact      Target of the communication
	 * @param bool  $public_batch Is it a public post?
	 *
	 * @return int The result of the transmission
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function sendStatus(array $item, array $owner, array $contact, $public_batch = false)
	{
		$status = self::buildStatus($item, $owner);

		return self::buildAndTransmit($owner, $contact, $status["type"], $status["message"], $public_batch, $item["guid"]);
	}

	/**
	 * Creates a "like" object
	 *
	 * @param array $item  The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array The data for a "like"
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function constructLike(array $item, array $owner)
	{
		$parent = Item::selectFirst(['guid', 'uri', 'thr-parent'], ['uri' => $item["thr-parent"]]);
		if (!DBA::isResult($parent)) {
			return false;
		}

		$target_type = ($parent["uri"] === $parent["thr-parent"] ? "Post" : "Comment");
		$positive = null;
		if ($item['verb'] === Activity::LIKE) {
			$positive = "true";
		} elseif ($item['verb'] === Activity::DISLIKE) {
			$positive = "false";
		}

		return(["author" => self::myHandle($owner),
				"guid" => $item["guid"],
				"parent_guid" => $parent["guid"],
				"parent_type" => $target_type,
				"positive" => $positive,
				"author_signature" => ""]);
	}

	/**
	 * Creates an "EventParticipation" object
	 *
	 * @param array $item  The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array The data for an "EventParticipation"
	 * @throws \Exception
	 */
	private static function constructAttend(array $item, array $owner)
	{
		$parent = Item::selectFirst(['guid'], ['uri' => $item['thr-parent']]);
		if (!DBA::isResult($parent)) {
			return false;
		}

		switch ($item['verb']) {
			case Activity::ATTEND:
				$attend_answer = 'accepted';
				break;
			case Activity::ATTENDNO:
				$attend_answer = 'declined';
				break;
			case Activity::ATTENDMAYBE:
				$attend_answer = 'tentative';
				break;
			default:
				Logger::log('Unknown verb '.$item['verb'].' in item '.$item['guid']);
				return false;
		}

		return(["author" => self::myHandle($owner),
				"guid" => $item["guid"],
				"parent_guid" => $parent["guid"],
				"status" => $attend_answer,
				"author_signature" => ""]);
	}

	/**
	 * Creates the object for a comment
	 *
	 * @param array $item  The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array|false The data for a comment
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function constructComment(array $item, array $owner)
	{
		$cachekey = "diaspora:constructComment:".$item['guid'];

		$result = DI::cache()->get($cachekey);
		if (!is_null($result)) {
			return $result;
		}

		$toplevel_item = Item::selectFirst(['guid', 'author-id', 'author-link'], ['id' => $item['parent'], 'parent' => $item['parent']]);
		if (!DBA::isResult($toplevel_item)) {
			Logger::error('Missing parent conversation item', ['parent' => $item['parent']]);
			return false;
		}

		$thread_parent_item = $toplevel_item;
		if ($item['thr-parent'] != $item['parent-uri']) {
			$thread_parent_item = Item::selectFirst(['guid', 'author-id', 'author-link'], ['uri' => $item['thr-parent'], 'uid' => $item['uid']]);
		}

		$body = $item["body"];

		// The replied to autor mention is prepended for clarity if:
		// - Item replied isn't yours
		// - Item is public or explicit mentions are disabled
		// - Implicit mentions are enabled
		if (
			$item['author-id'] != $thread_parent_item['author-id']
			&& (empty($item['uid']) || !Feature::isEnabled($item['uid'], 'explicit_mentions'))
			&& !DI::config()->get('system', 'disable_implicit_mentions')
		) {
			$body = self::prependParentAuthorMention($body, $thread_parent_item['author-link']);
		}

		$text = html_entity_decode(BBCode::toMarkdown($body));
		$created = DateTimeFormat::utc($item["created"], DateTimeFormat::ATOM);
		$edited = DateTimeFormat::utc($item["edited"], DateTimeFormat::ATOM);

		$comment = [
			"author"      => self::myHandle($owner),
			"guid"        => $item["guid"],
			"created_at"  => $created,
			"edited_at"   => $edited,
			"parent_guid" => $toplevel_item["guid"],
			"text"        => $text,
			"author_signature" => ""
		];

		// Send the thread parent guid only if it is a threaded comment
		if ($item['thr-parent'] != $item['parent-uri']) {
			$comment['thread_parent_guid'] = $thread_parent_item['guid'];
		}

		DI::cache()->set($cachekey, $comment, Duration::QUARTER_HOUR);

		return($comment);
	}

	/**
	 * Send a like or a comment
	 *
	 * @param array $item         The item that will be exported
	 * @param array $owner        the array of the item owner
	 * @param array $contact      Target of the communication
	 * @param bool  $public_batch Is it a public post?
	 *
	 * @return int The result of the transmission
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function sendFollowup(array $item, array $owner, array $contact, $public_batch = false)
	{
		if (in_array($item['verb'], [Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE])) {
			$message = self::constructAttend($item, $owner);
			$type = "event_participation";
		} elseif (in_array($item["verb"], [Activity::LIKE, Activity::DISLIKE])) {
			$message = self::constructLike($item, $owner);
			$type = "like";
		} elseif (!in_array($item["verb"], [Activity::FOLLOW, Activity::TAG])) {
			$message = self::constructComment($item, $owner);
			$type = "comment";
		}

		if (empty($message)) {
			return false;
		}

		$message["author_signature"] = self::signature($owner, $message);

		return self::buildAndTransmit($owner, $contact, $type, $message, $public_batch, $item["guid"]);
	}

	/**
	 * Creates a message from a signature record entry
	 *
	 * @param array $item The item that will be exported
	 * @return array The message
	 */
	private static function messageFromSignature(array $item)
	{
		// Split the signed text
		$signed_parts = explode(";", $item['signed_text']);

		if ($item["deleted"]) {
			$message = ["author" => $item['signer'],
					"target_guid" => $signed_parts[0],
					"target_type" => $signed_parts[1]];
		} elseif (in_array($item["verb"], [Activity::LIKE, Activity::DISLIKE])) {
			$message = ["author" => $signed_parts[4],
					"guid" => $signed_parts[1],
					"parent_guid" => $signed_parts[3],
					"parent_type" => $signed_parts[2],
					"positive" => $signed_parts[0],
					"author_signature" => $item['signature'],
					"parent_author_signature" => ""];
		} else {
			// Remove the comment guid
			$guid = array_shift($signed_parts);

			// Remove the parent guid
			$parent_guid = array_shift($signed_parts);

			// Remove the handle
			$handle = array_pop($signed_parts);

			$message = [
				"author" => $handle,
				"guid" => $guid,
				"parent_guid" => $parent_guid,
				"text" => implode(";", $signed_parts),
				"author_signature" => $item['signature'],
				"parent_author_signature" => ""
			];
		}
		return $message;
	}

	/**
	 * Relays messages (like, comment, retraction) to other servers if we are the thread owner
	 *
	 * @param array $item         The item that will be exported
	 * @param array $owner        the array of the item owner
	 * @param array $contact      Target of the communication
	 * @param bool  $public_batch Is it a public post?
	 *
	 * @return int The result of the transmission
	 * @throws \Exception
	 */
	public static function sendRelay(array $item, array $owner, array $contact, $public_batch = false)
	{
		if ($item["deleted"]) {
			return self::sendRetraction($item, $owner, $contact, $public_batch, true);
		} elseif (in_array($item["verb"], [Activity::LIKE, Activity::DISLIKE])) {
			$type = "like";
		} else {
			$type = "comment";
		}

		Logger::log("Got relayable data ".$type." for item ".$item["guid"]." (".$item["id"].")", Logger::DEBUG);

		$msg = json_decode($item['signed_text'], true);

		$message = [];
		if (is_array($msg)) {
			foreach ($msg as $field => $data) {
				if (!$item["deleted"]) {
					if ($field == "diaspora_handle") {
						$field = "author";
					}
					if ($field == "target_type") {
						$field = "parent_type";
					}
				}

				$message[$field] = $data;
			}
		} else {
			Logger::log("Signature text for item ".$item["guid"]." (".$item["id"].") couldn't be extracted: ".$item['signed_text'], Logger::DEBUG);
		}

		$message["parent_author_signature"] = self::signature($owner, $message);

		Logger::info('Relayed data', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, $type, $message, $public_batch, $item["guid"]);
	}

	/**
	 * Sends a retraction (deletion) of a message, like or comment
	 *
	 * @param array $item         The item that will be exported
	 * @param array $owner        the array of the item owner
	 * @param array $contact      Target of the communication
	 * @param bool  $public_batch Is it a public post?
	 * @param bool  $relay        Is the retraction transmitted from a relay?
	 *
	 * @return int The result of the transmission
	 * @throws \Exception
	 */
	public static function sendRetraction(array $item, array $owner, array $contact, $public_batch = false, $relay = false)
	{
		$itemaddr = self::handleFromContact($item["contact-id"], $item["author-id"]);

		$msg_type = "retraction";

		if ($item['gravity'] == GRAVITY_PARENT) {
			$target_type = "Post";
		} elseif (in_array($item["verb"], [Activity::LIKE, Activity::DISLIKE])) {
			$target_type = "Like";
		} else {
			$target_type = "Comment";
		}

		$message = ["author" => $itemaddr,
				"target_guid" => $item['guid'],
				"target_type" => $target_type];

		Logger::info('Got message', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, $msg_type, $message, $public_batch, $item["guid"]);
	}

	/**
	 * Sends a mail
	 *
	 * @param array $item    The item that will be exported
	 * @param array $owner   The owner
	 * @param array $contact Target of the communication
	 *
	 * @return int The result of the transmission
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function sendMail(array $item, array $owner, array $contact)
	{
		$myaddr = self::myHandle($owner);

		$cnv = DBA::selectFirst('conv', [], ['id' => $item["convid"], 'uid' => $item["uid"]]);
		if (!DBA::isResult($cnv)) {
			Logger::log("conversation not found.");
			return;
		}

		$body = BBCode::toMarkdown($item["body"]);
		$created = DateTimeFormat::utc($item["created"], DateTimeFormat::ATOM);

		$msg = [
			"author" => $myaddr,
			"guid" => $item["guid"],
			"conversation_guid" => $cnv["guid"],
			"text" => $body,
			"created_at" => $created,
		];

		if ($item["reply"]) {
			$message = $msg;
			$type = "message";
		} else {
			$message = [
				"author" => $cnv["creator"],
				"guid" => $cnv["guid"],
				"subject" => $cnv["subject"],
				"created_at" => DateTimeFormat::utc($cnv['created'], DateTimeFormat::ATOM),
				"participants" => $cnv["recips"],
				"message" => $msg
			];

			$type = "conversation";
		}

		return self::buildAndTransmit($owner, $contact, $type, $message, false, $item["guid"]);
	}

	/**
	 * Split a name into first name and last name
	 *
	 * @param string $name The name
	 *
	 * @return array The array with "first" and "last"
	 */
	public static function splitName($name) {
		$name = trim($name);

		// Is the name longer than 64 characters? Then cut the rest of it.
		if (strlen($name) > 64) {
			if ((strpos($name, ' ') <= 64) && (strpos($name, ' ') !== false)) {
				$name = trim(substr($name, 0, strrpos(substr($name, 0, 65), ' ')));
			} else {
				$name = substr($name, 0, 64);
			}
		}

		// Take the first word as first name
		$first = ((strpos($name, ' ') ? trim(substr($name, 0, strpos($name, ' '))) : $name));
		$last = (($first === $name) ? '' : trim(substr($name, strlen($first))));
		if ((strlen($first) < 32) && (strlen($last) < 32)) {
			return ['first' => $first, 'last' => $last];
		}

		// Take the last word as last name
		$first = ((strrpos($name, ' ') ? trim(substr($name, 0, strrpos($name, ' '))) : $name));
		$last = (($first === $name) ? '' : trim(substr($name, strlen($first))));

		if ((strlen($first) < 32) && (strlen($last) < 32)) {
			return ['first' => $first, 'last' => $last];
		}

		// Take the first 32 characters if there is no space in the first 32 characters
		if ((strpos($name, ' ') > 32) || (strpos($name, ' ') === false)) {
			$first = substr($name, 0, 32);
			$last = substr($name, 32);
			return ['first' => $first, 'last' => $last];
		}

		$first = trim(substr($name, 0, strrpos(substr($name, 0, 33), ' ')));
		$last = (($first === $name) ? '' : trim(substr($name, strlen($first))));

		// Check if the last name is longer than 32 characters
		if (strlen($last) > 32) {
			if (strpos($last, ' ') <= 32) {
				$last = trim(substr($last, 0, strrpos(substr($last, 0, 33), ' ')));
			} else {
				$last = substr($last, 0, 32);
			}
		}

		return ['first' => $first, 'last' => $last];
	}

	/**
	 * Create profile data
	 *
	 * @param int $uid The user id
	 *
	 * @return array The profile data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function createProfileData($uid)
	{
		$profile = DBA::selectFirst('owner-view', ['uid', 'addr', 'name', 'location', 'net-publish', 'dob', 'about', 'pub_keywords'], ['uid' => $uid]);
		if (!DBA::isResult($profile)) {
			return [];
		}

		$handle = $profile["addr"];

		$split_name = self::splitName($profile['name']);
		$first = $split_name['first'];
		$last = $split_name['last'];

		$large = DI::baseUrl().'/photo/custom/300/'.$profile['uid'].'.jpg';
		$medium = DI::baseUrl().'/photo/custom/100/'.$profile['uid'].'.jpg';
		$small = DI::baseUrl().'/photo/custom/50/'  .$profile['uid'].'.jpg';
		$searchable = ($profile['net-publish'] ? 'true' : 'false');

		$dob = null;
		$about = null;
		$location = null;
		$tags = null;
		if ($searchable === 'true') {
			$dob = '';

			if ($profile['dob'] && ($profile['dob'] > '0000-00-00')) {
				list($year, $month, $day) = sscanf($profile['dob'], '%4d-%2d-%2d');
				if ($year < 1004) {
					$year = 1004;
				}
				$dob = DateTimeFormat::utc($year . '-' . $month . '-'. $day, 'Y-m-d');
			}

			$about = BBCode::toMarkdown($profile['about']);

			$location = $profile['location'];
			$tags = '';
			if ($profile['pub_keywords']) {
				$kw = str_replace(',', ' ', $profile['pub_keywords']);
				$kw = str_replace('  ', ' ', $kw);
				$arr = explode(' ', $kw);
				if (count($arr)) {
					for ($x = 0; $x < 5; $x ++) {
						if (!empty($arr[$x])) {
							$tags .= '#'. trim($arr[$x]) .' ';
						}
					}
				}
			}
			$tags = trim($tags);
		}

		return ["author" => $handle,
				"first_name" => $first,
				"last_name" => $last,
				"image_url" => $large,
				"image_url_medium" => $medium,
				"image_url_small" => $small,
				"birthday" => $dob,
				"bio" => $about,
				"location" => $location,
				"searchable" => $searchable,
				"nsfw" => "false",
				"tag_string" => $tags];
	}

	/**
	 * Sends profile data
	 *
	 * @param int  $uid    The user id
	 * @param bool $recips optional, default false
	 * @return void
	 * @throws \Exception
	 */
	public static function sendProfile($uid, $recips = false)
	{
		if (!$uid) {
			return;
		}

		$owner = User::getOwnerDataById($uid);
		if (!$owner) {
			return;
		}

		if (!$recips) {
			$recips = q(
				"SELECT `id`,`name`,`network`,`pubkey`,`notify` FROM `contact` WHERE `network` = '%s'
				AND `uid` = %d AND `rel` != %d",
				DBA::escape(Protocol::DIASPORA),
				intval($uid),
				intval(Contact::SHARING)
			);
		}

		if (!$recips) {
			return;
		}

		$message = self::createProfileData($uid);

		// @ToDo Split this into single worker jobs
		foreach ($recips as $recip) {
			Logger::log("Send updated profile data for user ".$uid." to contact ".$recip["id"], Logger::DEBUG);
			self::buildAndTransmit($owner, $recip, "profile", $message);
		}
	}

	/**
	 * Creates the signature for likes that are created on our system
	 *
	 * @param integer $uid  The user of that comment
	 * @param array   $item Item array
	 *
	 * @return array Signed content
	 * @throws \Exception
	 */
	public static function createLikeSignature($uid, array $item)
	{
		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			Logger::info('No owner post, so not storing signature');
			return false;
		}

		if (!in_array($item["verb"], [Activity::LIKE, Activity::DISLIKE])) {
			return false;
		}

		$message = self::constructLike($item, $owner);
		if ($message === false) {
			return false;
		}

		$message["author_signature"] = self::signature($owner, $message);

		return $message;
	}

	/**
	 * Creates the signature for Comments that are created on our system
	 *
	 * @param integer $uid  The user of that comment
	 * @param array   $item Item array
	 *
	 * @return array Signed content
	 * @throws \Exception
	 */
	public static function createCommentSignature($uid, array $item)
	{
		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			Logger::info('No owner post, so not storing signature');
			return false;
		}

		$parent = Item::selectFirst(['parent-uri'], ['uri' => $item['thr-parent']]);
		if (!DBA::isResult($parent)) {
			return;
		}

		$item['parent-uri'] = $parent['parent-uri'];

		$message = self::constructComment($item, $owner);
		if ($message === false) {
			return false;
		}

		$message["author_signature"] = self::signature($owner, $message);

		return $message;
	}
}
