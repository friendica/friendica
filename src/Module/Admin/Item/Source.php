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

namespace Friendica\Module\Admin\Item;

use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\BaseAdmin;

class Source extends BaseAdmin

{
	protected function content(array $request = []): string
	{
		parent::content();

		$guid = basename($_REQUEST['guid'] ?? $this->parameters['guid'] ?? '');

		$source = '';
		$item_uri = '';
		$item_id = '';
		$terms = [];
		if (!empty($guid)) {
			$item = Model\Post::selectFirst(['id', 'uri-id', 'guid', 'uri'], ['guid' => $guid]);

			if ($item) {
				$conversation = Model\Conversation::getByItemUri($item['uri']);

				$item_id = $item['id'];
				$item_uri = $item['uri'];
				$source = $conversation['source'];
				$terms = Model\Tag::getByURIId($item['uri-id'], [Model\Tag::HASHTAG, Model\Tag::MENTION, Model\Tag::IMPLICIT_MENTION]);
			}
		}

		$tpl = Renderer::getMarkupTemplate('admin/item/source.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$title'       => DI::l10n()->t('Item Source'),
			'$guid'        => ['guid', DI::l10n()->t('Item Guid'), $guid, ''],
			'$source'      => $source,
			'$item_uri'    => $item_uri,
			'$item_id'     => $item_id,
			'$terms'       => $terms,
			'$itemidlbl'   => DI::l10n()->t('Item Id'),
			'$itemurilbl'  => DI::l10n()->t('Item URI'),
			'$submit'      => DI::l10n()->t('Submit'),
			'$termslbl'    => DI::l10n()->t('Terms'),
			'$taglbl'      => DI::l10n()->t('Tag'),
			'$typelbl'     => DI::l10n()->t('Type'),
			'$termlbl'     => DI::l10n()->t('Term'),
			'$urllbl'      => DI::l10n()->t('URL'),
			'$mentionlbl'  => DI::l10n()->t('Mention'),
			'$implicitlbl' => DI::l10n()->t('Implicit Mention'),
			'$sourcelbl'   => DI::l10n()->t('Source'),
		]);

		return $o;
	}
}
