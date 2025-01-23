<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Protocol;

use Friendica\Core\Hook;
use Friendica\Content\Text\BBCode;
use Friendica\Content\Text\HTML;
use Friendica\Core\Protocol;
use Friendica\DI;
use Friendica\Model\Item;
use Friendica\Util\Strings;
use IMAP\Connection;

/**
 * Email class
 */
class Email
{
	/**
	 * @param string $mailbox  The mailbox name
	 * @param string $username The username
	 * @param string $password The password
	 * @return Connection|resource|false
	 * @throws \Exception
	 */
	public static function connect(string $mailbox, string $username, string $password)
	{
		if (!function_exists('imap_open')) {
			return false;
		}

		$mbox = @imap_open($mailbox, $username, $password);

		$errors = imap_errors();
		if (!empty($errors)) {
			DI::logger()->notice('IMAP Errors occurred', ['errors' => $errors]);
		}

		$alerts = imap_alerts();
		if (!empty($alerts)) {
			DI::logger()->notice('IMAP Alerts occurred: ', ['alerts' => $alerts]);
		}

		if (empty($errors) && empty($alerts)) {
			Item::incrementInbound(Protocol::MAIL);
		}
		return $mbox;
	}

	/**
	 * @param Connection|resource $mbox       mailbox
	 * @param string              $email_addr email
	 * @throws \Exception
	 */
	public static function poll($mbox, string $email_addr): array
	{
		if (!$mbox || !$email_addr) {
			return [];
		}

		$search1 = @imap_search($mbox, 'UNDELETED FROM "' . $email_addr . '"', SE_UID);
		if (!$search1) {
			$search1 = [];
		} else {
			DI::logger()->debug("Found mails from ".$email_addr);
			Item::incrementInbound(Protocol::MAIL);
		}

		$search2 = @imap_search($mbox, 'UNDELETED TO "' . $email_addr . '"', SE_UID);
		if (!$search2) {
			$search2 = [];
		} else {
			DI::logger()->debug("Found mails to ".$email_addr);
			Item::incrementInbound(Protocol::MAIL);
		}

		$search3 = @imap_search($mbox, 'UNDELETED CC "' . $email_addr . '"', SE_UID);
		if (!$search3) {
			$search3 = [];
		} else {
			DI::logger()->debug("Found mails cc ".$email_addr);
			Item::incrementInbound(Protocol::MAIL);
		}

		$res = array_unique(array_merge($search1, $search2, $search3));

		return $res;
	}

	/**
	 * Returns mailbox name
	 *
	 * @param array   $mailacct mail account
	 * @return string
	 */
	public static function constructMailboxName(array $mailacct): string
	{
		$ret = '{' . $mailacct['server'] . ((intval($mailacct['port'])) ? ':' . $mailacct['port'] : '');
		$ret .= (($mailacct['ssltype']) ?  '/' . $mailacct['ssltype'] . '/novalidate-cert' : '');
		$ret .= '}' . $mailacct['mailbox'];
		return $ret;
	}

	/**
	 * @param Connection|resource $mbox     mailbox
	 * @param string              $sequence
	 * @return mixed
	 */
	public static function messageMeta($mbox, string $sequence)
	{
		$ret = (($mbox && $sequence) ? @imap_fetch_overview($mbox, $sequence, FT_UID) : [[]]); // POSSIBLE CLEANUP --> array(array()) is probably redundant now
		return (count($ret)) ? $ret : [];
	}

	/**
	 * @param Connection|resource $mbox  mailbox
	 * @param integer             $uid   user id
	 * @param string              $reply reply
	 * @param array               $item  Item
	 * @return array
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function getMessage($mbox, int $uid, string $reply, array $item): array
	{
		$ret   = $item;
		$struc = (($mbox && $uid) ? @imap_fetchstructure($mbox, $uid, FT_UID) : null);

		if (!$struc) {
			DI::logger()->notice("IMAP structure couldn't be fetched", ['uid' => $uid]);
			return $ret;
		}

		Item::incrementInbound(Protocol::MAIL);

		if (empty($struc->parts)) {
			$html = trim(self::messageGetPart($mbox, $uid, $struc, 0, 'html'));

			if (!empty($html)) {
				$message = ['text' => '', 'html' => $html, 'item' => $ret];
				Hook::callAll('email_getmessage', $message);
				$ret = $message['item'];
				if (empty($ret['body'])) {
					$ret['body'] = HTML::toBBCode($message['html']);
				}
			}

			if (empty($ret['body'])) {
				$text = self::messageGetPart($mbox, $uid, $struc, 0, 'plain');

				$message = ['text' => $text, 'html' => '', 'item' => $ret];
				Hook::callAll('email_getmessage', $message);
				$ret         = $message['item'];
				$ret['body'] = $message['text'];
			}
		} else {
			$text = '';
			$html = '';
			foreach ($struc->parts as $ptop => $p) {
				$x = self::messageGetPart($mbox, $uid, $p, $ptop + 1, 'plain');
				if ($x) {
					$text .= $x;
				}

				$x = self::messageGetPart($mbox, $uid, $p, $ptop + 1, 'html');
				if ($x) {
					$html .= $x;
				}
			}

			$message = ['text' => trim($text), 'html' => trim($html), 'item' => $ret];
			Hook::callAll('email_getmessage', $message);
			$ret = $message['item'];

			if (empty($ret['body']) && !empty($message['html'])) {
				$ret['body'] = HTML::toBBCode($message['html']);
			}

			if (empty($ret['body'])) {
				$ret['body'] = $message['text'];
			}
		}

		$ret['body'] = self::removeGPG($ret['body']);
		$msg         = self::removeSig($ret['body']);
		$ret['body'] = $msg['body'];
		$ret['body'] = self::convertQuote($ret['body'], $reply);

		if (trim($html) != '') {
			$ret['body'] = self::removeLinebreak($ret['body']);
		}

		$ret['body'] = self::unifyAttributionLine($ret['body']);

		$ret['body'] = Strings::escapeHtml($ret['body']);
		$ret['body'] = BBCode::limitBodySize($ret['body']);

		Hook::callAll('email_getmessage_end', $ret);

		return $ret;
	}

	/**
	 * fetch the specified message part number with the specified subtype
	 *
	 * @param Connection|resource $mbox    mailbox
	 * @param integer             $uid     user id
	 * @param object              $p       parts
	 * @param integer             $partno  part number
	 * @param string              $subtype sub type
	 * @return string
	 */
	private static function messageGetPart($mbox, int $uid, $p, int $partno, string $subtype): string
	{
		// $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
		global $htmlmsg,$plainmsg,$charset,$attachments;

		// DECODE DATA
		$data = ($partno)
			? @imap_fetchbody($mbox, $uid, $partno, FT_UID | FT_PEEK)
		: @imap_body($mbox, $uid, FT_UID | FT_PEEK);

		// Any part may be encoded, even plain text messages, so check everything.
		if ($p->encoding == 4) {
			$data = quoted_printable_decode($data);
		} elseif ($p->encoding == 3) {
			$data = base64_decode($data);
		}

		// PARAMETERS
		// get all parameters, like charset, filenames of attachments, etc.
		$params = [];
		if ($p->parameters) {
			foreach ($p->parameters as $x) {
				$params[strtolower($x->attribute)] = $x->value;
			}
		}

		if (isset($p->dparameters) && $p->dparameters) {
			foreach ($p->dparameters as $x) {
				$params[strtolower($x->attribute)] = $x->value;
			}
		}

		// ATTACHMENT
		// Any part with a filename is an attachment,
		// so an attached text file (type 0) is not mistaken as the message.

		if ((isset($params['filename']) && $params['filename']) || (isset($params['name']) && $params['name'])) {
			// filename may be given as 'Filename' or 'Name' or both
			$filename = $params['filename'] ?? $params['name'];
			// filename may be encoded, so see imap_mime_header_decode()
			$attachments[$filename] = $data;  // this is a problem if two files have same name
		}

		// TEXT
		if ($p->type == 0 && $data) {
			// Messages may be split in different parts because of inline attachments,
			// so append parts together with blank row.
			if (strtolower($p->subtype) == $subtype) {
				$data = iconv($params['charset'], 'UTF-8//IGNORE', $data);
				return (trim($data) ."\n\n");
			} else {
				$data = '';
			}

			// $htmlmsg .= $data ."<br><br>";
			$charset = $params['charset'];  // assume all parts are same charset
		}

		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		//	elseif ($p->type==2 && $data) {
		//		$plainmsg .= $data."\n\n";
		//	}

		// SUBPART RECURSION
		if (isset($p->parts) && $p->parts) {
			$x = "";
			foreach ($p->parts as $partno0 => $p2) {
				$x .= self::messageGetPart($mbox, $uid, $p2, $partno . '.' . ($partno0 + 1), $subtype);  // 1.2, 1.2.1, etc.
			}
			return $x;
		}
		return '';
	}

	/**
	 * Returns encoded header
	 *
	 * @param string $in_str  in string
	 * @param string $charset character set
	 * @return string
	 */
	public static function encodeHeader(string $in_str, string $charset): string
	{
		$out_str         = $in_str;
		$need_to_convert = false;

		for ($x = 0; $x < strlen($in_str); $x++) {
			if ((ord($in_str[$x]) == 0) || ((ord($in_str[$x]) > 128))) {
				$need_to_convert = true;
			}
		}

		if (!$need_to_convert) {
			return $in_str;
		}

		if ($out_str && $charset) {
			// define start delimiter, end delimiter and spacer
			$end    = "?=";
			$start  = "=?" . $charset . "?B?";
			$spacer = $end . "\r\n " . $start;

			// determine length of encoded text within chunks
			// and ensure length is even
			$length = 75 - strlen($start) - strlen($end);

			/*
				[EDIT BY danbrown AT php DOT net: The following
				is a bugfix provided by (gardan AT gmx DOT de)
				on 31-MAR-2005 with the following note:
				"This means: $length should not be even,
				but divisible by 4. The reason is that in
				base64-encoding 3 8-bit-chars are represented
				by 4 6-bit-chars. These 4 chars must not be
				split between two encoded words, according
				to RFC-2047.
			*/
			$length = $length - ($length % 4);

			// encode the string and split it into chunks
			// with spacers after each chunk
			$out_str = base64_encode($out_str);
			$out_str = chunk_split($out_str, $length, $spacer);

			// remove trailing spacer and
			// add start and end delimiters
			$spacer  = preg_quote($spacer, '/');
			$out_str = preg_replace("/" . $spacer . "$/", "", $out_str);
			$out_str = $start . $out_str . $end;
		}
		return $out_str;
	}

	/**
	 * Function send is used by Protocol::EMAIL code
	 * (not to notify the user, but to send items to email contacts)
	 *
	 * @param string $addr    address
	 * @param string $subject subject
	 * @param string $headers headers
	 * @param array  $item    item
	 * @return bool Status from mail()
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 * @todo This could be changed to use the Emailer class
	 */
	public static function send(string $addr, string $subject, string $headers, array$item)
	{
		//$headers .= 'MIME-Version: 1.0' . "\n";
		//$headers .= 'Content-Type: text/html; charset=UTF-8' . "\n";
		//$headers .= 'Content-Type: text/plain; charset=UTF-8' . "\n";
		//$headers .= 'Content-Transfer-Encoding: 8bit' . "\n\n";

		$part = uniqid('', true);

		$html = Item::prepareBody($item);

		$headers .= "Mime-Version: 1.0\n";
		$headers .= 'Content-Type: multipart/alternative; boundary="=_'.$part.'"'."\n\n";

		$body = "\n--=_".$part."\n";
		$body .= "Content-Transfer-Encoding: 8bit\n";
		$body .= "Content-Type: text/plain; charset=utf-8; format=flowed\n\n";

		$body .= HTML::toPlaintext($html)."\n";

		$body .= "--=_".$part."\n";
		$body .= "Content-Transfer-Encoding: 8bit\n";
		$body .= "Content-Type: text/html; charset=utf-8\n\n";

		$body .= '<html><head></head><body style="word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space; ">'.$html."</body></html>\n";

		$body .= "--=_".$part."--";

		//$message = '<html><body>' . $html . '</body></html>';
		//$message = html2plain($html);
		DI::logger()->notice('notifier: email delivery to ' . $addr);
		$success = mail($addr, $subject, $body, $headers);
		if ($success) {
			Item::incrementOutbound(Protocol::MAIL);
		}
		return $success;
	}

	/**
	 * Convert item URI to message id
	 *
	 * @param string $itemUri Item URI
	 * @return string Message id
	 */
	public static function iri2msgid(string $itemUri): string
	{
		$msgid = $itemUri;

		if (!strpos($itemUri, '@')) {
			$msgid = preg_replace("/urn:(\S+):(\S+)\.(\S+):(\d+):(\S+)/i", "urn!$1!$4!$5@$2.$3", $itemUri);
		}

		return $msgid;
	}

	/**
	 * Converts message id to item URI
	 *
	 * @param string $msgid Message id
	 * @return string Item URI
	 */
	public static function msgid2iri(string $msgid): string
	{
		$itemUri = $msgid;

		if (strpos($msgid, '@')) {
			$itemUri = preg_replace("/urn!(\S+)!(\d+)!(\S+)@(\S+)\.(\S+)/i", "urn:$1:$4.$5:$2:$3", $msgid);
		}

		return $itemUri;
	}

	/**
	 * Invokes preg_replace() but does return full text from parameter if it
	 * returned an empty message.
	 *
	 * @param string $pattern Pattern to match
	 * @param string $replace String to replace with
	 * @param string $text String to check
	 * @return string Replaced string
	 */
	private static function saveReplace(string $pattern, string $replace, string $text): string
	{
		$return = preg_replace($pattern, $replace, $text);

		if ($return == '') {
			$return = $text;
		}

		return $return;
	}

	/**
	 * Unifies attribution line(s)
	 *
	 * @param string $message Unfiltered message
	 * @return string Message with unified attribution line(s)
	 */
	private static function unifyAttributionLine(string $message): string
	{
		$quotestr = ['quote', 'spoiler'];
		foreach ($quotestr as $quote) {
			$message = self::saveReplace('/----- Original Message -----\s.*?From: "([^<"].*?)" <(.*?)>\s.*?To: (.*?)\s*?Cc: (.*?)\s*?Sent: (.*?)\s.*?Subject: ([^\n].*)\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/----- Original Message -----\s.*?From: "([^<"].*?)" <(.*?)>\s.*?To: (.*?)\s*?Sent: (.*?)\s.*?Subject: ([^\n].*)\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/-------- Original-Nachricht --------\s*\['.$quote.'\]\nDatum: (.*?)\nVon: (.*?) <(.*?)>\nAn: (.*?)\nBetreff: (.*?)\n/i', "[".$quote."='$2']\n", $message);
			$message = self::saveReplace('/-------- Original-Nachricht --------\s*\['.$quote.'\]\sDatum: (.*?)\s.*Von: "([^<"].*?)" <(.*?)>\s.*An: (.*?)\n.*/i', "[".$quote."='$2']\n", $message);
			$message = self::saveReplace('/-------- Original-Nachricht --------\s*\['.$quote.'\]\nDatum: (.*?)\nVon: (.*?)\nAn: (.*?)\nBetreff: (.*?)\n/i', "[".$quote."='$2']\n", $message);

			$message = self::saveReplace('/-----Urspr.*?ngliche Nachricht-----\sVon: "([^<"].*?)" <(.*?)>\s.*Gesendet: (.*?)\s.*An: (.*?)\s.*Betreff: ([^\n].*?).*:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/-----Urspr.*?ngliche Nachricht-----\sVon: "([^<"].*?)" <(.*?)>\s.*Gesendet: (.*?)\s.*An: (.*?)\s.*Betreff: ([^\n].*?)\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/Am (.*?), schrieb (.*?):\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);

			$message = self::saveReplace('/Am .*?, \d+ .*? \d+ \d+:\d+:\d+ \+\d+\sschrieb\s(.*?)\s<(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/Am (.*?) schrieb (.*?) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
			$message = self::saveReplace('/Am (.*?) schrieb <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
			$message = self::saveReplace('/Am (.*?) schrieb (.*?):\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
			$message = self::saveReplace('/Am (.*?) schrieb (.*?)\n(.*?):\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);

			$message = self::saveReplace('/(\d+)\/(\d+)\/(\d+) ([^<"].*?) <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);

			$message = self::saveReplace('/On .*?, \d+ .*? \d+ \d+:\d+:\d+ \+\d+\s(.*?)\s<(.*?)>\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/On (.*?) at (.*?), (.*?)\s<(.*?)>\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$3']\n", $message);
			$message = self::saveReplace('/On (.*?)\n([^<].*?)\s<(.*?)>\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
			$message = self::saveReplace('/On (.*?), (.*?), (.*?)\s<(.*?)>\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$3']\n", $message);
			$message = self::saveReplace('/On ([^,].*?), (.*?)\swrote:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
			$message = self::saveReplace('/On (.*?), (.*?)\swrote\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);

			// Der loescht manchmal den Body - was eigentlich unmoeglich ist
			$message = self::saveReplace('/On (.*?),(.*?),(.*?),(.*?), (.*?) wrote:\s*\['.$quote.'\]/i', "[".$quote."='$5']\n", $message);

			$message = self::saveReplace('/Zitat von ([^<].*?) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/Quoting ([^<].*?) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/From: "([^<"].*?)" <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/From: <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/Du \(([^)].*?)\) schreibst:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/--- (.*?) <.*?> schrieb am (.*?):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/--- (.*?) schrieb am (.*?):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/\* (.*?) <(.*?)> hat geschrieben:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/(.*?) <(.*?)> schrieb (.*?)\):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/(.*?) <(.*?)> schrieb am (.*?) um (.*):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/(.*?) schrieb am (.*?) um (.*):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/(.*?) \((.*?)\) schrieb:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
			$message = self::saveReplace('/(.*?) schrieb:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/(.*?) <(.*?)> writes:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/(.*?) \((.*?)\) writes:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
			$message = self::saveReplace('/(.*?) writes:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/\* (.*?) wrote:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/(.*?) wrote \(.*?\):\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/(.*?) wrote:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/([^<].*?) <.*?> hat am (.*?)\sum\s(.*)\sgeschrieben:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);

			$message = self::saveReplace('/(\d+)\/(\d+)\/(\d+) ([^<"].*?) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);
			$message = self::saveReplace('/(\d+)\/(\d+)\/(\d+) (.*?) <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);
			$message = self::saveReplace('/(\d+)\/(\d+)\/(\d+) <(.*?)>:\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);
			$message = self::saveReplace('/(\d+)\/(\d+)\/(\d+) <(.*?)>\s*\['.$quote.'\]/i', "[".$quote."='$4']\n", $message);

			$message = self::saveReplace('/(.*?) <(.*?)> schrubselte:\s*\['.$quote.'\]/i', "[".$quote."='$1']\n", $message);
			$message = self::saveReplace('/(.*?) \((.*?)\) schrubselte:\s*\['.$quote.'\]/i', "[".$quote."='$2']\n", $message);
		}
		return $message;
	}

	/**
	 * Removes GPG part from message
	 *
	 * @param string $message Unfiltered message
	 * @return string Message with GPG part
	 */
	private static function removeGPG(string $message): string
	{
		$pattern = '/(.*)\s*-----BEGIN PGP SIGNED MESSAGE-----\s*[\r\n].*Hash:.*?[\r\n](.*)'.
			'[\r\n]\s*-----BEGIN PGP SIGNATURE-----\s*[\r\n].*'.
			'[\r\n]\s*-----END PGP SIGNATURE-----(.*)/is';

		if (preg_match($pattern, $message, $result)) {
			$cleaned = trim($result[1].$result[2].$result[3]);

			$cleaned = str_replace(["\n- --\n", "\n- -"], ["\n-- \n", "\n-"], $cleaned);
		} else {
			$cleaned = $message;
		}

		return $cleaned;
	}

	/**
	 * Removes signature from message
	 *
	 * @param string $message Unfiltered message
	 * @return array Message array with no signature (elements "body" and "sig")
	 */
	private static function removeSig(string $message): array
	{
		$sigpos   = strrpos($message, "\n-- \n");
		$quotepos = strrpos($message, "[/quote]");

		if ($sigpos == 0) {
			// Especially for web.de who are using that as a separator
			$message  = str_replace("\n___________________________________________________________\n", "\n-- \n", $message);
			$sigpos   = strrpos($message, "\n-- \n");
			$quotepos = strrpos($message, "[/quote]");
		}

		// When the signature separator is inside a quote, we don't separate
		if (($sigpos < $quotepos) && ($sigpos != 0)) {
			return ['body' => $message, 'sig' => ''];
		}

		$pattern = '/(.*)[\r\n]-- [\r\n](.*)/is';

		preg_match($pattern, $message, $result);

		if (!empty($result[1]) && !empty($result[2])) {
			$cleaned = trim($result[1])."\n";
			$sig     = trim($result[2]);
		} else {
			$cleaned = $message;
			$sig     = '';
		}

		return ['body' => $cleaned, 'sig' => $sig];
	}

	/**
	 * Removes lines breaks from message
	 *
	 * @param string $message Unfiltered message
	 * @return string Message with no line breaks
	 */
	private static function removeLinebreak(string $message): string
	{
		$arrbody = explode("\n", trim($message));

		$lines  = [];
		$lineno = 0;

		foreach ($arrbody as $i => $line) {
			$currquotelevel = 0;
			$currline       = $line;
			while ((strlen($currline) > 0) && ((substr($currline, 0, 1) == '>')
				|| (substr($currline, 0, 1) == ' '))) {
				if (substr($currline, 0, 1) == '>') {
					$currquotelevel++;
				}

				$currline = ltrim(substr($currline, 1));
			}

			$quotelevel = 0;
			$nextline   = trim($arrbody[$i + 1] ?? '');
			while ((strlen($nextline) > 0) && ((substr($nextline, 0, 1) == '>')
				|| (substr($nextline, 0, 1) == ' '))) {
				if (substr($nextline, 0, 1) == '>') {
					$quotelevel++;
				}

				$nextline = ltrim(substr($nextline, 1));
			}

			if (!empty($lines[$lineno])) {
				if (substr($lines[$lineno], -1) != ' ') {
					$lines[$lineno] .= ' ';
				}

				while ((strlen($line) > 0) && ((substr($line, 0, 1) == '>')
					|| (substr($line, 0, 1) == ' '))) {

					$line = ltrim(substr($line, 1));
				}
			} else {
				$lines[$lineno] = '';
			}

			$lines[$lineno] .= $line;
			if (((substr($line, -1, 1) != ' '))
				|| ($quotelevel != $currquotelevel)) {
				$lineno++;
			}
		}
		return implode("\n", $lines);
	}

	private static function convertQuote(string $body, string $reply): string
	{
		// Convert Quotes
		$arrbody  = explode("\n", trim($body));
		$arrlevel = [];

		for ($i = 0; $i < count($arrbody); $i++) {
			$quotelevel = 0;
			$quoteline  = $arrbody[$i];

			while ((strlen($quoteline) > 0) and ((substr($quoteline, 0, 1) == '>')
				|| (substr($quoteline, 0, 1) == ' '))) {
				if (substr($quoteline, 0, 1) == '>') {
					$quotelevel++;
				}

				$quoteline = ltrim(substr($quoteline, 1));
			}

			$arrlevel[$i] = $quotelevel;
			$arrbody[$i]  = $quoteline;
		}

		$quotelevel    = 0;
		$arrbodyquoted = [];

		for ($i = 0; $i < count($arrbody); $i++) {
			$previousquote = $quotelevel;
			$quotelevel    = $arrlevel[$i];

			while ($previousquote < $quotelevel) {
				$quote       = "[quote]";
				$arrbody[$i] = $quote.$arrbody[$i];
				$previousquote++;
			}

			while ($previousquote > $quotelevel) {
				$arrbody[$i] = '[/quote]'.$arrbody[$i];
				$previousquote--;
			}

			$arrbodyquoted[] = $arrbody[$i];
		}
		while ($quotelevel > 0) {
			$arrbodyquoted[] = '[/quote]';
			$quotelevel--;
		}

		$body = implode("\n", $arrbodyquoted);

		if (strlen($body) > 0) {
			$body = $body."\n\n";
		}

		if ($reply) {
			$body = self::removeToFu($body);
		}

		return $body;
	}

	private static function removeToFu(string $message): string
	{
		$message = trim($message);

		do {
			$oldmessage = $message;
			$message    = preg_replace('=\[/quote\][\s](.*?)\[quote\]=i', '$1', $message);
			$message    = str_replace('[/quote][quote]', '', $message);
		} while ($message != $oldmessage);

		$quotes = [];

		$startquotes = 0;

		$start = 0;

		while (($pos = strpos($message, '[quote', $start)) > 0) {
			$quotes[$pos] = -1;
			$start        = $pos + 7;
			$startquotes++;
		}

		$endquotes = 0;
		$start     = 0;

		while (($pos = strpos($message, '[/quote]', $start)) > 0) {
			$start = $pos + 7;
			$endquotes++;
		}

		while ($endquotes < $startquotes) {
			$message .= '[/quote]';
			++$endquotes;
		}

		$start = 0;

		while (($pos = strpos($message, '[/quote]', $start)) > 0) {
			$quotes[$pos] = 1;
			$start        = $pos + 7;
		}

		if (strtolower(substr($message, -8)) != '[/quote]') {
			return($message);
		}

		krsort($quotes);

		$quotelevel = 0;
		$quotestart = 0;
		foreach ($quotes as $index => $quote) {
			$quotelevel += $quote;

			if (($quotelevel == 0) and ($quotestart == 0)) {
				$quotestart = $index;
			}
		}

		if ($quotestart != 0) {
			$message = trim(substr($message, 0, $quotestart))."\n[spoiler]".substr($message, $quotestart + 7, -8) . '[/spoiler]';
		}

		return $message;
	}
}
