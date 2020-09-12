<?php
/**
 * @copyright Copyright (C) 2020, Friendica
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

namespace Friendica\Domain\Entity\Api\Mastodon;

use Friendica\Domain\BaseEntity;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\User;
use Friendica\Module\Register;

/**
 * Class Instance
 *
 * @see https://docs.joinmastodon.org/api/entities/#instance
 */
class Instance extends BaseEntity
{
	/** @var string (URL) */
	protected $uri;
	/** @var string */
	protected $title;
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
	/** @var Account|null */
	protected $contact_account = null;

	/**
	 * Creates an instance record
	 *
	 * @return Instance
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public static function get()
	{
		$register_policy = intval(DI::config()->get('config', 'register_policy'));

		$baseUrl = DI::baseUrl();

		$instance = new Instance();
		$instance->uri = $baseUrl->get();
		$instance->title = DI::config()->get('config', 'sitename');
		$instance->description = DI::config()->get('config', 'info');
		$instance->email = DI::config()->get('config', 'admin_email');
		$instance->version = FRIENDICA_VERSION;
		$instance->urls = []; // Not supported
		$instance->stats = Stats::get();
		$instance->thumbnail = $baseUrl->get() . (DI::config()->get('system', 'shortcut_icon') ?? 'images/friendica-32.png');
		$instance->languages = [DI::config()->get('system', 'language')];
		$instance->max_toot_chars = (int)DI::config()->get('config', 'api_import_size', DI::config()->get('config', 'max_import_size'));
		$instance->registrations = ($register_policy != Register::CLOSED);
		$instance->approval_required = ($register_policy == Register::APPROVE);
		$instance->contact_account = [];

		if (!empty(DI::config()->get('config', 'admin_email'))) {
			$adminList = explode(',', str_replace(' ', '', DI::config()->get('config', 'admin_email')));
			$administrator = User::getByEmail($adminList[0], ['nickname']);
			if (!empty($administrator)) {
				$adminContact = DBA::selectFirst('contact', ['id'], ['nick' => $administrator['nickname'], 'self' => true]);
				$instance->contact_account = DI::mstdnAccount()->createFromContactId($adminContact['id']);
			}
		}

		return $instance;
	}
}
