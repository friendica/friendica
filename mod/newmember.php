<?php

function newmember_content(App $a) {


	$o = '<h1>' . t('Welcome to Friendica') . '</h1>';

	$o .= '<h3>' . t('New Member Checklist') . '</h3>';

	$o .= '<div style="font-size: 120%;">';

	$o .= t('We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.');

	$o .= '<h4>' . t('Getting Started') . '</h4>';

	$o .= '<ul>';

	$o .= '<li> ' . '<a target="newmember" href="help/guide">' . t('Friendica Walk-Through') . '</a><br />' . t('On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, make some new connections, and find some groups to join.') . '</li>' . EOL;

	$o .= '</ul>';

	$o .= '<h4>' . t('Settings') . '</h4>';

	$o .= '<ul>';

	$o .= '<li>' . '<a target="newmember" href="settings">' . t('Go to Your Settings') . '</a><br />' . t('On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.') . '</li>' . EOL;

	$o .= '<li>' . t('Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.') . '</li>' . EOL;

	$o .= '</ul>';

	$o .= '<h4>' . t('Profile') . '</h4>';

	$o .= '<ul>';

	$o .= '<li>' . '<a target="newmember" href="profile_photo">' . t('Upload Profile Photo') . '</a><br />' . t('Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.') . '</li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="profiles">' . t('Edit Your Profile') . '</a><br />' . t('Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.') . '</li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="profiles">' . t('Profile Keywords') . '</a><br />' . t('Set some public keywords for your default profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.') . '</li>' . EOL;

	$o .= '</ul>';

	$o .= '<h4>' . t('Connecting') . '</h4>';

	$o .= '<ul>';

    $mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);

	if(! $mail_disabled)
		$o .= '<li>' . '<a target="newmember" href="settings/connectors">' . t('Importing Emails') . '</a><br />' . t('Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX') . '</li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="contacts">' . t('Go to Your Contacts Page') . '</a><br />' . t('Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.') . '</li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="directory">' . t("Go to Your Site's Directory") . '</a><br />' . t('The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.') . '</li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="contacts">' . t('Finding New People') . '</a><br />' . t("On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.") . '</li>' . EOL;

	$o .= '</ul>';

	$o .= '<h4>' . t('Groups') . '</h4>';

	$o .= '<ul>';

	$o .= '<li>' . '<a target="newmember" href="contacts">' . t('Group Your Contacts') . '</a><br />' . t('Once you have made some friends, organize them into private conversation groups from the sidebar of your Contacts page and then you can interact with each group privately on your Network page.') . '</li>' . EOL;

	if(get_config('system', 'newuser_private')) {
		$o .= '<li>' . '<a target="newmember" href="help/Groups-and-Privacy">' . t("Why Aren't My Posts Public?") . '</a><br />' . t("Friendica respects your privacy. By default, your posts will only show up to people you've added as friends. For more information, see the help section from the link above.") . '</li>' . EOL;
	}

	$o .= '</ul>';

	$o .= '<h4>' . t('Getting Help') . '</h4>';

	$o .= '<ul>';

	$o .= '<li>' . '<a target="newmember" href="help">' . t('Go to the Help Section') . '</a><br />' . t('Our <strong>help</strong> pages may be consulted for detail on other program features and resources.') . '</li>' . EOL;

	$o .= '</ul>';

	$o .= '</div>';

	return $o;
}
