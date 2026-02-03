<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Settings;

use Friendica\App;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Capability\IHandleUserSessions;
use Friendica\Core\Worker;
use Friendica\Module\BaseSettings;
use Friendica\Module\Response;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException;
use Friendica\Util\Network;
use Friendica\Util\Profiler;
use Friendica\Worker\AddContact;
use Psr\Log\LoggerInterface;

/**
 * Module to export user data
 **/
class ContactImport extends BaseSettings
{
	/** @var IManageConfigValues */
	private $config;
	/** @var IManagePersonalConfigValues */
	private $pconfig;
	/** @var SystemMessages */
	protected $systemMessages;

	public function __construct(SystemMessages $systemMessages, IManagePersonalConfigValues $pconfig, IManageConfigValues $config, IHandleUserSessions $session, App\Page $page, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($session, $page, $l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config         = $config;
		$this->pconfig        = $pconfig;
		$this->systemMessages = $systemMessages;
	}

	protected function post(array $request = [])
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\ForbiddenException($this->l10n->t('Permission denied.'));
		}

		self::checkFormSecurityTokenRedirectOnError($this->args->getQueryString(), 'contactimport');

		parent::post();

		// Import Contacts from CSV file
		if (!empty($request['importcontact-submit'])) {
			$this->pconfig->set($this->session->getLocalUserId(), 'ostatus', 'legacy_contact', $request['legacy_contact']);
			if (isset($_FILES['importcontact-filename']) && !empty($_FILES['importcontact-filename']['tmp_name'])) {
				// was there an error
				if ($_FILES['importcontact-filename']['error'] > 0) {
					$this->logger->notice('Contact CSV file upload error', ['error' => $_FILES['importcontact-filename']['error']]);
					$this->systemMessages->addNotice($this->l10n->t('Contact CSV file upload error'));
				} else {
					$csvArray = array_map('str_getcsv', file($_FILES['importcontact-filename']['tmp_name']));
					$this->logger->notice('Import started', ['lines' => count($csvArray)]);
					// import contacts
					foreach ($csvArray as $csvRow) {
						// The 1st row may, or may not contain the headers of the table
						// We expect the 1st field of the row to contain either the URL
						// or the handle of the account, therefore we check for either
						// "http" or "@" to be present in the string.
						// All other fields from the row will be ignored
						if ((strpos($csvRow[0], '@') !== false) || Network::isValidHttpUrl($csvRow[0])) {
							AddContact::add(Worker::PRIORITY_MEDIUM, $this->session->getLocalUserId(), trim($csvRow[0], '@'));
						} else {
							$this->logger->notice('Invalid account', ['url' => $csvRow[0]]);
						}
					}
					$this->logger->notice('Import done');

					$this->systemMessages->addInfo($this->l10n->t('Importing Contacts done'));
					// delete temp file
					unlink($_FILES['importcontact-filename']['tmp_name']);
				}
			} else {
				$this->logger->notice('Import triggered, but no import file was found.');
			}
		}
		$this->baseUrl->redirect($this->args->getQueryString());
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			throw new HTTPException\ForbiddenException($this->l10n->t('Permission denied.'));
		}

		parent::content();

		$legacy_contact = $this->pconfig->get($this->session->getLocalUserId(), 'ostatus', 'legacy_contact');

		if (!empty($legacy_contact)) {
			$this->baseUrl->redirect('ostatus/subscribe?url=' . urlencode($legacy_contact));
		}

		$tpl = Renderer::getMarkupTemplate('settings/contactimport.tpl');
		return Renderer::replaceMacros($tpl, [
			'$title'                 => $this->l10n->t('Import Contacts'),
			'$submit'                => $this->l10n->t('Save Settings'),
			'$form_security_token'   => self::getFormSecurityToken('contactimport'),
			'$importcontact_text'    => $this->l10n->t('Upload a CSV file that contains the handle of your followed accounts in the first column you exported from the old account.'),
			'$importcontact_button'  => $this->l10n->t('Upload File'),
			'$importcontact_maxsize' => $this->config->get('system', 'max_csv_file_size', 30720),
			'$legacy_contact'        => ['legacy_contact', $this->t('Your legacy ActivityPub/GNU Social account'), $legacy_contact, $this->t('If you enter your old account name from an ActivityPub based system or your GNU Social/Statusnet account name here (in the format user@domain.tld), your contacts will be added automatically. The field will be emptied when done.')],
		]);
	}
}
