<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\Util;

use RuntimeException;
use ZipArchive;

/**
 * Builds a ZIP file on disk with unique entry names.
 * Entries are buffered in temporary files, so only one entry is held in memory at a time.
 */
final class ZipDownload
{
	private readonly ZipArchive $zip;
	private readonly string $path;

	/** @var array<string, true> */
	private array $names = [];

	/** @var string[] */
	private array $entryFiles = [];

	public function __construct(private readonly string $tempPath)
	{
		$path = tempnam($tempPath, 'zip-');
		if ($path === false) {
			throw new RuntimeException('Unable to create a temporary file in ' . $tempPath);
		}

		$this->path = $path;
		$this->zip  = new ZipArchive();
		if ($this->zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			unlink($path);
			throw new RuntimeException('Unable to open ZIP file ' . $path);
		}
	}

	/**
	 * Adds an entry and returns the name it was stored under.
	 */
	public function add(string $name, string $data): string
	{
		$name = $this->uniqueName($name);

		$file = tempnam($this->tempPath, 'zip-entry-');
		if ($file === false || file_put_contents($file, $data) === false) {
			throw new RuntimeException('Unable to buffer ZIP entry ' . $name);
		}

		$this->entryFiles[] = $file;

		if (!$this->zip->addFile($file, $name)) {
			throw new RuntimeException('Unable to add ZIP entry ' . $name);
		}

		$this->names[$name] = true;

		return $name;
	}

	/**
	 * Writes the archive and returns its path, the caller deletes the file.
	 */
	public function close(): string
	{
		$closed = $this->zip->close();

		foreach ($this->entryFiles as $file) {
			unlink($file);
		}
		$this->entryFiles = [];

		if (!$closed) {
			throw new RuntimeException('Unable to write ZIP file ' . $this->path);
		}

		return $this->path;
	}

	private function uniqueName(string $name): string
	{
		$name = basename(str_replace('\\', '/', trim($name)));
		if ($name === '' || $name === '.' || $name === '..') {
			$name = 'file';
		}

		$dot       = strrpos($name, '.');
		$basename  = $dot ? substr($name, 0, $dot) : $name;
		$extension = $dot ? substr($name, $dot) : '';

		for ($i = 1; isset($this->names[$name]); $i++) {
			$name = $basename . '-' . $i . $extension;
		}

		return $name;
	}
}
