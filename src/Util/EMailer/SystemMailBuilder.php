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
use Friendica\App\BaseURL;
use Friendica\Content\Text\BBCode;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Network\HTTPException\InternalServerErrorException;
use Psr\Log\LoggerInterface;

/**
 * Builder for system-wide emails without any dependency to concrete entities (like items, activities, ..)
 */
class SystemMailBuilder extends MailBuilder
{
	/** @var string */
	protected $subject = '';
	/** @var string */
	protected $preamble = '';
	/** @var string */
	protected $body = '';

	/** @var string */
	protected $siteAdmin;

	public function __construct(L10n $l10n, BaseURL $baseUrl, IManageConfigValues $config, LoggerInterface $logger,
	                            string $siteEmailAddress, string $siteName)
	{
		parent::__construct($l10n, $baseUrl, $config, $logger);

		if ($this->config->get('config', 'admin_name')) {
			$this->siteAdmin = $l10n->t('%1$s, %2$s Administrator', $this->config->get('config', 'admin_name'), $siteName);
		} else {
			$this->siteAdmin = $l10n->t('%s Administrator', $siteName);
		}

		// Set the system wide site address/name as sender (default for system mails)
		$this->withSender($siteName, $siteEmailAddress, $siteEmailAddress);
	}

	/**
	 * Adds a message
	 *
	 * @param string $subject  The subject of the email
	 * @param string $preamble The preamble of the email
	 * @param string $body     The body of the email (optional)
	 *
	 * @return static
	 */
	public function withMessage(string $subject, string $preamble, string $body = '')
	{
		$this->subject  = $subject;
		$this->preamble = $preamble;
		$this->body     = $body;

		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function getSubject()
	{
		return $this->subject;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws InternalServerErrorException
	 * @throws Exception
	 */
	protected function getHtmlMessage()
	{
		// load the template for private message notifications
		$tpl = Renderer::getMarkupTemplate('email/system/html.tpl');
		return Renderer::replaceMacros($tpl, [
			'$preamble'    => str_replace("\n", "<br>\n", $this->preamble),
			'$thanks'      => $this->l10n->t('thanks'),
			'$site_admin'  => $this->siteAdmin,
			'$htmlversion' => BBCode::convert($this->body),
		]);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	protected function getPlaintextMessage()
	{
		// load the template for private message notifications
		$tpl = Renderer::getMarkupTemplate('email/system/text.tpl');
		return Renderer::replaceMacros($tpl, [
			'$preamble'    => $this->preamble,
			'$thanks'      => $this->l10n->t('thanks'),
			'$site_admin'  => $this->siteAdmin,
			'$textversion' => BBCode::toPlaintext($this->body),
		]);
	}
}
