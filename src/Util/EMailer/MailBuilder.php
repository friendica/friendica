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

namespace Friendica\Util\EMailer;

use Exception;
use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Model\User;
use Friendica\Network\HTTPException\UnprocessableEntityException;
use Friendica\Object\Email;
use Friendica\Object\EMail\IEmail;
use Psr\Log\LoggerInterface;

/**
 * A base class for building new emails
 */
abstract class MailBuilder
{
	/** @var string The default email banner in case nothing else is defined */
	const DEFAULT_EMAIL_BANNER = 'images/friendica-32.png';

	/** @var L10n */
	protected $l10n;
	/** @var IManageConfigValues */
	protected $config;
	/** @var BaseURL */
	protected $baseUrl;
	/** @var LoggerInterface */
	protected $logger;

	/** @var string[][] */
	protected $headers;

	/** @var string */
	protected $senderName = null;
	/** @var string */
	protected $senderAddress = null;
	/** @var string */
	protected $senderNoReply = null;

	/** @var string */
	protected $recipientAddress = null;
	/** @var int */
	protected $recipientUid = null;

	public function __construct(L10n $l10n, BaseURL $baseUrl, IManageConfigValues $config, LoggerInterface $logger)
	{
		$this->l10n    = $l10n;
		$this->baseUrl = $baseUrl;
		$this->config  = $config;
		$this->logger  = $logger;

		$hostname = $baseUrl->getHost();
		if (strpos($hostname, ':')) {
			$hostname = substr($hostname, 0, strpos($hostname, ':'));
		}

		$this->headers = [
			'Precedence'           => ['list'],
			'X-Friendica-Host'     => [$hostname],
			'X-Friendica-Platform' => [App::PLATFORM],
			'X-Friendica-Version'  => [App::VERSION],
			'List-ID'              => ['<notification.' . $hostname . '>'],
			'List-Archive'         => ['<' . $baseUrl . '/notifications/system>'],
		];
	}

	/**
	 * Gets the subject of the concrete builder, which inherits this base class
	 *
	 * @return string
	 */
	abstract protected function getSubject();

	/**
	 * Gets the HTML version of the body of the concrete builder, which inherits this base class
	 *
	 * @return string
	 */
	abstract protected function getHtmlMessage();

	/**
	 * Gets the Plaintext version of the body of the concrete builder, which inherits this base class
	 *
	 * @return string
	 */
	abstract protected function getPlaintextMessage();

	/**
	 * Adds the User ID to the email in case the mail sending needs additional properties of this user
	 *
	 * @param array $user The user entity/array, for which the email should be sent
	 *
	 * @return static
	 * @todo Once the user array is replaced with a user entity, replace this array parameter as well
	 */
	public function forUser(array $user)
	{
		$this->recipientUid = $user['uid'] ?? 0;
		try {
			$this->l10n = isset($user['language']) ? $this->l10n->withLang($user['language']) : $this->l10n;
		} catch (Exception $e) {
			$this->logger->warning('cannot use language.', ['user' => $user, 'exception' => $e]);
		}

		return $this;
	}

	/**
	 * Adds the sender to the email (if not called/set, the sender will get loaded with the help of the user id)
	 *
	 * @param string      $name    The name of the sender
	 * @param string      $address The (email) address of the sender
	 * @param string|null $noReply Optional "no-reply" (email) address (if not set, it's the same as the address)
	 *
	 * @return static
	 */
	public function withSender(string $name, string $address, string $noReply = null)
	{
		$this->senderName    = $name;
		$this->senderAddress = $address;
		$this->senderNoReply = $noReply ?? $this->senderNoReply;

		return $this;
	}

	/**
	 * Adds a recipient to the email
	 *
	 * @param string $address The (email) address of the recipient
	 *
	 * @return static
	 */
	public function withRecipient(string $address)
	{
		$this->recipientAddress = $address;

		return $this;
	}

	/**
	 * Returns the current headers
	 *
	 * @return string[][]
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Sets the headers
	 *
	 * Expected format is
	 * [
	 *   'Header1' => ['value1', 'value2', ...],
	 *   'Header2' => ['value3', 'value4', ...],
	 *   ...
	 * ]
	 *
	 * @param string[][] $headers
	 * @return $this
	 */
	public function withHeaders(array $headers): MailBuilder
	{
		$this->headers = $headers;

		return $this;
	}

	/**
	 * Adds a value to a header
	 *
	 * @param string $name The header name
	 * @param string $value The value of the header to add
	 *
	 * @return static
	 */
	public function addHeader(string $name, string $value)
	{
		$this->headers[$name][] = $value;

		return $this;
	}

	/**
	 * Sets a value to a header (overwrites existing values)
	 *
	 * @param string $name The header name
	 * @param string $value The value to set
	 *
	 * @return static
	 */
	public function	setHeader(string $name, string $value)
	{
		$this->headers[$name] = [$value];

		return $this;
	}

	/**
	 * Build a email based on the given attributes
	 *
	 * @param bool $raw True, if the email shouldn't get extended by the default email-template
	 *
	 * @return IEmail A new generated email
	 *
	 * @throws UnprocessableEntityException
	 * @throws Exception
	 */
	public function build(bool $raw = false)
	{
		if ((empty($this->recipientAddress)) &&
		    !empty($this->recipientUid)) {
			$user = User::getById($this->recipientUid, ['email']);

			if (!empty($user['email'])) {
				$this->recipientAddress = $user['email'];
			}
		}

		if (empty($this->recipientAddress)) {
			throw new UnprocessableEntityException('Recipient address is missing.');
		}

		if (empty($this->senderAddress) || empty($this->senderName)) {
			throw new UnprocessableEntityException('Sender address or name is missing.');
		}

		$this->senderNoReply = $this->senderNoReply ?? $this->senderAddress;

		$msgHtml = $this->getHtmlMessage() ?? '';

		if (!$raw) {
			// load the template for private message notifications
			$tpl     = Renderer::getMarkupTemplate('email/html.tpl');
			$msgHtml = Renderer::replaceMacros($tpl, [
				'$title'       => $this->l10n->t('Friendica Notification'),
				'$product'     => App::PLATFORM,
				'$htmlversion' => $msgHtml,
				'$sitename'    => $this->config->get('config', 'sitename'),
				'$banner'      => $this->config->get('system', 'email_banner',
					$this->baseUrl . DIRECTORY_SEPARATOR . self::DEFAULT_EMAIL_BANNER),
			]);
		}

		return new Email(
			$this->senderName,
			$this->senderAddress,
			$this->senderNoReply,
			$this->recipientAddress,
			$this->getSubject() ?? '',
			$msgHtml,
			$this->getPlaintextMessage() ?? '',
			$this->headers,
			$this->recipientUid ?? null);
	}
}
