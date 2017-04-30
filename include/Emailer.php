<?php

require_once('include/email.php');

class Emailer {
	/**
	 * Send a multipart/alternative message with Text and HTML versions
	 *
	 * @param fromName			name of the sender
	 * @param fromEmail			email fo the sender
	 * @param replyTo			replyTo address to direct responses
	 * @param toEmail			destination email address
	 * @param messageSubject	subject of the message
	 * @param htmlVersion		html version of the message
	 * @param textVersion		text only version of the message
	 * @param additionalMailHeader	additions to the smtp mail header
	 * @param optional uid      user id of the destination user
	 */
	static public function send($params) {

		call_hooks('emailer_send_prepare', $params);

		$email_textonly = False;
		if (x($params,"uid")) {
			$email_textonly = get_pconfig($params['uid'], "system", "email_textonly");
		}

		$fromName = email_header_encode(html_entity_decode($params['fromName'],ENT_QUOTES,'UTF-8'),'UTF-8');
		$messageSubject = email_header_encode(html_entity_decode($params['messageSubject'],ENT_QUOTES,'UTF-8'),'UTF-8');

		// generate a mime boundary
		$mimeBoundary   =rand(0,9)."-"
				.rand(100000000,999999999)."-"
				.rand(100000000,999999999)."=:"
				.rand(10000,99999);

		// generate a multipart/alternative message header
		$messageHeader =
			$params['additionalMailHeader'] .
			"From: $fromName <{$params['fromEmail']}>\n" .
			"Reply-To: $fromName <{$params['replyTo']}>\n" .
			"MIME-Version: 1.0\n" .
			"Content-Type: multipart/alternative; boundary=\"{$mimeBoundary}\"";

		// assemble the final multipart message body with the text and html types included
		$textBody	=	chunk_split(base64_encode($params['textVersion']));
		$htmlBody	=	chunk_split(base64_encode($params['htmlVersion']));
		$multipartMessageBody =
			"--" . $mimeBoundary . "\n" .					// plain text section
			"Content-Type: text/plain; charset=UTF-8\n" .
			"Content-Transfer-Encoding: base64\n\n" .
			$textBody . "\n";

		if (!$email_textonly && !is_null($params['htmlVersion'])){
			$multipartMessageBody .=
				"--" . $mimeBoundary . "\n" .				// text/html section
				"Content-Type: text/html; charset=UTF-8\n" .
				"Content-Transfer-Encoding: base64\n\n" .
				$htmlBody . "\n";
		}
		$multipartMessageBody .=
			"--" . $mimeBoundary . "--\n";					// message ending

		// send the message
		$hookdata = array(
			'to' => $params['toEmail'],
			'subject' => $messageSubject,
			'body' => $multipartMessageBody,
			'headers' => $messageHeader
		);
		//echo "<pre>"; var_dump($hookdata); killme();
		call_hooks("emailer_send", $hookdata);
		$res = mail(
			$hookdata['to'],							// send to address
			$hookdata['subject'],						// subject
			$hookdata['body'], 	 						// message body
			$hookdata['headers']						// message headers
		);
		logger("header " . 'To: ' . $params['toEmail'] . "\n" . $messageHeader, LOGGER_DEBUG);
		logger("return value " . (($res)?"true":"false"), LOGGER_DEBUG);
		return $res;
	}
}
