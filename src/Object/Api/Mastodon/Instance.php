<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Object\Api\Mastodon;

use Friendica\App;
use Friendica\App\BaseURL;
use Friendica\BaseDataTransferObject;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Database\Database;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Register;
use Friendica\Network\HTTPException;

/**
 * Class Instance
 *
 * @see https://docs.joinmastodon.org/api/entities/#instance
 */
class Instance extends BaseDataTransferObject
{
	/** @var string (URL) */
	protected $uri;
	/** @var string */
	protected $title;
	/** @var string */
	protected $short_description;
	/** @var string */
	protected $description;
	/** @var string */
	protected $email;
	/** @var string */
	protected $version;
	/** @var array */
	protected $urls;
	/** @var Stats */
	protected $stats;
	/** @var string|null */
	protected $thumbnail = null;
	/** @var array */
	protected $languages;
	/** @var int */
	protected $max_toot_chars;
	/** @var bool */
	protected $registrations;
	/** @var bool */
	protected $approval_required;
	/** @var bool */
	protected $invites_enabled;
	/** @var Account|null */
	protected $contact_account = null;
	/** @var array */
	protected $rules = [];

	/**
	 * @param IManageConfigValues $config
	 * @param BaseURL             $baseUrl
	 * @param Database            $database
	 * @param array               $rules
	 * @throws HTTPException\InternalServerErrorException
	 * @throws HTTPException\NotFoundException
	 * @throws \ImagickException
	 */
	public function __construct(IManageConfigValues $config, BaseURL $baseUrl, Database $database, array $rules = [])
	{
		$register_policy = intval($config->get('config', 'register_policy'));

		$this->uri               = $baseUrl->get();
		$this->title             = $config->get('config', 'sitename');
		$this->short_description = $this->description = $config->get('config', 'info');
		$this->email             = implode(',', User::getAdminEmailList());
		$this->version           = '2.8.0 (compatible; Friendica ' . App::VERSION . ')';
		$this->urls              = null; // Not supported
		$this->stats             = new Stats($config, $database);
		$this->thumbnail         = $baseUrl->get() . ($config->get('system', 'shortcut_icon') ?? 'images/friendica-32.png');
		$this->languages         = [$config->get('system', 'language')];
		$this->max_toot_chars    = (int)$config->get('config', 'api_import_size', $config->get('config', 'max_import_size'));
		$this->registrations     = ($register_policy != Register::CLOSED);
		$this->approval_required = ($register_policy == Register::APPROVE);
		$this->invites_enabled   = false;
		$this->contact_account   = [];
		$this->rules             = $rules;

		$administrator = User::getFirstAdmin(['nickname']);
		if ($administrator) {
			$adminContact = $database->selectFirst('contact', ['uri-id'], ['nick' => $administrator['nickname'], 'self' => true]);
			$this->contact_account = DI::mstdnAccount()->createFromUriId($adminContact['uri-id']);
		}
	}
}
