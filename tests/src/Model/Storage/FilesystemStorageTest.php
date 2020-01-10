<?php

namespace Friendica\Test\src\Model\Storage;

use Friendica\Core\Config\IConfiguration;
use Friendica\Core\L10n\L10n;
use Friendica\Model\Storage\Filesystem;
use Friendica\Model\Storage\IStorage;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;

class FilesystemStorageTest extends StorageTest
{
	use VFSTrait;

	/** @var MockInterface|IConfiguration */
	protected $config;

	protected function setUp()
	{
		$this->setUpVfsDir();

		vfsStream::create(['storage' => []], $this->root);

		parent::setUp();
	}

	protected function getInstance()
	{
		$logger = new NullLogger();
		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		/** @var MockInterface|L10n $l10n */
		$l10n = \Mockery::mock(L10n::class)->makePartial();
		$this->config = \Mockery::mock(IConfiguration::class);
		$this->config->shouldReceive('get')
		             ->with('storage', 'filesystem_path', Filesystem::DEFAULT_BASE_FOLDER)
		             ->andReturn($this->root->getChild('storage')->url());

		return new Filesystem($this->config, $logger, $l10n);
	}

	protected function assertOption(IStorage $storage)
	{
		$this->assertEquals([
			'storagepath' => [
				'input', 'Storage base path',
				$this->root->getChild('storage')->url(),
				'Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'
			]
		], $storage->getOptions());
	}

	/**
	 * Test the exception in case of missing directorsy permissions
	 *
	 * @expectedException  \Friendica\Model\Storage\StorageException
	 * @expectedExceptionMessageRegExp /Filesystem storage failed to create \".*\". Check you write permissions./
	 */
	public function testMissingDirPermissions()
	{
		$this->root->getChild('storage')->chmod(000);

		$instance = $this->getInstance();
		$instance->put('test');
	}

	/**
	 * Test the exception in case of missing file permissions
	 *
	 * @expectedException \Friendica\Model\Storage\StorageException
	 * @expectedExceptionMessageRegExp /Filesystem storage failed to save data to \".*\". Check your write permissions/
	 */
	public function testMissingFilePermissions()
	{
		$this->markTestIncomplete("Cannot catch file_put_content() error due vfsStream failure");

		vfsStream::create(['storage' => ['f0' => ['c0' => ['k0i0' => '']]]], $this->root);

		$this->root->getChild('storage/f0/c0/k0i0')->chmod(000);

		$instance = $this->getInstance();
		$instance->put('test', 'f0c0k0i0');
	}

	/**
	 * Test the backend storage of the Filesystem Storage class
	 */
	public function testDirectoryTree()
	{
		$instance = $this->getInstance();

		$instance->put('test', 'f0c0d0i0');

		$dir = $this->root->getChild('storage/f0/c0')->url();
		$file = $this->root->getChild('storage/f0/c0/d0i0')->url();

		$this->assertDirectoryExists($dir);
		$this->assertFileExists($file);

		$this->assertDirectoryIsWritable($dir);
		$this->assertFileIsWritable($file);

		$this->assertEquals('test', file_get_contents($file));
	}
}
