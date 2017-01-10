<?php
/**
 * @file include/diaspora.php
 * @brief The implementation of the diaspora protocol
 *
 * The new protocol is described here: http://diaspora.github.io/diaspora_federation/index.html
 * Currently this implementation here interprets the old and the new protocol and sends the old one.
 * This will change in the future.
 */

require_once("include/items.php");
require_once("include/bb2diaspora.php");
require_once("include/Scrape.php");
require_once("include/Contact.php");
require_once("include/Photo.php");
require_once("include/socgraph.php");
require_once("include/group.php");
require_once("include/xml.php");
require_once("include/datetime.php");
require_once("include/queue_fn.php");
require_once("include/cache.php");

/**
 * @brief This class contain functions to create and send Diaspora XML files
 *
 */
class Diaspora {

	/**
	 * @brief Return a list of relay servers
	 *
	 * This is an experimental Diaspora feature.
	 *
	 * @return array of relay servers
	 */
	public static function relay_list() {

		$serverdata = get_config("system", "relay_server");
		if ($serverdata == "")
			return array();

		$relay = array();

		$servers = explode(",", $serverdata);

		foreach($servers AS $server) {
			$server = trim($server);
			$batch = $server."/receive/public";

			$relais = q("SELECT `batch`, `id`, `name`,`network` FROM `contact` WHERE `uid` = 0 AND `batch` = '%s' LIMIT 1", dbesc($batch));

			if (!$relais) {
				$addr = "relay@".str_replace("http://", "", normalise_link($server));

				$r = q("INSERT INTO `contact` (`uid`, `created`, `name`, `nick`, `addr`, `url`, `nurl`, `batch`, `network`, `rel`, `blocked`, `pending`, `writable`, `name-date`, `uri-date`, `avatar-date`)
					VALUES (0, '%s', '%s', 'relay', '%s', '%s', '%s', '%s', '%s', %d, 0, 0, 1, '%s', '%s', '%s')",
					datetime_convert(),
					dbesc($addr),
					dbesc($addr),
					dbesc($server),
					dbesc(normalise_link($server)),
					dbesc($batch),
					dbesc(NETWORK_DIASPORA),
					intval(CONTACT_IS_FOLLOWER),
					dbesc(datetime_convert()),
					dbesc(datetime_convert()),
					dbesc(datetime_convert())
				);

				$relais = q("SELECT `batch`, `id`, `name`,`network` FROM `contact` WHERE `uid` = 0 AND `batch` = '%s' LIMIT 1", dbesc($batch));
				if ($relais)
					$relay[] = $relais[0];
			} else
				$relay[] = $relais[0];
		}

		return $relay;
	}

	/**
	 * @brief repairs a signature that was double encoded
	 *
	 * The function is unused at the moment. It was copied from the old implementation.
	 *
	 * @param string $signature The signature
	 * @param string $handle The handle of the signature owner
	 * @param integer $level This value is only set inside this function to avoid endless loops
	 *
	 * @return string the repaired signature
	 */
	private static function repair_signature($signature, $handle = "", $level = 1) {

		if ($signature == "")
			return ($signature);

		if (base64_encode(base64_decode(base64_decode($signature))) == base64_decode($signature)) {
			$signature = base64_decode($signature);
			logger("Repaired double encoded signature from Diaspora/Hubzilla handle ".$handle." - level ".$level, LOGGER_DEBUG);

			// Do a recursive call to be able to fix even multiple levels
			if ($level < 10)
				$signature = self::repair_signature($signature, $handle, ++$level);
		}

		return($signature);
	}

	/**
	 * @brief verify the envelope and return the verified data
	 *
	 * @param string $envelope The magic envelope
	 *
	 * @return string verified data
	 */
	private static function verify_magic_envelope($envelope) {

		$basedom = parse_xml_string($envelope, false);

		if (!is_object($basedom)) {
			logger("Envelope is no XML file");
			return false;
		}

		$children = $basedom->children('http://salmon-protocol.org/ns/magic-env');

		if (sizeof($children) == 0) {
			logger("XML has no children");
			return false;
		}

		$handle = "";

		$data = base64url_decode($children->data);
		$type = $children->data->attributes()->type[0];

		$encoding = $children->encoding;

		$alg = $children->alg;

		$sig = base64url_decode($children->sig);
		$key_id = $children->sig->attributes()->key_id[0];
		if ($key_id != "")
			$handle = base64url_decode($key_id);

		$b64url_data = base64url_encode($data);
		$msg = str_replace(array("\n", "\r", " ", "\t"), array("", "", "", ""), $b64url_data);

		$signable_data = $msg.".".base64url_encode($type).".".base64url_encode($encoding).".".base64url_encode($alg);

		$key = self::key($handle);

		$verify = rsa_verify($signable_data, $sig, $key);
		if (!$verify) {
			logger('Message did not verify. Discarding.');
			return false;
		}

		return $data;
	}

	/**
	 * @brief: Decodes incoming Diaspora message
	 *
	 * @param array $importer Array of the importer user
	 * @param string $xml urldecoded Diaspora salmon
	 *
	 * @return array
	 * 'message' -> decoded Diaspora XML message
	 * 'author' -> author diaspora handle
	 * 'key' -> author public key (converted to pkcs#8)
	 */
	public static function decode($importer, $xml) {

		$public = false;
		$basedom = parse_xml_string($xml);

		if (!is_object($basedom))
			return false;

		$children = $basedom->children('https://joindiaspora.com/protocol');

		if($children->header) {
			$public = true;
			$author_link = str_replace('acct:','',$children->header->author_id);
		} else {

			$encrypted_header = json_decode(base64_decode($children->encrypted_header));

			$encrypted_aes_key_bundle = base64_decode($encrypted_header->aes_key);
			$ciphertext = base64_decode($encrypted_header->ciphertext);

			$outer_key_bundle = '';
			openssl_private_decrypt($encrypted_aes_key_bundle,$outer_key_bundle,$importer['prvkey']);

			$j_outer_key_bundle = json_decode($outer_key_bundle);

			$outer_iv = base64_decode($j_outer_key_bundle->iv);
			$outer_key = base64_decode($j_outer_key_bundle->key);

			$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $outer_key, $ciphertext, MCRYPT_MODE_CBC, $outer_iv);


			$decrypted = pkcs5_unpad($decrypted);

			logger('decrypted: '.$decrypted, LOGGER_DEBUG);
			$idom = parse_xml_string($decrypted,false);

			$inner_iv = base64_decode($idom->iv);
			$inner_aes_key = base64_decode($idom->aes_key);

			$author_link = str_replace('acct:','',$idom->author_id);
		}

		$dom = $basedom->children(NAMESPACE_SALMON_ME);

		// figure out where in the DOM tree our data is hiding

		if($dom->provenance->data)
			$base = $dom->provenance;
		elseif($dom->env->data)
			$base = $dom->env;
		elseif($dom->data)
			$base = $dom;

		if (!$base) {
			logger('unable to locate salmon data in xml');
			http_status_exit(400);
		}


		// Stash the signature away for now. We have to find their key or it won't be good for anything.
		$signature = base64url_decode($base->sig);

		// unpack the  data

		// strip whitespace so our data element will return to one big base64 blob
		$data = str_replace(array(" ","\t","\r","\n"),array("","","",""),$base->data);


		// stash away some other stuff for later

		$type = $base->data[0]->attributes()->type[0];
		$keyhash = $base->sig[0]->attributes()->keyhash[0];
		$encoding = $base->encoding;
		$alg = $base->alg;


		$signed_data = $data.'.'.base64url_encode($type).'.'.base64url_encode($encoding).'.'.base64url_encode($alg);


		// decode the data
		$data = base64url_decode($data);


		if($public)
			$inner_decrypted = $data;
		else {

			// Decode the encrypted blob

			$inner_encrypted = base64_decode($data);
			$inner_decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $inner_encrypted, MCRYPT_MODE_CBC, $inner_iv);
			$inner_decrypted = pkcs5_unpad($inner_decrypted);
		}

		if (!$author_link) {
			logger('Could not retrieve author URI.');
			http_status_exit(400);
		}
		// Once we have the author URI, go to the web and try to find their public key
		// (first this will look it up locally if it is in the fcontact cache)
		// This will also convert diaspora public key from pkcs#1 to pkcs#8

		logger('Fetching key for '.$author_link);
		$key = self::key($author_link);

		if (!$key) {
			logger('Could not retrieve author key.');
			http_status_exit(400);
		}

		$verify = rsa_verify($signed_data,$signature,$key);

		if (!$verify) {
			logger('Message did not verify. Discarding.');
			http_status_exit(400);
		}

		logger('Message verified.');

		return array('message' => (string)$inner_decrypted,
				'author' => unxmlify($author_link),
				'key' => (string)$key);
	}


	/**
	 * @brief Dispatches public messages and find the fitting receivers
	 *
	 * @param array $msg The post that will be dispatched
	 *
	 * @return int The message id of the generated message, "true" or "false" if there was an error
	 */
	public static function dispatch_public($msg) {

		$enabled = intval(get_config("system", "diaspora_enabled"));
		if (!$enabled) {
			logger("diaspora is disabled");
			return false;
		}

		// Use a dummy importer to import the data for the public copy
		$importer = array("uid" => 0, "page-flags" => PAGE_FREELOVE);
		$message_id = self::dispatch($importer,$msg);

		// Now distribute it to the followers
		$r = q("SELECT `user`.* FROM `user` WHERE `user`.`uid` IN
			(SELECT `contact`.`uid` FROM `contact` WHERE `contact`.`network` = '%s' AND `contact`.`addr` = '%s')
			AND NOT `account_expired` AND NOT `account_removed`",
			dbesc(NETWORK_DIASPORA),
			dbesc($msg["author"])
		);
		if ($r) {
			foreach ($r as $rr) {
				logger("delivering to: ".$rr["username"]);
				self::dispatch($rr,$msg);
			}
		} else
			logger("No subscribers for ".$msg["author"]." ".print_r($msg, true));

		return $message_id;
	}

	/**
	 * @brief Dispatches the different message types to the different functions
	 *
	 * @param array $importer Array of the importer user
	 * @param array $msg The post that will be dispatched
	 *
	 * @return int The message id of the generated message, "true" or "false" if there was an error
	 */
	public static function dispatch($importer, $msg) {

		// The sender is the handle of the contact that sent the message.
		// This will often be different with relayed messages (for example "like" and "comment")
		$sender = $msg["author"];

		if (!self::valid_posting($msg, $fields)) {
			logger("Invalid posting");
			return false;
		}

		$type = $fields->getName();

		logger("Received message type ".$type." from ".$sender." for user ".$importer["uid"], LOGGER_DEBUG);

		switch ($type) {
			case "account_deletion":
				return self::receive_account_deletion($importer, $fields);

			case "comment":
				return self::receive_comment($importer, $sender, $fields, $msg["message"]);

			case "contact":
				return self::receive_contact_request($importer, $fields);

			case "conversation":
				return self::receive_conversation($importer, $msg, $fields);

			case "like":
				return self::receive_like($importer, $sender, $fields);

			case "message":
				return self::receive_message($importer, $fields);

			case "participation": // Not implemented
				return self::receive_participation($importer, $fields);

			case "photo": // Not implemented
				return self::receive_photo($importer, $fields);

			case "poll_participation": // Not implemented
				return self::receive_poll_participation($importer, $fields);

			case "profile":
				return self::receive_profile($importer, $fields);

			case "reshare":
				return self::receive_reshare($importer, $fields, $msg["message"]);

			case "retraction":
				return self::receive_retraction($importer, $sender, $fields);

			case "status_message":
				return self::receive_status_message($importer, $fields, $msg["message"]);

			default:
				logger("Unknown message type ".$type);
				return false;
		}

		return true;
	}

	/**
	 * @brief Checks if a posting is valid and fetches the data fields.
	 *
	 * This function does not only check the signature.
	 * It also does the conversion between the old and the new diaspora format.
	 *
	 * @param array $msg Array with the XML, the sender handle and the sender signature
	 * @param object $fields SimpleXML object that contains the posting when it is valid
	 *
	 * @return bool Is the posting valid?
	 */
	private static function valid_posting($msg, &$fields) {

		$data = parse_xml_string($msg["message"], false);

		if (!is_object($data)) {
			logger("No valid XML ".$msg["message"], LOGGER_DEBUG);
			return false;
		}

		$first_child = $data->getName();

		// Is this the new or the old version?
		if ($data->getName() == "XML") {
			$oldXML = true;
			foreach ($data->post->children() as $child)
				$element = $child;
		} else {
			$oldXML = false;
			$element = $data;
		}

		$type = $element->getName();
		$orig_type = $type;

		logger("Got message type ".$type.": ".$msg["message"], LOGGER_DATA);

		// All retractions are handled identically from now on.
		// In the new version there will only be "retraction".
		if (in_array($type, array("signed_retraction", "relayable_retraction")))
			$type = "retraction";

		if ($type == "request")
			$type = "contact";

		$fields = new SimpleXMLElement("<".$type."/>");

		$signed_data = "";

		foreach ($element->children() AS $fieldname => $entry) {
			if ($oldXML) {
				// Translation for the old XML structure
				if ($fieldname == "diaspora_handle")
					$fieldname = "author";

				if ($fieldname == "participant_handles")
					$fieldname = "participants";

				if (in_array($type, array("like", "participation"))) {
					if ($fieldname == "target_type")
						$fieldname = "parent_type";
				}

				if ($fieldname == "sender_handle")
					$fieldname = "author";

				if ($fieldname == "recipient_handle")
					$fieldname = "recipient";

				if ($fieldname == "root_diaspora_id")
					$fieldname = "root_author";

				if ($type == "retraction") {
					if ($fieldname == "post_guid")
						$fieldname = "target_guid";

					if ($fieldname == "type")
						$fieldname = "target_type";
				}
			}

			if (($fieldname == "author_signature") AND ($entry != ""))
				$author_signature = base64_decode($entry);
			elseif (($fieldname == "parent_author_signature") AND ($entry != ""))
				$parent_author_signature = base64_decode($entry);
			elseif (!in_array($fieldname, array("author_signature", "parent_author_signature", "target_author_signature"))) {
				if ($signed_data != "") {
					$signed_data .= ";";
					$signed_data_parent .= ";";
				}

				$signed_data .= $entry;
			}
			if (!in_array($fieldname, array("parent_author_signature", "target_author_signature")) OR
				($orig_type == "relayable_retraction"))
				xml::copy($entry, $fields, $fieldname);
		}

		// This is something that shouldn't happen at all.
		if (in_array($type, array("status_message", "reshare", "profile")))
			if ($msg["author"] != $fields->author) {
				logger("Message handle is not the same as envelope sender. Quitting this message.");
				return false;
			}

		// Only some message types have signatures. So we quit here for the other types.
		if (!in_array($type, array("comment", "message", "like")))
			return true;

		// No author_signature? This is a must, so we quit.
		if (!isset($author_signature)) {
			logger("No author signature for type ".$type." - Message: ".$msg["message"], LOGGER_DEBUG);
			return false;
		}

		if (isset($parent_author_signature)) {
			$key = self::key($msg["author"]);

			if (!rsa_verify($signed_data, $parent_author_signature, $key, "sha256")) {
				logger("No valid parent author signature for parent author ".$msg["author"]. " in type ".$type." - signed data: ".$signed_data." - Message: ".$msg["message"]." - Signature ".$parent_author_signature, LOGGER_DEBUG);
				return false;
			}
		}

		$key = self::key($fields->author);

		if (!rsa_verify($signed_data, $author_signature, $key, "sha256")) {
			logger("No valid author signature for author ".$fields->author. " in type ".$type." - signed data: ".$signed_data." - Message: ".$msg["message"]." - Signature ".$author_signature, LOGGER_DEBUG);
			return false;
		} else
			return true;
	}

	/**
	 * @brief Fetches the public key for a given handle
	 *
	 * @param string $handle The handle
	 *
	 * @return string The public key
	 */
	private static function key($handle) {
		$handle = strval($handle);

		logger("Fetching diaspora key for: ".$handle);

		$r = self::person_by_handle($handle);
		if($r)
			return $r["pubkey"];

		return "";
	}

	/**
	 * @brief Fetches data for a given handle
	 *
	 * @param string $handle The handle
	 *
	 * @return array the queried data
	 */
	private static function person_by_handle($handle) {

		$r = q("SELECT * FROM `fcontact` WHERE `network` = '%s' AND `addr` = '%s' LIMIT 1",
			dbesc(NETWORK_DIASPORA),
			dbesc($handle)
		);
		if ($r) {
			$person = $r[0];
			logger("In cache ".print_r($r,true), LOGGER_DEBUG);

			// update record occasionally so it doesn't get stale
			$d = strtotime($person["updated"]." +00:00");
			if ($d < strtotime("now - 14 days"))
				$update = true;

			if ($person["guid"] == "")
				$update = true;
		}

		if (!$person OR $update) {
			logger("create or refresh", LOGGER_DEBUG);
			$r = probe_url($handle, PROBE_DIASPORA);

			// Note that Friendica contacts will return a "Diaspora person"
			// if Diaspora connectivity is enabled on their server
			if ($r AND ($r["network"] === NETWORK_DIASPORA)) {
				self::add_fcontact($r, $update);
				$person = $r;
			}
		}
		return $person;
	}

	/**
	 * @brief Updates the fcontact table
	 *
	 * @param array $arr The fcontact data
	 * @param bool $update Update or insert?
	 *
	 * @return string The id of the fcontact entry
	 */
	private static function add_fcontact($arr, $update = false) {

		if($update) {
			$r = q("UPDATE `fcontact` SET
					`name` = '%s',
					`photo` = '%s',
					`request` = '%s',
					`nick` = '%s',
					`addr` = '%s',
					`guid` = '%s',
					`batch` = '%s',
					`notify` = '%s',
					`poll` = '%s',
					`confirm` = '%s',
					`alias` = '%s',
					`pubkey` = '%s',
					`updated` = '%s'
				WHERE `url` = '%s' AND `network` = '%s'",
					dbesc($arr["name"]),
					dbesc($arr["photo"]),
					dbesc($arr["request"]),
					dbesc($arr["nick"]),
					dbesc(strtolower($arr["addr"])),
					dbesc($arr["guid"]),
					dbesc($arr["batch"]),
					dbesc($arr["notify"]),
					dbesc($arr["poll"]),
					dbesc($arr["confirm"]),
					dbesc($arr["alias"]),
					dbesc($arr["pubkey"]),
					dbesc(datetime_convert()),
					dbesc($arr["url"]),
					dbesc($arr["network"])
				);
		} else {
			$r = q("INSERT INTO `fcontact` (`url`,`name`,`photo`,`request`,`nick`,`addr`, `guid`,
					`batch`, `notify`,`poll`,`confirm`,`network`,`alias`,`pubkey`,`updated`)
				VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
					dbesc($arr["url"]),
					dbesc($arr["name"]),
					dbesc($arr["photo"]),
					dbesc($arr["request"]),
					dbesc($arr["nick"]),
					dbesc($arr["addr"]),
					dbesc($arr["guid"]),
					dbesc($arr["batch"]),
					dbesc($arr["notify"]),
					dbesc($arr["poll"]),
					dbesc($arr["confirm"]),
					dbesc($arr["network"]),
					dbesc($arr["alias"]),
					dbesc($arr["pubkey"]),
					dbesc(datetime_convert())
				);
		}

		return $r;
	}

	/**
	 * @brief get a handle (user@domain.tld) from a given contact id or gcontact id
	 *
	 * @param int $contact_id The id in the contact table
	 * @param int $gcontact_id The id in the gcontact table
	 *
	 * @return string the handle
	 */
	public static function handle_from_contact($contact_id, $gcontact_id = 0) {
		$handle = false;

		logger("contact id is ".$contact_id." - gcontact id is ".$gcontact_id, LOGGER_DEBUG);

		if ($gcontact_id != 0) {
			$r = q("SELECT `addr` FROM `gcontact` WHERE `id` = %d AND `addr` != ''",
				intval($gcontact_id));

			if (dbm::is_result($r)) {
				return strtolower($r[0]["addr"]);
			}
		}

		$r = q("SELECT `network`, `addr`, `self`, `url`, `nick` FROM `contact` WHERE `id` = %d",
			intval($contact_id));

		if (dbm::is_result($r)) {
			$contact = $r[0];

			logger("contact 'self' = ".$contact['self']." 'url' = ".$contact['url'], LOGGER_DEBUG);

			if ($contact['addr'] != "") {
				$handle = $contact['addr'];
			} else {
				$baseurl_start = strpos($contact['url'],'://') + 3;
				$baseurl_length = strpos($contact['url'],'/profile') - $baseurl_start; // allows installations in a subdirectory--not sure how Diaspora will handle
				$baseurl = substr($contact['url'], $baseurl_start, $baseurl_length);
				$handle = $contact['nick'].'@'.$baseurl;
			}
		}

		return strtolower($handle);
	}

	/**
	 * @brief get a url (scheme://domain.tld/u/user) from a given Diaspora*
	 * fcontact guid
	 *
	 * @param mixed $fcontact_guid Hexadecimal string guid
	 *
	 * @return string the contact url or null
	 */
	public static function url_from_contact_guid($fcontact_guid) {
		logger("fcontact guid is ".$fcontact_guid, LOGGER_DEBUG);

		$r = q("SELECT `url` FROM `fcontact` WHERE `url` != '' AND `network` = '%s' AND `guid` = '%s'",
			dbesc(NETWORK_DIASPORA),
			dbesc($fcontact_guid)
		);

		if (dbm::is_result($r)) {
			return $r[0]['url'];
		}

		return null;
	}

	/**
	 * @brief Get a contact id for a given handle
	 *
	 * @param int $uid The user id
	 * @param string $handle The handle in the format user@domain.tld
	 *
	 * @return The contact id
	 */
	private static function contact_by_handle($uid, $handle) {

		// First do a direct search on the contact table
		$r = q("SELECT * FROM `contact` WHERE `uid` = %d AND `addr` = '%s' LIMIT 1",
			intval($uid),
			dbesc($handle)
		);

		if (dbm::is_result($r)) {
			return $r[0];
		} else {
			// We haven't found it?
			// We use another function for it that will possibly create a contact entry
			$cid = get_contact($handle, $uid);

			if ($cid > 0) {
				$r = q("SELECT * FROM `contact` WHERE `id` = %d LIMIT 1", intval($cid));

				if (dbm::is_result($r)) {
					return $r[0];
				}
			}
		}

		$handle_parts = explode("@", $handle);
		$nurl_sql = "%%://".$handle_parts[1]."%%/profile/".$handle_parts[0];
		$r = q("SELECT * FROM `contact` WHERE `network` = '%s' AND `uid` = %d AND `nurl` LIKE '%s' LIMIT 1",
			dbesc(NETWORK_DFRN),
			intval($uid),
			dbesc($nurl_sql)
		);
		if (dbm::is_result($r)) {
			return $r[0];
		}

		logger("Haven't found contact for user ".$uid." and handle ".$handle, LOGGER_DEBUG);
		return false;
	}

	/**
	 * @brief Check if posting is allowed for this contact
	 *
	 * @param array $importer Array of the importer user
	 * @param array $contact The contact that is checked
	 * @param bool $is_comment Is the check for a comment?
	 *
	 * @return bool is the contact allowed to post?
	 */
	private static function post_allow($importer, $contact, $is_comment = false) {

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends.
		// Normally this should have handled by getting a request - but this could get lost
		if($contact["rel"] == CONTACT_IS_FOLLOWER && in_array($importer["page-flags"], array(PAGE_FREELOVE))) {
			q("UPDATE `contact` SET `rel` = %d, `writable` = 1 WHERE `id` = %d AND `uid` = %d",
				intval(CONTACT_IS_FRIEND),
				intval($contact["id"]),
				intval($importer["uid"])
			);
			$contact["rel"] = CONTACT_IS_FRIEND;
			logger("defining user ".$contact["nick"]." as friend");
		}

		if(($contact["blocked"]) || ($contact["readonly"]) || ($contact["archive"]))
			return false;
		if($contact["rel"] == CONTACT_IS_SHARING || $contact["rel"] == CONTACT_IS_FRIEND)
			return true;
		if($contact["rel"] == CONTACT_IS_FOLLOWER)
			if(($importer["page-flags"] == PAGE_COMMUNITY) OR $is_comment)
				return true;

		// Messages for the global users are always accepted
		if ($importer["uid"] == 0)
			return true;

		return false;
	}

	/**
	 * @brief Fetches the contact id for a handle and checks if posting is allowed
	 *
	 * @param array $importer Array of the importer user
	 * @param string $handle The checked handle in the format user@domain.tld
	 * @param bool $is_comment Is the check for a comment?
	 *
	 * @return array The contact data
	 */
	private static function allowed_contact_by_handle($importer, $handle, $is_comment = false) {
		$contact = self::contact_by_handle($importer["uid"], $handle);
		if (!$contact) {
			logger("A Contact for handle ".$handle." and user ".$importer["uid"]." was not found");
			return false;
		}

		if (!self::post_allow($importer, $contact, $is_comment)) {
			logger("The handle: ".$handle." is not allowed to post to user ".$importer["uid"]);
			return false;
		}
		return $contact;
	}

	/**
	 * @brief Does the message already exists on the system?
	 *
	 * @param int $uid The user id
	 * @param string $guid The guid of the message
	 *
	 * @return int|bool message id if the message already was stored into the system - or false.
	 */
	private static function message_exists($uid, $guid) {
		$r = q("SELECT `id` FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
			intval($uid),
			dbesc($guid)
		);

		if (dbm::is_result($r)) {
			logger("message ".$guid." already exists for user ".$uid);
			return $r[0]["id"];
		}

		return false;
	}

	/**
	 * @brief Checks for links to posts in a message
	 *
	 * @param array $item The item array
	 */
	private static function fetch_guid($item) {
		preg_replace_callback("&\[url=/posts/([^\[\]]*)\](.*)\[\/url\]&Usi",
			function ($match) use ($item){
				return(self::fetch_guid_sub($match, $item));
			},$item["body"]);
	}

	/**
	 * @brief Checks for relative /people/* links in an item body to match local
	 * contacts or prepends the remote host taken from the author link.
	 *
	 * @param string $body The item body to replace links from
	 * @param string $author_link The author link for missing local contact fallback
	 *
	 * @return the replaced string
	 */
	public function replace_people_guid($body, $author_link) {
		$return = preg_replace_callback("&\[url=/people/([^\[\]]*)\](.*)\[\/url\]&Usi",
			function ($match) use ($author_link) {
				// $match
				// 0 => '[url=/people/0123456789abcdef]Foo Bar[/url]'
				// 1 => '0123456789abcdef'
				// 2 => 'Foo Bar'
				$handle = self::url_from_contact_guid($match[1]);

				if ($handle) {
					$return = '@[url='.$handle.']'.$match[2].'[/url]';
				} else {
					// No local match, restoring absolute remote URL from author scheme and host
					$author_url = parse_url($author_link);
					$return = '[url='.$author_url['scheme'].'://'.$author_url['host'].'/people/'.$match[1].']'.$match[2].'[/url]';
				}

				return $return;
			}, $body);

		return $return;
	}

	/**
	 * @brief sub function of "fetch_guid" which checks for links in messages
	 *
	 * @param array $match array containing a link that has to be checked for a message link
	 * @param array $item The item array
	 */
	private static function fetch_guid_sub($match, $item) {
		if (!self::store_by_guid($match[1], $item["author-link"]))
			self::store_by_guid($match[1], $item["owner-link"]);
	}

	/**
	 * @brief Fetches an item with a given guid from a given server
	 *
	 * @param string $guid the message guid
	 * @param string $server The server address
	 * @param int $uid The user id of the user
	 *
	 * @return int the message id of the stored message or false
	 */
	private static function store_by_guid($guid, $server, $uid = 0) {
		$serverparts = parse_url($server);
		$server = $serverparts["scheme"]."://".$serverparts["host"];

		logger("Trying to fetch item ".$guid." from ".$server, LOGGER_DEBUG);

		$msg = self::message($guid, $server);

		if (!$msg)
			return false;

		logger("Successfully fetched item ".$guid." from ".$server, LOGGER_DEBUG);

		// Now call the dispatcher
		return self::dispatch_public($msg);
	}

	/**
	 * @brief Fetches a message from a server
	 *
	 * @param string $guid message guid
	 * @param string $server The url of the server
	 * @param int $level Endless loop prevention
	 *
	 * @return array
	 *      'message' => The message XML
	 *      'author' => The author handle
	 *      'key' => The public key of the author
	 */
	private static function message($guid, $server, $level = 0) {

		if ($level > 5)
			return false;

		// This will work for new Diaspora servers and Friendica servers from 3.5
		$source_url = $server."/fetch/post/".$guid;
		logger("Fetch post from ".$source_url, LOGGER_DEBUG);

		$envelope = fetch_url($source_url);
		if($envelope) {
			logger("Envelope was fetched.", LOGGER_DEBUG);
			$x = self::verify_magic_envelope($envelope);
			if (!$x)
				logger("Envelope could not be verified.", LOGGER_DEBUG);
			else
				logger("Envelope was verified.", LOGGER_DEBUG);
		} else
			$x = false;

		// This will work for older Diaspora and Friendica servers
		if (!$x) {
			$source_url = $server."/p/".$guid.".xml";
			logger("Fetch post from ".$source_url, LOGGER_DEBUG);

			$x = fetch_url($source_url);
			if(!$x)
				return false;
		}

		$source_xml = parse_xml_string($x, false);

		if (!is_object($source_xml))
			return false;

		if ($source_xml->post->reshare) {
			// Reshare of a reshare - old Diaspora version
			logger("Message is a reshare", LOGGER_DEBUG);
			return self::message($source_xml->post->reshare->root_guid, $server, ++$level);
		} elseif ($source_xml->getName() == "reshare") {
			// Reshare of a reshare - new Diaspora version
			logger("Message is a new reshare", LOGGER_DEBUG);
			return self::message($source_xml->root_guid, $server, ++$level);
		}

		$author = "";

		// Fetch the author - for the old and the new Diaspora version
		if ($source_xml->post->status_message->diaspora_handle)
			$author = (string)$source_xml->post->status_message->diaspora_handle;
		elseif ($source_xml->author AND ($source_xml->getName() == "status_message"))
			$author = (string)$source_xml->author;

		// If this isn't a "status_message" then quit
		if (!$author) {
			logger("Message doesn't seem to be a status message", LOGGER_DEBUG);
			return false;
		}

		$msg = array("message" => $x, "author" => $author);

		$msg["key"] = self::key($msg["author"]);

		return $msg;
	}

	/**
	 * @brief Fetches the item record of a given guid
	 *
	 * @param int $uid The user id
	 * @param string $guid message guid
	 * @param string $author The handle of the item
	 * @param array $contact The contact of the item owner
	 *
	 * @return array the item record
	 */
	private static function parent_item($uid, $guid, $author, $contact) {
		$r = q("SELECT `id`, `body`, `wall`, `uri`, `private`, `origin`,
				`author-name`, `author-link`, `author-avatar`,
				`owner-name`, `owner-link`, `owner-avatar`
			FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
			intval($uid), dbesc($guid));

		if(!$r) {
			$result = self::store_by_guid($guid, $contact["url"], $uid);

			if (!$result) {
				$person = self::person_by_handle($author);
				$result = self::store_by_guid($guid, $person["url"], $uid);
			}

			if ($result) {
				logger("Fetched missing item ".$guid." - result: ".$result, LOGGER_DEBUG);

				$r = q("SELECT `id`, `body`, `wall`, `uri`, `private`, `origin`,
						`author-name`, `author-link`, `author-avatar`,
						`owner-name`, `owner-link`, `owner-avatar`
					FROM `item` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
					intval($uid), dbesc($guid));
			}
		}

		if (!$r) {
			logger("parent item not found: parent: ".$guid." - user: ".$uid);
			return false;
		} else {
			logger("parent item found: parent: ".$guid." - user: ".$uid);
			return $r[0];
		}
	}

	/**
	 * @brief returns contact details
	 *
	 * @param array $contact The default contact if the person isn't found
	 * @param array $person The record of the person
	 * @param int $uid The user id
	 *
	 * @return array
	 *      'cid' => contact id
	 *      'network' => network type
	 */
	private static function author_contact_by_url($contact, $person, $uid) {

		$r = q("SELECT `id`, `network`, `url` FROM `contact` WHERE `nurl` = '%s' AND `uid` = %d LIMIT 1",
			dbesc(normalise_link($person["url"])), intval($uid));
		if ($r) {
			$cid = $r[0]["id"];
			$network = $r[0]["network"];

			// We are receiving content from a user that is about to be terminated
			// This means the user is vital, so we remove a possible termination date.
			unmark_for_death($contact);
		} else {
			$cid = $contact["id"];
			$network = NETWORK_DIASPORA;
		}

		return array("cid" => $cid, "network" => $network);
	}

	/**
	 * @brief Is the profile a hubzilla profile?
	 *
	 * @param string $url The profile link
	 *
	 * @return bool is it a hubzilla server?
	 */
	public static function is_redmatrix($url) {
		return(strstr($url, "/channel/"));
	}

	/**
	 * @brief Generate a post link with a given handle and message guid
	 *
	 * @param string $addr The user handle
	 * @param string $guid message guid
	 *
	 * @return string the post link
	 */
	private static function plink($addr, $guid) {
		$r = q("SELECT `url`, `nick`, `network` FROM `fcontact` WHERE `addr`='%s' LIMIT 1", dbesc($addr));

		// Fallback
		if (!$r)
			return "https://".substr($addr,strpos($addr,"@")+1)."/posts/".$guid;

		// Friendica contacts are often detected as Diaspora contacts in the "fcontact" table
		// So we try another way as well.
		$s = q("SELECT `network` FROM `gcontact` WHERE `nurl`='%s' LIMIT 1", dbesc(normalise_link($r[0]["url"])));
		if ($s)
			$r[0]["network"] = $s[0]["network"];

		if ($r[0]["network"] == NETWORK_DFRN)
			return(str_replace("/profile/".$r[0]["nick"]."/", "/display/".$guid, $r[0]["url"]."/"));

		if (self::is_redmatrix($r[0]["url"]))
			return $r[0]["url"]."/?f=&mid=".$guid;

		return "https://".substr($addr,strpos($addr,"@")+1)."/posts/".$guid;
	}

	/**
	 * @brief Processes an account deletion
	 *
	 * @param array $importer Array of the importer user
	 * @param object $data The message object
	 *
	 * @return bool Success
	 */
	private static function receive_account_deletion($importer, $data) {

		/// @todo Account deletion should remove the contact from the global contacts as well

		$author = notags(unxmlify($data->author));

		$contact = self::contact_by_handle($importer["uid"], $author);
		if (!$contact) {
			logger("cannot find contact for author: ".$author);
			return false;
		}

		// We now remove the contact
		contact_remove($contact["id"]);
		return true;
	}

	/**
	 * @brief Fetch the uri from our database if we already have this item (maybe from ourselves)
	 *
	 * @param string $author Author handle
	 * @param string $guid Message guid
	 * @param boolean $onlyfound Only return uri when found in the database
	 *
	 * @return string The constructed uri or the one from our database
	 */
	private static function get_uri_from_guid($author, $guid, $onlyfound = false) {

		$r = q("SELECT `uri` FROM `item` WHERE `guid` = '%s' LIMIT 1", dbesc($guid));
		if (dbm::is_result($r)) {
			return $r[0]["uri"];
		} elseif (!$onlyfound) {
			return $author.":".$guid;
		}

		return "";
	}

	/**
	 * @brief Fetch the guid from our database with a given uri
	 *
	 * @param string $author Author handle
	 * @param string $uri Message uri
	 *
	 * @return string The post guid
	 */
	private static function get_guid_from_uri($uri, $uid) {

		$r = q("SELECT `guid` FROM `item` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1", dbesc($uri), intval($uid));
		if (dbm::is_result($r)) {
			return $r[0]["guid"];
		} else {
			return false;
		}
	}

	/**
	 * @brief Processes an incoming comment
	 *
	 * @param array $importer Array of the importer user
	 * @param string $sender The sender of the message
	 * @param object $data The message object
	 * @param string $xml The original XML of the message
	 *
	 * @return int The message id of the generated comment or "false" if there was an error
	 */
	private static function receive_comment($importer, $sender, $data, $xml) {
		$guid = notags(unxmlify($data->guid));
		$parent_guid = notags(unxmlify($data->parent_guid));
		$text = unxmlify($data->text);
		$author = notags(unxmlify($data->author));

		if (isset($data->created_at)) {
			$created_at = datetime_convert("UTC", "UTC", notags(unxmlify($data->created_at)));
		} else {
			$created_at = datetime_convert();
		}

		if (isset($data->thread_parent_guid)) {
			$thread_parent_guid = notags(unxmlify($data->thread_parent_guid));
			$thr_uri = self::get_uri_from_guid("", $thread_parent_guid, true);
		} else {
			$thr_uri = "";
		}

		$contact = self::allowed_contact_by_handle($importer, $sender, true);
		if (!$contact) {
			return false;
		}

		$message_id = self::message_exists($importer["uid"], $guid);
		if ($message_id) {
			return $message_id;
		}

		$parent_item = self::parent_item($importer["uid"], $parent_guid, $author, $contact);
		if (!$parent_item) {
			return false;
		}

		$person = self::person_by_handle($author);
		if (!is_array($person)) {
			logger("unable to find author details");
			return false;
		}

		// Fetch the contact id - if we know this contact
		$author_contact = self::author_contact_by_url($contact, $person, $importer["uid"]);

		$datarray = array();

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $author_contact["cid"];
		$datarray["network"]  = $author_contact["network"];

		$datarray["author-name"] = $person["name"];
		$datarray["author-link"] = $person["url"];
		$datarray["author-avatar"] = ((x($person,"thumb")) ? $person["thumb"] : $person["photo"]);

		$datarray["owner-name"] = $contact["name"];
		$datarray["owner-link"] = $contact["url"];
		$datarray["owner-avatar"] = ((x($contact,"thumb")) ? $contact["thumb"] : $contact["photo"]);

		$datarray["guid"] = $guid;
		$datarray["uri"] = self::get_uri_from_guid($author, $guid);

		$datarray["type"] = "remote-comment";
		$datarray["verb"] = ACTIVITY_POST;
		$datarray["gravity"] = GRAVITY_COMMENT;

		if ($thr_uri != "") {
			$datarray["parent-uri"] = $thr_uri;
		} else {
			$datarray["parent-uri"] = $parent_item["uri"];
		}

		$datarray["object-type"] = ACTIVITY_OBJ_COMMENT;
		$datarray["object"] = $xml;

		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = $created_at;

		$body = diaspora2bb($text);

		$datarray["body"] = self::replace_people_guid($body, $person["url"]);

		self::fetch_guid($datarray);

		$message_id = item_store($datarray);

		if ($message_id) {
			logger("Stored comment ".$datarray["guid"]." with message id ".$message_id, LOGGER_DEBUG);
		}

		// If we are the origin of the parent we store the original data and notify our followers
		if($message_id AND $parent_item["origin"]) {

			// Formerly we stored the signed text, the signature and the author in different fields.
			// We now store the raw data so that we are more flexible.
			q("INSERT INTO `sign` (`iid`,`signed_text`) VALUES (%d,'%s')",
				intval($message_id),
				dbesc(json_encode($data))
			);

			// notify others
			proc_run(PRIORITY_HIGH, "include/notifier.php", "comment-import", $message_id);
		}

		return $message_id;
	}

	/**
	 * @brief processes and stores private messages
	 *
	 * @param array $importer Array of the importer user
	 * @param array $contact The contact of the message
	 * @param object $data The message object
	 * @param array $msg Array of the processed message, author handle and key
	 * @param object $mesg The private message
	 * @param array $conversation The conversation record to which this message belongs
	 *
	 * @return bool "true" if it was successful
	 */
	private static function receive_conversation_message($importer, $contact, $data, $msg, $mesg, $conversation) {
		$guid = notags(unxmlify($data->guid));
		$subject = notags(unxmlify($data->subject));
		$author = notags(unxmlify($data->author));

		$msg_guid = notags(unxmlify($mesg->guid));
		$msg_parent_guid = notags(unxmlify($mesg->parent_guid));
		$msg_parent_author_signature = notags(unxmlify($mesg->parent_author_signature));
		$msg_author_signature = notags(unxmlify($mesg->author_signature));
		$msg_text = unxmlify($mesg->text);
		$msg_created_at = datetime_convert("UTC", "UTC", notags(unxmlify($mesg->created_at)));

		// "diaspora_handle" is the element name from the old version
		// "author" is the element name from the new version
		if ($mesg->author) {
			$msg_author = notags(unxmlify($mesg->author));
		} elseif ($mesg->diaspora_handle) {
			$msg_author = notags(unxmlify($mesg->diaspora_handle));
		} else {
			return false;
		}

		$msg_conversation_guid = notags(unxmlify($mesg->conversation_guid));

		if ($msg_conversation_guid != $guid) {
			logger("message conversation guid does not belong to the current conversation.");
			return false;
		}

		$body = diaspora2bb($msg_text);
		$message_uri = $msg_author.":".$msg_guid;

		$author_signed_data = $msg_guid.";".$msg_parent_guid.";".$msg_text.";".unxmlify($mesg->created_at).";".$msg_author.";".$msg_conversation_guid;

		$author_signature = base64_decode($msg_author_signature);

		if (strcasecmp($msg_author,$msg["author"]) == 0) {
			$person = $contact;
			$key = $msg["key"];
		} else {
			$person = self::person_by_handle($msg_author);

			if (is_array($person) && x($person, "pubkey")) {
				$key = $person["pubkey"];
			} else {
				logger("unable to find author details");
					return false;
			}
		}

		if (!rsa_verify($author_signed_data, $author_signature, $key, "sha256")) {
			logger("verification failed.");
			return false;
		}

		if ($msg_parent_author_signature) {
			$owner_signed_data = $msg_guid.";".$msg_parent_guid.";".$msg_text.";".unxmlify($mesg->created_at).";".$msg_author.";".$msg_conversation_guid;

			$parent_author_signature = base64_decode($msg_parent_author_signature);

			$key = $msg["key"];

			if (!rsa_verify($owner_signed_data, $parent_author_signature, $key, "sha256")) {
				logger("owner verification failed.");
				return false;
			}
		}

		$r = q("SELECT `id` FROM `mail` WHERE `uri` = '%s' LIMIT 1",
			dbesc($message_uri)
		);
		if (dbm::is_result($r)) {
			logger("duplicate message already delivered.", LOGGER_DEBUG);
			return false;
		}

		q("INSERT INTO `mail` (`uid`, `guid`, `convid`, `from-name`,`from-photo`,`from-url`,`contact-id`,`title`,`body`,`seen`,`reply`,`uri`,`parent-uri`,`created`)
			VALUES (%d, '%s', %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, '%s','%s','%s')",
			intval($importer["uid"]),
			dbesc($msg_guid),
			intval($conversation["id"]),
			dbesc($person["name"]),
			dbesc($person["photo"]),
			dbesc($person["url"]),
			intval($contact["id"]),
			dbesc($subject),
			dbesc($body),
			0,
			0,
			dbesc($message_uri),
			dbesc($author.":".$guid),
			dbesc($msg_created_at)
		);

		q("UPDATE `conv` SET `updated` = '%s' WHERE `id` = %d",
			dbesc(datetime_convert()),
			intval($conversation["id"])
		);

		notification(array(
			"type" => NOTIFY_MAIL,
			"notify_flags" => $importer["notify-flags"],
			"language" => $importer["language"],
			"to_name" => $importer["username"],
			"to_email" => $importer["email"],
			"uid" =>$importer["uid"],
			"item" => array("subject" => $subject, "body" => $body),
			"source_name" => $person["name"],
			"source_link" => $person["url"],
			"source_photo" => $person["thumb"],
			"verb" => ACTIVITY_POST,
			"otype" => "mail"
		));
		return true;
	}

	/**
	 * @brief Processes new private messages (answers to private messages are processed elsewhere)
	 *
	 * @param array $importer Array of the importer user
	 * @param array $msg Array of the processed message, author handle and key
	 * @param object $data The message object
	 *
	 * @return bool Success
	 */
	private static function receive_conversation($importer, $msg, $data) {
		$guid = notags(unxmlify($data->guid));
		$subject = notags(unxmlify($data->subject));
		$created_at = datetime_convert("UTC", "UTC", notags(unxmlify($data->created_at)));
		$author = notags(unxmlify($data->author));
		$participants = notags(unxmlify($data->participants));

		$messages = $data->message;

		if (!count($messages)) {
			logger("empty conversation");
			return false;
		}

		$contact = self::allowed_contact_by_handle($importer, $msg["author"], true);
		if (!$contact)
			return false;

		$conversation = null;

		$c = q("SELECT * FROM `conv` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
			intval($importer["uid"]),
			dbesc($guid)
		);
		if($c)
			$conversation = $c[0];
		else {
			$r = q("INSERT INTO `conv` (`uid`, `guid`, `creator`, `created`, `updated`, `subject`, `recips`)
				VALUES (%d, '%s', '%s', '%s', '%s', '%s', '%s')",
				intval($importer["uid"]),
				dbesc($guid),
				dbesc($author),
				dbesc($created_at),
				dbesc(datetime_convert()),
				dbesc($subject),
				dbesc($participants)
			);
			if($r)
				$c = q("SELECT * FROM `conv` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
					intval($importer["uid"]),
					dbesc($guid)
				);

			if($c)
				$conversation = $c[0];
		}
		if (!$conversation) {
			logger("unable to create conversation.");
			return;
		}

		foreach($messages as $mesg)
			self::receive_conversation_message($importer, $contact, $data, $msg, $mesg, $conversation);

		return true;
	}

	/**
	 * @brief Creates the body for a "like" message
	 *
	 * @param array $contact The contact that send us the "like"
	 * @param array $parent_item The item array of the parent item
	 * @param string $guid message guid
	 *
	 * @return string the body
	 */
	private static function construct_like_body($contact, $parent_item, $guid) {
		$bodyverb = t('%1$s likes %2$s\'s %3$s');

		$ulink = "[url=".$contact["url"]."]".$contact["name"]."[/url]";
		$alink = "[url=".$parent_item["author-link"]."]".$parent_item["author-name"]."[/url]";
		$plink = "[url=".App::get_baseurl()."/display/".urlencode($guid)."]".t("status")."[/url]";

		return sprintf($bodyverb, $ulink, $alink, $plink);
	}

	/**
	 * @brief Creates a XML object for a "like"
	 *
	 * @param array $importer Array of the importer user
	 * @param array $parent_item The item array of the parent item
	 *
	 * @return string The XML
	 */
	private static function construct_like_object($importer, $parent_item) {
		$objtype = ACTIVITY_OBJ_NOTE;
		$link = '<link rel="alternate" type="text/html" href="'.App::get_baseurl()."/display/".$importer["nickname"]."/".$parent_item["id"].'" />';
		$parent_body = $parent_item["body"];

		$xmldata = array("object" => array("type" => $objtype,
						"local" => "1",
						"id" => $parent_item["uri"],
						"link" => $link,
						"title" => "",
						"content" => $parent_body));

		return xml::from_array($xmldata, $xml, true);
	}

	/**
	 * @brief Processes "like" messages
	 *
	 * @param array $importer Array of the importer user
	 * @param string $sender The sender of the message
	 * @param object $data The message object
	 *
	 * @return int The message id of the generated like or "false" if there was an error
	 */
	private static function receive_like($importer, $sender, $data) {
		$positive = notags(unxmlify($data->positive));
		$guid = notags(unxmlify($data->guid));
		$parent_type = notags(unxmlify($data->parent_type));
		$parent_guid = notags(unxmlify($data->parent_guid));
		$author = notags(unxmlify($data->author));

		// likes on comments aren't supported by Diaspora - only on posts
		// But maybe this will be supported in the future, so we will accept it.
		if (!in_array($parent_type, array("Post", "Comment")))
			return false;

		$contact = self::allowed_contact_by_handle($importer, $sender, true);
		if (!$contact)
			return false;

		$message_id = self::message_exists($importer["uid"], $guid);
		if ($message_id)
			return $message_id;

		$parent_item = self::parent_item($importer["uid"], $parent_guid, $author, $contact);
		if (!$parent_item)
			return false;

		$person = self::person_by_handle($author);
		if (!is_array($person)) {
			logger("unable to find author details");
			return false;
		}

		// Fetch the contact id - if we know this contact
		$author_contact = self::author_contact_by_url($contact, $person, $importer["uid"]);

		// "positive" = "false" would be a Dislike - wich isn't currently supported by Diaspora
		// We would accept this anyhow.
		if ($positive == "true")
			$verb = ACTIVITY_LIKE;
		else
			$verb = ACTIVITY_DISLIKE;

		$datarray = array();

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $author_contact["cid"];
		$datarray["network"]  = $author_contact["network"];

		$datarray["author-name"] = $person["name"];
		$datarray["author-link"] = $person["url"];
		$datarray["author-avatar"] = ((x($person,"thumb")) ? $person["thumb"] : $person["photo"]);

		$datarray["owner-name"] = $contact["name"];
		$datarray["owner-link"] = $contact["url"];
		$datarray["owner-avatar"] = ((x($contact,"thumb")) ? $contact["thumb"] : $contact["photo"]);

		$datarray["guid"] = $guid;
		$datarray["uri"] = self::get_uri_from_guid($author, $guid);

		$datarray["type"] = "activity";
		$datarray["verb"] = $verb;
		$datarray["gravity"] = GRAVITY_LIKE;
		$datarray["parent-uri"] = $parent_item["uri"];

		$datarray["object-type"] = ACTIVITY_OBJ_NOTE;
		$datarray["object"] = self::construct_like_object($importer, $parent_item);

		$datarray["body"] = self::construct_like_body($contact, $parent_item, $guid);

		$message_id = item_store($datarray);

		if ($message_id)
			logger("Stored like ".$datarray["guid"]." with message id ".$message_id, LOGGER_DEBUG);

		// If we are the origin of the parent we store the original data and notify our followers
		if($message_id AND $parent_item["origin"]) {

			// Formerly we stored the signed text, the signature and the author in different fields.
			// We now store the raw data so that we are more flexible.
			q("INSERT INTO `sign` (`iid`,`signed_text`) VALUES (%d,'%s')",
				intval($message_id),
				dbesc(json_encode($data))
			);

			// notify others
			proc_run(PRIORITY_HIGH, "include/notifier.php", "comment-import", $message_id);
		}

		return $message_id;
	}

	/**
	 * @brief Processes private messages
	 *
	 * @param array $importer Array of the importer user
	 * @param object $data The message object
	 *
	 * @return bool Success?
	 */
	private static function receive_message($importer, $data) {
		$guid = notags(unxmlify($data->guid));
		$parent_guid = notags(unxmlify($data->parent_guid));
		$text = unxmlify($data->text);
		$created_at = datetime_convert("UTC", "UTC", notags(unxmlify($data->created_at)));
		$author = notags(unxmlify($data->author));
		$conversation_guid = notags(unxmlify($data->conversation_guid));

		$contact = self::allowed_contact_by_handle($importer, $author, true);
		if (!$contact) {
			return false;
		}

		$conversation = null;

		$c = q("SELECT * FROM `conv` WHERE `uid` = %d AND `guid` = '%s' LIMIT 1",
			intval($importer["uid"]),
			dbesc($conversation_guid)
		);
		if ($c) {
			$conversation = $c[0];
		} else {
			logger("conversation not available.");
			return false;
		}

		$message_uri = $author.":".$guid;

		$person = self::person_by_handle($author);
		if (!$person) {
			logger("unable to find author details");
			return false;
		}

		$r = q("SELECT `id` FROM `mail` WHERE `uri` = '%s' AND `uid` = %d LIMIT 1",
			dbesc($message_uri),
			intval($importer["uid"])
		);
		if (dbm::is_result($r)) {
			logger("duplicate message already delivered.", LOGGER_DEBUG);
			return false;
		}

		$body = diaspora2bb($text);

		$body = self::replace_people_guid($body, $person["url"]);

		q("INSERT INTO `mail` (`uid`, `guid`, `convid`, `from-name`,`from-photo`,`from-url`,`contact-id`,`title`,`body`,`seen`,`reply`,`uri`,`parent-uri`,`created`)
				VALUES ( %d, '%s', %d, '%s', '%s', '%s', %d, '%s', '%s', %d, %d, '%s','%s','%s')",
			intval($importer["uid"]),
			dbesc($guid),
			intval($conversation["id"]),
			dbesc($person["name"]),
			dbesc($person["photo"]),
			dbesc($person["url"]),
			intval($contact["id"]),
			dbesc($conversation["subject"]),
			dbesc($body),
			0,
			1,
			dbesc($message_uri),
			dbesc($author.":".$parent_guid),
			dbesc($created_at)
		);

		q("UPDATE `conv` SET `updated` = '%s' WHERE `id` = %d",
			dbesc(datetime_convert()),
			intval($conversation["id"])
		);

		return true;
	}

	/**
	 * @brief Processes participations - unsupported by now
	 *
	 * @param array $importer Array of the importer user
	 * @param object $data The message object
	 *
	 * @return bool always true
	 */
	private static function receive_participation($importer, $data) {
		// I'm not sure if we can fully support this message type
		return true;
	}

	/**
	 * @brief Processes photos - unneeded
	 *
	 * @param array $importer Array of the importer user
	 * @param object $data The message object
	 *
	 * @return bool always true
	 */
	private static function receive_photo($importer, $data) {
		// There doesn't seem to be a reason for this function, since the photo data is transmitted in the status message as well
		return true;
	}

	/**
	 * @brief Processes poll participations - unssupported
	 *
	 * @param array $importer Array of the importer user
	 * @param object $data The message object
	 *
	 * @return bool always true
	 */
	private static function receive_poll_participation($importer, $data) {
		// We don't support polls by now
		return true;
	}

	/**
	 * @brief Processes incoming profile updates
	 *
	 * @param array $importer Array of the importer user
	 * @param object $data The message object
	 *
	 * @return bool Success
	 */
	private static function receive_profile($importer, $data) {
		$author = strtolower(notags(unxmlify($data->author)));

		$contact = self::contact_by_handle($importer["uid"], $author);
		if (!$contact)
			return false;

		$name = unxmlify($data->first_name).((strlen($data->last_name)) ? " ".unxmlify($data->last_name) : "");
		$image_url = unxmlify($data->image_url);
		$birthday = unxmlify($data->birthday);
		$location = diaspora2bb(unxmlify($data->location));
		$about = diaspora2bb(unxmlify($data->bio));
		$gender = unxmlify($data->gender);
		$searchable = (unxmlify($data->searchable) == "true");
		$nsfw = (unxmlify($data->nsfw) == "true");
		$tags = unxmlify($data->tag_string);

		$tags = explode("#", $tags);

		$keywords = array();
		foreach ($tags as $tag) {
			$tag = trim(strtolower($tag));
			if ($tag != "")
				$keywords[] = $tag;
		}

		$keywords = implode(", ", $keywords);

		$handle_parts = explode("@", $author);
		$nick = $handle_parts[0];

		if($name === "")
			$name = $handle_parts[0];

		if( preg_match("|^https?://|", $image_url) === 0)
			$image_url = "http://".$handle_parts[1].$image_url;

		update_contact_avatar($image_url, $importer["uid"], $contact["id"]);

		// Generic birthday. We don't know the timezone. The year is irrelevant.

		$birthday = str_replace("1000", "1901", $birthday);

		if ($birthday != "")
			$birthday = datetime_convert("UTC", "UTC", $birthday, "Y-m-d");

		// this is to prevent multiple birthday notifications in a single year
		// if we already have a stored birthday and the 'm-d' part hasn't changed, preserve the entry, which will preserve the notify year

		if(substr($birthday,5) === substr($contact["bd"],5))
			$birthday = $contact["bd"];

		$r = q("UPDATE `contact` SET `name` = '%s', `nick` = '%s', `addr` = '%s', `name-date` = '%s', `bd` = '%s',
				`location` = '%s', `about` = '%s', `keywords` = '%s', `gender` = '%s' WHERE `id` = %d AND `uid` = %d",
			dbesc($name),
			dbesc($nick),
			dbesc($author),
			dbesc(datetime_convert()),
			dbesc($birthday),
			dbesc($location),
			dbesc($about),
			dbesc($keywords),
			dbesc($gender),
			intval($contact["id"]),
			intval($importer["uid"])
		);

		if ($searchable) {
			poco_check($contact["url"], $name, NETWORK_DIASPORA, $image_url, $about, $location, $gender, $keywords, "",
				datetime_convert(), 2, $contact["id"], $importer["uid"]);
		}

		$gcontact = array("url" => $contact["url"], "network" => NETWORK_DIASPORA, "generation" => 2,
					"photo" => $image_url, "name" => $name, "location" => $location,
					"about" => $about, "birthday" => $birthday, "gender" => $gender,
					"addr" => $author, "nick" => $nick, "keywords" => $keywords,
					"hide" => !$searchable, "nsfw" => $nsfw);

		update_gcontact($gcontact);

		logger("Profile of contact ".$contact["id"]." stored for user ".$importer["uid"], LOGGER_DEBUG);

		return true;
	}

	/**
	 * @brief Processes incoming friend requests
	 *
	 * @param array $importer Array of the importer user
	 * @param array $contact The contact that send the request
	 */
	private static function receive_request_make_friend($importer, $contact) {

		$a = get_app();

		if($contact["rel"] == CONTACT_IS_FOLLOWER && in_array($importer["page-flags"], array(PAGE_FREELOVE))) {
			q("UPDATE `contact` SET `rel` = %d, `writable` = 1 WHERE `id` = %d AND `uid` = %d",
				intval(CONTACT_IS_FRIEND),
				intval($contact["id"]),
				intval($importer["uid"])
			);
		}
		// send notification

		$r = q("SELECT `hide-friends` FROM `profile` WHERE `uid` = %d AND `is-default` = 1 LIMIT 1",
			intval($importer["uid"])
		);

		if($r && !$r[0]["hide-friends"] && !$contact["hidden"] && intval(get_pconfig($importer["uid"], "system", "post_newfriend"))) {

			$self = q("SELECT * FROM `contact` WHERE `self` AND `uid` = %d LIMIT 1",
				intval($importer["uid"])
			);

			// they are not CONTACT_IS_FOLLOWER anymore but that's what we have in the array

			if($self && $contact["rel"] == CONTACT_IS_FOLLOWER) {

				$arr = array();
				$arr["uri"] = $arr["parent-uri"] = item_new_uri($a->get_hostname(), $importer["uid"]);
				$arr["uid"] = $importer["uid"];
				$arr["contact-id"] = $self[0]["id"];
				$arr["wall"] = 1;
				$arr["type"] = 'wall';
				$arr["gravity"] = 0;
				$arr["origin"] = 1;
				$arr["author-name"] = $arr["owner-name"] = $self[0]["name"];
				$arr["author-link"] = $arr["owner-link"] = $self[0]["url"];
				$arr["author-avatar"] = $arr["owner-avatar"] = $self[0]["thumb"];
				$arr["verb"] = ACTIVITY_FRIEND;
				$arr["object-type"] = ACTIVITY_OBJ_PERSON;

				$A = "[url=".$self[0]["url"]."]".$self[0]["name"]."[/url]";
				$B = "[url=".$contact["url"]."]".$contact["name"]."[/url]";
				$BPhoto = "[url=".$contact["url"]."][img]".$contact["thumb"]."[/img][/url]";
				$arr["body"] = sprintf(t("%1$s is now friends with %2$s"), $A, $B)."\n\n\n".$Bphoto;

				$arr["object"] = self::construct_new_friend_object($contact);

				$arr["last-child"] = 1;

				$arr["allow_cid"] = $user[0]["allow_cid"];
				$arr["allow_gid"] = $user[0]["allow_gid"];
				$arr["deny_cid"]  = $user[0]["deny_cid"];
				$arr["deny_gid"]  = $user[0]["deny_gid"];

				$i = item_store($arr);
				if($i)
					proc_run(PRIORITY_HIGH, "include/notifier.php", "activity", $i);
			}
		}
	}

	/**
	 * @brief Creates a XML object for a "new friend" message
	 *
	 * @param array $contact Array of the contact
	 *
	 * @return string The XML
	 */
	private static function construct_new_friend_object($contact) {
		$objtype = ACTIVITY_OBJ_PERSON;
		$link = '<link rel="alternate" type="text/html" href="'.$contact["url"].'" />'."\n".
			'<link rel="photo" type="image/jpeg" href="'.$contact["thumb"].'" />'."\n";

		$xmldata = array("object" => array("type" => $objtype,
						"title" => $contact["name"],
						"id" => $contact["url"]."/".$contact["name"],
						"link" => $link));

		return xml::from_array($xmldata, $xml, true);
	}

	/**
	 * @brief Processes incoming sharing notification
	 *
	 * @param array $importer Array of the importer user
	 * @param object $data The message object
	 *
	 * @return bool Success
	 */
	private static function receive_contact_request($importer, $data) {
		$author = unxmlify($data->author);
		$recipient = unxmlify($data->recipient);

		if (!$author || !$recipient) {
			return false;
		}

		// the current protocol version doesn't know these fields
		// That means that we will assume their existance
		if (isset($data->following)) {
			$following = (unxmlify($data->following) == "true");
		} else {
			$following = true;
		}

		if (isset($data->sharing)) {
			$sharing = (unxmlify($data->sharing) == "true");
		} else {
			$sharing = true;
		}

		$contact = self::contact_by_handle($importer["uid"],$author);

		// perhaps we were already sharing with this person. Now they're sharing with us.
		// That makes us friends.
		if ($contact) {
			if ($following AND $sharing) {
				logger("Author ".$author." (Contact ".$contact["id"].") wants to have a bidirectional conection.", LOGGER_DEBUG);
				self::receive_request_make_friend($importer, $contact);

				// refetch the contact array
				$contact = self::contact_by_handle($importer["uid"],$author);

				// If we are now friends, we are sending a share message.
				// Normally we needn't to do so, but the first message could have been vanished.
				if (in_array($contact["rel"], array(CONTACT_IS_FRIEND, CONTACT_IS_FOLLOWER))) {
					$u = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1", intval($importer["uid"]));
					if ($u) {
						logger("Sending share message to author ".$author." - Contact: ".$contact["id"]." - User: ".$importer["uid"], LOGGER_DEBUG);
						$ret = self::send_share($u[0], $contact);
					}
				}
				return true;
			} else { /// @todo Handle all possible variations of adding and retracting of permissions
				logger("Author ".$author." (Contact ".$contact["id"].") wants to change the relationship: Following: ".$following." - sharing: ".$sharing. "(By now unsupported)", LOGGER_DEBUG);
				return false;
			}
		}

		if (!$following AND $sharing AND in_array($importer["page-flags"], array(PAGE_SOAPBOX, PAGE_NORMAL))) {
			logger("Author ".$author." wants to share with us - but doesn't want to listen. Request is ignored.", LOGGER_DEBUG);
			return false;
		} elseif (!$following AND !$sharing) {
			logger("Author ".$author." doesn't want anything - and we don't know the author. Request is ignored.", LOGGER_DEBUG);
			return false;
		} elseif (!$following AND $sharing) {
			logger("Author ".$author." wants to share with us.", LOGGER_DEBUG);
		} elseif ($following AND $sharing) {
			logger("Author ".$author." wants to have a bidirectional conection.", LOGGER_DEBUG);
		} elseif ($following AND !$sharing) {
			logger("Author ".$author." wants to listen to us.", LOGGER_DEBUG);
		}

		$ret = self::person_by_handle($author);

		if (!$ret || ($ret["network"] != NETWORK_DIASPORA)) {
			logger("Cannot resolve diaspora handle ".$author." for ".$recipient);
			return false;
		}

		$batch = (($ret["batch"]) ? $ret["batch"] : implode("/", array_slice(explode("/", $ret["url"]), 0, 3))."/receive/public");

		$r = q("INSERT INTO `contact` (`uid`, `network`,`addr`,`created`,`url`,`nurl`,`batch`,`name`,`nick`,`photo`,`pubkey`,`notify`,`poll`,`blocked`,`priority`)
			VALUES (%d, '%s', '%s', '%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s',%d,%d)",
			intval($importer["uid"]),
			dbesc($ret["network"]),
			dbesc($ret["addr"]),
			datetime_convert(),
			dbesc($ret["url"]),
			dbesc(normalise_link($ret["url"])),
			dbesc($batch),
			dbesc($ret["name"]),
			dbesc($ret["nick"]),
			dbesc($ret["photo"]),
			dbesc($ret["pubkey"]),
			dbesc($ret["notify"]),
			dbesc($ret["poll"]),
			1,
			2
		);

		// find the contact record we just created

		$contact_record = self::contact_by_handle($importer["uid"],$author);

		if (!$contact_record) {
			logger("unable to locate newly created contact record.");
			return;
		}

		logger("Author ".$author." was added as contact number ".$contact_record["id"].".", LOGGER_DEBUG);

		$def_gid = get_default_group($importer['uid'], $ret["network"]);

		if(intval($def_gid))
			group_add_member($importer["uid"], "", $contact_record["id"], $def_gid);

		update_contact_avatar($ret["photo"], $importer['uid'], $contact_record["id"], true);

		if($importer["page-flags"] == PAGE_NORMAL) {

			logger("Sending intra message for author ".$author.".", LOGGER_DEBUG);

			$hash = random_string().(string)time();   // Generate a confirm_key

			$ret = q("INSERT INTO `intro` (`uid`, `contact-id`, `blocked`, `knowyou`, `note`, `hash`, `datetime`)
				VALUES (%d, %d, %d, %d, '%s', '%s', '%s')",
				intval($importer["uid"]),
				intval($contact_record["id"]),
				0,
				0,
				dbesc(t("Sharing notification from Diaspora network")),
				dbesc($hash),
				dbesc(datetime_convert())
			);
		} else {

			// automatic friend approval

			logger("Does an automatic friend approval for author ".$author.".", LOGGER_DEBUG);

			update_contact_avatar($contact_record["photo"],$importer["uid"],$contact_record["id"]);

			// technically they are sharing with us (CONTACT_IS_SHARING),
			// but if our page-type is PAGE_COMMUNITY or PAGE_SOAPBOX
			// we are going to change the relationship and make them a follower.

			if (($importer["page-flags"] == PAGE_FREELOVE) AND $sharing AND $following)
				$new_relation = CONTACT_IS_FRIEND;
			elseif (($importer["page-flags"] == PAGE_FREELOVE) AND $sharing)
				$new_relation = CONTACT_IS_SHARING;
			else
				$new_relation = CONTACT_IS_FOLLOWER;

			$r = q("UPDATE `contact` SET `rel` = %d,
				`name-date` = '%s',
				`uri-date` = '%s',
				`blocked` = 0,
				`pending` = 0,
				`writable` = 1
				WHERE `id` = %d
				",
				intval($new_relation),
				dbesc(datetime_convert()),
				dbesc(datetime_convert()),
				intval($contact_record["id"])
			);

			$u = q("SELECT * FROM `user` WHERE `uid` = %d LIMIT 1", intval($importer["uid"]));
			if($u) {
				logger("Sending share message (Relation: ".$new_relation.") to author ".$author." - Contact: ".$contact_record["id"]." - User: ".$importer["uid"], LOGGER_DEBUG);
				$ret = self::send_share($u[0], $contact_record);

				// Send the profile data, maybe it weren't transmitted before
				self::send_profile($importer["uid"], array($contact_record));
			}
		}

		return true;
	}

	/**
	 * @brief Fetches a message with a given guid
	 *
	 * @param string $guid message guid
	 * @param string $orig_author handle of the original post
	 * @param string $author handle of the sharer
	 *
	 * @return array The fetched item
	 */
	private static function original_item($guid, $orig_author, $author) {

		// Do we already have this item?
		$r = q("SELECT `body`, `tag`, `app`, `created`, `object-type`, `uri`, `guid`,
				`author-name`, `author-link`, `author-avatar`
				FROM `item` WHERE `guid` = '%s' AND `visible` AND NOT `deleted` AND `body` != '' LIMIT 1",
			dbesc($guid));

		if (dbm::is_result($r)) {
			logger("reshared message ".$guid." already exists on system.");

			// Maybe it is already a reshared item?
			// Then refetch the content, if it is a reshare from a reshare.
			// If it is a reshared post from another network then reformat to avoid display problems with two share elements
			if (self::is_reshare($r[0]["body"], true)) {
				$r = array();
			} elseif (self::is_reshare($r[0]["body"], false)) {
				$r[0]["body"] = diaspora2bb(bb2diaspora($r[0]["body"]));

				$r[0]["body"] = self::replace_people_guid($r[0]["body"], $r[0]["author-link"]);

				// Add OEmbed and other information to the body
				$r[0]["body"] = add_page_info_to_body($r[0]["body"], false, true);

				return $r[0];
			} else {
				return $r[0];
			}
		}

		if (!dbm::is_result($r)) {
			$server = "https://".substr($orig_author, strpos($orig_author, "@") + 1);
			logger("1st try: reshared message ".$guid." will be fetched via SSL from the server ".$server);
			$item_id = self::store_by_guid($guid, $server);

			if (!$item_id) {
				$server = "http://".substr($orig_author, strpos($orig_author, "@") + 1);
				logger("2nd try: reshared message ".$guid." will be fetched without SLL from the server ".$server);
				$item_id = self::store_by_guid($guid, $server);
			}

			if ($item_id) {
				$r = q("SELECT `body`, `tag`, `app`, `created`, `object-type`, `uri`, `guid`,
						`author-name`, `author-link`, `author-avatar`
					FROM `item` WHERE `id` = %d AND `visible` AND NOT `deleted` AND `body` != '' LIMIT 1",
					intval($item_id));

				if (dbm::is_result($r)) {
					// If it is a reshared post from another network then reformat to avoid display problems with two share elements
					if (self::is_reshare($r[0]["body"], false)) {
						$r[0]["body"] = diaspora2bb(bb2diaspora($r[0]["body"]));
						$r[0]["body"] = self::replace_people_guid($r[0]["body"], $r[0]["author-link"]);
					}

					return $r[0];
				}

			}
		}
		return false;
	}

	/**
	 * @brief Processes a reshare message
	 *
	 * @param array $importer Array of the importer user
	 * @param object $data The message object
	 * @param string $xml The original XML of the message
	 *
	 * @return int the message id
	 */
	private static function receive_reshare($importer, $data, $xml) {
		$root_author = notags(unxmlify($data->root_author));
		$root_guid = notags(unxmlify($data->root_guid));
		$guid = notags(unxmlify($data->guid));
		$author = notags(unxmlify($data->author));
		$public = notags(unxmlify($data->public));
		$created_at = datetime_convert("UTC", "UTC", notags(unxmlify($data->created_at)));

		$contact = self::allowed_contact_by_handle($importer, $author, false);
		if (!$contact) {
			return false;
		}

		$message_id = self::message_exists($importer["uid"], $guid);
		if ($message_id) {
			return $message_id;
		}

		$original_item = self::original_item($root_guid, $root_author, $author);
		if (!$original_item) {
			return false;
		}

		$orig_url = App::get_baseurl()."/display/".$original_item["guid"];

		$datarray = array();

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $contact["id"];
		$datarray["network"]  = NETWORK_DIASPORA;

		$datarray["author-name"] = $contact["name"];
		$datarray["author-link"] = $contact["url"];
		$datarray["author-avatar"] = ((x($contact,"thumb")) ? $contact["thumb"] : $contact["photo"]);

		$datarray["owner-name"] = $datarray["author-name"];
		$datarray["owner-link"] = $datarray["author-link"];
		$datarray["owner-avatar"] = $datarray["author-avatar"];

		$datarray["guid"] = $guid;
		$datarray["uri"] = $datarray["parent-uri"] = self::get_uri_from_guid($author, $guid);

		$datarray["verb"] = ACTIVITY_POST;
		$datarray["gravity"] = GRAVITY_PARENT;

		$datarray["object"] = $xml;

		$prefix = share_header($original_item["author-name"], $original_item["author-link"], $original_item["author-avatar"],
					$original_item["guid"], $original_item["created"], $orig_url);
		$datarray["body"] = $prefix.$original_item["body"]."[/share]";

		$datarray["tag"] = $original_item["tag"];
		$datarray["app"]  = $original_item["app"];

		$datarray["plink"] = self::plink($author, $guid);
		$datarray["private"] = (($public == "false") ? 1 : 0);
		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = $created_at;

		$datarray["object-type"] = $original_item["object-type"];

		self::fetch_guid($datarray);
		$message_id = item_store($datarray);

		if ($message_id) {
			logger("Stored reshare ".$datarray["guid"]." with message id ".$message_id, LOGGER_DEBUG);
		}

		return $message_id;
	}

	/**
	 * @brief Processes retractions
	 *
	 * @param array $importer Array of the importer user
	 * @param array $contact The contact of the item owner
	 * @param object $data The message object
	 *
	 * @return bool success
	 */
	private static function item_retraction($importer, $contact, $data) {
		$target_type = notags(unxmlify($data->target_type));
		$target_guid = notags(unxmlify($data->target_guid));
		$author = notags(unxmlify($data->author));

		$person = self::person_by_handle($author);
		if (!is_array($person)) {
			logger("unable to find author detail for ".$author);
			return false;
		}

		$r = q("SELECT `id`, `parent`, `parent-uri`, `author-link` FROM `item` WHERE `guid` = '%s' AND `uid` = %d AND NOT `file` LIKE '%%[%%' LIMIT 1",
			dbesc($target_guid),
			intval($importer["uid"])
		);
		if (!$r) {
			return false;
		}

		// Check if the sender is the thread owner
		$p = q("SELECT `id`, `author-link`, `origin` FROM `item` WHERE `id` = %d",
			intval($r[0]["parent"]));

		// Only delete it if the parent author really fits
		if (!link_compare($p[0]["author-link"], $contact["url"]) AND !link_compare($r[0]["author-link"], $contact["url"])) {
			logger("Thread author ".$p[0]["author-link"]." and item author ".$r[0]["author-link"]." don't fit to expected contact ".$contact["url"], LOGGER_DEBUG);
			return false;
		}

		// Currently we don't have a central deletion function that we could use in this case. The function "item_drop" doesn't work for that case
		q("UPDATE `item` SET `deleted` = 1, `edited` = '%s', `changed` = '%s', `body` = '' , `title` = '' WHERE `id` = %d",
			dbesc(datetime_convert()),
			dbesc(datetime_convert()),
			intval($r[0]["id"])
		);
		delete_thread($r[0]["id"], $r[0]["parent-uri"]);

		logger("Deleted target ".$target_guid." (".$r[0]["id"].") from user ".$importer["uid"]." parent: ".$p[0]["id"], LOGGER_DEBUG);

		// Now check if the retraction needs to be relayed by us
		if ($p[0]["origin"]) {
			// notify others
			proc_run(PRIORITY_HIGH, "include/notifier.php", "drop", $r[0]["id"]);
		}

		return true;
	}

	/**
	 * @brief Receives retraction messages
	 *
	 * @param array $importer Array of the importer user
	 * @param string $sender The sender of the message
	 * @param object $data The message object
	 *
	 * @return bool Success
	 */
	private static function receive_retraction($importer, $sender, $data) {
		$target_type = notags(unxmlify($data->target_type));

		$contact = self::contact_by_handle($importer["uid"], $sender);
		if (!$contact) {
			logger("cannot find contact for sender: ".$sender." and user ".$importer["uid"]);
			return false;
		}

		logger("Got retraction for ".$target_type.", sender ".$sender." and user ".$importer["uid"], LOGGER_DEBUG);

		switch ($target_type) {
			case "Comment":
			case "Like":
			case "Post": // "Post" will be supported in a future version
			case "Reshare":
			case "StatusMessage":
				return self::item_retraction($importer, $contact, $data);;

			case "Contact":
			case "Person":
				/// @todo What should we do with an "unshare"?
				// Removing the contact isn't correct since we still can read the public items
				contact_remove($contact["id"]);
				return true;

			default:
				logger("Unknown target type ".$target_type);
				return false;
		}
		return true;
	}

	/**
	 * @brief Receives status messages
	 *
	 * @param array $importer Array of the importer user
	 * @param object $data The message object
	 * @param string $xml The original XML of the message
	 *
	 * @return int The message id of the newly created item
	 */
	private static function receive_status_message($importer, $data, $xml) {
		$raw_message = unxmlify($data->raw_message);
		$guid = notags(unxmlify($data->guid));
		$author = notags(unxmlify($data->author));
		$public = notags(unxmlify($data->public));
		$created_at = datetime_convert("UTC", "UTC", notags(unxmlify($data->created_at)));
		$provider_display_name = notags(unxmlify($data->provider_display_name));

		/// @todo enable support for polls
		//if ($data->poll) {
		//	foreach ($data->poll AS $poll)
		//		print_r($poll);
		//	die("poll!\n");
		//}
		$contact = self::allowed_contact_by_handle($importer, $author, false);
		if (!$contact) {
			return false;
		}

		$message_id = self::message_exists($importer["uid"], $guid);
		if ($message_id) {
			return $message_id;
		}

		$address = array();
		if ($data->location) {
			foreach ($data->location->children() AS $fieldname => $data) {
				$address[$fieldname] = notags(unxmlify($data));
			}
		}

		$body = diaspora2bb($raw_message);

		$datarray = array();

		// Attach embedded pictures to the body
		if ($data->photo) {
			foreach ($data->photo AS $photo) {
				$body = "[img]".unxmlify($photo->remote_photo_path).
					unxmlify($photo->remote_photo_name)."[/img]\n".$body;
			}

			$datarray["object-type"] = ACTIVITY_OBJ_IMAGE;
		} else {
			$datarray["object-type"] = ACTIVITY_OBJ_NOTE;

			// Add OEmbed and other information to the body
			if (!self::is_redmatrix($contact["url"])) {
				$body = add_page_info_to_body($body, false, true);
			}
		}

		$datarray["uid"] = $importer["uid"];
		$datarray["contact-id"] = $contact["id"];
		$datarray["network"] = NETWORK_DIASPORA;

		$datarray["author-name"] = $contact["name"];
		$datarray["author-link"] = $contact["url"];
		$datarray["author-avatar"] = ((x($contact,"thumb")) ? $contact["thumb"] : $contact["photo"]);

		$datarray["owner-name"] = $datarray["author-name"];
		$datarray["owner-link"] = $datarray["author-link"];
		$datarray["owner-avatar"] = $datarray["author-avatar"];

		$datarray["guid"] = $guid;
		$datarray["uri"] = $datarray["parent-uri"] = self::get_uri_from_guid($author, $guid);

		$datarray["verb"] = ACTIVITY_POST;
		$datarray["gravity"] = GRAVITY_PARENT;

		$datarray["object"] = $xml;

		$datarray["body"] = self::replace_people_guid($body, $contact["url"]);

		if ($provider_display_name != "") {
			$datarray["app"] = $provider_display_name;
		}

		$datarray["plink"] = self::plink($author, $guid);
		$datarray["private"] = (($public == "false") ? 1 : 0);
		$datarray["changed"] = $datarray["created"] = $datarray["edited"] = $created_at;

		if (isset($address["address"])) {
			$datarray["location"] = $address["address"];
		}

		if (isset($address["lat"]) AND isset($address["lng"])) {
			$datarray["coord"] = $address["lat"]." ".$address["lng"];
		}

		self::fetch_guid($datarray);
		$message_id = item_store($datarray);

		if ($message_id) {
			logger("Stored item ".$datarray["guid"]." with message id ".$message_id, LOGGER_DEBUG);
		}

		return $message_id;
	}

	/* ************************************************************************************** *
	 * Here are all the functions that are needed to transmit data with the Diaspora protocol *
	 * ************************************************************************************** */

	/**
	 * @brief returnes the handle of a contact
	 *
	 * @param array $me contact array
	 *
	 * @return string the handle in the format user@domain.tld
	 */
	private static function my_handle($contact) {
		if ($contact["addr"] != "") {
			return $contact["addr"];
		}

		// Normally we should have a filled "addr" field - but in the past this wasn't the case
		// So - just in case - we build the the address here.
		if ($contact["nickname"] != "") {
			$nick = $contact["nickname"];
		} else {
			$nick = $contact["nick"];
		}

		return $nick."@".substr(App::get_baseurl(), strpos(App::get_baseurl(),"://") + 3);
	}

	/**
	 * @brief Creates the envelope for the "fetch" endpoint
	 *
	 * @param string $msg The message that is to be transmitted
	 * @param array $user The record of the sender
	 *
	 * @return string The envelope
	 */

	public static function build_magic_envelope($msg, $user) {

		$b64url_data = base64url_encode($msg);
		$data = str_replace(array("\n", "\r", " ", "\t"), array("", "", "", ""), $b64url_data);

		$key_id = base64url_encode(self::my_handle($user));
		$type = "application/xml";
		$encoding = "base64url";
		$alg = "RSA-SHA256";
		$signable_data = $data.".".base64url_encode($type).".".base64url_encode($encoding).".".base64url_encode($alg);
		$signature = rsa_sign($signable_data, $user["prvkey"]);
		$sig = base64url_encode($signature);

		$xmldata = array("me:env" => array("me:data" => $data,
							"@attributes" => array("type" => $type),
							"me:encoding" => $encoding,
							"me:alg" => $alg,
							"me:sig" => $sig,
							"@attributes2" => array("key_id" => $key_id)));

		$namespaces = array("me" => "http://salmon-protocol.org/ns/magic-env");

		return xml::from_array($xmldata, $xml, false, $namespaces);
	}

	/**
	 * @brief Creates the envelope for a public message
	 *
	 * @param string $msg The message that is to be transmitted
	 * @param array $user The record of the sender
	 * @param array $contact Target of the communication
	 * @param string $prvkey The private key of the sender
	 * @param string $pubkey The public key of the receiver
	 *
	 * @return string The envelope
	 */
	private static function build_public_message($msg, $user, $contact, $prvkey, $pubkey) {

		logger("Message: ".$msg, LOGGER_DATA);

		$handle = self::my_handle($user);

		$b64url_data = base64url_encode($msg);

		$data = str_replace(array("\n", "\r", " ", "\t"), array("", "", "", ""), $b64url_data);

		$type = "application/xml";
		$encoding = "base64url";
		$alg = "RSA-SHA256";

		$signable_data = $data.".".base64url_encode($type).".".base64url_encode($encoding).".".base64url_encode($alg);

		$signature = rsa_sign($signable_data,$prvkey);
		$sig = base64url_encode($signature);

		$xmldata = array("diaspora" => array("header" => array("author_id" => $handle),
							"me:env" => array("me:encoding" => $encoding,
							"me:alg" => $alg,
							"me:data" => $data,
							"@attributes" => array("type" => $type),
							"me:sig" => $sig)));

		$namespaces = array("" => "https://joindiaspora.com/protocol",
				"me" => "http://salmon-protocol.org/ns/magic-env");

		$magic_env = xml::from_array($xmldata, $xml, false, $namespaces);

		logger("magic_env: ".$magic_env, LOGGER_DATA);
		return $magic_env;
	}

	/**
	 * @brief Creates the envelope for a private message
	 *
	 * @param string $msg The message that is to be transmitted
	 * @param array $user The record of the sender
	 * @param array $contact Target of the communication
	 * @param string $prvkey The private key of the sender
	 * @param string $pubkey The public key of the receiver
	 *
	 * @return string The envelope
	 */
	private static function build_private_message($msg, $user, $contact, $prvkey, $pubkey) {

		logger("Message: ".$msg, LOGGER_DATA);

		// without a public key nothing will work

		if (!$pubkey) {
			logger("pubkey missing: contact id: ".$contact["id"]);
			return false;
		}

		$inner_aes_key = random_string(32);
		$b_inner_aes_key = base64_encode($inner_aes_key);
		$inner_iv = random_string(16);
		$b_inner_iv = base64_encode($inner_iv);

		$outer_aes_key = random_string(32);
		$b_outer_aes_key = base64_encode($outer_aes_key);
		$outer_iv = random_string(16);
		$b_outer_iv = base64_encode($outer_iv);

		$handle = self::my_handle($user);

		$padded_data = pkcs5_pad($msg,16);
		$inner_encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $inner_aes_key, $padded_data, MCRYPT_MODE_CBC, $inner_iv);

		$b64_data = base64_encode($inner_encrypted);


		$b64url_data = base64url_encode($b64_data);
		$data = str_replace(array("\n", "\r", " ", "\t"), array("", "", "", ""), $b64url_data);

		$type = "application/xml";
		$encoding = "base64url";
		$alg = "RSA-SHA256";

		$signable_data = $data.".".base64url_encode($type).".".base64url_encode($encoding).".".base64url_encode($alg);

		$signature = rsa_sign($signable_data,$prvkey);
		$sig = base64url_encode($signature);

		$xmldata = array("decrypted_header" => array("iv" => $b_inner_iv,
							"aes_key" => $b_inner_aes_key,
							"author_id" => $handle));

		$decrypted_header = xml::from_array($xmldata, $xml, true);
		$decrypted_header = pkcs5_pad($decrypted_header,16);

		$ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $outer_aes_key, $decrypted_header, MCRYPT_MODE_CBC, $outer_iv);

		$outer_json = json_encode(array("iv" => $b_outer_iv, "key" => $b_outer_aes_key));

		$encrypted_outer_key_bundle = "";
		openssl_public_encrypt($outer_json, $encrypted_outer_key_bundle, $pubkey);

		$b64_encrypted_outer_key_bundle = base64_encode($encrypted_outer_key_bundle);

		logger("outer_bundle: ".$b64_encrypted_outer_key_bundle." key: ".$pubkey, LOGGER_DATA);

		$encrypted_header_json_object = json_encode(array("aes_key" => base64_encode($encrypted_outer_key_bundle),
								"ciphertext" => base64_encode($ciphertext)));
		$cipher_json = base64_encode($encrypted_header_json_object);

		$xmldata = array("diaspora" => array("encrypted_header" => $cipher_json,
						"me:env" => array("me:encoding" => $encoding,
								"me:alg" => $alg,
								"me:data" => $data,
								"@attributes" => array("type" => $type),
								"me:sig" => $sig)));

		$namespaces = array("" => "https://joindiaspora.com/protocol",
				"me" => "http://salmon-protocol.org/ns/magic-env");

		$magic_env = xml::from_array($xmldata, $xml, false, $namespaces);

		logger("magic_env: ".$magic_env, LOGGER_DATA);
		return $magic_env;
	}

	/**
	 * @brief Create the envelope for a message
	 *
	 * @param string $msg The message that is to be transmitted
	 * @param array $user The record of the sender
	 * @param array $contact Target of the communication
	 * @param string $prvkey The private key of the sender
	 * @param string $pubkey The public key of the receiver
	 * @param bool $public Is the message public?
	 *
	 * @return string The message that will be transmitted to other servers
	 */
	private static function build_message($msg, $user, $contact, $prvkey, $pubkey, $public = false) {

		if ($public)
			$magic_env =  self::build_public_message($msg,$user,$contact,$prvkey,$pubkey);
		else
			$magic_env =  self::build_private_message($msg,$user,$contact,$prvkey,$pubkey);

		// The data that will be transmitted is double encoded via "urlencode", strange ...
		$slap = "xml=".urlencode(urlencode($magic_env));
		return $slap;
	}

	/**
	 * @brief Creates a signature for a message
	 *
	 * @param array $owner the array of the owner of the message
	 * @param array $message The message that is to be signed
	 *
	 * @return string The signature
	 */
	private static function signature($owner, $message) {
		$sigmsg = $message;
		unset($sigmsg["author_signature"]);
		unset($sigmsg["parent_author_signature"]);

		$signed_text = implode(";", $sigmsg);

		return base64_encode(rsa_sign($signed_text, $owner["uprvkey"], "sha256"));
	}

	/**
	 * @brief Transmit a message to a target server
	 *
	 * @param array $owner the array of the item owner
	 * @param array $contact Target of the communication
	 * @param string $slap The message that is to be transmitted
	 * @param bool $public_batch Is it a public post?
	 * @param bool $queue_run Is the transmission called from the queue?
	 * @param string $guid message guid
	 *
	 * @return int Result of the transmission
	 */
	public static function transmit($owner, $contact, $slap, $public_batch, $queue_run=false, $guid = "") {

		$a = get_app();

		$enabled = intval(get_config("system", "diaspora_enabled"));
		if(!$enabled)
			return 200;

		$logid = random_string(4);
		$dest_url = (($public_batch) ? $contact["batch"] : $contact["notify"]);
		if (!$dest_url) {
			logger("no url for contact: ".$contact["id"]." batch mode =".$public_batch);
			return 0;
		}

		logger("transmit: ".$logid."-".$guid." ".$dest_url);

		if (!$queue_run && was_recently_delayed($contact["id"])) {
			$return_code = 0;
		} else {
			if (!intval(get_config("system", "diaspora_test"))) {
				post_url($dest_url."/", $slap);
				$return_code = $a->get_curl_code();
			} else {
				logger("test_mode");
				return 200;
			}
		}

		logger("transmit: ".$logid."-".$guid." returns: ".$return_code);

		if (!$return_code || (($return_code == 503) && (stristr($a->get_curl_headers(), "retry-after")))) {
			logger("queue message");

			$r = q("SELECT `id` FROM `queue` WHERE `cid` = %d AND `network` = '%s' AND `content` = '%s' AND `batch` = %d LIMIT 1",
				intval($contact["id"]),
				dbesc(NETWORK_DIASPORA),
				dbesc($slap),
				intval($public_batch)
			);
			if ($r) {
				logger("add_to_queue ignored - identical item already in queue");
			} else {
				// queue message for redelivery
				add_to_queue($contact["id"], NETWORK_DIASPORA, $slap, $public_batch);

				// The message could not be delivered. We mark the contact as "dead"
				mark_for_death($contact);
			}
		} elseif (($return_code >= 200) AND ($return_code <= 299)) {
			// We successfully delivered a message, the contact is alive
			unmark_for_death($contact);
		}

		return(($return_code) ? $return_code : (-1));
	}


	/**
	 * @brief Build the post xml
	 *
	 * @param string $type The message type
	 * @param array $message The message data
	 *
	 * @return string The post XML
	 */
	public static function build_post_xml($type, $message) {

		$data = array("XML" => array("post" => array($type => $message)));
		return xml::from_array($data, $xml);
	}

	/**
	 * @brief Builds and transmit messages
	 *
	 * @param array $owner the array of the item owner
	 * @param array $contact Target of the communication
	 * @param string $type The message type
	 * @param array $message The message data
	 * @param bool $public_batch Is it a public post?
	 * @param string $guid message guid
	 * @param bool $spool Should the transmission be spooled or transmitted?
	 *
	 * @return int Result of the transmission
	 */
	private static function build_and_transmit($owner, $contact, $type, $message, $public_batch = false, $guid = "", $spool = false) {

		$msg = self::build_post_xml($type, $message);

		logger('message: '.$msg, LOGGER_DATA);
		logger('send guid '.$guid, LOGGER_DEBUG);

		// Fallback if the private key wasn't transmitted in the expected field
		if ($owner['uprvkey'] == "")
			$owner['uprvkey'] = $owner['prvkey'];

		$slap = self::build_message($msg, $owner, $contact, $owner['uprvkey'], $contact['pubkey'], $public_batch);

		if ($spool) {
			add_to_queue($contact['id'], NETWORK_DIASPORA, $slap, $public_batch);
			return true;
		} else
			$return_code = self::transmit($owner, $contact, $slap, $public_batch, false, $guid);

		logger("guid: ".$item["guid"]." result ".$return_code, LOGGER_DEBUG);

		return $return_code;
	}

	/**
	 * @brief Sends a "share" message
	 *
	 * @param array $owner the array of the item owner
	 * @param array $contact Target of the communication
	 *
	 * @return int The result of the transmission
	 */
	public static function send_share($owner,$contact) {

		$message = array("sender_handle" => self::my_handle($owner),
				"recipient_handle" => $contact["addr"]);

		logger("Send share ".print_r($message, true), LOGGER_DEBUG);

		return self::build_and_transmit($owner, $contact, "request", $message);
	}

	/**
	 * @brief sends an "unshare"
	 *
	 * @param array $owner the array of the item owner
	 * @param array $contact Target of the communication
	 *
	 * @return int The result of the transmission
	 */
	public static function send_unshare($owner,$contact) {

		$message = array("post_guid" => $owner["guid"],
				"diaspora_handle" => self::my_handle($owner),
				"type" => "Person");

		logger("Send unshare ".print_r($message, true), LOGGER_DEBUG);

		return self::build_and_transmit($owner, $contact, "retraction", $message);
	}

	/**
	 * @brief Checks a message body if it is a reshare
	 *
	 * @param string $body The message body that is to be check
	 * @param bool $complete Should it be a complete check or a simple check?
	 *
	 * @return array|bool Reshare details or "false" if no reshare
	 */
	public static function is_reshare($body, $complete = true) {
		$body = trim($body);

		// Skip if it isn't a pure repeated messages
		// Does it start with a share?
		if ((strpos($body, "[share") > 0) AND $complete)
			return(false);

		// Does it end with a share?
		if (strlen($body) > (strrpos($body, "[/share]") + 8))
			return(false);

		$attributes = preg_replace("/\[share(.*?)\]\s?(.*?)\s?\[\/share\]\s?/ism","$1",$body);
		// Skip if there is no shared message in there
		if ($body == $attributes)
			return(false);

		// If we don't do the complete check we quit here
		if (!$complete)
			return true;

		$guid = "";
		preg_match("/guid='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "")
			$guid = $matches[1];

		preg_match('/guid="(.*?)"/ism', $attributes, $matches);
		if ($matches[1] != "")
			$guid = $matches[1];

		if ($guid != "") {
			$r = q("SELECT `contact-id` FROM `item` WHERE `guid` = '%s' AND `network` IN ('%s', '%s') LIMIT 1",
				dbesc($guid), NETWORK_DFRN, NETWORK_DIASPORA);
			if ($r) {
				$ret= array();
				$ret["root_handle"] = self::handle_from_contact($r[0]["contact-id"]);
				$ret["root_guid"] = $guid;
				return($ret);
			}
		}

		$profile = "";
		preg_match("/profile='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "")
			$profile = $matches[1];

		preg_match('/profile="(.*?)"/ism', $attributes, $matches);
		if ($matches[1] != "")
			$profile = $matches[1];

		$ret= array();

		$ret["root_handle"] = preg_replace("=https?://(.*)/u/(.*)=ism", "$2@$1", $profile);
		if (($ret["root_handle"] == $profile) OR ($ret["root_handle"] == ""))
			return(false);

		$link = "";
		preg_match("/link='(.*?)'/ism", $attributes, $matches);
		if ($matches[1] != "")
			$link = $matches[1];

		preg_match('/link="(.*?)"/ism', $attributes, $matches);
		if ($matches[1] != "")
			$link = $matches[1];

		$ret["root_guid"] = preg_replace("=https?://(.*)/posts/(.*)=ism", "$2", $link);
		if (($ret["root_guid"] == $link) OR (trim($ret["root_guid"]) == ""))
			return(false);

		return($ret);
	}

	/**
	 * @brief Create an event array
	 *
	 * @param integer $event_id The id of the event
	 *
	 * @return array with event data
	 */
	private static function build_event($event_id) {

		$r = q("SELECT `guid`, `uid`, `start`, `finish`, `nofinish`, `summary`, `desc`, `location`, `adjust` FROM `event` WHERE `id` = %d", intval($event_id));
		if (!dbm::is_result($r)) {
			return array();
		}

		$event = $r[0];

		$eventdata = array();

		$r = q("SELECT `timezone` FROM `user` WHERE `uid` = %d", intval($event['uid']));
		if (!dbm::is_result($r)) {
			return array();
		}

		$user = $r[0];

		$r = q("SELECT `addr`, `nick` FROM `contact` WHERE `uid` = %d AND `self`", intval($event['uid']));
		if (!dbm::is_result($r)) {
			return array();
		}

		$owner = $r[0];

		$eventdata['author'] = self::my_handle($owner);

		if ($event['guid']) {
			$eventdata['guid'] = $event['guid'];
		}

		$mask = 'Y-m-d\TH:i:s\Z';

		/// @todo - establish "all day" events in Friendica
		$eventdata["all_day"] = "false";

		if (!$event['adjust']) {
			$eventdata['timezone'] = $user['timezone'];

			if ($eventdata['timezone'] == "") {
				$eventdata['timezone'] = 'UTC';
			}
		}

		if ($event['start']) {
			$eventdata['start'] = datetime_convert($eventdata['timezone'], "UTC", $event['start'], $mask);
		}
		if ($event['finish'] AND !$event['nofinish']) {
			$eventdata['end'] = datetime_convert($eventdata['timezone'], "UTC", $event['finish'], $mask);
		}
		if ($event['summary']) {
			$eventdata['summary'] = html_entity_decode(bb2diaspora($event['summary']));
		}
		if ($event['desc']) {
			$eventdata['description'] = html_entity_decode(bb2diaspora($event['desc']));
		}
		if ($event['location']) {
			$location = array();
			$location["address"] = html_entity_decode(bb2diaspora($event['location']));
			$location["lat"] = 0;
			$location["lng"] = 0;
			$eventdata['location'] = $location;
		}

		return $eventdata;
	}

	/**
	 * @brief Create a post (status message or reshare)
	 *
	 * @param array $item The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array
	 * 'type' -> Message type ("status_message" or "reshare")
	 * 'message' -> Array of XML elements of the status
	 */
	public static function build_status($item, $owner) {

		$cachekey = "diaspora:build_status:".$item['guid'];

		$result = Cache::get($cachekey);
		if (!is_null($result)) {
			return $result;
		}

		$myaddr = self::my_handle($owner);

		$public = (($item["private"]) ? "false" : "true");

		$created = datetime_convert("UTC", "UTC", $item["created"], 'Y-m-d\TH:i:s\Z');

		// Detect a share element and do a reshare
		if (!$item['private'] AND ($ret = self::is_reshare($item["body"]))) {
			$message = array("root_diaspora_id" => $ret["root_handle"],
					"root_guid" => $ret["root_guid"],
					"guid" => $item["guid"],
					"diaspora_handle" => $myaddr,
					"public" => $public,
					"created_at" => $created,
					"provider_display_name" => $item["app"]);

			$type = "reshare";
		} else {
			$title = $item["title"];
			$body = $item["body"];

			// convert to markdown
			$body = html_entity_decode(bb2diaspora($body));

			// Adding the title
			if(strlen($title))
				$body = "## ".html_entity_decode($title)."\n\n".$body;

			if ($item["attach"]) {
				$cnt = preg_match_all('/href=\"(.*?)\"(.*?)title=\"(.*?)\"/ism', $item["attach"], $matches, PREG_SET_ORDER);
				if(cnt) {
					$body .= "\n".t("Attachments:")."\n";
					foreach($matches as $mtch)
						$body .= "[".$mtch[3]."](".$mtch[1].")\n";
				}
			}

			$location = array();

			if ($item["location"] != "")
				$location["address"] = $item["location"];

			if ($item["coord"] != "") {
				$coord = explode(" ", $item["coord"]);
				$location["lat"] = $coord[0];
				$location["lng"] = $coord[1];
			}

			$message = array("raw_message" => $body,
					"location" => $location,
					"guid" => $item["guid"],
					"diaspora_handle" => $myaddr,
					"public" => $public,
					"created_at" => $created,
					"provider_display_name" => $item["app"]);

			// Diaspora rejects messages when they contain a location without "lat" or "lng"
			if (!isset($location["lat"]) OR !isset($location["lng"])) {
				unset($message["location"]);
			}

			if ($item['event-id'] > 0) {
				$event = self::build_event($item['event-id']);
				if (count($event)) {
					$message['event'] = $event;

					/// @todo Once Diaspora supports it, we will remove the body
					// $message['raw_message'] = '';
				}
			}

			$type = "status_message";
		}

		$msg = array("type" => $type, "message" => $message);

		Cache::set($cachekey, $msg, CACHE_QUARTER_HOUR);

		return $msg;
	}

	/**
	 * @brief Sends a post
	 *
	 * @param array $item The item that will be exported
	 * @param array $owner the array of the item owner
	 * @param array $contact Target of the communication
	 * @param bool $public_batch Is it a public post?
	 *
	 * @return int The result of the transmission
	 */
	public static function send_status($item, $owner, $contact, $public_batch = false) {

		$status = self::build_status($item, $owner);

		return self::build_and_transmit($owner, $contact, $status["type"], $status["message"], $public_batch, $item["guid"]);
	}

	/**
	 * @brief Creates a "like" object
	 *
	 * @param array $item The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array The data for a "like"
	 */
	private static function construct_like($item, $owner) {

		$p = q("SELECT `guid`, `uri`, `parent-uri` FROM `item` WHERE `uri` = '%s' LIMIT 1",
			dbesc($item["thr-parent"]));
		if (!dbm::is_result($p))
			return false;

		$parent = $p[0];

		$target_type = ($parent["uri"] === $parent["parent-uri"] ? "Post" : "Comment");
		if ($item['verb'] === ACTIVITY_LIKE) {
			$positive = "true";
		} elseif ($item['verb'] === ACTIVITY_DISLIKE) {
			$positive = "false";
		}

		return(array("positive" => $positive,
				"guid" => $item["guid"],
				"target_type" => $target_type,
				"parent_guid" => $parent["guid"],
				"author_signature" => "",
				"diaspora_handle" => self::my_handle($owner)));
	}

	/**
	 * @brief Creates an "EventParticipation" object
	 *
	 * @param array $item The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array The data for an "EventParticipation"
	 */
	private static function construct_attend($item, $owner) {

		$p = q("SELECT `guid`, `uri`, `parent-uri` FROM `item` WHERE `uri` = '%s' LIMIT 1",
			dbesc($item["thr-parent"]));
		if (!dbm::is_result($p))
			return false;

		$parent = $p[0];

		switch ($item['verb']) {
			case ACTIVITY_ATTEND:
				$attend_answer = 'accepted';
				break;
			case ACTIVITY_ATTENDNO:
				$attend_answer = 'declined';
				break;
			case ACTIVITY_ATTENDMAYBE:
				$attend_answer = 'tentative';
				break;
			default:
				logger('Unknown verb '.$item['verb'].' in item '.$item['guid']);
				return false;
		}

		return(array("author" => self::my_handle($owner),
				"guid" => $item["guid"],
				"parent_guid" => $parent["guid"],
				"status" => $attend_answer,
				"author_signature" => ""));
	}

	/**
	 * @brief Creates the object for a comment
	 *
	 * @param array $item The item that will be exported
	 * @param array $owner the array of the item owner
	 *
	 * @return array The data for a comment
	 */
	private static function construct_comment($item, $owner) {

		$cachekey = "diaspora:construct_comment:".$item['guid'];

		$result = Cache::get($cachekey);
		if (!is_null($result)) {
			return $result;
		}

		$p = q("SELECT `guid` FROM `item` WHERE `parent` = %d AND `id` = %d LIMIT 1",
			intval($item["parent"]),
			intval($item["parent"])
		);

		if (!dbm::is_result($p))
			return false;

		$parent = $p[0];

		$text = html_entity_decode(bb2diaspora($item["body"]));
		$created = datetime_convert("UTC", "UTC", $item["created"], 'Y-m-d\TH:i:s\Z');

		$comment = array("guid" => $item["guid"],
				"parent_guid" => $parent["guid"],
				"author_signature" => "",
				"text" => $text,
				/// @todo Currently disabled until Diaspora supports it: "created_at" => $created,
				"diaspora_handle" => self::my_handle($owner));

		// Send the thread parent guid only if it is a threaded comment
		if ($item['thr-parent'] != $item['parent-uri']) {
			$comment['thread_parent_guid'] = self::get_guid_from_uri($item['thr-parent'], $item['uid']);
		}

		Cache::set($cachekey, $comment, CACHE_QUARTER_HOUR);

		return($comment);
	}

	/**
	 * @brief Send a like or a comment
	 *
	 * @param array $item The item that will be exported
	 * @param array $owner the array of the item owner
	 * @param array $contact Target of the communication
	 * @param bool $public_batch Is it a public post?
	 *
	 * @return int The result of the transmission
	 */
	public static function send_followup($item,$owner,$contact,$public_batch = false) {

		if (in_array($item['verb'], array(ACTIVITY_ATTEND, ACTIVITY_ATTENDNO, ACTIVITY_ATTENDMAYBE))) {
			$message = self::construct_attend($item, $owner);
			$type = "event_participation";
		} elseif (in_array($item["verb"], array(ACTIVITY_LIKE, ACTIVITY_DISLIKE))) {
			$message = self::construct_like($item, $owner);
			$type = "like";
		} else {
			$message = self::construct_comment($item, $owner);
			$type = "comment";
		}

		if (!$message)
			return false;

		$message["author_signature"] = self::signature($owner, $message);

		return self::build_and_transmit($owner, $contact, $type, $message, $public_batch, $item["guid"]);
	}

	/**
	 * @brief Creates a message from a signature record entry
	 *
	 * @param array $item The item that will be exported
	 * @param array $signature The entry of the "sign" record
	 *
	 * @return string The message
	 */
	private static function message_from_signature($item, $signature) {

		// Split the signed text
		$signed_parts = explode(";", $signature['signed_text']);

		if ($item["deleted"])
			$message = array("parent_author_signature" => "",
					"target_guid" => $signed_parts[0],
					"target_type" => $signed_parts[1],
					"sender_handle" => $signature['signer'],
					"target_author_signature" => $signature['signature']);
		elseif ($item['verb'] === ACTIVITY_LIKE)
			$message = array("positive" => $signed_parts[0],
					"guid" => $signed_parts[1],
					"target_type" => $signed_parts[2],
					"parent_guid" => $signed_parts[3],
					"parent_author_signature" => "",
					"author_signature" => $signature['signature'],
					"diaspora_handle" => $signed_parts[4]);
		else {
			// Remove the comment guid
			$guid = array_shift($signed_parts);

			// Remove the parent guid
			$parent_guid = array_shift($signed_parts);

			// Remove the handle
			$handle = array_pop($signed_parts);

			// Glue the parts together
			$text = implode(";", $signed_parts);

			$message = array("guid" => $guid,
					"parent_guid" => $parent_guid,
					"parent_author_signature" => "",
					"author_signature" => $signature['signature'],
					"text" => implode(";", $signed_parts),
					"diaspora_handle" => $handle);
		}
		return $message;
	}

	/**
	 * @brief Relays messages (like, comment, retraction) to other servers if we are the thread owner
	 *
	 * @param array $item The item that will be exported
	 * @param array $owner the array of the item owner
	 * @param array $contact Target of the communication
	 * @param bool $public_batch Is it a public post?
	 *
	 * @return int The result of the transmission
	 */
	public static function send_relay($item, $owner, $contact, $public_batch = false) {

		if ($item["deleted"])
			return self::send_retraction($item, $owner, $contact, $public_batch, true);
		elseif ($item['verb'] === ACTIVITY_LIKE)
			$type = "like";
		else
			$type = "comment";

		logger("Got relayable data ".$type." for item ".$item["guid"]." (".$item["id"].")", LOGGER_DEBUG);

		// fetch the original signature

		$r = q("SELECT `signed_text`, `signature`, `signer` FROM `sign` WHERE `iid` = %d LIMIT 1",
			intval($item["id"]));

		if (!$r) {
			logger("Couldn't fetch signatur for item ".$item["guid"]." (".$item["id"].")", LOGGER_DEBUG);
			return false;
		}

		$signature = $r[0];

		// Old way - is used by the internal Friendica functions
		/// @todo Change all signatur storing functions to the new format
		if ($signature['signed_text'] AND $signature['signature'] AND $signature['signer'])
			$message = self::message_from_signature($item, $signature);
		else {// New way
			$msg = json_decode($signature['signed_text'], true);

			$message = array();
			if (is_array($msg)) {
				foreach ($msg AS $field => $data) {
					if (!$item["deleted"]) {
						if ($field == "author")
							$field = "diaspora_handle";
						if ($field == "parent_type")
							$field = "target_type";
					}

					$message[$field] = $data;
				}
			} else
				logger("Signature text for item ".$item["guid"]." (".$item["id"].") couldn't be extracted: ".$signature['signed_text'], LOGGER_DEBUG);
		}

		$message["parent_author_signature"] = self::signature($owner, $message);

		logger("Relayed data ".print_r($message, true), LOGGER_DEBUG);

		return self::build_and_transmit($owner, $contact, $type, $message, $public_batch, $item["guid"]);
	}

	/**
	 * @brief Sends a retraction (deletion) of a message, like or comment
	 *
	 * @param array $item The item that will be exported
	 * @param array $owner the array of the item owner
	 * @param array $contact Target of the communication
	 * @param bool $public_batch Is it a public post?
	 * @param bool $relay Is the retraction transmitted from a relay?
	 *
	 * @return int The result of the transmission
	 */
	public static function send_retraction($item, $owner, $contact, $public_batch = false, $relay = false) {

		$itemaddr = self::handle_from_contact($item["contact-id"], $item["gcontact-id"]);

		// Check whether the retraction is for a top-level post or whether it's a relayable
		if ($item["uri"] !== $item["parent-uri"]) {
			$msg_type = "relayable_retraction";
			$target_type = (($item["verb"] === ACTIVITY_LIKE) ? "Like" : "Comment");
		} else {
			$msg_type = "signed_retraction";
			$target_type = "StatusMessage";
		}

		if ($relay AND ($item["uri"] !== $item["parent-uri"]))
			$signature = "parent_author_signature";
		else
			$signature = "target_author_signature";

		$signed_text = $item["guid"].";".$target_type;

		$message = array("target_guid" => $item['guid'],
				"target_type" => $target_type,
				"sender_handle" => $itemaddr,
				$signature => base64_encode(rsa_sign($signed_text,$owner['uprvkey'],'sha256')));

		logger("Got message ".print_r($message, true), LOGGER_DEBUG);

		return self::build_and_transmit($owner, $contact, $msg_type, $message, $public_batch, $item["guid"]);
	}

	/**
	 * @brief Sends a mail
	 *
	 * @param array $item The item that will be exported
	 * @param array $owner The owner
	 * @param array $contact Target of the communication
	 *
	 * @return int The result of the transmission
	 */
	public static function send_mail($item, $owner, $contact) {

		$myaddr = self::my_handle($owner);

		$r = q("SELECT * FROM `conv` WHERE `id` = %d AND `uid` = %d LIMIT 1",
			intval($item["convid"]),
			intval($item["uid"])
		);

		if (!dbm::is_result($r)) {
			logger("conversation not found.");
			return;
		}
		$cnv = $r[0];

		$conv = array(
			"guid" => $cnv["guid"],
			"subject" => $cnv["subject"],
			"created_at" => datetime_convert("UTC", "UTC", $cnv['created'], 'Y-m-d\TH:i:s\Z'),
			"diaspora_handle" => $cnv["creator"],
			"participant_handles" => $cnv["recips"]
		);

		$body = bb2diaspora($item["body"]);
		$created = datetime_convert("UTC", "UTC", $item["created"], 'Y-m-d\TH:i:s\Z');

		$signed_text = $item["guid"].";".$cnv["guid"].";".$body.";".$created.";".$myaddr.";".$cnv['guid'];
		$sig = base64_encode(rsa_sign($signed_text, $owner["uprvkey"], "sha256"));

		$msg = array(
			"guid" => $item["guid"],
			"parent_guid" => $cnv["guid"],
			"parent_author_signature" => $sig,
			"author_signature" => $sig,
			"text" => $body,
			"created_at" => $created,
			"diaspora_handle" => $myaddr,
			"conversation_guid" => $cnv["guid"]
		);

		if ($item["reply"]) {
			$message = $msg;
			$type = "message";
		} else {
			$message = array("guid" => $cnv["guid"],
					"subject" => $cnv["subject"],
					"created_at" => datetime_convert("UTC", "UTC", $cnv['created'], 'Y-m-d\TH:i:s\Z'),
					"message" => $msg,
					"diaspora_handle" => $cnv["creator"],
					"participant_handles" => $cnv["recips"]);

			$type = "conversation";
		}

		return self::build_and_transmit($owner, $contact, $type, $message, false, $item["guid"]);
	}

	/**
	 * @brief Sends profile data
	 *
	 * @param int $uid The user id
	 */
	public static function send_profile($uid, $recips = false) {

		if (!$uid)
			return;

		if (!$recips)
			$recips = q("SELECT `id`,`name`,`network`,`pubkey`,`notify` FROM `contact` WHERE `network` = '%s'
				AND `uid` = %d AND `rel` != %d",
				dbesc(NETWORK_DIASPORA),
				intval($uid),
				intval(CONTACT_IS_SHARING)
			);
		if (!$recips)
			return;

		$r = q("SELECT `profile`.`uid` AS `profile_uid`, `profile`.* , `user`.*, `user`.`prvkey` AS `uprvkey`, `contact`.`addr`
			FROM `profile`
			INNER JOIN `user` ON `profile`.`uid` = `user`.`uid`
			INNER JOIN `contact` ON `profile`.`uid` = `contact`.`uid`
			WHERE `user`.`uid` = %d AND `profile`.`is-default` AND `contact`.`self` LIMIT 1",
			intval($uid)
		);

		if (!$r)
			return;

		$profile = $r[0];

		$handle = $profile["addr"];
		$first = ((strpos($profile['name'],' ')
			? trim(substr($profile['name'],0,strpos($profile['name'],' '))) : $profile['name']));
		$last = (($first === $profile['name']) ? '' : trim(substr($profile['name'], strlen($first))));
		$large = App::get_baseurl().'/photo/custom/300/'.$profile['uid'].'.jpg';
		$medium = App::get_baseurl().'/photo/custom/100/'.$profile['uid'].'.jpg';
		$small = App::get_baseurl().'/photo/custom/50/'  .$profile['uid'].'.jpg';
		$searchable = (($profile['publish'] && $profile['net-publish']) ? 'true' : 'false');

		if ($searchable === 'true') {
			$dob = '1000-00-00';

			if (($profile['dob']) && ($profile['dob'] != '0000-00-00'))
				$dob = ((intval($profile['dob'])) ? intval($profile['dob']) : '1000') .'-'. datetime_convert('UTC','UTC',$profile['dob'],'m-d');

			$about = $profile['about'];
			$about = strip_tags(bbcode($about));

			$location = formatted_location($profile);
			$tags = '';
			if ($profile['pub_keywords']) {
				$kw = str_replace(',',' ',$profile['pub_keywords']);
				$kw = str_replace('  ',' ',$kw);
				$arr = explode(' ',$profile['pub_keywords']);
				if (count($arr)) {
					for($x = 0; $x < 5; $x ++) {
						if (trim($arr[$x]))
							$tags .= '#'. trim($arr[$x]) .' ';
					}
				}
			}
			$tags = trim($tags);
		}

		$message = array("diaspora_handle" => $handle,
				"first_name" => $first,
				"last_name" => $last,
				"image_url" => $large,
				"image_url_medium" => $medium,
				"image_url_small" => $small,
				"birthday" => $dob,
				"gender" => $profile['gender'],
				"bio" => $about,
				"location" => $location,
				"searchable" => $searchable,
				"tag_string" => $tags);

		foreach($recips as $recip) {
			logger("Send updated profile data for user ".$uid." to contact ".$recip["id"], LOGGER_DEBUG);
			self::build_and_transmit($profile, $recip, "profile", $message, false, "", true);
		}
	}

	/**
	 * @brief Stores the signature for likes that are created on our system
	 *
	 * @param array $contact The contact array of the "like"
	 * @param int $post_id The post id of the "like"
	 *
	 * @return bool Success
	 */
	public static function store_like_signature($contact, $post_id) {

		// Is the contact the owner? Then fetch the private key
		if (!$contact['self'] OR ($contact['uid'] == 0)) {
			logger("No owner post, so not storing signature", LOGGER_DEBUG);
			return false;
		}

		$r = q("SELECT `prvkey` FROM `user` WHERE `uid` = %d LIMIT 1", intval($contact['uid']));
		if(!$r)
			return false;

		$contact["uprvkey"] = $r[0]['prvkey'];

		$r = q("SELECT * FROM `item` WHERE `id` = %d LIMIT 1", intval($post_id));
		if (!$r)
			return false;

		if (!in_array($r[0]["verb"], array(ACTIVITY_LIKE, ACTIVITY_DISLIKE)))
			return false;

		$message = self::construct_like($r[0], $contact);
		$message["author_signature"] = self::signature($contact, $message);

		// We now store the signature more flexible to dynamically support new fields.
		// This will break Diaspora compatibility with Friendica versions prior to 3.5.
		q("INSERT INTO `sign` (`iid`,`signed_text`) VALUES (%d,'%s')",
			intval($message_id),
			dbesc(json_encode($message))
		);

		logger('Stored diaspora like signature');
		return true;
	}

	/**
	 * @brief Stores the signature for comments that are created on our system
	 *
	 * @param array $item The item array of the comment
	 * @param array $contact The contact array of the item owner
	 * @param string $uprvkey The private key of the sender
	 * @param int $message_id The message id of the comment
	 *
	 * @return bool Success
	 */
	public static function store_comment_signature($item, $contact, $uprvkey, $message_id) {

		if ($uprvkey == "") {
			logger('No private key, so not storing comment signature', LOGGER_DEBUG);
			return false;
		}

		$contact["uprvkey"] = $uprvkey;

		$message = self::construct_comment($item, $contact);
		$message["author_signature"] = self::signature($contact, $message);

		// We now store the signature more flexible to dynamically support new fields.
		// This will break Diaspora compatibility with Friendica versions prior to 3.5.
		q("INSERT INTO `sign` (`iid`,`signed_text`) VALUES (%d,'%s')",
			intval($message_id),
			dbesc(json_encode($message))
		);

		logger('Stored diaspora comment signature');
		return true;
	}
}
?>
