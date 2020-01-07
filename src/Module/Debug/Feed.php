<?php

namespace Friendica\Module\Debug;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Model;
use Friendica\Protocol;
use Friendica\Registry\App;
use Friendica\Util\Network;

/**
 * Tests a given feed of a contact
 */
class Feed extends BaseModule
{
	public static function init(array $parameters = [])
	{
		if (!local_user()) {
			info(L10n::t('You must be logged in to use this module'));
			App::baseUrl()->redirect();
		}
	}

	public static function content(array $parameters = [])
	{
		$result = [];
		if (!empty($_REQUEST['url'])) {
			$url = $_REQUEST['url'];

			$contact_id = Model\Contact::getIdForURL($url, local_user(), true);
			$contact = Model\Contact::getById($contact_id);

			$xml = Network::fetchUrl($contact['poll']);

			$import_result = Protocol\Feed::import($xml);

			$result = [
				'input' => $xml,
				'output' => var_export($import_result, true),
			];
		}

		$tpl = Renderer::getMarkupTemplate('feedtest.tpl');
		return Renderer::replaceMacros($tpl, [
			'$url'    => ['url', L10n::t('Source URL'), $_REQUEST['url'] ?? '', ''],
			'$result' => $result
		]);
	}
}
