# Connectors

Connectors allow you to connect with external social networks and services.
They are only required for posting to existing accounts on for example systems that use the AT Protocol (Bluesky, Eurosky, Blacksky), Tumblr or Twitter.
For the AT Protocol and Tumblr you can also enable a bidirectional synchronisation, so that you can use Friendica to read your timeline from Tumblr or an AT Protocol based system.
There is also a connector for accessing your email INBOX.

# Instructions For Connecting To People On Specific Services

## Friendica

You can either connect to others by providing your Identity Address on the 'Connect' page of any Friendica member.
Or you can put their Identity Address into the Connect box on your [Contacts](contact) page.


## Diaspora

Add the Diaspora 'handle' to the 'Connect/Follow' text box on your [Contacts](contact) page.

## Blogger, WordPress, RSS feeds, arbitrary web pages

Put the URL into the Connect box on your [Contacts](contact) page.
PLease note that you will not be able to reply to these contacts.

This feed reader feature will allow you to _connect_ with millions of pages on the internet.
All that the pages need to have is a discoverable feed using either the RSS or Atom syndication format, and which provides an author name and a site image in a form which we can extract.

## Email

If the php module for IMAP support is available on your server, Friendica can connect to email contacts as well.
Configure the email connector from your [Settings](settings) page.
Once this has been done, you may enter an email address to connect with using the Connect box on your [Contacts](contact) page.
They must be the sender of a message which is currently in your INBOX for the connection to succeed.
You may include email contacts in private conversations.
