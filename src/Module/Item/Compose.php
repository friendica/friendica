<?php
/**
 * @copyright Copyright (C) 2010-2024, the Friendica project
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

namespace Friendica\Module\Item;

use DateTime;
use Friendica\App;
use Friendica\BaseModule;
use Friendica\Content\Feature;
use Friendica\Core\ACL;
use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\Hook;
use Friendica\Core\L10n;
use Friendica\Core\PConfig\Capability\IManagePersonalConfigValues;
use Friendica\Core\Renderer;
use Friendica\Core\Session\Model\UserSession;
use Friendica\Core\Theme;
use Friendica\Database\DBA;
use Friendica\Model\Contact;
use Friendica\Model\Item;
use Friendica\Model\User;
use Friendica\Module\Response;
use Friendica\Module\Security\Login;
use Friendica\Navigation\SystemMessages;
use Friendica\Network\HTTPException\NotImplementedException;
use Friendica\Util\ACLFormatter;
use Friendica\Util\Crypto;
use Friendica\Util\Profiler;
use Friendica\Util\Temporal;
use Psr\Log\LoggerInterface;

class Compose extends BaseModule
{
	/** @var SystemMessages */
	private $systemMessages;

	/** @var ACLFormatter */
	private $ACLFormatter;

	/** @var App\Page */
	private $page;

	/** @var IManagePersonalConfigValues */
	private $pConfig;

	/** @var IManageConfigValues */
	private $config;

	/** @var UserSession */
	private $session;

	/** @var App */
	private $app;


	public function __construct(App $app, UserSession $session, IManageConfigValues $config, IManagePersonalConfigValues $pConfig, App\Page $page, ACLFormatter $ACLFormatter, SystemMessages $systemMessages, L10n $l10n, App\BaseURL $baseUrl, App\Arguments $args, LoggerInterface $logger, Profiler $profiler, Response $response, array $server, array $parameters = [])
	{
		parent::__construct($l10n, $baseUrl, $args, $logger, $profiler, $response, $server, $parameters);

		$this->systemMessages = $systemMessages;
		$this->ACLFormatter   = $ACLFormatter;
		$this->page           = $page;
		$this->pConfig        = $pConfig;
		$this->config         = $config;
		$this->session        = $session;
		$this->app            = $app;
	}

	protected function post(array $request = [])
	{
		if (!empty($_REQUEST['body'])) {
			$_REQUEST['return'] = 'network';
			require_once 'mod/item.php';
			item_post();
		} else {
			$this->systemMessages->addNotice($this->l10n->t('Please enter a post body.'));
		}
	}

	protected function content(array $request = []): string
	{
		if (!$this->session->getLocalUserId()) {
			return Login::form('compose');
		}

		if ($this->app->getCurrentTheme() !== 'frio') {
			throw new NotImplementedException($this->l10n->t('This feature is only available with the frio theme.'));
		}

		$posttype = $this->parameters['type'] ?? Item::PT_ARTICLE;
		if (!in_array($posttype, [Item::PT_ARTICLE, Item::PT_PERSONAL_NOTE])) {
			switch ($posttype) {
				case 'note':
					$posttype = Item::PT_PERSONAL_NOTE;
					break;
				default:
					$posttype = Item::PT_ARTICLE;
					break;
			}
		}

		$user = User::getById($this->session->getLocalUserId(), ['allow_cid', 'allow_gid', 'deny_cid', 'deny_gid', 'default-location']);

		$contact_allow_list = $this->ACLFormatter->expand($user['allow_cid']);
		$circle_allow_list  = $this->ACLFormatter->expand($user['allow_gid']);
		$contact_deny_list  = $this->ACLFormatter->expand($user['deny_cid']);
		$circle_deny_list   = $this->ACLFormatter->expand($user['deny_gid']);

		switch ($posttype) {
			case Item::PT_PERSONAL_NOTE:
				$compose_title = $this->l10n->t('Compose new personal note');
				$type = 'note';
				$doesFederate = false;
				$contact_allow_list = [$this->app->getContactId()];
				$circle_allow_list = [];
				$contact_deny_list = [];
				$circle_deny_list = [];
				break;
			default:
				$compose_title = $this->l10n->t('Compose new post');
				$type = 'post';
				$doesFederate = true;

				$contact_allow = $_REQUEST['contact_allow'] ?? '';
				$circle_allow = $_REQUEST['circle_allow'] ?? '';
				$contact_deny = $_REQUEST['contact_deny'] ?? '';
				$circle_deny = $_REQUEST['circle_deny'] ?? '';

				if ($contact_allow
					. $circle_allow
					. $contact_deny
				    . $circle_deny)
				{
					$contact_allow_list = $contact_allow ? explode(',', $contact_allow) : [];
					$circle_allow_list  = $circle_allow  ? explode(',', $circle_allow)  : [];
					$contact_deny_list  = $contact_deny  ? explode(',', $contact_deny)  : [];
					$circle_deny_list   = $circle_deny   ? explode(',', $circle_deny)   : [];
				}

				break;
		}

		$title         = $_REQUEST['title']         ?? '';
		$summary       = $_REQUEST['summary']       ?? '';
		$sensitive     = $_REQUEST['sensitive']     ?? false;
		$category      = $_REQUEST['category']      ?? '';
		$body          = $_REQUEST['body']          ?? '';
		$location      = $_REQUEST['location']      ?? $user['default-location'];
		$wall          = $_REQUEST['wall']          ?? $type == 'post';

		$jotplugins = '';
		Hook::callAll('jot_tool', $jotplugins);

		// Output
		$this->page->registerFooterScript(Theme::getPathForFile('js/ajaxupload.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/linkPreview.js'));
		$this->page->registerFooterScript(Theme::getPathForFile('js/compose.js'));

		$contact = Contact::getById($this->app->getContactId());

		if ($this->pConfig->get($this->session->getLocalUserId(), 'system', 'set_creation_date')) {
			$created_at = Temporal::getDateTimeField(
				new \DateTime(DBA::NULL_DATETIME),
				new \DateTime('now'),
				null,
				$this->l10n->t('Created at'),
				'created_at'
			);
		} else {
			$created_at = '';
		}

		$tpl = Renderer::getMarkupTemplate('item/compose.tpl');
		return Renderer::replaceMacros($tpl, [
			'$l10n' => [
				'compose_title'        => $compose_title,
				'default'              => '',
				'summary'              => $this->l10n->t('Summary'),
				'visibility_title'     => $this->l10n->t('Visibility'),
				'mytitle'              => $this->l10n->t('This is you'),
				'submit'               => $this->l10n->t('Submit'),
				'edbold'               => $this->l10n->t('Bold'),
				'editalic'             => $this->l10n->t('Italic'),
				'eduline'              => $this->l10n->t('Underline'),
				'edquote'              => $this->l10n->t('Quote'),
				'edemojis'             => $this->l10n->t('Add emojis'),
				'contentwarn'          => $this->l10n->t('Content Warning'),
				'edcode'               => $this->l10n->t('Code'),
				'edimg'                => $this->l10n->t('Image'),
				'edurl'                => $this->l10n->t('Link'),
				'edattach'             => $this->l10n->t('Link or Media'),
				'prompttext'           => $this->l10n->t('Please enter a image/video/audio/webpage URL:'),
				'preview'              => $this->l10n->t('Preview'),
				'location_set'         => $this->l10n->t('Set your location'),
				'location_clear'       => $this->l10n->t('Clear the location'),
				'location_unavailable' => $this->l10n->t('Location services are unavailable on your device'),
				'location_disabled'    => $this->l10n->t('Location services are disabled. Please check the website\'s permissions on your device'),
				'wait'                 => $this->l10n->t('Please wait'),
				'placeholdertitle'     => $this->l10n->t('Set title'),
				'placeholdersummary'   => Feature::isEnabled($this->session->getLocalUserId(), Feature::SUMMARY) ? $this->l10n->t('Set summary, abstract or spoiler text') : '',
				'placeholdercategory'  => Feature::isEnabled($this->session->getLocalUserId(), Feature::CATEGORIES) ? $this->l10n->t('Categories (comma-separated list)') : '',
				'always_open_compose'  => $this->pConfig->get($this->session->getLocalUserId(), 'frio', 'always_open_compose',
					$this->config->get('frio', 'always_open_compose', false)) ? '' :
						$this->l10n->t('You can make this page always open when you use the New Post button in the <a href="/settings/display">Theme Customization settings</a>.'),
			],

			'$id'           => 0,
			'$posttype'     => $posttype,
			'$type'         => $type,
			'$wall'         => $wall,
			'$mylink'       => $this->baseUrl->remove($contact['url']),
			'$myphoto'      => $this->baseUrl->remove($contact['thumb']),
			'$sensitive'    => ['sensitive', $this->l10n->t('Sensitive post'), $x['sensitive'] ?? false],
			'$scheduled_at' => Temporal::getDateTimeField(
				new DateTime(),
				new DateTime('now + 6 months'),
				null,
				$this->l10n->t('Scheduled at'),
				'scheduled_at'
			),
			'$created_at'   => $created_at,
			'$title'        => $title,
			'$summary'      => $summary,
			'sensitive'     => $sensitive,
			'$category'     => $category,
			'$body'         => $body,
			'$location'     => $location,

			'$contact_allow' => implode(',', $contact_allow_list),
			'$circle_allow'  => implode(',', $circle_allow_list),
			'$contact_deny'  => implode(',', $contact_deny_list),
			'$circle_deny'   => implode(',', $circle_deny_list),

			'$jotplugins'   => $jotplugins,
			'$rand_num'     => Crypto::randomDigits(12),
			'$acl_selector'  => ACL::getFullSelectorHTML($this->page, $this->session->getLocalUserId(), $doesFederate, [
				'allow_cid' => $contact_allow_list,
				'allow_gid' => $circle_allow_list,
				'deny_cid'  => $contact_deny_list,
				'deny_gid'  => $circle_deny_list,
			]),
		]);
	}
}
