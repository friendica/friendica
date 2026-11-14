<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Friendica\Core\Session\Capability;

/**
 * Contains all global supported Session methods
 */
interface IHandleSessions
{
	/**
	 * Start the current session
	 *
	 * @return self The own Session instance
	 */
	public function start(): IHandleSessions;

	/**
	 * Assign a new id to the current session, keeping its content
	 *
	 * Called whenever the privilege level of a session changes, so that an id
	 * known before that change cannot be used afterwards. Session types without
	 * a client-supplied id have nothing to rotate and do nothing here.
	 *
	 * @return self The own Session instance
	 */
	public function regenerateId(): IHandleSessions;

	/**
	 * Checks if the key exists in this session
	 *
	 * @param string $name
	 *
	 * @return boolean True, if it exists
	 */
	public function exists(string $name): bool;

	/**
	 * Retrieves a key from the session super global or the defaults if the key is missing or the value is falsy.
	 *
	 * Handle the case where session_start() hasn't been called and the super global isn't available.
	 *
	 * @param string $name
	 * @param mixed  $defaults Deprecated, use `Session->get($name) ?? $defaults` instead
	 *
	 * @return mixed
	 */
	public function get(string $name, $defaults = null);

	/**
	 * Retrieves a value from the provided key if it exists and removes it from session
	 *
	 * @param string $name
	 * @param mixed  $defaults Deprecated, use `Session->pop($name) ?? $defaults` instead
	 *
	 * @return mixed
	 */
	public function pop(string $name, $defaults = null);

	/**
	 * Sets a single session variable.
	 * Overrides value of existing key.
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	public function set(string $name, $value);

	/**
	 * Sets multiple session variables.
	 * Overrides values for existing keys.
	 *
	 * @param array $values
	 */
	public function setMultiple(array $values);

	/**
	 * Removes a session variable.
	 * Ignores missing keys.
	 *
	 * @param string $name
	 */
	public function remove(string $name);

	/**
	 * Clears the current session array
	 */
	public function clear();
}
