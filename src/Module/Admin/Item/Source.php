<?php

namespace Friendica\Module\Admin\Item;

use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;
use Friendica\Model;
use Friendica\Module\BaseAdminModule;

/**
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Source extends BaseAdminModule

{
	public static function content(array $parameters = [])
	{
		parent::content($parameters);

		$a = DI::app();

		$guid = null;
		// @TODO: Replace with parameter from router
		if (!empty($a->argv[3])) {
			$guid = $a->argv[3];
		}

		$guid = $_REQUEST['guid'] ?? $guid;

		$source = '';
		$item_uri = '';
		$item_id = '';
		$terms = [];
		if (!empty($guid)) {
			$item = Model\Item::selectFirst(['id', 'guid', 'uri'], ['guid' => $guid]);

			$conversation = Model\Conversation::getByItemUri($item['uri']);

			$item_id = $item['id'];
			$item_uri = $item['uri'];
			$source = $conversation['source'];
			$terms = Model\Term::tagArrayFromItemId($item['id'], [Model\Term::HASHTAG, Model\Term::MENTION, Model\Term::IMPLICIT_MENTION]);
		}

		$tpl = Renderer::getMarkupTemplate('admin/item/source.tpl');
		$o = Renderer::replaceMacros($tpl, [
			'$guid'          => ['guid', L10n::t('Item Guid'), $guid, ''],
			'$source'        => $source,
			'$item_uri'      => $item_uri,
			'$item_id'       => $item_id,
			'$terms'         => $terms,
		]);

		return $o;
	}
}
