<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Type;

use Friendica\Core\Session\Capability\IHandleSessions;

/**
 * Contains the base methods for $_SESSION interaction
 */
class AbstractSession implements IHandleSessions
{
	/**
	 * {@inheritDoc}
	 */
	public function start(): IHandleSessions
	{
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function regenerateId(): IHandleSessions
	{
		return $this;
	}

	/**
	 * {@inheritDoc}}
	 */
	public function exists(string $name): bool
	{
		return isset($_SESSION[$name]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get(string $name, $defaults = null)
	{
		return $_SESSION[$name] ?? $defaults;
	}

	/**
	 * {@inheritDoc}
	 */
	public function pop(string $name, $defaults = null)
	{
		$value = $defaults;
		if ($this->exists($name)) {
			$value = $this->get($name);
			$this->remove($name);
		}

		return $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function set(string $name, $value)
	{
		$_SESSION[$name] = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setMultiple(array $values)
	{
		$_SESSION = $values + ($_SESSION ?? []);
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove(string $name)
	{
		unset($_SESSION[$name]);
	}

	/**
	 * {@inheritDoc}
	 */
	public function clear()
	{
		$_SESSION = [];
	}
}
