<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\DI;

/**
 * Prints the welcome page for new users
 */
class Welcome extends BaseModule
{
	public static function content(array $parameters = [])
	{
		$config = DI::config();

		$mail_disabled   = ((function_exists('imap_open') &&
		                     (!$config->get('system', 'imap_disabled'))));
		$newuser_private = $config->get('system', 'newuser_private');

		$tpl = Renderer::getMarkupTemplate('welcome.tpl');

		return Renderer::replaceMacros($tpl, [
			'$welcome'     => L10n::t('Welcome to Friendica'),
			'$checklist'   => L10n::t('New Member Checklist'),
			'$description' => L10n::t('We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.'),

			'$started'         => L10n::t('Getting Started'),
			'$quickstart_link' => L10n::t('Friendica Walk-Through'),
			'$quickstart_txt'  => L10n::t('On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.'),

			'$settings'       => L10n::t('Settings'),
			'$settings_link'  => L10n::t('Go to Your Settings'),
			'$settings_txt'   => L10n::t('On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.'),
			'$settings_other' => L10n::t('Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.'),

			'$profile'                => L10n::t('Profile'),
			'$profile_photo_link'     => L10n::t('Upload Profile Photo'),
			'$profile_photo_txt'      => L10n::t('Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.'),
			'$profiles_link'          => L10n::t('Edit Your Profile'),
			'$profiles_txt'           => L10n::t('Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.'),
			'$profiles_keywords_link' => L10n::t('Profile Keywords'),
			'$profiles_keywords_txt'  => L10n::t('Set some public keywords for your default profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.'),

			'$connecting'       => L10n::t('Connecting'),
			'$mail_disabled'    => $mail_disabled,
			'$import_mail_link' => L10n::t('Importing Emails'),
			'$import_mail_txt'  => L10n::t('Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX'),
			'$contact_link'     => L10n::t('Go to Your Contacts Page'),
			'$contact_txt'      => L10n::t('Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.'),
			'$directory_link'   => L10n::t('Go to Your Site\'s Directory'),
			'$directory_txt'    => L10n::t('The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.'),
			'$finding_link'     => L10n::t('Finding New People'),
			'$finding_txt'      => L10n::t('On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.'),

			'$groups'             => L10n::t('Groups'),
			'$group_contact_link' => L10n::t('Group Your Contacts'),
			'$group_contact_txt'  => L10n::t('Once you have made some friends, organize them into private conversation groups from the sidebar of your Contacts page and then you can interact with each group privately on your Network page.'),
			'$newuser_private'    => $newuser_private,
			'$private_link'       => L10n::t('Why Aren\'t My Posts Public?'),
			'$private_txt'        => L10n::t('Friendica respects your privacy. By default, your posts will only show up to people you\'ve added as friends. For more information, see the help section from the link above.'),

			'$help'      => L10n::t('Getting Help'),
			'$help_link' => L10n::t('Go to the Help Section'),
			'$help_txt'  => L10n::t('Our <strong>help</strong> pages may be consulted for detail on other program features and resources.'),
		]);
	}
}
