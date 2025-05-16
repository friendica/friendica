<?php

// Copyright (C) 2010-2024, the Friendica project
// SPDX-FileCopyrightText: 2010-2024 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Database\Definition;

use Exception;
use Friendica\DI;
use Friendica\Event\ArrayFilterEvent;

/**
 * Stores the whole View definitions
 */
class ViewDefinition
{
	/** @var string the relative path to the database view config file */
	const DBSTRUCTURE_RELATIVE_PATH = '/static/dbview.config.php';

	/** @var array The complete view definition as an array */
	protected $definition;

	/** @var string */
	protected $configFile;

	/**
	 * @param string $basePath The basepath of the dbview file (loads relative path in case of null)
	 *
	 * @throws Exception in case the config file isn't available/readable
	 */
	public function __construct(string $basePath)
	{
		$this->configFile = $basePath . static::DBSTRUCTURE_RELATIVE_PATH;

		if (!is_readable($this->configFile)) {
			throw new Exception('Missing database structure config file static/dbview.config.php at basePath=' . $basePath);
		}
	}

	/**
	 * @return array Returns the whole Definition as an array
	 */
	public function getAll(): array
	{
		return $this->definition;
	}

	/**
	 * Loads the database structure definition from the static/dbview.config.php file.
	 * On first pass, defines DB_UPDATE_VERSION constant.
	 *
	 * @param bool  $withAddonStructure Whether to tack on addons additional tables
	 *
	 * @throws Exception in case the definition cannot be found
	 *
	 * @see static/dbview.config.php
	 *
	 * @return self The current instance
	 */
	public function load(bool $withAddonStructure = false): self
	{
		$definition = require $this->configFile;

		if (!is_array($definition)) {
			throw new Exception('Corrupted database structure config file static/dbstructure.config.php');
		}

		if ($withAddonStructure) {
			$eventDispatcher = DI::eventDispatcher();

			$definition = $eventDispatcher->dispatch(
				new ArrayFilterEvent(ArrayFilterEvent::DB_VIEW_DEFINITION, $definition),
			)->getArray();
		}

		$this->definition = $definition;

		return $this;
	}
}
