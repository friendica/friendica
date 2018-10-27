<?php
/**
 * @file src/Module/Itemsource.php
 */
namespace Friendica\Module;

use Friendica\Content\Text;
use Friendica\Core\L10n;
use Friendica\Model;

/**
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Itemsource extends \Friendica\BaseModule
{
	public static function content()
	{
		if (!is_site_admin()) {
			return;
		}

		$source = '';
		$item_uri = '';
		if (!empty($_REQUEST['guid'])) {
			$item = Model\Item::selectFirst([], ['guid' => $_REQUEST['guid']]);

			$conversation = Model\Conversation::getByItemUri($item['uri']);

			$item_uri = $item['uri'];
			$source = htmlspecialchars($conversation['source']);
		}

		$tpl = get_markup_template('debug/itemsource.tpl');
		$o = Text::replaceMacros($tpl, [
			'$guid'          => ['guid', L10n::t('Item Guid'), htmlentities(defaults($_REQUEST, 'guid', '')), ''],
			'$source'        => $source,
			'$item_uri'      => $item_uri
		]);

		return $o;
	}
}
