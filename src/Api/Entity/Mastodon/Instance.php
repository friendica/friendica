<?php

namespace Friendica\Api\Entity\Mastodon;

use Friendica\Api\Entity;
use Friendica\App;
use Friendica\Core\Config;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\APContact;
use Friendica\Model\User;
use Friendica\Module\Register;

/**
 * Class Instance
 *
 * @see https://docs.joinmastodon.org/api/entities/#instance
 */
class Instance extends Entity
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
	 * @param App $app
	 *
	 * @return Instance
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function get()
	{
		$register_policy = intval(Config::get('config', 'register_policy'));

		$baseUrl = DI::baseUrl();

		$instance = new Instance();
		$instance->uri = $baseUrl->get();
		$instance->title = Config::get('config', 'sitename');
		$instance->description = Config::get('config', 'info');
		$instance->email = Config::get('config', 'admin_email');
		$instance->version = FRIENDICA_VERSION;
		$instance->urls = []; // Not supported
		$instance->stats = Stats::get();
		$instance->thumbnail = $baseUrl->get() . (Config::get('system', 'shortcut_icon') ?? 'images/friendica-32.png');
		$instance->languages = [Config::get('system', 'language')];
		$instance->max_toot_chars = (int)Config::get('config', 'api_import_size', Config::get('config', 'max_import_size'));
		$instance->registrations = ($register_policy != Register::CLOSED);
		$instance->approval_required = ($register_policy == Register::APPROVE);
		$instance->contact_account = [];

		if (!empty(Config::get('config', 'admin_email'))) {
			$adminList = explode(',', str_replace(' ', '', Config::get('config', 'admin_email')));
			$administrator = User::getByEmail($adminList[0], ['nickname']);
			if (!empty($administrator)) {
				$adminContact = DBA::selectFirst('contact', [], ['nick' => $administrator['nickname'], 'self' => true]);
				$apcontact = APContact::getByURL($adminContact['url'], false);
				$instance->contact_account = Account::create($baseUrl, $adminContact, $apcontact);
			}
		}

		return $instance;
	}
}
