<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

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

	public function __construct(
		L10n $l10n,
		BaseURL $baseUrl,
		IManageConfigValues $config,
		LoggerInterface $logger,
		string $siteEmailAddress,
		string $siteName
	) {
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
			'$htmlversion' => BBCode::convertForUriId(0, $this->body, BBCode::EXTERNAL),
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
