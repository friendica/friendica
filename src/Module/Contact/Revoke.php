<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license   GNU AGPL version 3 or any later version
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

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Content\Nav;
use Friendica\Core\Protocol;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\Contact;
use Friendica\Module\Security\Login;
use Friendica\Network\HTTPException;

class Revoke extends BaseModule
{
	/** @var array */
	private static $contact;

	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			return;
		}

		$data = Model\Contact::getPublicAndUserContactID($parameters['id'], local_user());
		if (!DBA::isResult($data)) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Unknown contact.'));
		}

		if (empty($data['user'])) {
			throw new HTTPException\ForbiddenException();
		}

		self::$contact = Model\Contact::getById($data['user']);

		if (self::$contact['deleted']) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Contact is deleted.'));
		}

		if (!empty(self::$contact['network']) && self::$contact['network'] == Protocol::PHANTOM) {
			throw new HTTPException\NotFoundException(DI::l10n()->t('Contact is being deleted.'));
		}
	}

	public static function post(array $parameters = [])
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException();
		}

		self::checkFormSecurityTokenRedirectOnError('contact/' . $parameters['id'], 'contact_revoke');

		Model\Contact::revokeFollow(self::$contact);

		notice(DI::l10n()->t('Follow was successfully revoked.'));

		DI::baseUrl()->redirect('contact/' . $parameters['id']);
	}

	public static function content(array $parameters = []): string
	{
		if (!local_user()) {
			return Login::form($_SERVER['REQUEST_URI']);
		}

		Nav::setSelected('contact');

		return Renderer::replaceMacros(Renderer::getMarkupTemplate('contact_drop_confirm.tpl'), [
			'$l10n' => [
				'header'  => DI::l10n()->t('Revoke Follow'),
				'message' => DI::l10n()->t('Do you really want to revoke this contact\'s follow? This cannot be undone and they will have to manually follow you back again.'),
				'confirm' => DI::l10n()->t('Yes'),
				'cancel'  => DI::l10n()->t('Cancel'),
			],
			'$contact'       => Contact::getContactTemplateVars(self::$contact),
			'$method'        => 'post',
			'$confirm_url'   => DI::args()->getCommand(),
			'$confirm_name'  => 'form_security_token',
			'$confirm_value' => BaseModule::getFormSecurityToken('contact_revoke'),
		]);
	}
}
