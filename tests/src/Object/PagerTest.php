<?php

namespace Friendica\Test\src\Object;

use Friendica\Object\Pager;
use PHPUnit\Framework\TestCase;

class PagerTest extends TestCase
{
	public function dataPager()
	{
		return [
			'default'          => [
				'data'   => [
					'page'         => null,
					'itemsPerPage' => null,
				],
				'expect' => [
					'start'           => 0,
					'itemsPerPage'    => 50,
					'page'            => 1,
				],
			],
			'withStart'        => [
				'data'   => [
					'page'         => 5,
					'itemsPerPage' => null,
				],
				'expect' => [
					'start'           => 200,
					'itemsPerPage'    => 50,
					'page'            => 5,
				],
			],
			'withItemsPerPage' => [
				'data'   => [
					'page'         => 3,
					'itemsPerPage' => 77,
				],
				'expect' => [
					'start'           => 154,
					'itemsPerPage'    => 77,
					'page'            => 3,
				],
			],
			'negativeItemsPerPage' => [
				'data'   => [
					'page'         => 2,
					'itemsPerPage' => -35,
				],
				'expect' => [
					'start'           => 1,
					'itemsPerPage'    => 1,
					'page'            => 2,
				],
			],
			'negativePage' => [
				'data'   => [
					'page'         => -24,
					'itemsPerPage' => 20,
				],
				'expect' => [
					'start'           => 0,
					'itemsPerPage'    => 20,
					'page'            => 1,
				],
			],
			'negativePageItemsPerPage' => [
				'data'   => [
					'page'         => -24,
					'itemsPerPage' => -52,
				],
				'expect' => [
					'start'           => 0,
					'itemsPerPage'    => 1,
					'page'            => 1,
				],
			],
		];
	}

	/**
	 * Test some given data for constructing a Pager
	 *
	 * @dataProvider dataPager
	 */
	public function testConstructor(array $data, array $expect)
	{
		if (isset($data['itemsPerPage'])) {
			$pager = new Pager($data['page'], $data['itemsPerPage']);
		} elseif (isset($data['page'])) {
			$pager = new Pager($data['page']);
		} else {
			$pager = new Pager();
		}

		$this->assertEquals($expect['itemsPerPage'], $pager->getItemsPerPage());
		$this->assertEquals($expect['page'], $pager->getPage());
		$this->assertEquals($expect['start'], $pager->getStart());
	}

	/**
	 * Test some given data for the Setter of a Pager
	 *
	 * @dataProvider dataPager
	 */
	public function testSetter(array $data, array $expect)
	{
		$pager = new Pager('');

		$pager->setPage($data['page']);
		$pager->setItemsPerPage($data['itemsPerPage']);

		$this->assertEquals($expect['start'], $pager->getStart());
		$this->assertEquals($expect['itemsPerPage'], $pager->getItemsPerPage());
		$this->assertEquals($expect['page'], $pager->getPage());
	}
}
