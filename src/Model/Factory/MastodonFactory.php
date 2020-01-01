<?php

namespace Friendica\Model\Factory;

use Friendica\Api\Mastodon\Account;
use Friendica\Api\Mastodon\Stats;
use Friendica\App\BaseURL;
use Friendica\Core\Config\Configuration;
use Friendica\Database\Database;
use Friendica\Model\APContact;
use Friendica\Model\Entity\Api\Mastodon\Instance;
use Friendica\Model\User;
use Friendica\Module\Register;
use Psr\Log\LoggerInterface;

class MastodonFactory extends BaseFactory
{
	/** @var Instance */
	static $entity = Instance::class;

	/** @var LoggerInterface */
	protected $logger;
	/** @var BaseURL */
	protected $baseUrl;
	/** @var Configuration */
	protected $config;
	/** @var Database */
	protected $dba;

	/**
	 * @param LoggerInterface $logger
	 * @param BaseURL         $baseUrl
	 * @param Configuration   $config
	 * @param Database        $dba
	 */
	public function __construct(LoggerInterface $logger, BaseURL $baseUrl, Configuration $config, Database $dba)
	{
		$this->logger  = $logger;
		$this->baseUrl = $baseUrl;
		$this->config  = $config;
		$this->dba     = $dba;
	}

	/**
	 * @return Instance
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \ImagickException
	 */
	public function createInstance()
	{
		$register_policy = intval($this->config->get('config', 'register_policy'));

		$contact_account = [];

		if ($this->config->get('config', 'admin_email')) {
			$adminList     = explode(',', str_replace(' ', '', $this->config->get('config', 'admin_email')));
			$administrator = User::getByEmail($adminList[0], ['nickname']);
			if (!empty($administrator)) {
				$adminContact    = $this->dba->selectFirst('contact', [], ['nick' => $administrator['nickname'], 'self' => true]);
				$apContact       = APContact::getByURL($adminContact['url'], false);
				$contact_account = Account::create($this->baseUrl, $adminContact, $apContact);
			}
		}

		return self::$entity::create([
			'uri'               => $this->baseUrl->get(),
			'title'             => $this->config->get('config', 'sitename'),
			'description'       => $this->config->get('config', 'info'),
			'email'             => $this->config->get('config', 'admin_email'),
			'version'           => FRIENDICA_VERSION,
			'urls'              => [],
			'stats'             => Stats::get(),
			'thumbnail'         => $this->baseUrl->get() . ($this->config->get('system', 'shortcut_icon') ?? 'images/friendica-32.png'),
			'languages'         => [$this->config->get('system', 'language')],
			'max_toot_chars'    => (int)$this->config->get('config', 'api_import_size', $this->config->get('config', 'max_import_size')),
			'registrations'     => $register_policy != Register::CLOSED,
			'approval_required' => ($register_policy == Register::APPROVE),
			'contact_account'   => $contact_account,
		]);
	}
}
