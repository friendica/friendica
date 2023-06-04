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

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Renderer;
use Friendica\DI;

/**
 * Prints the welcome page for new users
 */
class Welcome extends BaseModule
{
	protected function content(array $request = []): string
	{
		$config = DI::config();

		$mail_disabled   = ((function_exists('imap_open') &&
		                     (!$config->get('system', 'imap_disabled'))));
		$newuser_private = $config->get('system', 'newuser_private');

		$tpl = Renderer::getMarkupTemplate('welcome.tpl');

		return Renderer::replaceMacros($tpl, [
			'$welcome'     => DI::l10n()->t('Welcome to Friendica'),
			'$checklist'   => DI::l10n()->t('New Member Checklist'),
			'$description' => DI::l10n()->t('We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'),

			'$started'         => DI::l10n()->t('Getting Started'),
			'$quickstart_link' => DI::l10n()->t('Friendica Walk-Through'),
			'$quickstart_txt'  => DI::l10n()->t('On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'),

			'$settings'       => DI::l10n()->t('Settings'),
			'$settings_link'  => DI::l10n()->t('Go to Your Settings'),
			'$settings_txt'   => DI::l10n()->t('On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'),
			'$settings_other' => DI::l10n()->t('Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'),

			'$profile'                => DI::l10n()->t('Profile'),
			'$profile_photo_link'     => DI::l10n()->t('Upload Profile Photo'),
			'$profile_photo_txt'      => DI::l10n()->t('Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'),
			'$profiles_link'          => DI::l10n()->t('Edit Your Profile'),
			'$profiles_txt'           => DI::l10n()->t('Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'),
			'$profiles_keywords_link' => DI::l10n()->t('Profile Keywords'),
			'$profiles_keywords_txt'  => DI::l10n()->t('Set some public keywords for your profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.'),

			'$connecting'       => DI::l10n()->t('Connecting'),
			'$mail_disabled'    => $mail_disabled,
			'$import_mail_link' => DI::l10n()->t('Importing Emails'),
			'$import_mail_txt'  => DI::l10n()->t('Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'),
			'$contact_link'     => DI::l10n()->t('Go to Your Contacts Page'),
			'$contact_txt'      => DI::l10n()->t('Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'),
			'$directory_link'   => DI::l10n()->t('Go to Your Site\'s Directory'),
			'$directory_txt'    => DI::l10n()->t('The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'),
			'$finding_link'     => DI::l10n()->t('Finding New People'),
			'$finding_txt'      => DI::l10n()->t('On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'),

			'$circles'             => DI::l10n()->t('Circles'),
			'$circle_contact_link' => DI::l10n()->t('Add Your Contacts To Circle'),
			'$circle_contact_txt'  => DI::l10n()->t('Once you have made some friends, organize them into private conversation circles from the sidebar of your Contacts page and then you can interact with each circle privately on your Network page.'),
			'$newuser_private'    => $newuser_private,
			'$private_link'       => DI::l10n()->t('Why Aren\'t My Posts Public?'),
			'$private_txt'        => DI::l10n()->t('Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'),

			'$help'      => DI::l10n()->t('Getting Help'),
			'$help_link' => DI::l10n()->t('Go to the Help Section'),
			'$help_txt'  => DI::l10n()->t('Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'),
		]);
	}
}
