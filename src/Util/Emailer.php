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

namespace Friendica\Util;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Friendica\Object\EMail\IEmail;
use Friendica\Protocol\Email;
use Friendica\Util\EMailer\NotifyMailBuilder;
use Friendica\Util\EMailer\SystemMailBuilder;
use Psr\Log\LoggerInterface;

/**
 * class to handle emailing
 */
class Emailer
{
	/** @var IManageConfigValues */
	private $config;
	/** @var IManagePersonalConfigValues */
	private $pConfig;
	/** @var LoggerInterface */
	private $logger;
	/** @var App\BaseURL */
	private $baseUrl;
	/** @var L10n */
	private $l10n;

	/** @var string */
	private $siteEmailAddress;
	/** @var string */
	private $siteEmailName;

	public function __construct(IManageConfigValues $config, IManagePersonalConfigValues $pConfig, App\BaseURL $baseURL, LoggerInterface $logger,
	                            L10n $defaultLang)
	{
		$this->config      = $config;
		$this->pConfig     = $pConfig;
		$this->logger      = $logger;
		$this->baseUrl     = $baseURL;
		$this->l10n        = $defaultLang;

		$this->siteEmailAddress = $this->config->get('config', 'sender_email');
		if (empty($this->siteEmailAddress)) {
			$hostname = $this->baseUrl->getHost();
			if (strpos($hostname, ':')) {
				$hostname = substr($hostname, 0, strpos($hostname, ':'));
			}

			$this->siteEmailAddress = 'noreply@' . $hostname;
		}

		$this->siteEmailName = $this->config->get('config', 'sitename', 'Friendica Social Network');
	}

	/**
	 * Gets the site's default sender email address
	 *
	 * @return string
	 */
	public function getSiteEmailAddress()
	{
		return $this->siteEmailAddress;
	}

	/**
	 * Gets the site's default sender name
	 *
	 * @return string
	 */
	public function getSiteEmailName()
	{
		return $this->siteEmailName;
	}

	/**
	 * Creates a new system email
	 *
	 * @return SystemMailBuilder
	 */
	public function newSystemMail()
	{
		return new SystemMailBuilder($this->l10n, $this->baseUrl, $this->config, $this->logger,
			$this->getSiteEmailAddress(), $this->getSiteEmailName());
	}

	/**
	 * Creates a new mail for notifications
	 *
	 * @return NotifyMailBuilder
	 */
	public function newNotifyMail()
	{
		return new NotifyMailBuilder($this->l10n, $this->baseUrl, $this->config, $this->logger,
			$this->getSiteEmailAddress(), $this->getSiteEmailName());
	}

	/**
	 * Send a multipart/alternative message with Text and HTML versions
	 *
	 * @param IEmail $email The email to send
	 *
	 * @return bool
	 * @throws InternalServerErrorException
	 */
	public function send(IEmail $email)
	{
		Hook::callAll('emailer_send_prepare', $email);

		if (empty($email)) {
			return true;
		}

		// @see https://github.com/friendica/friendica/issues/9142
		$countMessageId = 0;
		foreach ($email->getAdditionalMailHeader() as $name => $value) {
			if (strtolower($name) == 'message-id') {
				$countMessageId += count($value);
			}
		}
		if ($countMessageId > 1) {
			$this->logger->warning('More than one Message-ID found - RFC violation', ['email' => $email]);
		}

		$email_textonly = false;
		if (!empty($email->getRecipientUid())) {
			$email_textonly = $this->pConfig->get($email->getRecipientUid(), 'system', 'email_textonly');
		}

		$fromName       = Email::encodeHeader(html_entity_decode($email->getFromName(), ENT_QUOTES, 'UTF-8'), 'UTF-8');
		$fromAddress      = $email->getFromAddress();
		$replyTo        = $email->getReplyTo();
		$messageSubject = Email::encodeHeader(html_entity_decode($email->getSubject(), ENT_QUOTES, 'UTF-8'), 'UTF-8');

		// generate a mime boundary
		$mimeBoundary = rand(0, 9) . '-'
		                . rand(100000000, 999999999) . '-'
		                . rand(100000000, 999999999) . '=:'
		                . rand(10000, 99999);

		// generate a multipart/alternative message header
		$messageHeader = $email->getAdditionalMailHeaderString() .
		                 "From: $fromName <{$fromAddress}>\r\n" .
		                 "Reply-To: $fromName <{$replyTo}>\r\n" .
		                 "MIME-Version: 1.0\r\n" .
		                 "Content-Type: multipart/alternative; boundary=\"{$mimeBoundary}\"";

		// assemble the final multipart message body with the text and html types included
		$textBody             = chunk_split(base64_encode($email->getMessage(true)));
		$htmlBody             = chunk_split(base64_encode($email->getMessage()));
		$multipartMessageBody = "--" . $mimeBoundary . "\n" .                    // plain text section
		                        "Content-Type: text/plain; charset=UTF-8\n" .
		                        "Content-Transfer-Encoding: base64\n\n" .
		                        $textBody . "\n";

		if (!$email_textonly && !is_null($email->getMessage())) {
			$multipartMessageBody .=
				"--" . $mimeBoundary . "\n" .                // text/html section
				"Content-Type: text/html; charset=UTF-8\n" .
				"Content-Transfer-Encoding: base64\n\n" .
				$htmlBody . "\n";
		}
		$multipartMessageBody .=
			"--" . $mimeBoundary . "--\n";                    // message ending

		if ($this->config->get('system', 'sendmail_params', true)) {
			$sendmail_params = '-f ' . $fromAddress;
		} else {
			$sendmail_params = null;
		}

		// send the message
		$hookdata = [
			'to'         => $email->getToAddress(),
			'subject'    => $messageSubject,
			'body'       => $multipartMessageBody,
			'headers'    => $messageHeader,
			'parameters' => $sendmail_params,
			'sent'       => false,
		];

		Hook::callAll('emailer_send', $hookdata);

		if ($hookdata['sent']) {
			return true;
		}

		$res = $this->mail(
			$hookdata['to'],
			$hookdata['subject'],
			$hookdata['body'],
			$hookdata['headers'],
			$hookdata['parameters']
		);

		$this->logger->debug('header ' . 'To: ' . $email->getToAddress() . '\n' . $messageHeader);
		$this->logger->debug('return value ' . (($res) ? 'true' : 'false'));

		return $res;
	}

	/**
	 * Wrapper around the mail() method (mainly used to overwrite for tests)
	 * @see mail()
	 *
	 * @param string $to         Recipient of this mail
	 * @param string $subject    Subject of this mail
	 * @param string $body       Message body of this mail
	 * @param string $headers    Headers of this mail
	 * @param string $parameters Additional (sendmail) parameters of this mail
	 *
	 * @return boolean true if the mail was successfully accepted for delivery, false otherwise.
	 */
	protected function mail(string $to, string $subject, string $body, string $headers, string $parameters)
	{
		return mail($to, $subject, $body, $headers, $parameters);
	}
}
