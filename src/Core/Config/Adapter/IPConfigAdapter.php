<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Friendica\Core\Config\Adapter;

/**
 *
 * @author benlo
 */
interface IPConfigAdapter
{
	/**
	 * Loads all configuration values of a user's config family and returns the loaded category as an array.
	 *
	 * @param string $uid The user_id
	 * @param string $cat The category of the configuration value
	 *
	 * @return array
	 */
	public function load(string $uid, string $cat);

	/**
	 * Get a particular user's config variable given the category name
	 * ($family) and a key.
	 *
	 * Note: Boolean variables are defined as 0/1 in the database
	 *
	 * @param string  $uid           The user_id
	 * @param string  $cat           The category of the configuration value
	 * @param string  $key           The configuration key to query
	 *
	 * @return null|mixed Stored value or null if it does not exist
	 */
	public function get(string $uid, string $cat, string $key);

	/**
	 * Stores a config value ($value) in the category ($family) under the key ($key)
	 * for the user_id $uid.
	 *
	 * @note Please do not store booleans - convert to 0/1 integer values!
	 *
	 * @param string $uid   The user_id
	 * @param string $cat   The category of the configuration value
	 * @param string $key   The configuration key to set
	 * @param string $value The value to store
	 *
	 * @return bool Operation success
	 */
	public function set(string $uid, string $cat, string $key, $value);

	/**
	 * Removes the configured value from the stored cache
	 * and removes it from the database.
	 *
	 * @param string $uid The user_id
	 * @param string $cat The category of the configuration value
	 * @param string $key The configuration key to delete
	 *
	 * @return bool Operation success
	 */
	public function delete(string $uid, string $cat, string $key);

	/**
	 * Checks, if the current adapter is connected to the backend
	 *
	 * @return bool
	 */
	public function isConnected();

	/**
	 * Checks, if a config key ($key) in the category ($cat) is already loaded for the user_id $uid.
	 *
	 * @param string $uid The user_id
	 * @param string $cat The configuration category
	 * @param string $key The configuration key
	 *
	 * @return bool
	 */
	public function isLoaded(string $uid, string $cat, string $key);
}
