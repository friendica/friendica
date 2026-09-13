<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Type;

use Friendica\Core\Session\Capability\IHandleSessions;

class ArraySession implements IHandleSessions
{
	/** @var array */
	protected $data = [];

	public function __construct(array $data = [])
	{
		$this->data = $data;
	}

	public function start(): IHandleSessions
	{
		return $this;
	}

	public function regenerateId(): IHandleSessions
	{
		return $this;
	}

	public function exists(string $name): bool
	{
		return !empty($this->data[$name]);
	}

	public function get(string $name, $defaults = null)
	{
		return $this->data[$name] ?? $defaults;
	}

	public function pop(string $name, $defaults = null)
	{
		$value = $defaults;
		if ($this->exists($name)) {
			$value = $this->get($name);
			$this->remove($name);
		}

		return $value;
	}

	public function set(string $name, $value)
	{
		$this->data[$name] = $value;
	}

	public function setMultiple(array $values)
	{
		$this->data = array_merge($values, $this->data);
	}

	public function remove(string $name)
	{
		unset($this->data[$name]);
	}

	public function clear()
	{
		$this->data = [];
	}
}
