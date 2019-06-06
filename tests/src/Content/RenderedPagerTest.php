<?php

namespace Friendica\Test\src\Content;

use Friendica\Content\RenderedPager;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\AppMockTrait;
use Friendica\Test\Util\L10nMockTrait;
use Friendica\Test\Util\RendererMockTrait;
use Friendica\Test\Util\VFSTrait;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 *
 * @note if you change the count of the pages at the full view (@see RenderedPager::DEFAULT_PAGE_LIMIT )
 *       you have to change the pageFrom / pageTo accordingly
 */
class RenderedPagerTest extends MockedTest
{
	use VFSTrait;
	use AppMockTrait;
	use RendererMockTrait;
	use L10nMockTrait;

	protected function setUp()
	{
		parent::setUp();

		$this->setUpVfsDir();
		$this->mockApp($this->root);
		$this->mockL10nT();
	}

	/**
	 * Assertion of a minimal rendering pager
	 *
	 * @param string $urlPrev      The url of the previous page
	 * @param string $urlNext      The url of the next page
	 * @param bool   $prevDisabled true, if the link of the previous page is disabled
	 * @param bool   $nextDisabled true, if the the link of the next page is disabled
	 */
	private function assertMinimal(string $urlPrev, string $urlNext, bool $prevDisabled, bool $nextDisabled)
	{
		$this->assertData([
			'class' => 'pager',
			'prev'  => [
				'url'   => $urlPrev,
				'text'  => 'newer',
				'class' => $prevDisabled ? 'previous disabled' : 'previous',
			],
			'next'  => [
				'url'   => $urlNext,
				'text'  => 'older',
				'class' => $nextDisabled ? 'next disabled' : 'next',
			]
		]);
	}

	/**
	 * Assertion of a full rendering pager
	 *
	 * @param string $urlFirst      The url of the first page
	 * @param string $urlPrev       The url of the previous page
	 * @param string $urlNext       The url of the next page
	 * @param string $urlLast       The url of the last page
	 * @param bool   $firstDisabled true, if the url of the first page is disabled
	 * @param bool   $prevDisabled  true, if the url of the previous page is disabled
	 * @param bool   $nextDisabled  true, if the url of the next page is disabled
	 * @param bool   $lastDisabled  true, if the url of the last page is disabled
	 * @param string $urlPages      url-pattern for every url in the pager
	 * @param int    $pagesFrom     first shown number/page of the pager (depends on the current page!)
	 * @param int    $pagesTo       last shown number/page of the pager (depends on the current page!)
	 * @param int    $current       The current page
	 */
	private function assertFull(string $urlFirst, string $urlPrev, string $urlNext, string $urlLast,
	                            bool $firstDisabled, bool $prevDisabled, bool $nextDisabled, bool $lastDisabled,
	                            string $urlPages, int $pagesFrom, int $pagesTo, int $current)
	{
		$pages = [];
		for ($i = $pagesFrom; $i <= $pagesTo; $i++) {
			if ($i === $current) {
				$pages[$i] = [
					'url'   => '#',
					'text'  => $i,
					'class' => 'current active',
				];
			} else {
				$pages[$i] = [
					'url'   => $urlPages . $i,
					'text'  => $i,
					'class' => 'n',
				];
			}
		}

		$this->assertData([
			'class' => 'pagination',
			'first' => [
				'url'   => $urlFirst,
				'text'  => 'first',
				'class' => $firstDisabled ? 'disabled' : '',
			],
			'prev'  => [
				'url'   => $urlPrev,
				'text'  => 'prev',
				'class' => $prevDisabled ? 'disabled' : '',
			],
			'pages' => $pages,
			'next'  => [
				'url'   => $urlNext,
				'text'  => 'next',
				'class' => $nextDisabled ? 'disabled' : '',
			],
			'last'  => [

				'url'   => $urlLast,
				'text'  => 'last',
				'class' => $lastDisabled ? 'disabled' : '',
			],
		]);
	}

	/**
	 * Assert a markup
	 *
	 * @todo In a perfect world, we don't have to use mocking-spies for unit-tests (they are now just failing with ugly exceptions..)
	 *       depends on App/Config/Rendering mocking
	 *
	 * @param array $expect
	 */
	private function assertData(array $expect)
	{
		$this->mockGetMarkupTemplate('paginate.tpl', 'test', 1);
		$this->mockReplaceMacros('test', ['pager' => $expect], '', 1);
	}

	public function dataMinimal()
	{
		return [
			'firstPage'  => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 1,
					'itemsPerPage' => 20,
					'itemCount'    => 200,
				],
				'assert' => [
					'prev'         => 'test.php?page=0',
					'next'         => 'test.php?page=2',
					'prevDisabled' => true,
					'nextDisabled' => false,
				],
			],
			'lastPage'   => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 5,
					'itemsPerPage' => 20,
					'itemCount'    => 19,
				],
				'assert' => [
					'prev'         => 'test.php?page=4',
					'next'         => 'test.php?page=6',
					'prevDisabled' => false,
					'nextDisabled' => true,
				],
			],
			'middlePage' => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 2,
					'itemsPerPage' => 20,
					'itemCount'    => 100,
				],
				'assert' => [
					'prev'         => 'test.php?page=1',
					'next'         => 'test.php?page=3',
					'prevDisabled' => false,
					'nextDisabled' => false,
				],
			],
			'onePage'    => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 1,
					'itemsPerPage' => 20,
					'itemCount'    => 19,
				],
				'assert' => [
					'prev'         => 'test.php?page=0',
					'next'         => 'test.php?page=2',
					'prevDisabled' => true,
					'nextDisabled' => true,
				],
			],
		];
	}

	/**
	 * Test the rendering of a minimal pager
	 *
	 * @dataProvider dataMinimal
	 */
	public function testMinimal(array $data, array $assert)
	{
		$this->assertMinimal($assert['prev'], $assert['next'], $assert['prevDisabled'], $assert['nextDisabled']);

		if (isset($data['itemsPerPage'])) {
			$pager = new RenderedPager($data['queryString'], $data['page'], $data['itemsPerPage']);
		} elseif (isset($data['page'])) {
			$pager = new RenderedPager($data['queryString'], $data['page']);
		} else {
			$pager = new RenderedPager($data['queryString']);
		}

		$pager->renderMinimal($data['itemCount']);
	}

	public function dataFull()
	{
		return [
			'firstPage'              => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 1,
					'itemsPerPage' => 20,
					'itemCount'    => 100,
				],
				'assert' => [
					'urlFirst'      => 'test.php?page=1',
					'urlPrev'       => 'test.php?page=0',
					'urlNext'       => 'test.php?page=2',
					'urlLast'       => 'test.php?page=5',
					'firstDisabled' => true,
					'prevDisabled'  => true,
					'nextDisabled'  => false,
					'lastDisabled'  => false,
					'urlPages'      => 'test.php?page=',
					'pagesFrom'     => 1,
					'pagesTo'       => 5,
					'current'       => 1,
				],
			],
			'lastPage'               => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 5,
					'itemsPerPage' => 20,
					'itemCount'    => 100,
				],
				'assert' => [
					'urlFirst'      => 'test.php?page=1',
					'urlPrev'       => 'test.php?page=4',
					'urlNext'       => 'test.php?page=6',
					'urlLast'       => 'test.php?page=5',
					'firstDisabled' => false,
					'prevDisabled'  => false,
					'nextDisabled'  => true,
					'lastDisabled'  => true,
					'urlPages'      => 'test.php?page=',
					'pagesFrom'     => 1,
					'pagesTo'       => 5,
					'current'       => 5,
				],
			],
			'middlePage'             => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 2,
					'itemsPerPage' => 20,
					'itemCount'    => 100,
				],
				'assert' => [
					'urlFirst'      => 'test.php?page=1',
					'urlPrev'       => 'test.php?page=1',
					'urlNext'       => 'test.php?page=3',
					'urlLast'       => 'test.php?page=5',
					'firstDisabled' => false,
					'prevDisabled'  => false,
					'nextDisabled'  => false,
					'lastDisabled'  => false,
					'urlPages'      => 'test.php?page=',
					'pagesFrom'     => 1,
					'pagesTo'       => 5,
					'current'       => 2,
				],
			],
			'unevenCountMiddle'      => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 5,
					'itemsPerPage' => 11,
					'itemCount'    => 100,
				],
				'assert' => [
					'urlFirst'      => 'test.php?page=1',
					'urlPrev'       => 'test.php?page=4',
					'urlNext'       => 'test.php?page=6',
					'urlLast'       => 'test.php?page=10',
					'firstDisabled' => false,
					'prevDisabled'  => false,
					'nextDisabled'  => false,
					'lastDisabled'  => false,
					'urlPages'      => 'test.php?page=',
					'pagesFrom'     => 1,
					'pagesTo'       => 10,
					'current'       => 5,
				],
			],
			'unevenCountLast'        => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 10,
					'itemsPerPage' => 11,
					'itemCount'    => 100,
				],
				'assert' => [
					'urlFirst'      => 'test.php?page=1',
					'urlPrev'       => 'test.php?page=9',
					'urlNext'       => 'test.php?page=11',
					'urlLast'       => 'test.php?page=10',
					'firstDisabled' => false,
					'prevDisabled'  => false,
					'nextDisabled'  => true,
					'lastDisabled'  => true,
					'urlPages'      => 'test.php?page=',
					'pagesFrom'     => 6,
					'pagesTo'       => 10,
					'current'       => 10,
				],
			],
			'unevenCountFirst'       => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 1,
					'itemsPerPage' => 11,
					'itemCount'    => 100,
				],
				'assert' => [
					'urlFirst'      => 'test.php?page=1',
					'urlPrev'       => 'test.php?page=0',
					'urlNext'       => 'test.php?page=2',
					'urlLast'       => 'test.php?page=10',
					'firstDisabled' => true,
					'prevDisabled'  => true,
					'nextDisabled'  => false,
					'lastDisabled'  => false,
					'urlPages'      => 'test.php?page=',
					'pagesFrom'     => 1,
					'pagesTo'       => 10,
					'current'       => 1,
				],
			],
			'stripAfterDefaultPages' => [
				'data'   => [
					'queryString'  => 'test.php',
					'page'         => 1,
					'itemsPerPage' => 11,
					'itemCount'    => 1000,
				],
				'assert' => [
					'urlFirst'      => 'test.php?page=1',
					'urlPrev'       => 'test.php?page=0',
					'urlNext'       => 'test.php?page=2',
					'urlLast'       => 'test.php?page=91',
					'firstDisabled' => true,
					'prevDisabled'  => true,
					'nextDisabled'  => false,
					'lastDisabled'  => false,
					'urlPages'      => 'test.php?page=',
					'pagesFrom'     => 1,
					'pagesTo'       => 10, // Default to 10, change in case you change the default page count
					'current'       => 1,
				],
			],
		];
	}

	/**
	 * Test the rendering of a full pager
	 *
	 * @dataProvider dataFull
	 */
	public function testFull(array $data, array $assert)
	{
		$this->assertFull($assert['urlFirst'], $assert['urlPrev'], $assert['urlNext'], $assert['urlLast'],
			$assert['firstDisabled'], $assert['prevDisabled'], $assert['nextDisabled'], $assert['lastDisabled'],
			$assert['urlPages'], $assert['pagesFrom'], $assert['pagesTo'], $assert['current']);


		if (isset($data['itemsPerPage'])) {
			$pager = new RenderedPager($data['queryString'], $data['page'], $data['itemsPerPage']);
		} elseif (isset($data['page'])) {
			$pager = new RenderedPager($data['queryString'], $data['page']);
		} else {
			$pager = new RenderedPager($data['queryString']);
		}

		$pager->renderFull($data['itemCount']);
	}
}
