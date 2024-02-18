Channels
=====

* [Home](help)

Channels are a way to discover new content or to display content that you might have missed otherwise.
There are several predefined channels, additionally you can create your own channels, based on some rules.
Channels only display posts from the last 24 hours (this value can be changed by the admin).

In the display settings in the section "Timelines" you can define which channels and other timelines you want to see in the "Channels" widget on the network page and which channels should appear in the menu bar at the top of the page.

Also in the display settings in the section "Channels" you can define all the languages that you want to see in your channels. Here you can select more than one language.

On the contact page you can define the channel frequency for every contact. The options are:

* Default frequency: Posts by this contact are displayed in the "for you" channel if you interact often with this contact or if a post reached some level of interaction.
* Display all posts of this contact: All posts from this contact will appear on the "for you" channel.
* Display only few posts: When a contact creates a lot of posts in a short period, this setting reduces the number of displayed posts in every channel.
* Never display posts: Posts from this contact will never be displayed in any channel.

Predefined Channels
---

* For you: Posts from contacts you interact with and who interact with you. In detail, it consists of:
    * Posts from people you interact with on a more than average level.
    * Posts from the accounts that you follow with a more than average number of interactions-
    * Posts from accounts where you activated "notify on new posts" or where you have set the channel frequency accordingly.
* Discover: Posts from contacts you don't follow, but that might be of interest for you to follow. In detail, it consists of:
    * Posts from people you don't follow but you interact with on a more than average level.
    * Posts from people you don't follow but that interact with you on a more than average level.
    * Popular posts from people you don't follow but you interacted with or who interacted with you on any level.
* What's Hot: Posts with a more than average number of interactions.
* Language: Posts in your language.
* Followers: Posts from your followers that you don't follow.
* Sharers of sharers: Posts from accounts that are followed by accounts that you follow.
* Quiet sharers: Posts from accounts that you follow but who don't post very often.
* Images: Posts with images.
* Audio: Posts with audio.
* Videos: Posts with videos.

User defined Channels
---

In the "Channels" settings you can create your own channels.

Each channel is defined by these values:

* Label: This value is mandatory and is used for the menu label.
* Description: A short description of the content. This can help to keep the overview, when you have got a lot of channels.
* Access Key: When you want to access this channel via an access key, you can define it here. Pay attention to not use an already used one.
* Circle: This defines the data source for this channel. By default it is set to the public timeline. There are some predefined values, like the accounts that you follow or the accounts that follow you. Also all of your circles can be selected. 
* Include Tags: Comma separated list of tags. A post will be used when it contains any of the listed tags.
* Exclude Tags: Comma separated list of tags. If a post contain any of these tags, then it will not be part of nthis channel.
* Full Text Search: This can be used to include or exclude content, based on the content and some additional keywords. It uses the "boolean mode" operators from MariaDB: https://mariadb.com/kb/en/full-text-index-overview/#in-boolean-mode
* Images, Videos, Audio: When selected, you will see content with the selected media type. This can be combined. If none of these fields are checked, you will see any content, with or without attacked media.

Additional keywords for the full text search
---

Additionally to the search for content, there are keywords that can be used in the full text search.
Alternatives are presented with "|".

* from - Use "from:nickname" or "from:nickname@domain.tld" to search for posts from a specific author.
* to - Use "from:nickname" or "from:nickname@domain.tld" to search for posts with the given contact as receiver.
* group - Use "group:nickname" or "group:nickname@domain.tld" to search for group post of the given group.
* application | relay - Use "application:nickname" or "application:nickname@domain.tld" to search for posts that had been reshared by the given relay application.
* server - Use "server:hostname" to search for posts from a specific server. In the case of group postings, the search text contains both the hostname of the group server and the author's hostname.
* source - The ActivityPub type of the post source. Use this for example to include or exclude group posts or posts from services (aka bots).
    * source:person - The post is created by a regular user account.
    * source:organization - The post is created by an organisation.
    * source:group - The post is created by or distributed via a group.
    * source:service | source:news - The posts originates from a service account. This source type is often used to mark bot accounts.
    * source:application | source:relay - The post is created by an application. This is most likely unused in the fediverse for post creation.
* tag - Use "tag:tagname" to search for a specific tag.
* media - With this keyword you can search for attached media.
    * media:image | media:photo | media:picture - The post contains an image
    * media:video - The post contains a video
    * media:audio - The post contains audio
    * media:card - The post contains a link preview card
    * media:post - The post links another post, means it is a quoted post
* network | net - Use this to include or exclude some networks from your channel.
    * network:apub | network:activitypub - ActivityPub (Used by the systems in the Fediverse)
    * network:dfrn | network:friendica - Legacy Friendica protocol. Nowayday Friendica mostly uses ActivityPub.
    * network:dspr | network:diaspora - The Diaspora protocol is mainly used by Diaspora itself. Some other systems support the protocol as well like Hubzilla, Socialhome or Ganggo.
    * network:feed - RSS/Atom feeds
    * network:mail - Mails that had been imported via IMAP.
    * network:stat | network:ostatus - The OStatus protocol is mainly used by old GNU Social installations.
    * network:dscs | network:discourse - Posts that are received by the Discourse connector.
    * network:tmbl | network:tumblr - Posts that are received by the Tumblr connector.
    * network:bsky | network:bluesky - Posts that are received by the Bluesky connector.
* platform - Use this to include or exclude some platforms from your channel, e.g. "+platform:friendica". In the case of group postings, the search text contains both the platform of the group server and the author's platform.
* visibility - You have the choice between different visibilities. You can only see unlisted or private posts that you have the access for.
    * visibility:public
    * visibility:unlisted
    * visibility:private
* language | lang - Use "language:code" to search for posts with the given language in the [ISO 639-1](https://en.wikipedia.org/wiki/ISO_639-1) format. 

Remember that you can combine these kerywords.
So for example you can create a channel with all posts that talk about the Fediverse - that aren't posted in the Fediverse with the search terms: "fediverse -network:apub -network:dfrn"