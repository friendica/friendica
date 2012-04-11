<?php

function newmember_content(&$a) {


	$o = '<h3>' . t('Welcome to Friendica') . '</h3>';

	$o .= '<h3>' . t('New Member Checklist') . '</h3>';

	$o .= '<div style="font-size: 120%;">';

	$o .= t('We would like to offer some tips and links to help make your experience enjoyable. Click any item to visit the relevant page. A link to this page will be visible from your home page for two weeks after your initial registration and then will quietly disappear.');

	$o .= '<ul>';

	$o .= '<li>' . '<a target="newmember" href="help/guide">' . t('On your <em>Quick Start</em> page - find a brief introduction to your profile and network tabs, connect to Facebook, make some new connections, and find some groups to join.') . '</a></li>' . EOL; 

	$o .= '<li>' . '<a target="newmember" href="settings">' . t('On your <em>Settings</em> page -  change your initial password. Also make a note of your Identity Address. This looks just like an email address - and will be useful in making friends on the free social web.') . '</a></li>' . EOL; 

	$o .= '<li>' . '<a target="newmember" href="settings">' . t('Review the other settings, particularly the privacy settings. An unpublished directory listing is like having an unlisted phone number. In general, you should probably publish your listing - unless all of your friends and potential friends know exactly how to find you.') . '</a></li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="profile_photo">' . t('Upload a profile photo if you have not done so already. Studies have shown that people with real photos of themselves are ten times more likely to make friends than people who do not.') . '</a></li>' . EOL;  

	if(in_array('facebook', $a->plugins))
		$o .= '<li>' . '<a target="newmember" href="facebook">' . t("Authorise the Facebook Connector if you currently have a Facebook account and we will \x28optionally\x29 import all your Facebook friends and conversations.") . '</a></li>' . EOL;
	else
		$o .= '<li>' . '<a target="newmember" href="help/Installing-Connectors">' . t("<em>If</em> this is your own personal server, installing the Facebook addon may ease your transition to the free social web.") . '</a></li>' . EOL;

    $mail_disabled = ((function_exists('imap_open') && (! get_config('system','imap_disabled'))) ? 0 : 1);
	
	if(! $mail_disabled)
		$o .= '<li>' . '<a target="newmember" href="settings/connectors">' .  t('Enter your email access information on your Connector Settings page if you wish to import and interact with friends or mailing lists from your email INBOX') . '</a></li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="profiles">' . t('Edit your <strong>default</strong> profile to your liking. Review the settings for hiding your list of friends and hiding the profile from unknown visitors.') . '</a></li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="profiles">' . t('Set some public keywords for your default profile which describe your interests. We may be able to find other people with similar interests and suggest friendships.') . '</a></li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="contacts">' . t('Your Contacts page is your gateway to managing friendships and connecting with friends on other networks. Typically you enter their address or site URL in the <em>Add New Contact</em> dialog.') . '</a></li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="directory">' . t('The Directory page lets you find other people in this network or other federated sites. Look for a <em>Connect</em> or <em>Follow</em> link on their profile page. Provide your own Identity Address if requested.') . '</a></li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="contacts">' . t("On the side panel of the Contacts page are several tools to find new friends. We can match people by interest, look up people by name or interest, and provide suggestions based on network relationships. On a brand new site, friend suggestions will usually begin to be populated within 24 hours.") . '</a></li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="contacts">' . t('Once you have made some friends, organize them into private conversation groups from the sidebar of your Contacts page and then you can interact with each group privately on your Network page.') . '</a></li>' . EOL;

	$o .= '<li>' . '<a target="newmember" href="help">' . t('Our <strong>help</strong> pages may be consulted for detail on other program features and resources.') . '</a></li>' . EOL;

	$o .= '</div>';

	return $o;
}
