<?php

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\DI;
use Friendica\Model\Search;

/**
 * Node subscription preferences for social realy systems
 * @see https://git.feneas.org/jaywink/social-relay/blob/master/docs/relays.md
 */
class XSocialRelay extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		$app = DI::app();
		$config = $app->getConfig();

		$subscribe = $config->get('system', 'relay_subscribe', false);

		if ($subscribe) {
			$scope = $config->get('system', 'relay_scope', SR_SCOPE_ALL);
		} else {
			$scope = SR_SCOPE_NONE;
		}

		$systemTags = [];
		$userTags = [];

		if ($scope == SR_SCOPE_TAGS) {
			$server_tags = $config->get('system', 'relay_server_tags');
			$tagitems = explode(',', $server_tags);

			/// @todo Check if it was better to use "strtolower" on the tags
			foreach ($tagitems AS $tag) {
				$systemTags[] = trim($tag, '# ');
			}

			if ($config->get('system', 'relay_user_tags')) {
				$userTags = Search::getUserTags();
			}
		}

		$tagList = array_unique(array_merge($systemTags, $userTags));

		$relay = [
			'subscribe' => $subscribe,
			'scope'     => $scope,
			'tags'      => $tagList,
			'protocols' => [
				'diaspora' => [
					'receive' => $app->getBaseURL() . '/receive/public'
				],
				'dfrn'     => [
					'receive' => $app->getBaseURL() . '/dfrn_notify'
				]
			]
		];

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($relay, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		exit;
	}
}
