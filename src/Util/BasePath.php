<?php

namespace Friendica\Util;

class BasePath
{
	/**
	 * @var string
	 */
	private $baseDir;
	/**
	 * @var array
	 */
	private $server;

	/**
	 * @param string|null $baseDir The default base path
	 * @param array       $server  server arguments
	 */
	public function __construct(string $baseDir, array $server = [])
	{
		$this->baseDir = $baseDir;
		$this->server = $server;
	}

	/**
	 * @brief Returns the base filesystem path of the App
	 *
	 * It first checks for the internal variable, then for DOCUMENT_ROOT and
	 * finally for PWD
	 *
	 * @return string
	 *
	 * @throws \Exception if directory isn't usable
	 */
	public function getPath()
	{
		$baseDir = $this->baseDir;
		$server = $this->server;

		if ((!$baseDir || !is_dir($baseDir)) && !empty($server['DOCUMENT_ROOT'])) {
			$baseDir = $server['DOCUMENT_ROOT'];
		}

		if ((!$baseDir || !is_dir($baseDir)) && !empty($server['PWD'])) {
			$baseDir = $server['PWD'];
		}

		$baseDir = self::getRealPath($baseDir);

		if (!is_dir($baseDir)) {
			throw new \Exception(sprintf('\'%s\' is not a valid basepath', $baseDir));
		}

		return $baseDir;
	}

	/**
	 * @brief Returns a normalized file path
	 *
	 * This is a wrapper for the "realpath" function.
	 * That function cannot detect the real path when some folders aren't readable.
	 * Since this could happen with some hosters we need to handle this.
	 *
	 * @param string $path The path that is about to be normalized
	 * @return string normalized path - when possible
	 */
	public static function getRealPath($path)
	{
		$normalized = realpath($path);

		if (!is_bool($normalized)) {
			return $normalized;
		} else {
			return $path;
		}
	}
}
