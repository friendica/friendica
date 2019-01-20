<?php

namespace Friendica\Test\Util\Mocks;

use Mockery\MockInterface;

class ItemStub
{
	// All fields in the item table
	const ITEM_FIELDLIST = ['id', 'uid', 'parent', 'uri', 'parent-uri', 'thr-parent', 'guid',
		'contact-id', 'type', 'wall', 'gravity', 'extid', 'icid', 'iaid', 'psid',
		'created', 'edited', 'commented', 'received', 'changed', 'verb',
		'postopts', 'plink', 'resource-id', 'event-id', 'tag', 'attach', 'inform',
		'file', 'allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'post-type',
		'private', 'pubmail', 'moderated', 'visible', 'starred', 'bookmark',
		'unseen', 'deleted', 'origin', 'forum_mode', 'mention', 'global', 'network',
		'title', 'content-warning', 'body', 'location', 'coord', 'app',
		'rendered-hash', 'rendered-html', 'object-type', 'object', 'target-type', 'target',
		'author-id', 'author-link', 'author-name', 'author-avatar',
		'owner-id', 'owner-link', 'owner-name', 'owner-avatar'];

	const PT_ARTICLE = 0;
}

trait ItemMockTrait
{
	/**
	 * @var MockInterface The mocking interface of Friendica\Core\Hook
	 */
	private $itemMock;

	public function mockItemConstants()
	{
		if (!isset($this->itemMock)) {
			$this->itemMock = \Mockery::namedMock('Friendica\Model\Item', 'Friendica\Test\Util\Mocks\ItemStub');
		}
	}

	public function mockItemSelectFirst(array $fields = [], array $condition = [], $params = [], $return = [], $times = null)
	{
		if (!isset($this->itemMock)) {
			$this->itemMock = \Mockery::namedMock('Friendica\Model\Item', 'Friendica\Test\Util\Mocks\ItemStub');
		}

		$closure = function (array $fields = [], array $condition = [], $params = []) {
			return true;
		};

		$this->itemMock
			->shouldReceive('selectFirst')
			->withArgs($closure)
			->times($times)
			->andReturn($return);
	}
}
