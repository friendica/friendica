<?php

namespace Friendica\Module\WellKnown;

use Friendica\BaseModule;
use Friendica\DI;

/**
 * Standardized way of exposing metadata about a server running one of the distributed social networks.
 * @see https://github.com/jhass/nodeinfo/blob/master/PROTOCOL.md
 */
class NodeInfo extends BaseModule
{
	public static function rawContent(array $parameters = [])
	{
		self::printWellKnown();
	}

	/**
	 * Prints the well-known nodeinfo redirect
	 *
	 * @throws \Friendica\Network\HTTPException\NotFoundException
	 */
	private static function printWellKnown()
	{
		$nodeinfo = [
			'links' => [
				['rel'  => 'http://nodeinfo.diaspora.software/ns/schema/1.0',
				'href' => DI::baseUrl()->get() . '/nodeinfo/1.0'],
				['rel'  => 'http://nodeinfo.diaspora.software/ns/schema/2.0',
				'href' => DI::baseUrl()->get() . '/nodeinfo/2.0'],
			]
		];

		header('Content-type: application/json; charset=utf-8');
		echo json_encode($nodeinfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		exit;
	}
}
