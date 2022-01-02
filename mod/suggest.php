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

use Friendica\App;
use Friendica\Content\Widget;
use Friendica\Core\Renderer;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Contact;
use Friendica\Module\Contact as ModuleContact;
use Friendica\Network\HTTPException;

function suggest_content(App $a)
{
	if (!local_user()) {
		throw new HTTPException\ForbiddenException(DI::l10n()->t('Permission denied.'));
	}

	$_SESSION['return_path'] = DI::args()->getCommand();

	DI::page()['aside'] .= Widget::findPeople();
	DI::page()['aside'] .= Widget::follow();

	$contacts = Contact\Relation::getSuggestions(local_user());
	if (!DBA::isResult($contacts)) {
		return DI::l10n()->t('No suggestions available. If this is a new site, please try again in 24 hours.');
	}

	$entries = [];
	foreach ($contacts as $contact) {
		$entries[] = ModuleContact::getContactTemplateVars($contact);
	}

	$tpl = Renderer::getMarkupTemplate('viewcontact_template.tpl');

	return Renderer::replaceMacros($tpl,[
		'$title' => DI::l10n()->t('Friend Suggestions'),
		'$contacts' => $entries,
	]);
}
