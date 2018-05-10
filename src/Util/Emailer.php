<?php
/**
 * @file src/Util/Emailer.php
 */
namespace Friendica\Util;

use Friendica\Core\Addon;
use Friendica\Core\PConfig;
use Friendica\Protocol\Email;

/**
 * @brief class to handle emailing
 */
class Emailer
{
	/**
	 * Send a multipart/alternative message with Text and HTML versions
	 *
	 * @param array $params parameters
	 *                      fromName name of the sender
	 *                      fromEmail			 email fo the sender
	 *                      replyTo			     replyTo address to direct responses
	 *                      toEmail			     destination email address
	 *                      messageSubject	     subject of the message
	 *                      htmlVersion		     html version of the message
	 *                      textVersion		     text only version of the message
	 *                      additionalMailHeader additions to the smtp mail header
	 *                      optional             uid user id of the destination user
	 *
	 * @return object
	 */
	public static function send($params)
	{
		Addon::callHooks('emailer_send_prepare', $params);

		$email_textonly = false;
		if (x($params, "uid")) {
			$email_textonly = PConfig::get($params['uid'], "system", "email_textonly");
		}

		$fromName = Email::encodeHeader(html_entity_decode($params['fromName'], ENT_QUOTES, 'UTF-8'), 'UTF-8');
		$messageSubject = Email::encodeHeader(html_entity_decode($params['messageSubject'], ENT_QUOTES, 'UTF-8'), 'UTF-8');

		// generate a mime boundary
		$mimeBoundary   =rand(0, 9)."-"
				.rand(100000000, 999999999)."-"
				.rand(100000000, 999999999)."=:"
				.rand(10000, 99999);

		// generate a multipart/alternative message header
		$messageHeader = $params['additionalMailHeader'] .
						"From: $fromName <{$params['fromEmail']}>\n" .
						"Reply-To: $fromName <{$params['replyTo']}>\n" .
						"MIME-Version: 1.0\n" .
						"Content-Type: multipart/alternative; boundary=\"{$mimeBoundary}\"";

		// assemble the final multipart message body with the text and html types included
		$textBody	=	chunk_split(base64_encode($params['textVersion']));
		$htmlBody	=	chunk_split(base64_encode($params['htmlVersion']));
		$multipartMessageBody =	"--" . $mimeBoundary . "\n" .					// plain text section
								"Content-Type: text/plain; charset=UTF-8\n" .
								"Content-Transfer-Encoding: base64\n\n" .
								$textBody . "\n";

		if (!$email_textonly && !is_null($params['htmlVersion'])) {
			$multipartMessageBody .=
				"--" . $mimeBoundary . "\n" .				// text/html section
				"Content-Type: text/html; charset=UTF-8\n" .
				"Content-Transfer-Encoding: base64\n\n" .
				$htmlBody . "\n";
		}
		$multipartMessageBody .=
			"--" . $mimeBoundary . "--\n";					// message ending

		// send the message
		$hookdata = [
			'to' => $params['toEmail'],
			'subject' => $messageSubject,
			'body' => $multipartMessageBody,
			'headers' => $messageHeader
		];
		//echo "<pre>"; var_dump($hookdata); killme();
		Addon::callHooks("emailer_send", $hookdata);
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
