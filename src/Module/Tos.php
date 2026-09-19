<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Content\Text\BBCode;
use Friendica\Model\User;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Tos extends BaseModule
{
	// Some text elements we need more than once to keep updating them easy.
	public $privacy_operate;
	public $privacy_distribute;
	public $privacy_delete;
	public $privacy_complete;

	/** @var IManageConfigValues */
	protected $config;

	/**
	 * constructor for the module, initializing the text variables
	 *
	 * To make the text variables available outside of the module, they need to
	 * be properties of the class, however cannot be set directly as the property
	 * cannot depend on a function result when declaring the variable.
	 **/
	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, IManageConfigValues $config, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->config = $config;

		$this->privacy_operate    = $this->t('At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name, a username and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.');
		$this->privacy_distribute = $this->t('This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.');
		$this->privacy_delete     = $this->t('At any point in time a logged in user can export their account data from the <a href="%1$s">account settings</a>. If the user wants to delete their account they can do so at <a href="%2$s">%2$s</a>. The deletion of the account will be permanent. Deletion of the data will also be requested from the nodes of the communication partners.', $this->baseUrl . "/settings/userexport", $this->baseUrl . "/settings/removeme");
		// In some cases we don't need every single one of the above separate, but all in one block.
		// So here is an array to look over
		$this->privacy_complete = [$this->t('Privacy Statement'), $this->privacy_operate,
			$this->privacy_distribute, $this->privacy_delete];
	}

	/**
	 * generate the content of the /tos page
	 *
	 * The content of the /tos page is generated from two parts.
	 * (1) a free form part the admin of the node can set in the admin panel
	 * (2) an optional privacy statement that gives some transparency about
	 *     what information are needed by the software to provide the service.
	 *     This privacy statement has fixed text, so it can be translated easily.
	 *
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	protected function content(array $request = []): string
	{
		if ($this->config->get('system', 'singleuser')) {
			$this->baseUrl->redirect('profile/' . $this->config->get('system', 'singleuser'));
		}

		$tpl = Renderer::getMarkupTemplate('tos.tpl');
		if ($this->config->get('system', 'tosdisplay')) {
			$displaytos  = (bool) true;
			$tos_title   = $this->t('Terms of Service');
			$tos_text    = BBCode::convertForUriId(User::getSystemUriId(), $this->config->get('system', 'tostext'));
			$rules_title = $this->t('Rules'); 
			$lines = trim((string) $this->config->get('system', 'tosrules') ?: '');
			if ($lines) {
				$rules = "[ol]";
				foreach (explode("\n", $lines) as $line) {
					if (trim($line)) {
						$rules .= "\n[li]" . trim($line);
					}
				}
				$rules .= "\n[/ol]\n";
				$tos_rules  = BBCode::convertForUriId(User::getSystemUriId(), $rules);
			} else {
				$displaytos  = (bool) false;
				$tos_title   = '';
				$tos_text    = '';
				$rules_title = '';
				$tos_rules       = '';
			}
		}
		if ($this->config->get('system', 'tosprivstatement')) {
			$displayprivstatement = (bool) true;
			$priv_title   	   = $this->t('Privacy Statement');
			$priv_operate      = $this->t('At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name, a username and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.');
			$priv_distribute   = $this->t('This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.');
			$priv_delete       = $this->t('At any point in time a logged in user can export their account data from the <a href="%1$s">account settings</a>. If the user wants to delete their account they can do so at <a href="%1$s">%1$s</a>. The deletion of the account will be permanent. Deletion of the data will also be requested from the nodes of the communication partners.', $this->baseUrl . '/settings/userexport', $this->baseUrl . '/settings/removeme', );
		} else {
			$displayprivstatement = (bool) false;
			$priv_title        = '';
			$priv_operate      = '';
			$priv_distribute   = '';
			$priv_delete       = '';
		}			
		if ($displaytos || $displayprivstatement){
			return Renderer::replaceMacros($tpl, [
				'$title'                => $tos_title,
				'$tostext'              => $tos_text,
				'$rulestitle'           => $rules_title,
				'$rules'                => $tos_rules,
				'$displayprivstatement' => $displayprivstatement,
				'$privstatementtitle'   => $priv_title,
				'$privacy_operate'      => $priv_operate,
				'$privacy_distribute'   => $priv_distribute,
				'$privacy_delete'       => $priv_delete,
			]);
		} else {
			return '';
		}
	}
}
