<?php
/**
 * @file src/Model/Storage/Filesystem.php
 * @brief Storage backend system
 */

namespace Friendica\Model\Storage;

use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Logger;
use Friendica\Util\Strings;

/**
 * @brief Filesystem based storage backend
 *
 * This class manage data on filesystem.
 * Base folder for storage is set in storage.filesystem_path.
 * Best would be for storage folder to be outside webserver folder, we are using a
 * folder relative to code tree root as default to ease things for users in shared hostings.
 * Each new resource gets a value as reference and is saved in a
 * folder tree stucture created from that value.
 */
class Filesystem implements IStorage
{
	// Default base folder
	const DEFAULT_BASE_FOLDER = 'storage';

	private static function getBasePath()
	{
		return Config::get('storage', 'filesystem_path', self::DEFAULT_BASE_FOLDER);
	}

	/**
	 * @brief Split data ref and return file path
	 * @param string  $ref  Data reference
	 * @return string
	 */
	private static function pathForRef($ref)
	{
		$base = self::getBasePath();
		$fold1 = substr($ref, 0, 2);
		$fold2 = substr($ref, 2, 2);
		$file = substr($ref, 4);

		return implode('/', [$base, $fold1, $fold2, $file]);
	}


	/**
	 * @brief Create dirctory tree to store file, with .htaccess and index.html files
	 * @param string $file Path and filename
	 * @throws StorageException
	 */
	private static function createFoldersForFile($file)
	{
		$path = dirname($file);

		if (!is_dir($path)) {
			if (!mkdir($path, 0770, true)) {
				Logger::log('Failed to create dirs ' . $path);
				throw new StorageException(L10n::t('Filesystem storage failed to create "%s". Check you write permissions.', $path));
				killme();
			}
		}

		$base = self::getBasePath();

		while ($path !== $base) {
			if (!is_file($path . '/index.html')) {
				file_put_contents($path . '/index.html', '');
			}
			$path = dirname($path);
		}
		if (!is_file($path . '/index.html')) {
			file_put_contents($path . '/index.html', '');
		}
	}

	public static function get($ref)
	{
		$file = self::pathForRef($ref);
		if (!is_file($file)) {
			return '';
		}

		return file_get_contents($file);
	}

	public static function put($data, $ref = '')
	{
		if ($ref === '') {
			$ref = Strings::getRandomHex();
		}
		$file = self::pathForRef($ref);

		self::createFoldersForFile($file);

		$r = file_put_contents($file, $data);
		if ($r === FALSE) {
			Logger::log('Failed to write data to ' . $file);
			throw new StorageException(L10n::t('Filesystem storage failed to save data to "%s". Check your write permissions', $file));
			killme();
		}
		return $ref;
	}

	public static function delete($ref)
	{
		$file = self::pathForRef($ref);
		// return true if file doesn't exists. we want to delete it: success with zero work!
		if (!is_file($file)) {
			return true;
		}
		return unlink($file);
	}

	public static function getOptions()
	{
		return [
			'storagepath' => [
				'input',
				L10n::t('Storage base path'),
				self::getBasePath(),
				L10n::t('Folder were uploaded files are saved. For maximum security, This should be a path outside web server folder tree')
			]
		];
	}
	
	public static function saveOptions($data)
	{
		$storagepath = defaults($data, 'storagepath', '');
		if ($storagepath === '' || !is_dir($storagepath)) {
			return [
				'storagepath' => L10n::t('Enter a valid existing folder')
			];
		};
		Config::set('storage', 'filesystem_path', $storagepath);
		return [];
	}

}
