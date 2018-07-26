<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Friendica\Core\Config;

/**
 *
 * @author benlo
 */
interface IPConfigAdapter
{
	/**
	 * @brief Loads all configuration values of a user's config family into a cached storage.
	 *
	 * All configuration values of the given user are stored in global cache
	 * which is available under the global variable $a->config[$uid].
	 *
	 * @param string $uid The user_id
	 * @param string $cat The category of the configuration value
	 *
	 * @return void
	 */
	public function load($uid, $cat);

	/**
	 * @brief Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * Get a particular user's config value from the given category ($family)
	 * and the $key from a cached storage in $a->config[$uid].
	 *
	 * @param string  $uid           The user_id
	 * @param string  $cat           The category of the configuration value
	 * @param string  $k             The configuration key to query
	 * @param mixed   $default_value optional, The value to return if key is not set (default: null)
	 * @param boolean $refresh       optional, If true the config is loaded from the db and not from the cache (default: false)
	 *
	 * @return mixed Stored value or null if it does not exist
	 */
	public function get($uid, $cat, $k, $default_value = null, $refresh = false);

	/**
	 * @brief Sets a configuration value for a user
	 *
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * @note Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $uid   The user_id
	 * @param string $cat   The category of the configuration value
	 * @param string $k     The configuration key to set
	 * @param string $value The value to store
	 *
	 * @return bool Operation success
	 */
	public function set($uid, $cat, $k, $value);

	/**
	 * @brief Deletes the given key from the users's configuration.
	 *
	 * Removes the configured value from the stored cache in $a->config[$uid]
	 * and removes it from the database.
	 *
	 * @param string $uid The user_id
	 * @param string $cat The category of the configuration value
	 * @param string $k   The configuration key to delete
	 *
	 * @return mixed
	 */
	public function delete($uid, $cat, $k);
}
