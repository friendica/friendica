<?php

namespace Friendica\Test\API\Friendica\Photoalbum;

use Friendica\Test\API\ApiTest;

class DeleteTest extends ApiTest
{
	/**
	 * Test the api_fr_photoalbum_delete() function.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testDefault()
	{
		api_fr_photoalbum_delete('json');
	}

	/**
	 * Test the api_fr_photoalbum_delete() function with an album name.
	 * @return void
	 * @expectedException Friendica\Network\HTTPException\BadRequestException
	 */
	public function testWithAlbum()
	{
		$_REQUEST['album'] = 'album_name';
		api_fr_photoalbum_delete('json');
	}

	/**
	 * Test the api_fr_photoalbum_delete() function with an album name.
	 * @return void
	 */
	public function testWithValidAlbum()
	{
		$this->markTestIncomplete('We need to add a dataset for this.');
	}
}
