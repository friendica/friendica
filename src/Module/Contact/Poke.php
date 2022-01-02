<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module\Contact;

use Friendica\BaseModule;
use Friendica\Content\Widget;
use Friendica\Core\Hook;
use Friendica\Core\Logger;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model;
use Friendica\Model\Contact;
use Friendica\Network\HTTPException;
use Friendica\Protocol\Activity;
use Friendica\Util\XML;

class Poke extends BaseModule
{
	protected function post(array $request = [])
	{
		if (!local_user() || empty($this->parameters['id'])) {
			return self::postReturn(false);
		}

		$uid = local_user();

		if (empty($_POST['verb'])) {
			return self::postReturn(false);
		}

		$verb = $_POST['verb'];

		$verbs = DI::l10n()->getPokeVerbs();
		if (!array_key_exists($verb, $verbs)) {
			return self::postReturn(false);
		}

		$activity = Activity::POKE . '#' . urlencode($verbs[$verb][0]);

		$contact_id = intval($this->parameters['id']);
		if (!$contact_id) {
			return self::postReturn(false);
		}

		Logger::info('verb ' . $verb . ' contact ' . $contact_id);

		$contact = DBA::selectFirst('contact', ['id', 'name', 'url', 'photo'], ['id' => $this->parameters['id'], 'uid' => local_user()]);
		if (!DBA::isResult($contact)) {
			return self::postReturn(false);
		}

		$a = DI::app();

		$private = !empty($_POST['private']) ? Model\Item::PRIVATE : Model\Item::PUBLIC;

		$user = Model\User::getById($a->getLoggedInUserId());
		$allow_cid     = ($private ? '<' . $contact['id']. '>' : $user['allow_cid']);
		$allow_gid     = ($private ? '' : $user['allow_gid']);
		$deny_cid      = ($private ? '' : $user['deny_cid']);
		$deny_gid      = ($private ? '' : $user['deny_gid']);

		$actor = Contact::getById($a->getContactId());

		$uri = Model\Item::newURI($uid);

		$arr = [];

		$arr['guid']          = System::createUUID();
		$arr['uid']           = $uid;
		$arr['uri']           = $uri;
		$arr['wall']          = 1;
		$arr['contact-id']    = $actor['id'];
		$arr['owner-name']    = $actor['name'];
		$arr['owner-link']    = $actor['url'];
		$arr['owner-avatar']  = $actor['thumb'];
		$arr['author-name']   = $actor['name'];
		$arr['author-link']   = $actor['url'];
		$arr['author-avatar'] = $actor['thumb'];
		$arr['title']         = '';
		$arr['allow_cid']     = $allow_cid;
		$arr['allow_gid']     = $allow_gid;
		$arr['deny_cid']      = $deny_cid;
		$arr['deny_gid']      = $deny_gid;
		$arr['visible']       = 1;
		$arr['verb']          = $activity;
		$arr['private']       = $private;
		$arr['object-type']   = Activity\ObjectType::PERSON;

		$arr['origin']        = 1;
		$arr['body']          = '@[url=' . $actor['url'] . ']' . $actor['name'] . '[/url]' . ' ' . $verbs[$verb][2] . ' ' . '@[url=' . $contact['url'] . ']' . $contact['name'] . '[/url]';

		$arr['object'] = '<object><type>' . Activity\ObjectType::PERSON . '</type><title>' . XML::escape($contact['name']) . '</title><id>' . XML::escape($contact['url']) . '</id>';
		$arr['object'] .= '<link>' . XML::escape('<link rel="alternate" type="text/html" href="' . $contact['url'] . '" />') . "\n";

		$arr['object'] .= XML::escape('<link rel="photo" type="image/jpeg" href="' . $contact['photo'] . '" />') . "\n";
		$arr['object'] .= '</link></object>' . "\n";

		$result = Model\Item::insert($arr);

		Hook::callAll('post_local_end', $arr);

		return self::postReturn($result);
	}

	/**
	 * Since post() is called before rawContent(), we need to be able to return a JSON response in post() directly.
	 *
	 * @param bool $success
	 * @return bool
	 */
	private static function postReturn(bool $success)
	{
		if (!$success) {
			notice(DI::l10n()->t('Error while sending poke, please retry.'));
		}

		if (DI::mode()->isAjax()) {
			System::jsonExit(['success' => $success]);
		}

		return $success;
	}

	protected function content(array $request = []): string
	{
		if (!local_user()) {
			throw new HTTPException\UnauthorizedException(DI::l10n()->t('You must be logged in to use this module.'));
		}

		if (empty($this->parameters['id'])) {
			throw new HTTPException\BadRequestException();
		}

		$contact = DBA::selectFirst('contact', ['id', 'url', 'name'], ['id' => $this->parameters['id'], 'uid' => local_user()]);
		if (!DBA::isResult($contact)) {
			throw new HTTPException\NotFoundException();
		}

		DI::page()['aside'] = Widget\VCard::getHTML(Model\Contact::getByURL($contact["url"], false));

		$verbs = [];
		foreach (DI::l10n()->getPokeVerbs() as $verb => $translations) {
			if ($translations[1] !== 'NOTRANSLATION') {
				$verbs[$verb] = $translations[1];
			}
		}

		$tpl = Renderer::getMarkupTemplate('contact/poke.tpl');
		$o = Renderer::replaceMacros($tpl,[
			'$title'    => DI::l10n()->t('Poke/Prod'),
			'$desc'     => DI::l10n()->t('poke, prod or do other things to somebody'),
			'$id'       => $contact['id'],
			'$verb'     => ['verb', DI::l10n()->t('Choose what you wish to do to recipient'), '', '', $verbs],
			'$private'  => ['private', DI::l10n()->t('Make this post private')],
			'$loading'  => DI::l10n()->t('Loading...'),
			'$submit'   => DI::l10n()->t('Submit'),

		]);

		return $o;
	}
}
