<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Module\Contact;

use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Core\L10n;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\Contact as ModelContact;
use Friendica\Module\Contact;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

class Revoke extends BaseModule
{
	/**
	 * User-specific contact (uid != 0) array
	 * @var array
	 */
	protected $contact;

	/** @var Database */
	protected $dba;

	public function __construct(L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Database $dba, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->dba = $dba;

		if (!DI::userSession()->getLocalUserId()) {
			return;
		}

		$ucid = Model\Contact::getUserContactId($this->parameters['id'], DI::userSession()->getLocalUserId());
		if (!$ucid) {
			throw new HTTPException\ForbiddenException();
		}

		$this->contact = Model\Contact::getById($ucid);

		if ($this->contact['deleted']) {
			throw new HTTPException\NotFoundException($this->t('Contact is deleted.'));
		}

		if (!empty($this->contact['network']) && $this->contact['network'] == Protocol::PHANTOM) {
			throw new HTTPException\NotFoundException($this->t('Contact is being deleted.'));
		}
	}

	protected function post(array $request = [])
	{
		if (!DI::userSession()->getLocalUserId()) {
			throw new HTTPException\UnauthorizedException();
		}

		self::checkFormSecurityTokenRedirectOnError('contact/' . $this->parameters['id'], 'contact_revoke');

		Model\Contact::revokeFollow($this->contact);

		DI::sysmsg()->addNotice($this->t('Follow was successfully revoked.'));

		$this->baseUrl->redirect('contact/' . ModelContact::getPublicContactId($this->parameters['id'], DI::userSession()->getLocalUserId()));
	}

	protected function content(array $request = []): string
	{
		if (!DI::userSession()->getLocalUserId()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		Nav::setSelected('contacts');

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('contact_drop_confirm.tpl'), [
			'$l10n' => [
				'header'  => $this->t('Revoke Follow'),
				'message' => $this->t('Do you really want to revoke this contact\'s follow? This cannot be undone and they will have to manually follow you back again.'),
				'confirm' => $this->t('Yes'),
				'cancel'  => $this->t('Cancel'),
			],
			'$contact'       => Contact::getContactTemplateVars($this->contact),
			'$method'        => 'post',
			'$confirm_url'   => $this->args->getCommand(),
			'$confirm_name'  => 'form_security_token',
			'$confirm_value' => BaseModule::getFormSecurityToken('contact_revoke'),
		]);
	}
}
