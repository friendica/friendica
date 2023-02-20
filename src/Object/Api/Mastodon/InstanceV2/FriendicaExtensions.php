<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Object\Api\Mastodon\InstanceV2;

use Friendica\App;
use Friendica\BaseDataTransferObject;
use Friendica\DI;

/**
 * Class FriendicaExtensions
 *
 * Friendica specific additional fields on the Instance V2 object
 *
 * @see https://docs.joinmastodon.org/entities/Instance/
 */
class FriendicaExtensions extends BaseDataTransferObject
{
	/** @var string */
	protected $version;
	/** @var string */
	protected $codename;
	/** @var int */
	protected $db_version;

	public function __construct()
	{
		$this->version    = App::VERSION;
		$this->codename   = App::CODENAME;
		$this->db_version = DI::config()->get('system', 'build');
	}
}
