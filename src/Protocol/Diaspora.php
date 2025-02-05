<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use Friendica\Content\Feature;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\Markdown;
use Friendica\Core\Cache\Enum\Duration;
use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Core\Worker;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Model\Conversation;
use Friendica\Model\GServer;
use Friendica\Model\Item;
use Friendica\Model\ItemURI;
use Friendica\Model\Mail;
use Friendica\Model\Post;
use Friendica\Model\Tag;
use Friendica\Model\User;
use Friendica\Network\HTTPClient\Client\HttpClientAccept;
use Friendica\Network\HTTPClient\Client\HttpClientRequest;
use Friendica\Network\HTTPException;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Network\Probe;
use Friendica\Util\Crypto;
use Friendica\Util\DateTimeFormat;
use Friendica\Util\Map;
use Friendica\Util\Network;
use Friendica\Util\Proxy;
use Friendica\Util\Strings;
use Friendica\Util\XML;
use GuzzleHttp\Psr7\Uri;
use SimpleXMLElement;

/**
 * This class contains functions to communicate via the Diaspora protocol
 * @see https://diaspora.github.io/diaspora_federation/
 */
class Diaspora
{
	const PUSHED       = 0;
	const FETCHED      = 1;
	const FORCED_FETCH = 2;

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
	public static function participantsForThread(array $item, array $contacts): array
	{
		if (!in_array($item['private'], [Item::PUBLIC, Item::UNLISTED]) || in_array($item['verb'], [Activity::FOLLOW, Activity::TAG])) {
			DI::logger()->info('Item is private or a participation request. It will not be relayed', ['guid' => $item['guid'], 'private' => $item['private'], 'verb' => $item['verb']]);
			return $contacts;
		}

		$items = Post::select(
			['author-id', 'author-link', 'parent-author-link', 'parent-guid', 'guid'],
			['parent' => $item['parent'], 'gravity' => [Item::GRAVITY_COMMENT, Item::GRAVITY_ACTIVITY]]
		);
		while ($item = Post::fetch($items)) {
			$contact = DBA::selectFirst(
				'contact',
				['id', 'url', 'name', 'protocol', 'batch', 'network'],
				['id' => $item['author-id']]
			);
			if (
				!DBA::isResult($contact) || empty($contact['batch']) ||
				($contact['network'] != Protocol::DIASPORA) ||
				Strings::compareLink($item['parent-author-link'], $item['author-link'])
			) {
				continue;
			}

			$exists = false;
			foreach ($contacts as $entry) {
				if ($entry['batch'] == $contact['batch']) {
					$exists = true;
				}
			}

			if (!$exists) {
				DI::logger()->info('Add participant to receiver list', ['parent' => $item['parent-guid'], 'item' => $item['guid'], 'participant' => $contact['url']]);
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
	 * @return string|bool verified data or false on error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function verifyMagicEnvelope(string $envelope)
	{
		$basedom = XML::parseString($envelope, true);

		if (!is_object($basedom)) {
			DI::logger()->notice('Envelope is no XML file');
			return false;
		}

		$children = $basedom->children(ActivityNamespace::SALMON_ME);

		if (sizeof($children) == 0) {
			DI::logger()->notice('XML has no children');
			return false;
		}

		$handle = '';

		$data = Strings::base64UrlDecode($children->data);
		$type = $children->data->attributes()->type[0];

		$encoding = $children->encoding;

		$alg = $children->alg;

		$sig    = Strings::base64UrlDecode($children->sig);
		$key_id = $children->sig->attributes()->key_id[0];
		if ($key_id != '') {
			$handle = Strings::base64UrlDecode($key_id);
		}

		$b64url_data = Strings::base64UrlEncode($data);
		$msg         = str_replace(["\n", "\r", " ", "\t"], ['', '', '', ''], $b64url_data);

		$signable_data = $msg . '.' . Strings::base64UrlEncode($type) . '.' . Strings::base64UrlEncode($encoding) . '.' . Strings::base64UrlEncode($alg);

		if ($handle == '') {
			DI::logger()->notice('No author could be decoded. Discarding. Message: ' . $envelope);
			return false;
		}

		try {
			$key = self::key(WebFingerUri::fromString($handle));
			if ($key == '') {
				throw new \InvalidArgumentException();
			}
		} catch (\InvalidArgumentException $e) {
			DI::logger()->notice("Couldn't get a key for handle " . $handle . ". Discarding.");
			return false;
		}

		$verify = Crypto::rsaVerify($signable_data, $sig, $key);
		if (!$verify) {
			DI::logger()->notice('Message from ' . $handle . ' did not verify. Discarding.');
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
	private static function aesEncrypt(string $key, string $iv, string $data): string
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
	private static function aesDecrypt(string $key, string $iv, string $encrypted): string
	{
		return openssl_decrypt($encrypted, 'aes-256-cbc', str_pad($key, 32, "\0"), OPENSSL_RAW_DATA, str_pad($iv, 16, "\0"));
	}

	/**
	 * Decodes incoming Diaspora message in the new format. This method returns false on an error.
	 *
	 * @param string  $raw      raw post message
	 * @param string  $privKey   The private key of the importer
	 * @param boolean $no_exit  Don't do an http exit on error
	 *
	 * @return bool|array
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
			try {
				if (!isset($data->aes_key) || !isset($data->encrypted_magic_envelope)) {
					DI::logger()->info('Missing keys "aes_key" and/or "encrypted_magic_envelope"', ['data' => $data]);
					throw new \RuntimeException('Missing keys "aes_key" and/or "encrypted_magic_envelope"');
				}

				$encrypted_aes_key_bundle = base64_decode($data->aes_key);
				$ciphertext               = base64_decode($data->encrypted_magic_envelope);

				$outer_key_bundle = '';
				@openssl_private_decrypt($encrypted_aes_key_bundle, $outer_key_bundle, $privKey);
				$j_outer_key_bundle = json_decode($outer_key_bundle);

				if (!is_object($j_outer_key_bundle)) {
					DI::logger()->info('Unable to decode outer key bundle', ['outer_key_bundle' => $outer_key_bundle]);
					throw new \RuntimeException('Unable to decode outer key bundle');
				}

				if (!isset($j_outer_key_bundle->iv) || !isset($j_outer_key_bundle->key)) {
					DI::logger()->info('Missing keys "iv" and/or "key" from outer Salmon', ['j_outer_key_bundle' => $j_outer_key_bundle]);
					throw new \RuntimeException('Missing keys "iv" and/or "key" from outer Salmon');
				}

				$outer_iv  = base64_decode($j_outer_key_bundle->iv);
				$outer_key = base64_decode($j_outer_key_bundle->key);

				$xml = self::aesDecrypt($outer_key, $outer_iv, $ciphertext);
			} catch (\Throwable $e) {
				DI::logger()->notice('Outer Salmon did not verify. Discarding.');
				if ($no_exit) {
					return false;
				} else {
					throw new HTTPException\BadRequestException();
				}
			}
		} else {
			$xml = $raw;
		}

		$basedom = XML::parseString($xml, true);

		if (!is_object($basedom)) {
			DI::logger()->notice('Received data does not seem to be an XML. Discarding. ' . $xml);
			if ($no_exit) {
				return false;
			} else {
				throw new HTTPException\BadRequestException();
			}
		}

		$base = $basedom->children(ActivityNamespace::SALMON_ME);

		// Not sure if this cleaning is needed
		$data = str_replace([" ", "\t", "\r", "\n"], ['', '', '', ''], $base->data);

		// Build the signed data
		$type        = $base->data[0]->attributes()->type[0];
		$encoding    = $base->encoding;
		$alg         = $base->alg;
		$signed_data = $data . '.' . Strings::base64UrlEncode($type) . '.' . Strings::base64UrlEncode($encoding) . '.' . Strings::base64UrlEncode($alg);

		// This is the signature
		$signature = Strings::base64UrlDecode($base->sig);

		// Get the senders' public key
		$key_id      = $base->sig[0]->attributes()->key_id[0];
		$author_addr = base64_decode($key_id);
		if ($author_addr == '') {
			DI::logger()->notice('No author could be decoded. Discarding. Message: ' . $xml);
			if ($no_exit) {
				return false;
			} else {
				throw new HTTPException\BadRequestException();
			}
		}

		try {
			$author = WebFingerUri::fromString($author_addr);
			$key    = self::key($author);
			if ($key == '') {
				throw new \InvalidArgumentException();
			}
		} catch (\InvalidArgumentException $e) {
			DI::logger()->notice("Couldn't get a key for handle " . $author_addr . ". Discarding.");
			if ($no_exit) {
				return false;
			} else {
				throw new HTTPException\BadRequestException();
			}
		}

		$verify = Crypto::rsaVerify($signed_data, $signature, $key);
		if (!$verify) {
			DI::logger()->notice('Message did not verify. Discarding.');
			if ($no_exit) {
				return false;
			} else {
				throw new HTTPException\BadRequestException();
			}
		}

		return [
			'message' => (string)Strings::base64UrlDecode($base->data),
			'author'  => $author->getAddr(),
			'key'     => (string)$key
		];
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
		$public  = false;
		$basedom = XML::parseString($xml);

		if (!is_object($basedom)) {
			DI::logger()->notice('XML is not parseable.');
			return false;
		}
		$children = $basedom->children('https://joindiaspora.com/protocol');

		$inner_aes_key = null;
		$inner_iv      = null;

		if ($children->header) {
			$public = true;
			$idom   = $children->header;
		} else {
			// This happens with posts from a relais
			if (empty($privKey)) {
				DI::logger()->info('This is no private post in the old format');
				return false;
			}

			$encrypted_header = json_decode(base64_decode($children->encrypted_header));

			$encrypted_aes_key_bundle = base64_decode($encrypted_header->aes_key);
			$ciphertext               = base64_decode($encrypted_header->ciphertext);

			$outer_key_bundle = '';
			openssl_private_decrypt($encrypted_aes_key_bundle, $outer_key_bundle, $privKey);

			$j_outer_key_bundle = json_decode($outer_key_bundle);

			$outer_iv  = base64_decode($j_outer_key_bundle->iv);
			$outer_key = base64_decode($j_outer_key_bundle->key);

			$decrypted = self::aesDecrypt($outer_key, $outer_iv, $ciphertext);

			DI::logger()->info('decrypted', ['data' => $decrypted]);
			$idom = XML::parseString($decrypted);

			$inner_iv      = base64_decode($idom->iv);
			$inner_aes_key = base64_decode($idom->aes_key);
		}

		try {
			$author = WebFingerUri::fromString($idom->author_id);
		} catch (\Throwable $e) {
			DI::logger()->notice('Could not retrieve author URI.', ['idom' => $idom]);
			throw new \Friendica\Network\HTTPException\BadRequestException();
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
			DI::logger()->notice('unable to locate salmon data in xml');
			throw new HTTPException\BadRequestException();
		}


		// Stash the signature away for now. We have to find their key or it won't be good for anything.
		$signature = Strings::base64UrlDecode($base->sig);

		// unpack the  data

		// strip whitespace so our data element will return to one big base64 blob
		$data = str_replace([" ", "\t", "\r", "\n"], ['', '', '', ''], $base->data);


		// stash away some other stuff for later

		$type     = $base->data[0]->attributes()->type[0];
		$keyhash  = $base->sig[0]->attributes()->keyhash[0];
		$encoding = $base->encoding;
		$alg      = $base->alg;

		$signed_data = $data . '.' . Strings::base64UrlEncode($type) . '.' . Strings::base64UrlEncode($encoding) . '.' . Strings::base64UrlEncode($alg);

		// decode the data
		$data = Strings::base64UrlDecode($data);

		if ($public) {
			$inner_decrypted = $data;
		} else {
			// Decode the encrypted blob
			$inner_encrypted = base64_decode($data);
			$inner_decrypted = self::aesDecrypt($inner_aes_key, $inner_iv, $inner_encrypted);
		}

		// Once we have the author URI, go to the web and try to find their public key
		// (first this will look it up locally if it is in the diaspora-contact cache)
		// This will also convert diaspora public key from pkcs#1 to pkcs#8
		DI::logger()->info('Fetching key for ' . $author);
		$key = self::key($author);
		if (!$key) {
			DI::logger()->notice('Could not retrieve author key.');
			throw new HTTPException\BadRequestException();
		}

		$verify = Crypto::rsaVerify($signed_data, $signature, $key);

		if (!$verify) {
			DI::logger()->notice('Message did not verify. Discarding.');
			throw new HTTPException\BadRequestException();
		}

		DI::logger()->info('Message verified.');

		return [
			'message' => $inner_decrypted,
			'author'  => $author->getAddr(),
			'key'     => $key
		];
	}


	/**
	 * Dispatches public messages and find the fitting receivers
	 *
	 * @param array $msg       The post that will be dispatched
	 * @param int   $direction Indicates if the message had been fetched or pushed (self::PUSHED, self::FETCHED, self::FORCED_FETCH)
	 *
	 * @return int|bool The message id of the generated message, "true" or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function dispatchPublic(array $msg, int $direction)
	{
		if (!DI::config()->get('system', 'diaspora_enabled')) {
			DI::logger()->notice('Diaspora is disabled');
			return false;
		}

		if (!($fields = self::validPosting($msg))) {
			DI::logger()->notice('Invalid posting', ['msg' => $msg]);
			return false;
		}

		$importer = [
			'uid'        => 0,
			'page-flags' => User::PAGE_FLAGS_FREELOVE
		];
		$success = self::dispatch($importer, $msg, $fields, $direction);

		return $success;
	}

	/**
	 * Dispatches the different message types to the different functions
	 *
	 * @param array            $importer  Array of the importer user
	 * @param array            $msg       The post that will be dispatched
	 * @param SimpleXMLElement $fields    SimpleXML object that contains the message
	 * @param int              $direction Indicates if the message had been fetched or pushed (self::PUSHED, self::FETCHED, self::FORCED_FETCH)
	 *
	 * @return int|bool The message id of the generated message, "true" or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function dispatch(array $importer, array $msg, SimpleXMLElement $fields = null, int $direction = self::PUSHED)
	{
		// The sender is the handle of the contact that sent the message.
		// This will often be different with relayed messages (for example "like" and "comment")
		$sender = WebFingerUri::fromString($msg['author']);

		// This is only needed for private postings since this is already done for public ones before
		if (is_null($fields)) {
			$private = true;
			if (!($fields = self::validPosting($msg))) {
				DI::logger()->notice('Invalid posting', ['msg' => $msg]);
				return false;
			}
		} else {
			$private = false;
		}

		$type = $fields->getName();

		DI::logger()->info('Received message', ['type' => $type, 'sender' => $sender->getAddr(), 'user' => $importer['uid']]);

		switch ($type) {
			case 'account_migration':
				if (!$private) {
					DI::logger()->notice('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveAccountMigration($importer, $fields);

			case 'account_deletion':
				return self::receiveAccountDeletion($fields);

			case 'comment':
				return self::receiveComment($importer, $sender, $fields, $msg['message'], $direction);

			case 'contact':
				if (!$private) {
					DI::logger()->notice('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveContactRequest($importer, $fields);

			case 'conversation':
				if (!$private) {
					DI::logger()->notice('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveConversation($importer, $msg, $fields);

			case 'like':
				return self::receiveLike($importer, $sender, $fields, $direction);

			case 'message':
				if (!$private) {
					DI::logger()->notice('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveMessage($importer, $fields);

			case 'participation':
				if (!$private) {
					DI::logger()->notice('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveParticipation($importer, $fields, $direction);

			case 'photo': // Not implemented
				return self::receivePhoto($importer, $fields);

			case 'poll_participation': // Not implemented
				return self::receivePollParticipation($importer, $fields);

			case 'profile':
				if (!$private) {
					DI::logger()->notice('Message with type ' . $type . ' is not private, quitting.');
					return false;
				}
				return self::receiveProfile($importer, $fields);

			case 'reshare':
				return self::receiveReshare($importer, $fields, $msg['message'], $direction);

			case 'retraction':
				return self::receiveRetraction($importer, $sender, $fields);

			case 'status_message':
				return self::receiveStatusMessage($importer, $fields, $msg['message'], $direction);

			default:
				DI::logger()->notice('Unknown message type ' . $type);
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
	private static function validPosting(array $msg)
	{
		$element = XML::parseString($msg['message']);

		if (!is_object($element)) {
			DI::logger()->info('No valid XML', ['message' => $msg['message']]);
			return false;
		}

		$oldXML = false;

		// Is this the new or the old version?
		if ($element->getName() === 'XML') {
			$oldXML = true;
			foreach ($element->post->children() as $child) {
				$element = $child;
			}
		}

		$type      = $element->getName();
		$orig_type = $type;

		DI::logger()->debug('Got message', ['type' => $type, 'message' => $msg['message']]);

		// All retractions are handled identically from now on.
		// In the new version there will only be "retraction".
		if (in_array($type, ['signed_retraction', 'relayable_retraction'])) {
			$type = 'retraction';
		}

		if ($type == 'request') {
			$type = 'contact';
		}

		$fields = new SimpleXMLElement('<' . $type . '/>');

		$signed_data             = '';
		$author_signature        = null;
		$parent_author_signature = null;

		foreach ($element->children() as $fieldname => $entry) {
			if ($oldXML) {
				// Translation for the old XML structure
				if ($fieldname == 'diaspora_handle') {
					$fieldname = 'author';
				}
				if ($fieldname == 'participant_handles') {
					$fieldname = 'participants';
				}
				if (in_array($type, ['like', 'participation'])) {
					if ($fieldname == 'target_type') {
						$fieldname = 'parent_type';
					}
				}
				if ($fieldname == 'sender_handle') {
					$fieldname = 'author';
				}
				if ($fieldname == 'recipient_handle') {
					$fieldname = 'recipient';
				}
				if ($fieldname == 'root_diaspora_id') {
					$fieldname = 'root_author';
				}
				if ($type == 'status_message') {
					if ($fieldname == 'raw_message') {
						$fieldname = 'text';
					}
				}
				if ($type == 'retraction') {
					if ($fieldname == 'post_guid') {
						$fieldname = 'target_guid';
					}
					if ($fieldname == 'type') {
						$fieldname = 'target_type';
					}
				}
			}

			if (($fieldname == 'author_signature') && ($entry != '')) {
				$author_signature = base64_decode($entry);
			} elseif (($fieldname == 'parent_author_signature') && ($entry != '')) {
				$parent_author_signature = base64_decode($entry);
			} elseif (!in_array($fieldname, ['author_signature', 'parent_author_signature', 'target_author_signature'])) {
				if ($signed_data != '') {
					$signed_data .= ';';
				}

				$signed_data .= $entry;
			}
			if (
				!in_array($fieldname, ['parent_author_signature', 'target_author_signature'])
				|| ($orig_type == 'relayable_retraction')
			) {
				XML::copy($entry, $fields, $fieldname);
			}
		}

		// This is something that shouldn't happen at all.
		if (in_array($type, ['status_message', 'reshare', 'profile'])) {
			if ($msg['author'] != $fields->author) {
				DI::logger()->notice('Message handle is not the same as envelope sender. Quitting this message.', ['author1' => $msg['author'], 'author2' => $fields->author]);
				return false;
			}
		}

		// Only some message types have signatures. So we quit here for the other types.
		if (!in_array($type, ['comment', 'like'])) {
			return $fields;
		}

		if (!isset($author_signature) && ($msg['author'] == $fields->author)) {
			DI::logger()->debug('No author signature, but the sender matches the author', ['type' => $type, 'msg-author' => $msg['author'], 'message' => $msg['message']]);
			return $fields;
		}

		// No author_signature? This is a must, so we quit.
		if (!isset($author_signature)) {
			DI::logger()->info('No author signature', ['type' => $type, 'msg-author' => $msg['author'], 'fields-author' => $fields->author, 'message' => $msg['message']]);
			return false;
		}

		if (isset($parent_author_signature)) {
			$key = self::key(WebFingerUri::fromString($msg['author']));
			if (empty($key)) {
				DI::logger()->info('No key found for parent', ['author' => $msg['author']]);
				return false;
			}

			if (!Crypto::rsaVerify($signed_data, $parent_author_signature, $key, 'sha256')) {
				DI::logger()->info('No valid parent author signature', ['author' => $msg['author'], 'type' => $type, 'signed data' => $signed_data, 'message' => $msg['message'], 'signature' => $parent_author_signature]);
				return false;
			}
		}

		try {
			$key = self::key(WebFingerUri::fromString($fields->author));
			if (empty($key)) {
				throw new \InvalidArgumentException();
			}
		} catch (\Throwable $e) {
			DI::logger()->info('No key found', ['author' => $fields->author]);
			return false;
		}

		if (!Crypto::rsaVerify($signed_data, $author_signature, $key, 'sha256')) {
			DI::logger()->info('No valid author signature for author', ['author' => $fields->author, 'type' => $type, 'signed data' => $signed_data, 'message' => $msg['message'], 'signature' => $author_signature]);
			return false;
		} else {
			return $fields;
		}
	}

	/**
	 * Fetches the public key for a given handle
	 *
	 * @param WebFingerUri $uri The handle
	 *
	 * @return string The public key
	 * @throws NotFoundException
	 * @throws \ImagickException
	 */
	private static function key(WebFingerUri $uri): string
	{
		DI::logger()->info('Fetching diaspora key', ['handle' => $uri->getAddr()]);
		try {
			return DI::dsprContact()->getByAddr($uri)->pubKey;
		} catch (NotFoundException | \InvalidArgumentException $e) {
			return '';
		}
	}

	/**
	 * Get a contact id for a given handle
	 *
	 * @param int          $uid The user id
	 * @param WebFingerUri $uri
	 *
	 * @return array Contact data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function contactByHandle(int $uid, WebFingerUri $uri): array
	{
		Contact::updateByUrlIfNeeded($uri->getAddr());
		return Contact::getByURL($uri->getAddr(), null, [], $uid);
	}

	/**
	 * Checks if the given contact url does support ActivityPub
	 *
	 * @param string       $url    profile url or WebFinger address
	 * @param boolean|null $update true = always update, false = never update, null = update when not found or outdated
	 * @return boolean
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function isSupportedByContactUrl(string $url, ?bool $update = null): bool
	{
		$contact = Contact::getByURL($url, $update, ['uri-id', 'network']);

		$supported = DI::dsprContact()->existsByUriId($contact['uri-id'] ?? 0);

		if (!$supported && is_null($update) && ($contact['network'] == Protocol::DFRN)) {
			$supported = self::isSupportedByContactUrl($url, true);
		}

		return $supported;
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
	private static function postAllow(array $importer, array $contact, bool $is_comment = false): bool
	{
		/*
		 * Perhaps we were already sharing with this person. Now they're sharing with us.
		 * That makes us friends.
		 * Normally this should have handled by getting a request - but this could get lost
		 */
		// It is deactivated by now, due to side effects. See issue https://github.com/friendica/friendica/pull/4033
		// It is not removed by now. Possibly the code is needed?
		//if (!$is_comment && $contact["rel"] == Contact::FOLLOWER && in_array($importer["page-flags"], array(User::PAGE_FLAGS_FREELOVE))) {
		//	Contact::update(
		//		array('rel' => Contact::FRIEND, 'writable' => true),
		//		array('id' => $contact["id"], 'uid' => $contact["uid"])
		//	);
		//
		//	$contact["rel"] = Contact::FRIEND;
		//	DI::logger()->notice("defining user ".$contact["nick"]." as friend");
		//}

		// Contact server is blocked
		if (Network::isUrlBlocked($contact['url'])) {
			return false;
			// We don't seem to like that person
		} elseif ($contact['blocked']) {
			// Maybe blocked, don't accept.
			return false;
			// We are following this person?
		} elseif (($contact['rel'] == Contact::SHARING) || ($contact['rel'] == Contact::FRIEND)) {
			// Yes, then it is fine.
			return true;
			// Is the message a global user or a comment?
		} elseif (($importer['uid'] == 0) || $is_comment) {
			// Messages for the global users and comments are always accepted
			return true;
		}

		return false;
	}

	/**
	 * Fetches the contact id for a handle and checks if posting is allowed
	 *
	 * @param array        $importer    Array of the importer user
	 * @param WebFingerUri $contact_uri The checked contact
	 * @param bool         $is_comment  Is the check for a comment?
	 *
	 * @return array|bool The contact data or false on error
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function allowedContactByHandle(array $importer, WebFingerUri $contact_uri, bool $is_comment = false)
	{
		$contact = self::contactByHandle($importer['uid'], $contact_uri);
		if (!$contact) {
			DI::logger()->notice('A Contact for handle ' . $contact_uri . ' and user ' . $importer['uid'] . ' was not found');
			// If a contact isn't found, we accept it anyway if it is a comment
			if ($is_comment && ($importer['uid'] != 0)) {
				return self::contactByHandle(0, $contact_uri);
			} elseif ($is_comment) {
				return $importer;
			} else {
				return false;
			}
		}

		if (!self::postAllow($importer, $contact, $is_comment)) {
			DI::logger()->notice('The handle: ' . $contact_uri . ' is not allowed to post to user ' . $importer['uid']);
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
	private static function messageExists(int $uid, string $guid)
	{
		$item = Post::selectFirst(['id'], ['uid' => $uid, 'guid' => $guid]);
		if (DBA::isResult($item)) {
			DI::logger()->notice('Message already exists.', ['uid' => $uid, 'guid' => $guid, 'id' => $item['id']]);
			return $item['id'];
		}

		return false;
	}

	/**
	 * Checks for links to posts in a message
	 *
	 * @param array $item The item array
	 *
	 * @return void
	 */
	private static function fetchGuid(array $item)
	{
		preg_replace_callback(
			"=diaspora://.*?/post/([0-9A-Za-z\-_@.:]{15,254}[0-9A-Za-z])=ism",
			function ($match) use ($item) {
				self::fetchGuidSub($match, $item);
			},
			$item['body']
		);

		preg_replace_callback(
			"&\[url=/?posts/([^\[\]]*)\](.*)\[\/url\]&Usi",
			function ($match) use ($item) {
				self::fetchGuidSub($match, $item);
			},
			$item['body']
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
	public static function replacePeopleGuid(string $body, string $author_link): string
	{
		$return = preg_replace_callback(
			"&\[url=/people/([^\[\]]*)\](.*)\[\/url\]&Usi",
			function ($match) use ($author_link) {
				// $match
				// 0 => '[url=/people/0123456789abcdef]Foo Bar[/url]'
				// 1 => '0123456789abcdef'
				// 2 => 'Foo Bar'
				$handle = DI::dsprContact()->getUrlByGuid($match[1]);

				if ($handle) {
					$return = '@[url=' . $handle . ']' . $match[2] . '[/url]';
				} else {
					// No local match, restoring absolute remote URL from author scheme and host
					$author_url = parse_url($author_link);
					$return     = '[url=' . $author_url['scheme'] . '://' . $author_url['host'] . '/people/' . $match[1] . ']' . $match[2] . '[/url]';
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
	private static function fetchGuidSub(array $match, array $item)
	{
		if (!self::storeByGuid($match[1], $item['author-link'], true)) {
			self::storeByGuid($match[1], $item['owner-link'], true);
		}
	}

	/**
	 * Fetches an item with a given guid from a given server
	 *
	 * @param string $guid   the message guid
	 * @param string $server The server address
	 * @param bool   $force  Forced fetch
	 *
	 * @return int|bool the message id of the stored message or false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function storeByGuid(string $guid, string $server, bool $force)
	{
		$serverparts = parse_url($server);

		if (empty($serverparts['host']) || empty($serverparts['scheme'])) {
			return false;
		}

		$server = $serverparts['scheme'] . '://' . $serverparts['host'];

		DI::logger()->info('Trying to fetch item ' . $guid . ' from ' . $server);

		$msg = self::message($guid, $server);

		if (!$msg) {
			return false;
		}

		DI::logger()->info('Successfully fetched item ' . $guid . ' from ' . $server);

		// Now call the dispatcher
		return self::dispatchPublic($msg, $force ? self::FORCED_FETCH : self::FETCHED);
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
	public static function message(string $guid, string $server, int $level = 0)
	{
		if ($level > 5) {
			return false;
		}

		// This will work for new Diaspora servers and Friendica servers from 3.5
		$source_url = $server . '/fetch/post/' . urlencode($guid);

		DI::logger()->info('Fetch post from ' . $source_url);

		$envelope = DI::httpClient()->fetch($source_url, HttpClientAccept::MAGIC, 0, '', HttpClientRequest::DIASPORA);
		if ($envelope) {
			DI::logger()->info('Envelope was fetched.');
			$x = self::verifyMagicEnvelope($envelope);
			if (!$x) {
				DI::logger()->info('Envelope could not be verified.');
			} else {
				DI::logger()->info('Envelope was verified.');
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
			DI::logger()->info('Message is a reshare');
			return self::message($source_xml->post->reshare->root_guid, $server, ++$level);
		} elseif ($source_xml->getName() == 'reshare') {
			// Reshare of a reshare - new Diaspora version
			DI::logger()->info('Message is a new reshare');
			return self::message($source_xml->root_guid, $server, ++$level);
		}

		$author_handle = '';

		// Fetch the author - for the old and the new Diaspora version
		if ($source_xml->post->status_message && $source_xml->post->status_message->diaspora_handle) {
			$author_handle = (string)$source_xml->post->status_message->diaspora_handle;
		} elseif ($source_xml->author && ($source_xml->getName() == 'status_message')) {
			$author_handle = (string)$source_xml->author;
		}

		try {
			$author = WebFingerUri::fromString($author_handle);
		} catch (\InvalidArgumentException $e) {
			// If this isn't a "status_message" then quit
			DI::logger()->info("Message doesn't seem to be a status message");
			return false;
		}

		return [
			'message' => $x,
			'author'  => $author->getAddr(),
			'key'     => self::key($author)
		];
	}

	/**
	 * Fetches an item with a given URL
	 *
	 * @param string $url the message url
	 * @param int $uid User id
	 *
	 * @return int|bool the message id of the stored message or false
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function fetchByURL(string $url, int $uid = 0)
	{
		// Check for Diaspora (and Friendica) typical paths
		if (!preg_match('=(https?://.+)/(?:posts|display|objects)/([a-zA-Z0-9-_@.:%]+[a-zA-Z0-9])=i', $url, $matches)) {
			DI::logger()->notice('Invalid url', ['url' => $url]);
			return false;
		}

		$guid = urldecode($matches[2]);

		$item = Post::selectFirst(['id'], ['guid' => $guid, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			DI::logger()->info('Found', ['id' => $item['id']]);
			return $item['id'];
		}

		DI::logger()->info('Fetch GUID from origin', ['guid' => $guid, 'server' => $matches[1]]);
		$ret = self::storeByGuid($guid, $matches[1], true);
		DI::logger()->info('Result', ['ret' => $ret]);

		$item = Post::selectFirst(['id'], ['guid' => $guid, 'uid' => $uid]);
		if (DBA::isResult($item)) {
			DI::logger()->info('Found', ['id' => $item['id']]);
			return $item['id'];
		} else {
			DI::logger()->notice('Not found', ['guid' => $guid, 'uid' => $uid]);
			return false;
		}
	}

	/**
	 * Fetches the item record of a given guid
	 *
	 * @param int          $uid     The user id
	 * @param string       $guid    message guid
	 * @param WebFingerUri $author
	 * @param array        $contact The contact of the item owner
	 *
	 * @return array|bool the item record or false on failure
	 * @throws \Exception
	 */
	private static function parentItem(int $uid, string $guid, WebFingerUri $author, array $contact)
	{
		$fields = [
			'id', 'parent', 'body', 'wall', 'uri', 'guid', 'private', 'origin',
			'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid',
			'author-name', 'author-link', 'author-avatar', 'gravity',
			'owner-name', 'owner-link', 'owner-avatar'
		];

		$condition = ['uid' => $uid, 'guid' => $guid];
		$item      = Post::selectFirst($fields, $condition);

		if (!DBA::isResult($item)) {
			try {
				$result = self::storeByGuid($guid, DI::dsprContact()->getByAddr($author)->url, false);

				// We don't have an url for items that arrived at the public dispatcher
				if (!$result && !empty($contact['url'])) {
					$result = self::storeByGuid($guid, $contact['url'], false);
				}

				if ($result) {
					DI::logger()->info('Fetched missing item ' . $guid . ' - result: ' . $result);

					$item = Post::selectFirst($fields, $condition);
				}
			} catch (HTTPException\NotFoundException $e) {
				DI::logger()->notice('Unable to retrieve author details', ['author' => $author->getAddr()]);
			}
		}

		if (!DBA::isResult($item)) {
			DI::logger()->notice('Parent item not found: parent: ' . $guid . ' - user: ' . $uid);
			return false;
		} else {
			DI::logger()->info('Parent item found: parent: ' . $guid . ' - user: ' . $uid);
			return $item;
		}
	}

	/**
	 * returns contact details for the given user
	 *
	 * @param array  $def_contact The default details if the contact isn't found
	 * @param string $contact_url The url of the contact
	 * @param int    $uid         The user id
	 *
	 * @return array
	 *      'cid' => contact id
	 *      'network' => network type
	 * @throws \Exception
	 */
	private static function authorContactByUrl(array $def_contact, string $contact_url, int $uid): array
	{
		$condition = ['nurl' => Strings::normaliseLink($contact_url), 'uid' => $uid];
		$contact   = DBA::selectFirst('contact', ['id', 'network'], $condition);
		if (DBA::isResult($contact)) {
			$cid     = $contact['id'];
			$network = $contact['network'];
		} else {
			$cid     = $def_contact['id'];
			$network = Protocol::DIASPORA;
		}

		return [
			'cid'     => $cid,
			'network' => $network
		];
	}

	/**
	 * Is the profile a hubzilla profile?
	 *
	 * @param string $url The profile link
	 *
	 * @return bool is it a hubzilla server?
	 */
	private static function isHubzilla(string $url): bool
	{
		return strstr($url, '/channel/');
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
	private static function plink(string $addr, string $guid, string $parent_guid = ''): string
	{
		$contact = Contact::getByURL($addr);
		if (empty($contact)) {
			DI::logger()->info('No contact data for address', ['addr' => $addr]);
			return '';
		}

		if (empty($contact['baseurl'])) {
			$contact['baseurl'] = 'https://' . substr($addr, strpos($addr, '@') + 1);
			DI::logger()->info('Create baseurl from address', ['baseurl' => $contact['baseurl'], 'url' => $contact['url']]);
		}

		$platform = '';
		$gserver  = DBA::selectFirst('gserver', ['platform'], ['nurl' => Strings::normaliseLink($contact['baseurl'])]);
		if (!empty($gserver['platform'])) {
			$platform = strtolower($gserver['platform']);
			DI::logger()->info('Detected platform', ['platform' => $platform, 'url' => $contact['url']]);
		}

		if (!in_array($platform, ['diaspora', 'friendica', 'hubzilla', 'socialhome'])) {
			if (self::isHubzilla($contact['url'])) {
				DI::logger()->info('Detected unknown platform as Hubzilla', ['platform' => $platform, 'url' => $contact['url']]);
				$platform = 'hubzilla';
			} elseif ($contact['network'] == Protocol::DFRN) {
				DI::logger()->info('Detected unknown platform as Friendica', ['platform' => $platform, 'url' => $contact['url']]);
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
			DI::logger()->info('Unknown platform', ['platform' => $platform, 'url' => $contact['url']]);
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
	 * @param SimpleXMLElement $data The message object
	 *
	 * @return bool Success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveAccountMigration(array $importer, SimpleXMLElement $data): bool
	{
		try {
			$old_author = WebFingerUri::fromString(XML::unescape($data->author));
			$new_author = WebFingerUri::fromString(XML::unescape($data->profile->author));
		} catch (\Throwable $e) {
			DI::logger()->notice('Cannot find handles for sender and user', ['data' => $data]);
			return false;
		}

		$signature = XML::unescape($data->signature);

		$contact = self::contactByHandle($importer['uid'], $old_author);
		if (!$contact) {
			DI::logger()->notice('Cannot find contact for sender: ' . $old_author . ' and user ' . $importer['uid']);
			return false;
		}

		DI::logger()->info('Got migration for ' . $old_author . ', to ' . $new_author . ' with user ' . $importer['uid']);

		// Check signature
		$signed_text = 'AccountMigration:' . $old_author . ':' . $new_author;
		$key         = self::key($old_author);
		if (!Crypto::rsaVerify($signed_text, $signature, $key, 'sha256')) {
			DI::logger()->notice('No valid signature for migration.');
			return false;
		}

		// Update the profile
		self::receiveProfile($importer, $data->profile);

		// change the technical stuff in contact
		$data = Probe::uri($new_author);
		if ($data['network'] == Protocol::PHANTOM) {
			DI::logger()->notice("Account for " . $new_author . " couldn't be probed.");
			return false;
		}

		$fields = [
			'url'     => $data['url'],
			'nurl'    => Strings::normaliseLink($data['url']),
			'name'    => $data['name'],
			'nick'    => $data['nick'],
			'addr'    => $data['addr'],
			'batch'   => $data['batch'],
			'notify'  => $data['notify'],
			'poll'    => $data['poll'],
			'network' => $data['network'],
		];

		Contact::update($fields, ['addr' => $old_author->getAddr()]);

		DI::logger()->info('Contacts are updated.');

		return true;
	}

	/**
	 * Processes an account deletion
	 *
	 * @param SimpleXMLElement $data The message object
	 *
	 * @return bool Success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function receiveAccountDeletion(SimpleXMLElement $data): bool
	{
		$author_handle = XML::unescape($data->author);

		$contacts = DBA::select('contact', ['id'], ['addr' => $author_handle]);
		while ($contact = DBA::fetch($contacts)) {
			Contact::remove($contact['id']);
		}
		DBA::close($contacts);

		DI::logger()->info('Removed contacts for ' . $author_handle);

		return true;
	}

	/**
	 * Fetch the uri from our database if we already have this item (maybe from ourselves)
	 *
	 * @param string            $guid       Message guid
	 * @param WebFingerUri|null $person_uri Optional person to derive the base URL from
	 *
	 * @return string The constructed uri or the one from our database or empty string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function getUriFromGuid(string $guid, WebFingerUri $person_uri = null): string
	{
		$item = Post::selectFirst(['uri'], ['guid' => $guid]);
		if ($item) {
			return $item['uri'];
		} elseif ($person_uri) {
			try {
				return DI::dsprContact()->selectOneByAddr($person_uri)->baseurl . '/objects/' . $guid;
			} catch (HTTPException\NotFoundException | \InvalidArgumentException $e) {
				return '';
			}
		}

		return '';
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
			if ($match === '') {
				continue;
			}

			try {
				$contact = DI::dsprContact()->getByUrl(new Uri($match[3]));
				Tag::storeByHash($uriid, $match[1], $contact->name ?: $contact->nick, $contact->url);
			} catch (\Throwable $e) {
			}
		}
	}

	/**
	 * Processes an incoming comment
	 *
	 * @param array            $importer  Array of the importer user
	 * @param WebFingerUri     $sender    The sender of the message
	 * @param SimpleXMLElement $data      The message object
	 * @param string           $xml       The original XML of the message
	 * @param int              $direction Indicates if the message had been fetched or pushed (self::PUSHED, self::FETCHED, self::FORCED_FETCH)
	 *
	 * @return bool The message id of the generated comment or "false" if there was an error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveComment(array $importer, WebFingerUri $sender, SimpleXMLElement $data, string $xml, int $direction): bool
	{
		$author      = WebFingerUri::fromString(XML::unescape($data->author));
		$guid        = XML::unescape($data->guid);
		$parent_guid = XML::unescape($data->parent_guid);
		$text        = XML::unescape($data->text);

		if (isset($data->created_at)) {
			$created_at = DateTimeFormat::utc(XML::unescape($data->created_at));
		} else {
			$created_at = DateTimeFormat::utcNow();
		}

		if (isset($data->thread_parent_guid)) {
			$thread_parent_guid = XML::unescape($data->thread_parent_guid);
			$thr_parent         = self::getUriFromGuid($thread_parent_guid);
		} else {
			$thr_parent = '';
		}

		$contact = self::allowedContactByHandle($importer, $sender, true);
		if (!$contact) {
			return false;
		}

		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::DIASPORA);
		}

		$message_id = self::messageExists($importer['uid'], $guid);
		if ($message_id) {
			return true;
		}

		$toplevel_parent_item = self::parentItem($importer['uid'], $parent_guid, $author, $contact);
		if (!$toplevel_parent_item) {
			return false;
		}

		try {
			$author_url = (string)DI::dsprContact()->getByAddr($author)->url;
		} catch (HTTPException\NotFoundException | \InvalidArgumentException $e) {
			DI::logger()->notice('Unable to find author details', ['author' => $author->getAddr()]);
			return false;
		}

		// Fetch the contact id - if we know this contact
		$author_contact = self::authorContactByUrl($contact, $author_url, $importer['uid']);

		$datarray = [];

		$datarray['uid']        = $importer['uid'];
		$datarray['contact-id'] = $author_contact['cid'];
		$datarray['network']    = $author_contact['network'];

		$datarray['author-link'] = $author_url;
		$datarray['author-id']   = Contact::getIdForURL($author_url);

		$datarray['owner-link'] = $contact['url'];
		$datarray['owner-id']   = Contact::getIdForURL($contact['url']);

		// Will be overwritten for sharing accounts in Item::insert
		$datarray = self::setDirection($datarray, $direction);

		$datarray['guid']   = $guid;
		$datarray['uri']    = self::getUriFromGuid($guid, $author);
		$datarray['uri-id'] = ItemURI::insert(['uri' => $datarray['uri'], 'guid' => $datarray['guid']]);

		$datarray['verb']    = Activity::POST;
		$datarray['gravity'] = Item::GRAVITY_COMMENT;

		$datarray['private']   = $toplevel_parent_item['private'];
		$datarray['allow_cid'] = $toplevel_parent_item['allow_cid'];
		$datarray['allow_gid'] = $toplevel_parent_item['allow_gid'];
		$datarray['deny_cid']  = $toplevel_parent_item['deny_cid'];
		$datarray['deny_gid']  = $toplevel_parent_item['deny_gid'];

		$datarray['thr-parent'] = $thr_parent ?: $toplevel_parent_item['uri'];

		$datarray['object-type'] = Activity\ObjectType::COMMENT;
		$datarray['post-type']   = Item::PT_NOTE;

		$datarray['protocol'] = Conversation::PARCEL_DIASPORA;
		$datarray['source']   = $xml;

		$datarray = self::setDirection($datarray, $direction);

		$datarray['changed'] = $datarray['created'] = $datarray['edited'] = $created_at;

		$datarray['plink'] = self::plink($author, $guid, $toplevel_parent_item['guid']);
		$body              = Markdown::toBBCode($text);

		$datarray['body'] = self::replacePeopleGuid($body, $author_url);

		self::storeMentions($datarray['uri-id'], $text);
		Tag::storeRawTagsFromBody($datarray['uri-id'], $datarray['body']);

		self::fetchGuid($datarray);

		// If we are the origin of the parent we store the original data.
		// We notify our followers during the item storage.
		if ($toplevel_parent_item['origin']) {
			$datarray['diaspora_signed_text'] = json_encode($data);
		}

		if (DI::contentItem()->isTooOld($datarray['created'], $datarray['uid'])) {
			DI::logger()->info('Comment is too old', ['created' => $datarray['created'], 'uid' => $datarray['uid'], 'guid' => $datarray['guid']]);
			return false;
		}

		$message_id = Item::insert($datarray);

		if ($message_id <= 0) {
			return false;
		}

		if ($message_id) {
			DI::logger()->info('Stored comment ' . $datarray['guid'] . ' with message id ' . $message_id);
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
	 * @param SimpleXMLElement $data         The message object
	 * @param array  $msg          Array of the processed message, author handle and key
	 * @param object $mesg         The private message
	 * @param array  $conversation The conversation record to which this message belongs
	 *
	 * @return bool "true" if it was successful
	 * @throws \Exception
	 * @todo Find type-hint for $mesg and update documentation
	 */
	private static function receiveConversationMessage(array $importer, array $contact, SimpleXMLElement $data, array $msg, $mesg, array $conversation): bool
	{
		$author_handle = XML::unescape($data->author);
		$guid          = XML::unescape($data->guid);
		$subject       = XML::unescape($data->subject);

		// "diaspora_handle" is the element name from the old version
		// "author" is the element name from the new version
		if ($mesg->author) {
			$msg_author_handle = XML::unescape($mesg->author);
		} elseif ($mesg->diaspora_handle) {
			$msg_author_handle = XML::unescape($mesg->diaspora_handle);
		} else {
			return false;
		}

		try {
			$msg_author_uri = WebFingerUri::fromString($msg_author_handle);
		} catch (\InvalidArgumentException $e) {
			return false;
		}

		$msg_guid              = XML::unescape($mesg->guid);
		$msg_conversation_guid = XML::unescape($mesg->conversation_guid);
		$msg_text              = XML::unescape($mesg->text);
		$msg_created_at        = DateTimeFormat::utc(XML::unescape($mesg->created_at));

		if ($msg_conversation_guid != $guid) {
			DI::logger()->notice('Message conversation guid does not belong to the current conversation.', ['guid' => $guid]);
			return false;
		}

		$msg_author = DI::dsprContact()->getByAddr($msg_author_uri);

		return Mail::insert([
			'uid'        => $importer['uid'],
			'guid'       => $msg_guid,
			'convid'     => $conversation['id'],
			'from-name'  => $msg_author->name,
			'from-photo' => (string)$msg_author->photo,
			'from-url'   => (string)$msg_author->url,
			'contact-id' => $contact['id'],
			'title'      => $subject,
			'body'       => Markdown::toBBCode($msg_text),
			'uri'        => $msg_author_handle . ':' . $msg_guid,
			'parent-uri' => $author_handle . ':' . $guid,
			'created'    => $msg_created_at
		]);
	}

	/**
	 * Processes new private messages (answers to private messages are processed elsewhere)
	 *
	 * @param array  $importer Array of the importer user
	 * @param array  $msg      Array of the processed message, author handle and key
	 * @param SimpleXMLElement $data     The message object
	 *
	 * @return bool Success
	 * @throws \Exception
	 */
	private static function receiveConversation(array $importer, array $msg, SimpleXMLElement $data)
	{
		$author_handle = XML::unescape($data->author);
		$guid          = XML::unescape($data->guid);
		$subject       = XML::unescape($data->subject);
		$created_at    = DateTimeFormat::utc(XML::unescape($data->created_at));
		$participants  = XML::unescape($data->participants);

		$messages = $data->message;

		if (!count($messages)) {
			DI::logger()->notice('Empty conversation');
			return false;
		}

		$contact = self::allowedContactByHandle($importer, WebFingerUri::fromString($msg['author']), true);
		if (!$contact) {
			return false;
		}

		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::DIASPORA);
		}

		$conversation = DBA::selectFirst('conv', [], ['uid' => $importer['uid'], 'guid' => $guid]);
		if (!DBA::isResult($conversation)) {
			$r = DBA::insert('conv', [
				'uid'     => $importer['uid'],
				'guid'    => $guid,
				'creator' => $author_handle,
				'created' => $created_at,
				'updated' => DateTimeFormat::utcNow(),
				'subject' => $subject,
				'recips'  => $participants
			]);

			if ($r) {
				$conversation = DBA::selectFirst('conv', [], ['uid' => $importer['uid'], 'guid' => $guid]);
			}
		}
		if (!$conversation) {
			DI::logger()->warning('Unable to create conversation.');
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
	 * @param array            $importer  Array of the importer user
	 * @param WebFingerUri     $sender    The sender of the message
	 * @param SimpleXMLElement $data      The message object
	 * @param int              $direction Indicates if the message had been fetched or pushed (self::PUSHED, self::FETCHED, self::FORCED_FETCH)
	 *
	 * @return bool Success or failure
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveLike(array $importer, WebFingerUri $sender, SimpleXMLElement $data, int $direction): bool
	{
		$author      = WebFingerUri::fromString(XML::unescape($data->author));
		$guid        = XML::unescape($data->guid);
		$parent_guid = XML::unescape($data->parent_guid);
		$parent_type = XML::unescape($data->parent_type);
		$positive    = XML::unescape($data->positive);

		// likes on comments aren't supported by Diaspora - only on posts
		// But maybe this will be supported in the future, so we will accept it.
		if (!in_array($parent_type, ['Post', 'Comment'])) {
			return false;
		}

		$contact = self::allowedContactByHandle($importer, $sender, true);
		if (!$contact) {
			return false;
		}

		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::DIASPORA);
		}

		$message_id = self::messageExists($importer['uid'], $guid);
		if ($message_id) {
			return true;
		}

		$toplevel_parent_item = self::parentItem($importer['uid'], $parent_guid, $author, $contact);
		if (!$toplevel_parent_item) {
			return false;
		}

		try {
			$author_url = (string)DI::dsprContact()->getByAddr($author)->url;
		} catch (HTTPException\NotFoundException | \InvalidArgumentException $e) {
			DI::logger()->notice('Unable to find author details', ['author' => $author->getAddr()]);
			return false;
		}

		// Fetch the contact id - if we know this contact
		$author_contact = self::authorContactByUrl($contact, $author_url, $importer['uid']);

		// "positive" = "false" would be a Dislike - wich isn't currently supported by Diaspora
		// We would accept this anyhow.
		if ($positive == 'true') {
			$verb = Activity::LIKE;
		} else {
			$verb = Activity::DISLIKE;
		}

		$datarray = [];

		$datarray['protocol'] = Conversation::PARCEL_DIASPORA;

		$datarray['uid']        = $importer['uid'];
		$datarray['contact-id'] = $author_contact['cid'];
		$datarray['network']    = $author_contact['network'];

		$datarray = self::setDirection($datarray, $direction);

		$datarray['owner-link'] = $datarray['author-link'] = $author_url;
		$datarray['owner-id']   = $datarray['author-id'] = Contact::getIdForURL($author_url);

		$datarray['guid'] = $guid;
		$datarray['uri']  = self::getUriFromGuid($guid, $author);

		$datarray['verb']    = $verb;
		$datarray['gravity'] = Item::GRAVITY_ACTIVITY;

		$datarray['private']   = $toplevel_parent_item['private'];
		$datarray['allow_cid'] = $toplevel_parent_item['allow_cid'];
		$datarray['allow_gid'] = $toplevel_parent_item['allow_gid'];
		$datarray['deny_cid']  = $toplevel_parent_item['deny_cid'];
		$datarray['deny_gid']  = $toplevel_parent_item['deny_gid'];

		$datarray['thr-parent'] = $toplevel_parent_item['uri'];

		$datarray['object-type'] = Activity\ObjectType::NOTE;

		$datarray['body'] = $verb;

		// Diaspora doesn't provide a date for likes
		$datarray['changed'] = $datarray['created'] = $datarray['edited'] = DateTimeFormat::utcNow();

		// like on comments have the comment as parent. So we need to fetch the toplevel parent
		if ($toplevel_parent_item['gravity'] != Item::GRAVITY_PARENT) {
			$toplevel = Post::selectFirst(['origin'], ['id' => $toplevel_parent_item['parent']]);
			$origin   = $toplevel['origin'];
		} else {
			$origin = $toplevel_parent_item['origin'];
		}

		// If we are the origin of the parent we store the original data.
		// We notify our followers during the item storage.
		if ($origin) {
			$datarray['diaspora_signed_text'] = json_encode($data);
		}

		if (DI::contentItem()->isTooOld($datarray['created'], $datarray['uid'])) {
			DI::logger()->info('Like is too old', ['created' => $datarray['created'], 'uid' => $datarray['uid'], 'guid' => $datarray['guid']]);
			return false;
		}

		$message_id = Item::insert($datarray);

		if ($message_id <= 0) {
			return false;
		}

		if ($message_id) {
			DI::logger()->info('Stored like ' . $datarray['guid'] . ' with message id ' . $message_id);
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
	 * @param SimpleXMLElement $data     The message object
	 *
	 * @return bool Success?
	 * @throws \Exception
	 */
	private static function receiveMessage(array $importer, SimpleXMLElement $data): bool
	{
		$author_uri        = WebFingerUri::fromString(XML::unescape($data->author));
		$guid              = XML::unescape($data->guid);
		$conversation_guid = XML::unescape($data->conversation_guid);
		$text              = XML::unescape($data->text);
		$created_at        = DateTimeFormat::utc(XML::unescape($data->created_at));

		$contact = self::allowedContactByHandle($importer, $author_uri, true);
		if (!$contact) {
			return false;
		}

		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::DIASPORA);
		}

		$condition    = ['uid' => $importer['uid'], 'guid' => $conversation_guid];
		$conversation = DBA::selectFirst('conv', [], $condition);
		if (!DBA::isResult($conversation)) {
			DI::logger()->notice('Conversation not available.');
			return false;
		}

		try {
			$author = DI::dsprContact()->getByAddr($author_uri);
		} catch (HTTPException\NotFoundException | \InvalidArgumentException $e) {
			DI::logger()->notice('Unable to find author details', ['author' => $author_uri->getAddr()]);
			return false;
		}

		$body = Markdown::toBBCode($text);

		$body = self::replacePeopleGuid($body, $author->url);

		return Mail::insert([
			'uid'        => $importer['uid'],
			'guid'       => $guid,
			'convid'     => $conversation['id'],
			'from-name'  => $author->name,
			'from-photo' => (string)$author->photo,
			'from-url'   => (string)$author->url,
			'contact-id' => $contact['id'],
			'title'      => $conversation['subject'],
			'body'       => $body,
			'reply'      => 1,
			'uri'        => $author_uri . ':' . $guid,
			'parent-uri' => $author_uri . ':' . $conversation['guid'],
			'created'    => $created_at
		]);
	}

	/**
	 * Processes participations - unsupported by now
	 *
	 * @param array  $importer  Array of the importer user
	 * @param SimpleXMLElement $data      The message object
	 * @param int    $direction Indicates if the message had been fetched or pushed (self::PUSHED, self::FETCHED, self::FORCED_FETCH)
	 *
	 * @return bool success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveParticipation(array $importer, SimpleXMLElement $data, int $direction): bool
	{
		$author      = WebFingerUri::fromString(strtolower(XML::unescape($data->author)));
		$guid        = XML::unescape($data->guid);
		$parent_guid = XML::unescape($data->parent_guid);

		$contact = self::allowedContactByHandle($importer, $author, true);
		if (!$contact) {
			return false;
		}

		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::DIASPORA);
		}

		if (self::messageExists($importer['uid'], $guid)) {
			return true;
		}

		$toplevel_parent_item = self::parentItem($importer['uid'], $parent_guid, $author, $contact);
		if (!$toplevel_parent_item) {
			return false;
		}

		if (!$toplevel_parent_item['origin']) {
			DI::logger()->info('Not our origin. Participation is ignored', ['parent_guid' => $parent_guid, 'guid' => $guid, 'author' => $author]);
		}

		if (!in_array($toplevel_parent_item['private'], [Item::PUBLIC, Item::UNLISTED])) {
			DI::logger()->info('Item is not public, participation is ignored', ['parent_guid' => $parent_guid, 'guid' => $guid, 'author' => $author]);
			return false;
		}

		try {
			$author_url = (string)DI::dsprContact()->getByAddr($author)->url;
		} catch (HTTPException\NotFoundException | \InvalidArgumentException $e) {
			DI::logger()->notice('unable to find author details', ['author' => $author->getAddr()]);
			return false;
		}

		$author_contact = self::authorContactByUrl($contact, $author_url, $importer['uid']);

		// Store participation
		$datarray = [];

		$datarray['protocol'] = Conversation::PARCEL_DIASPORA;

		$datarray['uid']        = $importer['uid'];
		$datarray['contact-id'] = $author_contact['cid'];
		$datarray['network']    = $author_contact['network'];

		$datarray = self::setDirection($datarray, $direction);

		$datarray['owner-link'] = $datarray['author-link'] = $author_url;
		$datarray['owner-id']   = $datarray['author-id'] = Contact::getIdForURL($author_url);

		$datarray['guid'] = $guid;
		$datarray['uri']  = self::getUriFromGuid($guid, $author);

		$datarray['verb']       = Activity::FOLLOW;
		$datarray['gravity']    = Item::GRAVITY_ACTIVITY;
		$datarray['thr-parent'] = $toplevel_parent_item['uri'];

		$datarray['object-type'] = Activity\ObjectType::NOTE;

		$datarray['body'] = Activity::FOLLOW;

		// Diaspora doesn't provide a date for a participation
		$datarray['changed'] = $datarray['created'] = $datarray['edited'] = DateTimeFormat::utcNow();

		if (DI::contentItem()->isTooOld($datarray['created'], $datarray['uid'])) {
			DI::logger()->info('Participation is too old', ['created' => $datarray['created'], 'uid' => $datarray['uid'], 'guid' => $datarray['guid']]);
			return false;
		}

		$message_id = Item::insert($datarray);

		DI::logger()->info('Participation stored', ['id' => $message_id, 'guid' => $guid, 'parent_guid' => $parent_guid, 'author' => $author]);

		// Send all existing comments and likes to the requesting server
		$comments = Post::select(
			['id', 'uri-id', 'parent-author-network', 'author-network', 'verb', 'gravity'],
			['parent' => $toplevel_parent_item['id'], 'gravity' => [Item::GRAVITY_COMMENT, Item::GRAVITY_ACTIVITY]]
		);
		while ($comment = Post::fetch($comments)) {
			if (($comment['gravity'] == Item::GRAVITY_ACTIVITY) && !in_array($comment['verb'], [Activity::LIKE, Activity::DISLIKE])) {
				DI::logger()->info('Unsupported activities are not relayed', ['item' => $comment['id'], 'verb' => $comment['verb']]);
				continue;
			}

			if ($comment['author-network'] == Protocol::ACTIVITYPUB) {
				DI::logger()->info('Comments from ActivityPub authors are not relayed', ['item' => $comment['id']]);
				continue;
			}

			if ($comment['parent-author-network'] == Protocol::ACTIVITYPUB) {
				DI::logger()->info('Comments to comments from ActivityPub authors are not relayed', ['item' => $comment['id']]);
				continue;
			}

			DI::logger()->info('Deliver participation', ['item' => $comment['id'], 'contact' => $author_contact['cid']]);
			if (Worker::add(Worker::PRIORITY_HIGH, 'Delivery', Delivery::POST, $comment['uri-id'], $author_contact['cid'], $datarray['uid'])) {
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
	 * @param SimpleXMLElement $data     The message object
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
	 * Processes poll participations - unsupported
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
	 * @param SimpleXMLElement $data     The message object
	 *
	 * @return bool Success
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveProfile(array $importer, SimpleXMLElement $data): bool
	{
		$author = WebFingerUri::fromString(strtolower(XML::unescape($data->author)));

		$contact = self::contactByHandle($importer['uid'], $author);
		if (!$contact) {
			return false;
		}

		$name       = XML::unescape($data->first_name) . ((strlen($data->last_name)) ? ' ' . XML::unescape($data->last_name) : '');
		$image_url  = XML::unescape($data->image_url);
		$birthday   = XML::unescape($data->birthday);
		$about      = Markdown::toBBCode(XML::unescape($data->bio));
		$location   = Markdown::toBBCode(XML::unescape($data->location));
		$searchable = (XML::unescape($data->searchable) == 'true');
		$nsfw       = (XML::unescape($data->nsfw) == 'true');
		$tags       = XML::unescape($data->tag_string);

		$tags = explode('#', $tags);

		$keywords = [];
		foreach ($tags as $tag) {
			$tag = trim(strtolower($tag));
			if ($tag != '') {
				$keywords[] = $tag;
			}
		}

		$keywords = implode(', ', $keywords);

		if ($name === '') {
			$name = $author->getUser();
		}

		if (preg_match('|^https?://|', $image_url) === 0) {
			// @TODO No HTTPS here?
			$image_url = 'http://' . $author->getFullHost() . $image_url;
		}

		Contact::updateAvatar($contact['id'], $image_url);

		// Generic birthday. We don't know the timezone. The year is irrelevant.

		$birthday = str_replace('1000', '1901', $birthday);

		if ($birthday != '') {
			$birthday = DateTimeFormat::utc($birthday, 'Y-m-d');
		}

		// this is to prevent multiple birthday notifications in a single year
		// if we already have a stored birthday and the 'm-d' part hasn't changed, preserve the entry, which will preserve the notify year

		if (substr($birthday, 5) === substr($contact['bd'], 5)) {
			$birthday = $contact['bd'];
		}

		$fields = [
			'name'         => $name, 'location' => $location,
			'name-date'    => DateTimeFormat::utcNow(), 'about' => $about,
			'addr'         => $author->getAddr(), 'nick' => $author->getUser(), 'keywords' => $keywords,
			'unsearchable' => !$searchable, 'sensitive' => $nsfw
		];

		if (!empty($birthday)) {
			$fields['bd'] = $birthday;
		}

		Contact::update($fields, ['id' => $contact['id']]);

		DI::logger()->info('Profile of contact ' . $contact['id'] . ' stored for user ' . $importer['uid']);

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
		if ($contact['rel'] == Contact::SHARING) {
			Contact::update(
				['rel' => Contact::FRIEND, 'writable' => true],
				['id' => $contact['id'], 'uid' => $importer['uid']]
			);
		}
	}

	/**
	 * Processes incoming sharing notification
	 *
	 * @param array  $importer Array of the importer user
	 * @param SimpleXMLElement $data     The message object
	 *
	 * @return bool Success
	 * @throws \Exception
	 */
	private static function receiveContactRequest(array $importer, SimpleXMLElement $data): bool
	{
		$author_handle = XML::unescape($data->author);
		$recipient     = XML::unescape($data->recipient);

		if (!$author_handle || !$recipient) {
			return false;
		}

		$author = WebFingerUri::fromString($author_handle);

		// the current protocol version doesn't know these fields
		// That means that we will assume their existence
		if (isset($data->following)) {
			$following = (XML::unescape($data->following) == 'true');
		} else {
			$following = true;
		}

		if (isset($data->sharing)) {
			$sharing = (XML::unescape($data->sharing) == 'true');
		} else {
			$sharing = true;
		}

		$contact = self::contactByHandle($importer['uid'], $author);

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends.
		if ($contact) {
			if ($following) {
				DI::logger()->info('Author ' . $author . ' (Contact ' . $contact['id'] . ') wants to follow us.');
				self::receiveRequestMakeFriend($importer, $contact);

				// refetch the contact array
				$contact = self::contactByHandle($importer['uid'], $author);

				// If we are now friends, we are sending a share message.
				// Normally we needn't to do so, but the first message could have been vanished.
				if (in_array($contact['rel'], [Contact::FRIEND])) {
					$user = DBA::selectFirst('user', [], ['uid' => $importer['uid']]);
					if (DBA::isResult($user)) {
						DI::logger()->info('Sending share message to author ' . $author . ' - Contact: ' . $contact['id'] . ' - User: ' . $importer['uid']);
						self::sendShare($user, $contact);
					}
				}
				return true;
			} else {
				DI::logger()->info("Author " . $author . " doesn't want to follow us anymore.");
				Contact::removeFollower($contact);
				return true;
			}
		}

		if (!$following && $sharing && in_array($importer['page-flags'], [User::PAGE_FLAGS_SOAPBOX, User::PAGE_FLAGS_NORMAL])) {
			DI::logger()->info("Author " . $author . " wants to share with us - but doesn't want to listen. Request is ignored.");
			return false;
		} elseif (!$following && !$sharing) {
			DI::logger()->info("Author " . $author . " doesn't want anything - and we don't know the author. Request is ignored.");
			return false;
		} elseif (!$following && $sharing) {
			DI::logger()->info("Author " . $author . " wants to share with us.");
		} elseif ($following && $sharing) {
			DI::logger()->info("Author " . $author . " wants to have a bidirectional connection.");
		} elseif ($following && !$sharing) {
			DI::logger()->info("Author " . $author . " wants to listen to us.");
		}

		try {
			$author_url = (string)DI::dsprContact()->getByAddr($author)->url;
		} catch (HTTPException\NotFoundException | \InvalidArgumentException $e) {
			DI::logger()->notice('Cannot resolve diaspora handle for recipient', ['author' => $author->getAddr(), 'recipient' => $recipient]);
			return false;
		}

		$cid = Contact::getIdForURL($author_url, $importer['uid']);
		if (!empty($cid)) {
			$contact = DBA::selectFirst('contact', [], ['id' => $cid, 'network' => Protocol::NATIVE_SUPPORT]);
		} else {
			$contact = [];
		}

		$item = [
			'author-id'   => Contact::getIdForURL($author_url),
			'author-link' => $author_url
		];

		$result = Contact::addRelationship($importer, $contact, $item, false);
		if ($result === true) {
			$contact_record = self::contactByHandle($importer['uid'], $author);
			if (!$contact_record) {
				DI::logger()->info('unable to locate newly created contact record.');
				return false;
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
	 * Processes a reshare message
	 *
	 * @param array  $importer  Array of the importer user
	 * @param SimpleXMLElement $data      The message object
	 * @param string $xml       The original XML of the message
	 * @param int    $direction Indicates if the message had been fetched or pushed (self::PUSHED, self::FETCHED, self::FORCED_FETCH)
	 *
	 * @return bool Success or failure
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveReshare(array $importer, SimpleXMLElement $data, string $xml, int $direction): bool
	{
		$author     = WebFingerUri::fromString(XML::unescape($data->author));
		$guid       = XML::unescape($data->guid);
		$created_at = DateTimeFormat::utc(XML::unescape($data->created_at));
		try {
			$root_author = WebFingerUri::fromString(XML::unescape($data->root_author));
		} catch (\InvalidArgumentException $e) {
			return false;
		}

		$root_guid = XML::unescape($data->root_guid);
		/// @todo handle unprocessed property "provider_display_name"
		$public = XML::unescape($data->public);

		$contact = self::allowedContactByHandle($importer, $author);
		if (!$contact) {
			return false;
		}

		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::DIASPORA);
		}

		$message_id = self::messageExists($importer['uid'], $guid);
		if ($message_id) {
			return true;
		}

		try {
			$original_person = DI::dsprContact()->getByAddr($root_author);
		} catch (HTTPException\NotFoundException $e) {
			return false;
		}

		$datarray = [];

		$datarray['uid']        = $importer['uid'];
		$datarray['contact-id'] = $contact['id'];
		$datarray['network']    = Protocol::DIASPORA;

		$datarray['author-link'] = $contact['url'];
		$datarray['author-id']   = Contact::getIdForURL($contact['url'], 0);

		$datarray['owner-link'] = $datarray['author-link'];
		$datarray['owner-id']   = $datarray['author-id'];

		$datarray['guid']   = $guid;
		$datarray['uri']    = $datarray['thr-parent'] = self::getUriFromGuid($guid, $author);
		$datarray['uri-id'] = ItemURI::insert(['uri' => $datarray['uri'], 'guid' => $datarray['guid']]);

		$datarray['verb']    = Activity::POST;
		$datarray['gravity'] = Item::GRAVITY_PARENT;

		$datarray['protocol'] = Conversation::PARCEL_DIASPORA;
		$datarray['source']   = $xml;

		$datarray = self::setDirection($datarray, $direction);

		$datarray['quote-uri-id'] = self::getQuoteUriId($root_guid, $importer['uid'], $original_person->url);
		if (empty($datarray['quote-uri-id'])) {
			return false;
		}

		$datarray['body']    = '';
		$datarray['plink']   = self::plink($author, $guid);
		$datarray['private'] = (($public == 'false') ? Item::PRIVATE : Item::PUBLIC);
		$datarray['changed'] = $datarray['created'] = $datarray['edited'] = $created_at;

		self::fetchGuid($datarray);

		if (DI::contentItem()->isTooOld($datarray['created'], $datarray['uid'])) {
			DI::logger()->info('Reshare is too old', ['created' => $datarray['created'], 'uid' => $datarray['uid'], 'guid' => $datarray['guid']]);
			return false;
		}

		$message_id = Item::insert($datarray);

		self::sendParticipation($contact, $datarray);

		if ($message_id) {
			DI::logger()->info('Stored reshare ' . $datarray['guid'] . ' with message id ' . $message_id);
			if ($datarray['uid'] == 0) {
				Item::distribute($message_id);
			}
			return true;
		} else {
			return false;
		}
	}

	private static function getQuoteUriId(string $guid, int $uid, string $host): int
	{
		$shared_item = Post::selectFirst(['uri-id'], ['guid' => $guid, 'uid' => [$uid, 0], 'private' => [Item::PUBLIC, Item::UNLISTED]]);

		if (!DBA::isResult($shared_item) && !empty($host) && Diaspora::storeByGuid($guid, $host, true)) {
			DI::logger()->debug('Fetched post', ['guid' => $guid, 'host' => $host, 'uid' => $uid]);
			$shared_item = Post::selectFirst(['uri-id'], ['guid' => $guid, 'uid' => [$uid, 0], 'private' => [Item::PUBLIC, Item::UNLISTED]]);
		} elseif (DBA::isResult($shared_item)) {
			DI::logger()->debug('Found existing post', ['guid' => $guid, 'host' => $host, 'uid' => $uid]);
		}

		if (!DBA::isResult($shared_item)) {
			DI::logger()->notice('Post does not exist.', ['guid' => $guid, 'host' => $host, 'uid' => $uid]);
			return 0;
		}

		return $shared_item['uri-id'];
	}

	/**
	 * Processes retractions
	 *
	 * @param array  $importer Array of the importer user
	 * @param array  $contact  The contact of the item owner
	 * @param SimpleXMLElement $data     The message object
	 *
	 * @return bool success
	 * @throws \Exception
	 */
	private static function itemRetraction(array $importer, array $contact, SimpleXMLElement $data): bool
	{
		$author_uri  = WebFingerUri::fromString(XML::unescape($data->author));
		$target_guid = XML::unescape($data->target_guid);
		$target_type = XML::unescape($data->target_type);

		try {
			$author = DI::dsprContact()->getByAddr($author_uri);
		} catch (HTTPException\NotFoundException | \InvalidArgumentException $e) {
			DI::logger()->notice('Unable to find details for author', ['author' => $author_uri->getAddr()]);
			return false;
		}

		$contact_url = $contact['url'] ?? '' ?: (string)$author->url;

		// Fetch items that are about to be deleted
		$fields = ['uid', 'id', 'parent', 'author-link', 'uri-id'];

		// When we receive a public retraction, we delete every item that we find.
		if ($importer['uid'] == 0) {
			$condition = ['guid' => $target_guid, 'deleted' => false];
		} else {
			$condition = ['guid' => $target_guid, 'deleted' => false, 'uid' => $importer['uid']];
		}

		$r = Post::select($fields, $condition);
		if (!DBA::isResult($r)) {
			DI::logger()->notice('Target guid ' . $target_guid . ' was not found on this system for user ' . $importer['uid'] . '.');
			return false;
		}

		while ($item = Post::fetch($r)) {
			if (DBA::exists('post-category', ['uri-id' => $item['uri-id'], 'uid' => $item['uid'], 'type' => Post\Category::FILE])) {
				DI::logger()->info("Target guid " . $target_guid . " for user " . $item['uid'] . " is filed. So it won't be deleted.");
				continue;
			}

			// Fetch the parent item
			$parent = Post::selectFirst(['author-link'], ['id' => $item['parent']]);

			// Only delete it if the parent author really fits
			if (!Strings::compareLink($parent['author-link'], $contact_url) && !Strings::compareLink($item['author-link'], $contact_url)) {
				DI::logger()->info("Thread author " . $parent['author-link'] . " and item author " . $item['author-link'] . " don't fit to expected contact " . $contact_url);
				continue;
			}

			Item::markForDeletion(['id' => $item['id']]);

			DI::logger()->info('Deleted target ' . $target_guid . ' (' . $item['id'] . ') from user ' . $item['uid'] . ' parent: ' . $item['parent']);
		}
		DBA::close($r);

		return true;
	}

	/**
	 * Receives retraction messages
	 *
	 * @param array            $importer Array of the importer user
	 * @param WebFingerUri     $sender   The sender of the message
	 * @param SimpleXMLElement $data     The message object
	 *
	 * @return bool Success
	 * @throws \Exception
	 */
	private static function receiveRetraction(array $importer, WebFingerUri $sender, SimpleXMLElement $data)
	{
		$target_type = XML::unescape($data->target_type);

		$contact = self::contactByHandle($importer['uid'], $sender);
		if (!$contact && (in_array($target_type, ['Contact', 'Person']))) {
			DI::logger()->notice('Cannot find contact for sender: ' . $sender . ' and user ' . $importer['uid']);
			return false;
		}

		if (!$contact) {
			$contact = [];
		}

		DI::logger()->info('Got retraction for ' . $target_type . ', sender ' . $sender . ' and user ' . $importer['uid']);

		switch ($target_type) {
			case 'Comment':
			case 'Like':
			case 'Post':
			case 'Reshare':
			case 'StatusMessage':
				return self::itemRetraction($importer, $contact, $data);

			case 'PollParticipation':
			case 'Photo':
				// Currently unsupported
				break;

			default:
				DI::logger()->notice('Unknown target type ' . $target_type);
				return false;
		}
		return true;
	}

	/**
	 * Checks if an incoming message is wanted
	 *
	 * @param array  $item
	 * @param string $author
	 * @param string $body
	 * @param int    $direction Indicates if the message had been fetched or pushed (self::PUSHED, self::FETCHED, self::FORCED_FETCH)
	 *
	 * @return boolean Is the message wanted?
	 */
	private static function isSolicitedMessage(array $item, string $author, string $body, int $direction): bool
	{
		$contact = Contact::getByURL($author);
		if (DBA::exists('contact', ['`nurl` = ? AND `uid` != ? AND `rel` IN (?, ?)', $contact['nurl'], 0, Contact::FRIEND, Contact::SHARING])) {
			DI::logger()->debug('Author has got followers - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri'], 'author' => $author]);
			return true;
		}

		if ($direction == self::FORCED_FETCH) {
			DI::logger()->debug('Post is a forced fetch - accepted', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri'], 'author' => $author]);
			return true;
		}

		$tags = array_column(Tag::getByURIId($item['uri-id'], [Tag::HASHTAG]), 'name');
		if (Relay::isSolicitedPost($tags, $body, $contact['id'], $item['uri'], Protocol::DIASPORA)) {
			DI::logger()->debug('Post is accepted because of the relay settings', ['uri-id' => $item['uri-id'], 'guid' => $item['guid'], 'url' => $item['uri'], 'author' => $author]);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Store an attached photo in the post-media table
	 *
	 * @param int $uriid
	 * @param object $photo
	 *
	 * @return void
	 */
	private static function storePhotoAsMedia(int $uriid, $photo)
	{
		// @TODO Need to find object type, roland@f.haeder.net
		DI::logger()->debug('photo=' . get_class($photo));
		$data = [
			'uri-id'      => $uriid,
			'type'        => Post\Media::IMAGE,
			'url'         => XML::unescape($photo->remote_photo_path) . XML::unescape($photo->remote_photo_name),
			'height'      => (int)XML::unescape($photo->height ?? 0),
			'width'       => (int)XML::unescape($photo->width ?? 0),
			'description' => XML::unescape($photo->text ?? ''),
		];

		Post\Media::insert($data);
	}

	/**
	 * Set direction and post reason
	 *
	 * @param array $datarray
	 * @param integer $direction
	 *
	 * @return array
	 */
	public static function setDirection(array $datarray, int $direction): array
	{
		$datarray['direction'] = in_array($direction, [self::FETCHED, self::FORCED_FETCH]) ? Conversation::PULL : Conversation::PUSH;

		if (in_array($direction, [self::FETCHED, self::FORCED_FETCH])) {
			$datarray['post-reason'] = Item::PR_FETCHED;
		} elseif ($datarray['uid'] == 0) {
			$datarray['post-reason'] = Item::PR_GLOBAL;
		} else {
			$datarray['post-reason'] = Item::PR_PUSHED;
		}

		return $datarray;
	}

	/**
	 * Receives status messages
	 *
	 * @param array            $importer  Array of the importer user
	 * @param SimpleXMLElement $data      The message object
	 * @param string           $xml       The original XML of the message
	 * @param int              $direction Indicates if the message had been fetched or pushed (self::PUSHED, self::FETCHED, self::FORCED_FETCH)
	 *
	 * @return int|bool The message id of the newly created item or false on error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function receiveStatusMessage(array $importer, SimpleXMLElement $data, string $xml, int $direction)
	{
		$author                = WebFingerUri::fromString(XML::unescape($data->author));
		$guid                  = XML::unescape($data->guid);
		$created_at            = DateTimeFormat::utc(XML::unescape($data->created_at));
		$public                = XML::unescape($data->public);
		$text                  = XML::unescape($data->text);
		$provider_display_name = XML::unescape($data->provider_display_name);

		$contact = self::allowedContactByHandle($importer, $author);
		if (!$contact) {
			return false;
		}

		if (!empty($contact['gsid'])) {
			GServer::setProtocol($contact['gsid'], Post\DeliveryData::DIASPORA);
		}

		$message_id = self::messageExists($importer['uid'], $guid);
		if ($message_id) {
			return true;
		}

		$address = [];
		if ($data->location) {
			foreach ($data->location->children() as $fieldname => $data) {
				$address[$fieldname] = XML::unescape($data);
			}
		}

		$raw_body = $body = Markdown::toBBCode($text);

		$datarray = [
			'guid'        => $guid,
			'plink'       => self::plink($author, $guid),
			'uid'         => $importer['uid'],
			'contact-id'  => $contact['id'],
			'network'     => Protocol::DIASPORA,
			'author-link' => $contact['url'],
			'author-id'   => Contact::getIdForURL($contact['url'], 0),
			'verb'        => Activity::POST,
			'gravity'     => Item::GRAVITY_PARENT,
			'protocol'    => Conversation::PARCEL_DIASPORA,
			'source'      => $xml,
			'body'        => self::replacePeopleGuid($body, $contact['url']),
			'raw-body'    => self::replacePeopleGuid($raw_body, $contact['url']),
			'private'     => (($public == 'false') ? Item::PRIVATE : Item::PUBLIC),
			// Default is note (aka. comment), later below is being checked the real type
			'object-type' => Activity\ObjectType::NOTE,
			'post-type'   => Item::PT_NOTE,
		];

		$datarray['uri']        = $datarray['thr-parent'] = self::getUriFromGuid($guid, $author);
		$datarray['uri-id']     = ItemURI::insert(['uri' => $datarray['uri'], 'guid' => $datarray['guid']]);
		$datarray['owner-link'] = $datarray['author-link'];
		$datarray['owner-id']   = $datarray['author-id'];

		$datarray = self::setDirection($datarray, $direction);

		// Attach embedded pictures to the body
		if ($data->photo) {
			foreach ($data->photo as $photo) {
				self::storePhotoAsMedia($datarray['uri-id'], $photo);
			}

			$datarray['object-type'] = Activity\ObjectType::IMAGE;
			$datarray['post-type']   = Item::PT_IMAGE;
		} elseif ($data->poll) {
			$datarray['post-type'] = Item::PT_POLL;
		}

		/// @todo enable support for polls
		//if ($data->poll) {
		//	foreach ($data->poll as $poll)
		//		print_r($poll);
		//	die("poll!\n");
		//}

		/// @todo enable support for events

		self::storeMentions($datarray['uri-id'], $text);
		Tag::storeRawTagsFromBody($datarray['uri-id'], $datarray['body']);

		if (!self::isSolicitedMessage($datarray, $author, $body, $direction)) {
			DBA::delete('item-uri', ['uri' => $datarray['uri']]);
			return false;
		}

		if ($provider_display_name != '') {
			$datarray['app'] = $provider_display_name;
		}

		$datarray['changed'] = $datarray['created'] = $datarray['edited'] = $created_at;

		if (isset($address['address'])) {
			$datarray['location'] = $address['address'];
		}

		if (isset($address['lat']) && isset($address['lng'])) {
			$datarray['coord'] = $address['lat'] . ' ' . $address['lng'];
		}

		self::fetchGuid($datarray);

		if (DI::contentItem()->isTooOld($datarray['created'], $datarray['uid'])) {
			DI::logger()->info('Status is too old', ['created' => $datarray['created'], 'uid' => $datarray['uid'], 'guid' => $datarray['guid']]);
			return false;
		}

		$message_id = Item::insert($datarray);

		self::sendParticipation($contact, $datarray);

		if ($message_id) {
			DI::logger()->info('Stored item ' . $datarray['guid'] . ' with message id ' . $message_id);
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
	 * returns the handle of a contact
	 *
	 * @param array $contact contact array
	 *
	 * @return string the handle in the format user@domain.tld
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function myHandle(array $contact): string
	{
		if (!empty($contact['addr'])) {
			return $contact['addr'];
		}

		// Normally we should have a filled "addr" field - but in the past this wasn't the case
		// So - just in case - we build the address here.
		if ($contact['nickname'] != '') {
			$nick = $contact['nickname'];
		} else {
			$nick = $contact['nick'];
		}

		return $nick . '@' . substr(DI::baseUrl(), strpos(DI::baseUrl(), '://') + 3);
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
	public static function encodePrivateData(string $msg, array $user, array $contact, string $prvkey, string $pubkey): string
	{
		DI::logger()->debug('Diaspora message', ['msg' => $msg]);

		// without a public key nothing will work
		if (!$pubkey) {
			DI::logger()->notice('pubkey missing: contact id: ' . $contact['id']);
			return false;
		}

		$aes_key   = random_bytes(32);
		$b_aes_key = base64_encode($aes_key);
		$iv        = random_bytes(16);
		$b_iv      = base64_encode($iv);

		$ciphertext = self::aesEncrypt($aes_key, $iv, $msg);

		$json = json_encode(['iv' => $b_iv, 'key' => $b_aes_key]);

		$encrypted_key_bundle = '';
		if (!@openssl_public_encrypt($json, $encrypted_key_bundle, $pubkey)) {
			return false;
		}

		$json_object = json_encode(
			[
				'aes_key'                  => base64_encode($encrypted_key_bundle),
				'encrypted_magic_envelope' => base64_encode($ciphertext)
			]
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
	public static function buildMagicEnvelope(string $msg, array $user): string
	{
		$b64url_data = Strings::base64UrlEncode($msg);
		$data        = str_replace(["\n", "\r", ' ', "\t"], ['', '', '', ''], $b64url_data);

		$key_id        = Strings::base64UrlEncode(self::myHandle($user));
		$type          = 'application/xml';
		$encoding      = 'base64url';
		$alg           = 'RSA-SHA256';
		$signable_data = $data . '.' . Strings::base64UrlEncode($type) . '.' . Strings::base64UrlEncode($encoding) . '.' . Strings::base64UrlEncode($alg);

		// Fallback if the private key wasn't transmitted in the expected field
		if ($user['uprvkey'] == '') {
			$user['uprvkey'] = $user['prvkey'];
		}

		$signature = Crypto::rsaSign($signable_data, $user['uprvkey']);
		$sig       = Strings::base64UrlEncode($signature);

		$xmldata = [
			'me:env' => [
				'me:data'      => $data,
				'@attributes'  => ['type' => $type],
				'me:encoding'  => $encoding,
				'me:alg'       => $alg,
				'me:sig'       => $sig,
				'@attributes2' => ['key_id' => $key_id]
			]
		];

		$namespaces = ['me' => ActivityNamespace::SALMON_ME];

		return XML::fromArray($xmldata, $dummy, false, $namespaces);
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
	public static function buildMessage(string $msg, array $user, array $contact, string $prvkey, string $pubkey, bool $public = false): string
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
	private static function signature(array $owner, array $message): string
	{
		$sigmsg = $message;
		unset($sigmsg['author_signature']);
		unset($sigmsg['parent_author_signature']);

		$signed_text = implode(';', $sigmsg);

		return base64_encode(Crypto::rsaSign($signed_text, $owner['uprvkey'], 'sha256'));
	}

	/**
	 * Transmit a message to a target server
	 *
	 * @param array  $contact      Target of the communication
	 * @param string $envelope     The message that is to be transmitted
	 * @param bool   $public_batch Is it a public post?
	 * @param string $guid         message guid
	 *
	 * @return int Result of the transmission
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function transmit(array $contact, string $envelope, bool $public_batch, string $guid = ''): int
	{
		$enabled = intval(DI::config()->get('system', 'diaspora_enabled'));
		if (!$enabled) {
			return 200;
		}

		$logid = Strings::getRandomHex(4);

		// We always try to use the data from the diaspora-contact table.
		// This is important for transmitting data to Friendica servers.
		try {
			$target   = DI::dsprContact()->getByAddr(WebFingerUri::fromString($contact['addr']));
			$dest_url = $public_batch ? $target->batch : $target->notify;
		} catch (HTTPException\NotFoundException | \InvalidArgumentException $e) {
		}

		if (empty($dest_url)) {
			$dest_url = ($public_batch ? $contact['batch'] : $contact['notify']);
		}

		if (!$dest_url) {
			DI::logger()->notice('No URL for contact: ' . $contact['id'] . ' batch mode =' . $public_batch);
			return 0;
		}

		DI::logger()->info('transmit: ' . $logid . '-' . $guid . ' ' . $dest_url);

		if (!intval(DI::config()->get('system', 'diaspora_test'))) {
			$content_type = (($public_batch) ? 'application/magic-envelope+xml' : 'application/json');

			try {
				$postResult = DI::httpClient()->post($dest_url . '/', $envelope, ['Content-Type' => $content_type], 0, HttpClientRequest::DIASPORA);
			} catch (\Throwable $th) {
				DI::logger()->notice('Got exception', ['code' => $th->getCode(), 'message' => $th->getMessage()]);
				return 0;
			}
			$return_code = $postResult->getReturnCode();
		} else {
			DI::logger()->notice('test_mode');
			return 200;
		}

		if (!empty($contact['gsid']) && (empty($return_code) || $postResult->isTimeout())) {
			GServer::setFailureById($contact['gsid']);
		} elseif (!empty($contact['gsid']) && ($return_code >= 200) && ($return_code <= 299)) {
			Item::incrementOutbound(Protocol::DIASPORA);
			GServer::setReachableById($contact['gsid'], Protocol::DIASPORA);
		}

		DI::logger()->info('transmit: ' . $logid . '-' . $guid . ' to ' . $dest_url . ' returns: ' . $return_code);

		return $return_code ? $return_code : -1;
	}


	/**
	 * Build the post xml
	 *
	 * @param string $type    The message type
	 * @param array  $message The message data
	 *
	 * @return string The post XML
	 * @throws \Exception
	 */
	public static function buildPostXml(string $type, array $message): string
	{
		return XML::fromArray([$type => $message]);
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
	private static function buildAndTransmit(array $owner, array $contact, string $type, array $message, bool $public_batch = false, string $guid = '')
	{
		$msg = self::buildPostXml($type, $message);

		// Fallback if the private key wasn't transmitted in the expected field
		if (empty($owner['uprvkey'])) {
			$owner['uprvkey'] = $owner['prvkey'];
		}

		// When sending content to Friendica contacts using the Diaspora protocol
		// we have to fetch the public key from the diaspora-contact.
		// This is due to the fact that legacy DFRN had unique keys for every contact.
		$pubkey = $contact['pubkey'];
		if (!empty($contact['addr'])) {
			try {
				$pubkey = DI::dsprContact()->getByAddr(WebFingerUri::fromString($contact['addr']))->pubKey;
			} catch (HTTPException\NotFoundException | \InvalidArgumentException $e) {
			}
		} else {
			// The "addr" field should always be filled.
			// If this isn't the case, it will raise a notice some lines later.
			// And in the log we will see where it came from, and we can handle it there.
			DI::logger()->notice('Empty addr', ['contact' => $contact]);
		}

		$envelope = self::buildMessage($msg, $owner, $contact, $owner['uprvkey'], $pubkey ?? '', $public_batch);

		$return_code = self::transmit($contact, $envelope, $public_batch, $guid);

		DI::logger()->info('Transmitted message', ['owner' => $owner['uid'], 'target' => $contact['addr'], 'type' => $type, 'guid' => $guid, 'result' => $return_code]);

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
	private static function sendParticipation(array $contact, array $item): int
	{
		// Don't send notifications for private postings
		if ($item['private'] == Item::PRIVATE) {
			return 0;
		}

		$cachekey = 'diaspora:sendParticipation:' . $item['guid'];

		$result = DI::cache()->get($cachekey);
		if (!is_null($result)) {
			return -1;
		}

		// Fetch some user id to have a valid handle to transmit the participation.
		// In fact it doesn't matter which user sends this - but it is needed by the protocol.
		// If the item belongs to a user, we take this user id.
		if ($item['uid'] == 0) {
			// @todo Possibly use an administrator account?
			$condition  = ['verified' => true, 'blocked' => false, 'account_removed' => false, 'account_expired' => false, 'account-type' => User::ACCOUNT_TYPE_PERSON];
			$first_user = DBA::selectFirst('user', ['uid'], $condition, ['order' => ['uid']]);
			$owner      = User::getOwnerDataById($first_user['uid']);
		} else {
			$owner = User::getOwnerDataById($item['uid']);
		}

		$author_handle = self::myHandle($owner);

		$message = [
			'author'      => $author_handle,
			'guid'        => System::createUUID(),
			'parent_type' => 'Post',
			'parent_guid' => $item['guid']
		];

		DI::logger()->info('Send participation for ' . $item['guid'] . ' by ' . $author_handle);

		// It doesn't matter what we store, we only want to avoid sending repeated notifications for the same item
		DI::cache()->set($cachekey, $item['guid'], Duration::QUARTER_HOUR);

		return self::buildAndTransmit($owner, $contact, 'participation', $message);
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
	public static function sendAccountMigration(array $owner, array $contact, int $uid): int
	{
		$old_handle = DI::pConfig()->get($uid, 'system', 'previous_addr');
		$profile    = self::createProfileData($uid);

		$signed_text = 'AccountMigration:' . $old_handle . ':' . $profile['author'];
		$signature   = base64_encode(Crypto::rsaSign($signed_text, $owner['uprvkey'], 'sha256'));

		$message = [
			'author'    => $old_handle,
			'profile'   => $profile,
			'signature' => $signature
		];

		DI::logger()->info('Send account migration', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, 'account_migration', $message);
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
	public static function sendShare(array $owner, array $contact): int
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

		$message = [
			'author'    => self::myHandle($owner),
			'recipient' => $contact['addr'],
			'following' => 'true',
			'sharing'   => 'true'
		];

		DI::logger()->info('Send share', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, 'contact', $message);
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
	public static function sendUnshare(array $owner, array $contact): int
	{
		$message = [
			'author'    => self::myHandle($owner),
			'recipient' => $contact['addr'],
			'following' => 'false',
			'sharing'   => 'false'
		];

		DI::logger()->info('Send unshare', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, 'contact', $message);
	}

	/**
	 * Fetch reshare details
	 *
	 * @param array $item The message body that is to be check
	 *
	 * @return array Reshare details (empty if the item is no reshare)
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function getReshareDetails(array $item): array
	{
		$reshared = DI::contentItem()->getSharedPost($item, ['guid', 'network', 'author-addr']);
		if (empty($reshared)) {
			return [];
		}

		// Skip if it isn't a pure repeated messages or not a real reshare
		if (!empty($reshared['comment']) || !in_array($reshared['post']['network'], [Protocol::DFRN, Protocol::DIASPORA])) {
			return [];
		}

		return [
			'root_handle' => strtolower($reshared['post']['author-addr']),
			'root_guid'   => $reshared['post']['guid'],
		];
	}

	/**
	 * Create an event array
	 *
	 * @param string $event_id The id of the event
	 *
	 * @return array with event data
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function buildEvent(string $event_id): array
	{
		$event = DBA::selectFirst('event', [], ['id' => $event_id]);
		if (!DBA::isResult($event)) {
			return [];
		}

		$eventdata = [];

		$owner = User::getOwnerDataById($event['uid']);
		if (!$owner) {
			return [];
		}

		$eventdata['author'] = self::myHandle($owner);

		if ($event['guid']) {
			$eventdata['guid'] = $event['guid'];
		}

		$mask = DateTimeFormat::ATOM;

		/// @todo - establish "all day" events in Friendica
		$eventdata['all_day'] = 'false';

		// @todo Should be user timezone - but only if the event is supposed to be displayed
		// in that specific timezone and not the user's timezone.
		// $eventdata['timezone'] = 'UTC';

		if ($event['start']) {
			$eventdata['start'] = DateTimeFormat::utc($event['start'], $mask);
		}
		if ($event['finish'] && !$event['nofinish']) {
			$eventdata['end'] = DateTimeFormat::utc($event['finish'], $mask);
		}
		if ($event['summary']) {
			$eventdata['summary'] = html_entity_decode(BBCode::toMarkdown($event['summary']));
		}
		if ($event['desc']) {
			$eventdata['description'] = html_entity_decode(BBCode::toMarkdown($event['desc']));
		}
		if ($event['location']) {
			$event['location'] = preg_replace("/\[map\](.*?)\[\/map\]/ism", '$1', $event['location']);
			$coord             = Map::getCoordinates($event['location']);

			$location            = [];
			$location['address'] = html_entity_decode(BBCode::toMarkdown($event['location']));
			if (!empty($coord['lat']) && !empty($coord['lon'])) {
				$location['lat'] = $coord['lat'];
				$location['lng'] = $coord['lon'];
			} else {
				$location['lat'] = 0;
				$location['lng'] = 0;
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
		$cachekey = 'diaspora:buildStatus:' . $item['guid'];

		$result = DI::cache()->get($cachekey);
		if (!is_null($result)) {
			return $result;
		}

		$myaddr = self::myHandle($owner);

		$public  = ($item['private'] == Item::PRIVATE ? 'false' : 'true');
		$created = DateTimeFormat::utc($item['received'], DateTimeFormat::ATOM);
		$edited  = DateTimeFormat::utc($item['edited'] ?? $item['created'], DateTimeFormat::ATOM);

		// Detect a share element and do a reshare
		if (($item['private'] != Item::PRIVATE) && ($ret = self::getReshareDetails($item))) {
			$message = [
				'author'                => $myaddr,
				'guid'                  => $item['guid'],
				'created_at'            => $created,
				'root_author'           => $ret['root_handle'],
				'root_guid'             => $ret['root_guid'],
				'provider_display_name' => $item['app'],
				'public'                => $public
			];

			$type = 'reshare';
		} else {
			$native_photos = DI::config()->get('diaspora', 'native_photos');
			if ($native_photos) {
				$item['body'] = Post\Media::removeFromEndOfBody($item['body']);
				$attach_media = [Post\Media::AUDIO, Post\Media::VIDEO];
			} else {
				$attach_media = [Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO];
			}

			$title = $item['title'];
			$body  = Post\Media::addAttachmentsToBody($item['uri-id'], DI::contentItem()->addSharedPost($item), $attach_media);
			$body  = Post\Media::addHTMLLinkToBody($item['uri-id'], $body);

			// Fetch the title from an attached link - if there is one
			if (empty($item['title']) && DI::pConfig()->get($owner['uid'], 'system', 'attach_link_title')) {
				$media = Post\Media::getByURIId($item['uri-id'], [Post\Media::HTML]);
				if (!empty($media) && !empty($media[0]['name']) && ($media[0]['name'] != $media[0]['url'])) {
					$title = $media[0]['name'];
				}
			}

			$attachments = Post\Media::getByURIId($item['uri-id'], [Post\Media::DOCUMENT, Post\Media::TORRENT]);
			if (!empty($attachments)) {
				$body .= "\n[hr]\n";
				foreach ($attachments as $attachment) {
					$body .= "[url=" . $attachment['url'] . "]" . $attachment['description'] . "[/url]\n";
				}
			}

			// convert to markdown
			$body = BBCode::toMarkdown($body);

			// Adding the title
			if (strlen($title)) {
				$body = '### ' . html_entity_decode($title) . "\n\n" . $body;
			}

			$location = [];

			if ($item['location'] != '') {
				$location['address'] = $item['location'];
			}

			if ($item['coord'] != '') {
				$coord           = explode(' ', $item['coord']);
				$location['lat'] = $coord[0];
				$location['lng'] = $coord[1];
			}

			$message = [
				'author'                => $myaddr,
				'guid'                  => $item['guid'],
				'created_at'            => $created,
				'edited_at'             => $edited,
				'public'                => $public,
				'text'                  => $body,
				'provider_display_name' => $item['app'],
				'location'              => $location
			];

			if ($native_photos) {
				$message = self::addPhotos($item, $message);
			}

			// Diaspora rejects messages when they contain a location without "lat" or "lng"
			if (!isset($location['lat']) || !isset($location['lng'])) {
				unset($message['location']);
			}

			if ($item['event-id'] > 0) {
				$event = self::buildEvent($item['event-id']);
				if (count($event)) {
					$message['event'] = $event;

					if (
						!empty($event['location']['address']) &&
						!empty($event['location']['lat']) &&
						!empty($event['location']['lng'])
					) {
						$message['location'] = $event['location'];
					}

					/// @todo Once Diaspora supports it, we will remove the body and the location hack above
					// $message['text'] = '';
				}
			}

			$type = 'status_message';
		}

		$msg = [
			'type'    => $type,
			'message' => $message
		];

		DI::cache()->set($cachekey, $msg, Duration::QUARTER_HOUR);

		return $msg;
	}

	/**
	 * Add photo elements to the message array
	 *
	 * @param array $item
	 * @param array $message
	 * @return array
	 */
	private static function addPhotos(array $item, array $message): array
	{
		$medias = Post\Media::getByURIId($item['uri-id'], [Post\Media::IMAGE]);
		$public = ($item['private'] == Item::PRIVATE ? 'false' : 'true');

		$counter = 0;
		foreach ($medias as $media) {
			if (Item::containsLink($item['body'], $media['preview'] ?? $media['url'], $media['type'])) {
				continue;
			}

			$name = basename($media['url']);
			$path = str_replace($name, '', $media['url']);

			$message[++$counter . ':photo'] = [
				'guid'                => Item::guid(['uri' => $media['url']], false),
				'author'              => $item['author-addr'],
				'public'              => $public,
				'created_at'          => $item['created'],
				'remote_photo_path'   => $path,
				'remote_photo_name'   => $name,
				'status_message_guid' => $item['guid'],
				'height'              => $media['height'],
				'width'               => $media['width'],
				'text'                => $media['description'],
			];
		}

		return $message;
	}

	private static function prependParentAuthorMention(string $body, string $profile_url): string
	{
		$profile = Contact::getByURL($profile_url, false, ['addr', 'name']);
		if (
			!empty($profile['addr'])
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
	public static function sendStatus(array $item, array $owner, array $contact, bool $public_batch = false): int
	{
		$status = self::buildStatus($item, $owner);

		return self::buildAndTransmit($owner, $contact, $status['type'], $status['message'], $public_batch, $item['guid']);
	}

	/**
	 * Creates a "like" object
	 *
	 * @param array $item  The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array|bool The data for a "like" or false on error
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	private static function constructLike(array $item, array $owner)
	{
		$parent = Post::selectFirst(['guid', 'uri', 'thr-parent'], ['uri' => $item['thr-parent']]);
		if (!DBA::isResult($parent)) {
			return false;
		}

		$target_type = ($parent['uri'] === $parent['thr-parent'] ? 'Post' : 'Comment');
		$positive    = null;
		if ($item['verb'] === Activity::LIKE) {
			$positive = 'true';
		} elseif ($item['verb'] === Activity::DISLIKE) {
			$positive = 'false';
		}

		return [
			'author'           => self::myHandle($owner),
			'guid'             => $item['guid'],
			'parent_guid'      => $parent['guid'],
			'parent_type'      => $target_type,
			'positive'         => $positive,
			'author_signature' => '',
		];
	}

	/**
	 * Creates an "EventParticipation" object
	 *
	 * @param array $item  The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array|bool The data for an "EventParticipation" or false on error
	 * @throws \Exception
	 */
	private static function constructAttend(array $item, array $owner)
	{
		$parent = Post::selectFirst(['guid'], ['uri' => $item['thr-parent']]);
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
				DI::logger()->warning('Unknown verb ' . $item['verb'] . ' in item ' . $item['guid']);
				return false;
		}

		return [
			'author'           => self::myHandle($owner),
			'guid'             => $item['guid'],
			'parent_guid'      => $parent['guid'],
			'status'           => $attend_answer,
			'author_signature' => ''
		];
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
		$cachekey = 'diaspora:constructComment:' . $item['guid'];

		$result = DI::cache()->get($cachekey);
		if (!is_null($result)) {
			return $result;
		}

		$toplevel_item = Post::selectFirst(['guid', 'author-id', 'author-link', 'gravity'], ['id' => $item['parent'], 'parent' => $item['parent']]);
		if (!DBA::isResult($toplevel_item)) {
			DI::logger()->error('Missing parent conversation item', ['parent' => $item['parent']]);
			return false;
		}

		$thread_parent_item = $toplevel_item;
		if ($item['thr-parent'] != $item['parent-uri']) {
			$thread_parent_item = Post::selectFirst(['guid', 'author-id', 'author-link', 'gravity'], ['uri' => $item['thr-parent'], 'uid' => $item['uid']]);
		}

		$body = Post\Media::addAttachmentsToBody($item['uri-id'], DI::contentItem()->addSharedPost($item));
		$body = Post\Media::addHTMLLinkToBody($item['uri-id'], $body);

		// The replied to autor mention is prepended for clarity if:
		// - Item replied isn't yours
		// - Item is public or explicit mentions are disabled
		// - Implicit mentions are enabled
		if (
			$item['author-id'] != $thread_parent_item['author-id']
			&& ($thread_parent_item['gravity'] != Item::GRAVITY_PARENT)
			&& (empty($item['uid']) || !Feature::isEnabled($item['uid'], Feature::EXPLICIT_MENTIONS))
			&& !DI::config()->get('system', 'disable_implicit_mentions')
		) {
			$body = self::prependParentAuthorMention($body, $thread_parent_item['author-link']);
		}

		$text    = html_entity_decode(BBCode::toMarkdown($body));
		$created = DateTimeFormat::utc($item['created'], DateTimeFormat::ATOM);
		$edited  = DateTimeFormat::utc($item['edited'], DateTimeFormat::ATOM);

		$comment = [
			'author'           => self::myHandle($owner),
			'guid'             => $item['guid'],
			'created_at'       => $created,
			'edited_at'        => $edited,
			'parent_guid'      => $toplevel_item['guid'],
			'text'             => $text,
			'author_signature' => '',
		];

		// Send the thread parent guid only if it is a threaded comment
		if ($item['thr-parent'] != $item['parent-uri']) {
			$comment['thread_parent_guid'] = $thread_parent_item['guid'];
		}

		DI::cache()->set($cachekey, $comment, Duration::QUARTER_HOUR);

		return $comment;
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
	public static function sendFollowup(array $item, array $owner, array $contact, bool $public_batch = false): int
	{
		$type = '';

		if (in_array($item['verb'], [Activity::ATTEND, Activity::ATTENDNO, Activity::ATTENDMAYBE])) {
			$message = self::constructAttend($item, $owner);
			$type    = 'event_participation';
		} elseif (in_array($item['verb'], [Activity::LIKE, Activity::DISLIKE])) {
			$message = self::constructLike($item, $owner);
			$type    = 'like';
		} elseif (!in_array($item['verb'], [Activity::FOLLOW, Activity::TAG])) {
			$message = self::constructComment($item, $owner);
			$type    = 'comment';
		}

		if (empty($message)) {
			return -1;
		}

		$message['author_signature'] = self::signature($owner, $message);

		return self::buildAndTransmit($owner, $contact, $type, $message, $public_batch, $item['guid']);
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
	public static function sendRelay(array $item, array $owner, array $contact, bool $public_batch = false): int
	{
		if ($item['deleted']) {
			return self::sendRetraction($item, $owner, $contact, $public_batch, true);
		} elseif (in_array($item['verb'], [Activity::LIKE, Activity::DISLIKE])) {
			$type = 'like';
		} else {
			$type = 'comment';
		}

		DI::logger()->info('Got relayable data ' . $type . ' for item ' . $item['guid'] . ' (' . $item['id'] . ')');

		$msg = json_decode($item['signed_text'] ?? '', true);

		$message = [];
		if (is_array($msg)) {
			foreach ($msg as $field => $data) {
				if (!$item['deleted']) {
					if ($field == 'diaspora_handle') {
						$field = 'author';
					}
					if ($field == 'target_type') {
						$field = 'parent_type';
					}
				}

				$message[$field] = $data;
			}
		} else {
			DI::logger()->info('Signature text for item ' . $item['guid'] . ' (' . $item['id'] . ') could not be extracted: ' . $item['signed_text']);
		}

		$message['parent_author_signature'] = self::signature($owner, $message);

		DI::logger()->info('Relayed data', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, $type, $message, $public_batch, $item['guid']);
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
	public static function sendRetraction(array $item, array $owner, array $contact, bool $public_batch = false, bool $relay = false): int
	{
		$itemaddr = strtolower($item['author-addr']);

		$msg_type = 'retraction';

		if ($item['gravity'] == Item::GRAVITY_PARENT) {
			$target_type = 'Post';
		} elseif (in_array($item['verb'], [Activity::LIKE, Activity::DISLIKE])) {
			$target_type = 'Like';
		} else {
			$target_type = 'Comment';
		}

		$message = [
			'author'      => $itemaddr,
			'target_guid' => $item['guid'],
			'target_type' => $target_type
		];

		DI::logger()->info('Got message', ['msg' => $message]);

		return self::buildAndTransmit($owner, $contact, $msg_type, $message, $public_batch, $item['guid']);
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
	public static function sendMail(array $item, array $owner, array $contact): int
	{
		$myaddr = self::myHandle($owner);

		$cnv = DBA::selectFirst('conv', [], ['id' => $item['convid'], 'uid' => $item['uid']]);
		if (!DBA::isResult($cnv)) {
			DI::logger()->notice('Conversation not found.');
			return -1;
		}

		$body    = BBCode::toMarkdown($item['body']);
		$created = DateTimeFormat::utc($item['created'], DateTimeFormat::ATOM);

		$msg = [
			'author'            => $myaddr,
			'guid'              => $item['guid'],
			'conversation_guid' => $cnv['guid'],
			'text'              => $body,
			'created_at'        => $created,
		];

		if ($item['reply']) {
			$message = $msg;
			$type    = 'message';
		} else {
			$message = [
				'author'       => $cnv['creator'],
				'guid'         => $cnv['guid'],
				'subject'      => $cnv['subject'],
				'created_at'   => DateTimeFormat::utc($cnv['created'], DateTimeFormat::ATOM),
				'participants' => $cnv['recips'],
				'message'      => $msg
			];

			$type = 'conversation';
		}

		return self::buildAndTransmit($owner, $contact, $type, $message, false, $item['guid']);
	}

	/**
	 * Split a name into first name and last name
	 *
	 * @param string $name The name
	 *
	 * @return array The array with "first" and "last"
	 */
	public static function splitName(string $name): array
	{
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
		$last  = (($first === $name) ? '' : trim(substr($name, strlen($first))));
		if ((strlen($first) < 32) && (strlen($last) < 32)) {
			return ['first' => $first, 'last' => $last];
		}

		// Take the last word as last name
		$first = ((strrpos($name, ' ') ? trim(substr($name, 0, strrpos($name, ' '))) : $name));
		$last  = (($first === $name) ? '' : trim(substr($name, strlen($first))));

		if ((strlen($first) < 32) && (strlen($last) < 32)) {
			return ['first' => $first, 'last' => $last];
		}

		// Take the first 32 characters if there is no space in the first 32 characters
		if ((strpos($name, ' ') > 32) || (strpos($name, ' ') === false)) {
			$first = substr($name, 0, 32);
			$last  = substr($name, 32);
			return ['first' => $first, 'last' => $last];
		}

		$first = trim(substr($name, 0, strrpos(substr($name, 0, 33), ' ')));
		$last  = (($first === $name) ? '' : trim(substr($name, strlen($first))));

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
	private static function createProfileData(int $uid): array
	{
		$profile = User::getOwnerDataById($uid);

		if (!DBA::isResult($profile)) {
			return [];
		}

		$split_name = self::splitName($profile['name']);

		$data = [
			'author'           => $profile['addr'],
			'edited_at'        => DateTimeFormat::utc($profile['updated']),
			'full_name'        => $profile['name'],
			'first_name'       => $split_name['first'],
			'last_name'        => $split_name['last'],
			'image_url'        => User::getAvatarUrl($profile, Proxy::SIZE_SMALL),
			'image_url_medium' => User::getAvatarUrl($profile, Proxy::SIZE_THUMB),
			'image_url_small'  => User::getAvatarUrl($profile, Proxy::SIZE_MICRO),
			'bio'              => null,
			'birthday'         => null,
			'gender'           => null,
			'location'         => null,
			'searchable'       => ($profile['net-publish'] ? 'true' : 'false'),
			'public'           => 'false',
			'nsfw'             => 'false',
			'tag_string'       => null,
		];

		if ($data['searchable'] === 'true') {
			$data['birthday'] = '';

			if ($profile['dob'] && ($profile['dob'] > '0000-00-00')) {
				list($year, $month, $day) = sscanf($profile['dob'], '%4d-%2d-%2d');
				if ($year < 1004) {
					$year = 1004;
				}
				$data['birthday'] = DateTimeFormat::utc($year . '-' . $month . '-' . $day, 'Y-m-d');
			}

			$data['bio'] = BBCode::toMarkdown($profile['about'] ?? '');

			$data['location']   = $profile['location'];
			$data['tag_string'] = '';

			if ($profile['pub_keywords']) {
				$kw  = str_replace(',', ' ', $profile['pub_keywords']);
				$kw  = str_replace('  ', ' ', $kw);
				$arr = explode(' ', $kw);
				if (count($arr)) {
					for ($x = 0; $x < 5; $x++) {
						if (!empty($arr[$x])) {
							$data['tag_string'] .= '#' . trim($arr[$x]) . ' ';
						}
					}
				}
			}
			$data['tag_string'] = trim($data['tag_string']);
		}

		return $data;
	}

	/**
	 * Sends profile data
	 *
	 * @param int   $uid        The user id
	 * @param array $recipients optional, default empty array
	 *
	 * @return void
	 * @throws \Exception
	 */
	public static function sendProfile(int $uid, array $recipients = [])
	{
		if (!$uid) {
			DI::logger()->warning('Parameter "uid" is empty');
			return;
		}

		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			DI::logger()->warning('Cannot fetch User record', ['uid' => $uid]);
			return;
		}

		if (empty($recipients)) {
			DI::logger()->debug('No recipients provided, fetching for user', ['uid' => $uid]);
			$recipients = DBA::selectToArray('contact', [], ['network' => Protocol::DIASPORA, 'uid' => $uid, 'rel' => [Contact::FOLLOWER, Contact::FRIEND]]);
		}

		if (empty($recipients)) {
			DI::logger()->warning('Cannot fetch recipients', ['uid' => $uid]);
			return;
		}

		$message = self::createProfileData($uid);

		// @todo Split this into single worker jobs
		foreach ($recipients as $recipient) {
			if ((empty($recipient['gsid']) || GServer::isReachableById($recipient['gsid'])) && !Contact\User::isBlocked($recipient['id'], $uid)) {
				DI::logger()->info('Send updated profile data for user ' . $uid . ' to contact ' . $recipient['id']);
				self::buildAndTransmit($owner, $recipient, 'profile', $message);
			}
		}
	}

	/**
	 * Creates the signature for likes that are created on our system
	 *
	 * @param integer $uid  The user of that comment
	 * @param array   $item Item array
	 *
	 * @return array|bool Signed content or false on error
	 * @throws \Exception
	 */
	public static function createLikeSignature(int $uid, array $item)
	{
		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			DI::logger()->info('No owner post, so not storing signature', ['uid' => $uid]);
			return false;
		}

		if (!in_array($item['verb'], [Activity::LIKE, Activity::DISLIKE])) {
			DI::logger()->warning('Item is neither a like nor a dislike', ['uid' => $uid, 'item[verb]' => $item['verb']]);
			;
			return false;
		}

		$message = self::constructLike($item, $owner);
		if ($message === false) {
			return false;
		}

		$message['author_signature'] = self::signature($owner, $message);

		return $message;
	}

	/**
	 * Creates the signature for Comments that are created on our system
	 *
	 * @param array   $item Item array
	 *
	 * @return array|bool Signed content or false on error
	 * @throws \Exception
	 */
	public static function createCommentSignature(array $item)
	{
		$contact = [];
		if (!empty($item['author-link'])) {
			$url = $item['author-link'];
		} else {
			$contact = Contact::getById($item['author-id'], ['url']);
			if (empty($contact['url'])) {
				DI::logger()->warning('Author Contact not found', ['author-id' => $item['author-id']]);
				return false;
			}
			$url = $contact['url'];
		}

		$uid = User::getIdForURL($url);
		if (empty($uid)) {
			DI::logger()->info('No owner post, so not storing signature', ['url' => $contact['url'] ?? 'No contact loaded']);
			return false;
		}

		$owner = User::getOwnerDataById($uid);
		if (empty($owner)) {
			DI::logger()->info('No owner post, so not storing signature');
			return false;
		}

		// This is only needed for the automated tests
		if (empty($owner['uprvkey'])) {
			return false;
		}

		if (!self::parentSupportDiaspora($item['thr-parent-id'], $uid)) {
			DI::logger()->info('One of the parents does not support Diaspora. A signature will not be created.', ['uri-id' => $item['uri-id'], 'guid' => $item['guid']]);
			return false;
		}

		$message = self::constructComment($item, $owner);
		if ($message === false) {
			return false;
		}

		$message['author_signature'] = self::signature($owner, $message);

		return $message;
	}

	/**
	 * Check if the parent and their parents support Diaspora
	 *
	 * @param integer $parent_id
	 * @param integer $uid
	 * @return boolean
	 * @throws InternalServerErrorException
	 * @throws \ImagickException
	 */
	private static function parentSupportDiaspora(int $parent_id, int $uid): bool
	{
		$parent_post = Post::selectFirst(['gravity', 'signed_text', 'author-link', 'thr-parent-id', 'protocol'], ['uri-id' => $parent_id, 'uid' => [0, $uid]]);
		if (empty($parent_post['thr-parent-id'])) {
			DI::logger()->warning('Parent post does not exist.', ['parent-id' => $parent_id]);
			return false;
		}

		if (!self::isSupportedByContactUrl($parent_post['author-link'])) {
			DI::logger()->info('Parent author is no Diaspora contact.', ['parent-id' => $parent_id]);
			return false;
		}

		if (($parent_post['protocol'] != Conversation::PARCEL_DIASPORA) && ($parent_post['gravity'] == Item::GRAVITY_COMMENT) && empty($parent_post['signed_text'])) {
			DI::logger()->info('Parent comment has got no Diaspora signature.', ['parent-id' => $parent_id]);
			return false;
		}

		if ($parent_post['gravity'] == Item::GRAVITY_COMMENT) {
			return self::parentSupportDiaspora($parent_post['thr-parent-id'], $uid);
		}

		return true;
	}

	public static function performReshare(int $UriId, int $uid): int
	{
		$owner  = User::getOwnerDataById($uid);
		$author = Contact::getPublicIdByUserId($uid);

		$item = [
			'uid'          => $uid,
			'verb'         => Activity::POST,
			'contact-id'   => $owner['id'],
			'author-id'    => $author,
			'owner-id'     => $author,
			'body'         => '',
			'quote-uri-id' => $UriId,
			'allow_cid'    => $owner['allow_cid'] ?? '',
			'allow_gid'    => $owner['allow_gid'] ?? '',
			'deny_cid'     => $owner['deny_cid']  ?? '',
			'deny_gid'     => $owner['deny_gid']  ?? '',
		];

		if (!empty($item['allow_cid'] . $item['allow_gid'] . $item['deny_cid'] . $item['deny_gid'])) {
			$item['private'] = Item::PRIVATE;
		} elseif (DI::pConfig()->get($uid, 'system', 'unlisted')) {
			$item['private'] = Item::UNLISTED;
		} else {
			$item['private'] = Item::PUBLIC;
		}

		// Don't trigger the addons
		$item['api_source'] = false;

		return Item::insert($item, true);
	}
}
