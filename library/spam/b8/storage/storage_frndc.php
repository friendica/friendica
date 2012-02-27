<?php

#   Copyright (C) 2006-2011 Tobias Leupold <tobias.leupold@web.de>
#
#   This file is part of the b8 package
#
#   This program is free software; you can redistribute it and/or modify it
#   under the terms of the GNU Lesser General Public License as published by
#   the Free Software Foundation in version 2.1 of the License.
#
#   This program is distributed in the hope that it will be useful, but
#   WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
#   or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
#   License for more details.
#
#   You should have received a copy of the GNU Lesser General Public License
#   along with this program; if not, write to the Free Software Foundation,
#   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307, USA.

/**
 * The MySQL abstraction layer for communicating with the database.
 * Copyright (C) 2009 Oliver Lillie (aka buggedcom)
 * Copyright (C) 2010-2011 Tobias Leupold <tobias.leupold@web.de>
 *
 * @license LGPL
 * @access public
 * @package b8
 * @author Oliver Lillie (aka buggedcom) (original PHP 5 port and optimizations)
 * @author Tobias Leupold
 */

class b8_storage_frndc extends b8_storage_base
{

	public $config = array(
		'database'        => 'b8_wordlist',
		'table_name'      => 'b8_wordlist',
		'host'            => 'localhost',
		'user'            => FALSE,
		'pass'            => FALSE,
		'connection'      => NULL
	);

	public $b8_config = array(
		'degenerator'     => NULL,
		'today'           => NULL
	);

	private $_connection                   = NULL;
	private $_deletes                      = array();
	private $_puts                         = array();
	private $_updates                      = array();
	private $uid                           = 0;

	const DATABASE_CONNECTION_FAIL         = 'DATABASE_CONNECTION_FAIL';
	const DATABASE_CONNECTION_ERROR        = 'DATABASE_CONNECTION_ERROR';
	const DATABASE_CONNECTION_BAD_RESOURCE = 'DATABASE_CONNECTION_BAD_RESOURCE';
	const DATABASE_SELECT_ERROR            = 'DATABASE_SELECT_ERROR';
	const DATABASE_TABLE_ACCESS_FAIL       = 'DATABASE_TABLE_ACCESS_FAIL';
	const DATABASE_WRONG_VERSION           = 'DATABASE_WRONG_VERSION';

	/**
	 * Constructs the database layer.
	 *
	 * @access public
	 * @param string $config
	 */

	function __construct($config, $degenerator, $today)
	{

		# Pass some variables of the main b8 config to this class
		$this->b8_config['degenerator'] = $degenerator;
		$this->b8_config['today']       = $today;

		# Validate the config items

		if(count($config) > 0) {

			foreach ($config as $name => $value) {

				switch($name) {

					case 'table_name':
					case 'host':
					case 'user':
					case 'pass':
					case 'database':
						$this->config[$name] = (string) $value;
						break;

					case 'connection':

						if($value !== NULL) {

							if(is_resource($value) === TRUE) {
								$resource_type = get_resource_type($value);
								$this->config['connection'] = $resource_type !== 'mysql link' && $resource_type !== 'mysql link persistent' ? FALSE : $value;
							}

							else
								$this->config['connection'] = FALSE;

						}

						break;

				}

			}

		}

	}

	/**
	 * Closes the database connection.
	 *
	 * @access public
	 * @return void
	 */

	function __destruct()
	{

		if($this->_connection === NULL)
			return;

		# Commit any changes before closing
		$this->_commit();

		# Just close the connection if no link-resource was passed and b8 created it's own connection
		if($this->config['connection'] === NULL)
			mysql_close($this->_connection);

		$this->connected = FALSE;

	}

	/**
	 * Connect to the database and do some checks.
	 *
	 * @access public
	 * @return mixed Returns TRUE on a successful database connection, otherwise returns a constant from b8.
	 */

	public function connect()
	{

		return TRUE;

		# Are we already connected?
		if($this->connected === TRUE)
			return TRUE;

		# Are we using an existing passed resource?
		if($this->config['connection'] === FALSE) {
			# ... yes we are, but the connection is not a resource, so return an error
			$this->connected = FALSE;
			return self::DATABASE_CONNECTION_BAD_RESOURCE;
		}

		elseif($this->config['connection'] === NULL) {

			# ... no we aren't so we have to connect.

			if($this->_connection = mysql_connect($this->config['host'], $this->config['user'], $this->config['pass'])) {
				if(mysql_select_db($this->config['database'], $this->_connection) === FALSE) {
					$this->connected = FALSE;
					return self::DATABASE_SELECT_ERROR . ": " . mysql_error();
				}
			}
			else {
				$this->connected = FALSE;
				return self::DATABASE_CONNECTION_ERROR;
			}

		}

		else {
			# ... yes we are
			$this->_connection = $this->config['connection'];
		}

		# Just in case ...
		if($this->_connection === NULL) {
			$this->connected = FALSE;
			return self::DATABASE_CONNECTION_FAIL;
		}

		# Check to see if the wordlist table exists
		if(mysql_query('DESCRIBE ' . $this->config['table_name'], $this->_connection) === FALSE) {
			$this->connected = FALSE;
			return self::DATABASE_TABLE_ACCESS_FAIL . ": " . mysql_error();
		}

		# Everything is okay and connected
		$this->connected = TRUE;

		# Let's see if this is a b8 database and the version is okay
		return $this->check_database();

	}

	/**
	 * Does the actual interaction with the database when fetching data.
	 *
	 * @access protected
	 * @param array $tokens
	 * @return mixed Returns an array of the returned data in the format array(token => data) or an empty array if there was no data.
	 */

	protected function _get_query($tokens, $uid)
	{

		# Construct the query ...

		if(count($tokens) > 0) {

			$where = array();

			foreach ($tokens as $token) {
				$token = dbesc($token);
				array_push($where, $token);
			}

			$where = 'token IN ("' . implode('", "', $where) . '")';
		}

		else {
			$token = dbesc($token);
			$where = 'token = "' . $token . '"';
		}

		# ... and fetch the data

		$result = q('
			SELECT token, count
			FROM ' . $this->config['table_name'] . '
			WHERE ' . $where . ' AND uid = ' . $uid );

		return $result;

	}

	/**
	 * Store a token to the database.
	 *
	 * @access protected
	 * @param string $token
	 * @param string $count
	 * @return void
	 */

	protected function _put($token, $count, $uid) {
		$token = dbesc($token);
		$count = dbesc($count);
		$uid = dbesc($uid);
		array_push($this->_puts, '("' . $token . '", "' . $count . '", "' . $uid .'")');
	}

	/**
	 * Update an existing token.
	 *
	 * @access protected
	 * @param string $token
	 * @param string $count
	 * @return void
	 */

	protected function _update($token, $count, $uid)
	{
		$token = dbesc($token);
		$count = dbesc($count);
		$uid = dbesc($uid);
		array_push($this->_puts, '("' . $token . '", "' . $count . '", "' . $uid .'")');
	}

	/**
	 * Remove a token from the database.
	 *
	 * @access protected
	 * @param string $token
	 * @return void
	 */

	protected function _del($token, $uid)
	{
		$token = dbesc($token);
		$uid = dbesc($uid);
		$this->uid = $uid;
		array_push($this->_deletes, $token);
	}

	/**
	 * Commits any modification queries.
	 *
	 * @access protected
	 * @return void
	 */

	protected function _commit($uid)
	{

		if(count($this->_deletes) > 0) {

			$result = q('
				DELETE FROM ' . $this->config['table_name'] . '
				WHERE token IN ("' . implode('", "', $this->_deletes) . '") AND uid = ' . $this->uid);

			$this->_deletes = array();

		}

		if(count($this->_puts) > 0) {

			$result = q('
				INSERT INTO ' . $this->config['table_name'] . '(token, count, uid)
				VALUES ' . implode(', ', $this->_puts));

			$this->_puts = array();

		}

		if(count($this->_updates) > 0) {

			// this still needs work
			$result = q("select * from " . $this->config['table_name'] . ' where token = ');

			
			$result = q('
				INSERT INTO ' . $this->config['table_name'] . '(token, count, uid)
				VALUES ' . implode(', ', $this->_updates) . ', ' . $uid . '
				ON DUPLICATE KEY UPDATE ' . $this->config['table_name'] . '.count = VALUES(count);', $this->_connection);

			$this->_updates = array();

		}

	}

}

?>