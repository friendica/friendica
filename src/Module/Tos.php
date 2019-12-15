<?php
/**
 * @file mod/tos.php
 *
 * This module displays the Terms of Service for a node, if the admin
 * wants them to be displayed.
 */

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Config;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Content\Text\BBCode;
use Friendica\DI;

class Tos extends BaseModule
{
	// Some text elements we need more than once to keep updating them easy.
	public $privacy_operate;
	public $privacy_distribute;
	public $privacy_delete;
	public $privacy_complete;

	/**
	 * @brief constructor for the module, initializing the text variables
	 *
	 * To make the text variables available outside of the module, they need to
	 * be properties of the class, however cannot be set directly as the property
	 * cannot depend on a function result when declaring the variable.
	 **/
	public function __construct()
	{
		$this->privacy_operate = L10n::t('At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.');
		$this->privacy_distribute = L10n::t('This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.');
		$this->privacy_delete = L10n::t('At any point in time a logged in user can export their account data from the <a href="%1$s/settings/userexport">account settings</a>. If the user wants to delete their account they can do so at <a href="%1$s/removeme">%1$s/removeme</a>. The deletion of the account will be permanent. Deletion of the data will also be requested from the nodes of the communication partners.', System::baseurl());
		// In some cases we don't need every single one of the above separate, but all in one block.
		// So here is an array to look over
		$this->privacy_complete = [L10n::t('Privacy Statement'), $this->privacy_operate, $this->privacy_distribute, $this->privacy_delete];
	}

	/**
	 * @brief initialize the TOS module.
	 *
	 * If this is a single user instance, we expect the user to know their
	 * dealings with their own node so a TOS is not necessary.
	 *
	 **/
	public static function init(array $parameters = [])
	{
		if (strlen(Config::get('system','singleuser'))) {
			DI::baseUrl()->redirect('profile/' . Config::get('system','singleuser'));
		}
	}

	/**
	 * @brief generate the content of the /tos page
	 *
	 * The content of the /tos page is generated from two parts.
	 * (1) a free form part the admin of the node can set in the admin panel
	 * (2) an optional privacy statement that gives some transparency about
	 *     what information are needed by the software to provide the service.
	 *     This privacy statement has fixed text, so it can be translated easily.
	 *
	 * @return string
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 */
	public static function content(array $parameters = []) {
		$tpl = Renderer::getMarkupTemplate('tos.tpl');
		if (Config::get('system', 'tosdisplay')) {
			return Renderer::replaceMacros($tpl, [
				'$title' => L10n::t('Terms of Service'),
				'$tostext' => BBCode::convert(Config::get('system', 'tostext')),
				'$displayprivstatement' => Config::get('system', 'tosprivstatement'),
				'$privstatementtitle' => L10n::t('Privacy Statement'),
				'$privacy_operate' => L10n::t('At the time of registration, and for providing communications between the user account and their contacts, the user has to provide a display name (pen name), an username (nickname) and a working email address. The names will be accessible on the profile page of the account by any visitor of the page, even if other profile details are not displayed. The email address will only be used to send the user notifications about interactions, but wont be visibly displayed. The listing of an account in the node\'s user directory or the global user directory is optional and can be controlled in the user settings, it is not necessary for communication.'),
				'$privacy_distribute' => L10n::t('This data is required for communication and is passed on to the nodes of the communication partners and is stored there. Users can enter additional private data that may be transmitted to the communication partners accounts.'),
				'$privacy_delete' => L10n::t('At any point in time a logged in user can export their account data from the <a href="%1$s/settings/userexport">account settings</a>. If the user wants to delete their account they can do so at <a href="%1$s/removeme">%1$s/removeme</a>. The deletion of the account will be permanent. Deletion of the data will also be requested from the nodes of the communication partners.', System::baseurl())
			]);
		} else {
			return;
		}
	}
}
