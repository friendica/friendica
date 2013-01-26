Frequently Asked Questions - FAQ
==============

* [Home](help)

User

* **[Why do I getting warnings about certificates?](help/FAQ#ssl)**
* **[Is it possible to have different avatars per profile?](help/FAQ#avatars)**
* **[What is the difference between blocked|ignored|archived|hidden contacts?](help/FAQ#contacts)**
* **[What happens when an account is removed? Is it truly deleted?](help/FAQ#removed)**
* **[Can I subscribe to a hashtag?](help/FAQ#hashtag)**
* **[How to create a RSS feed of the stream?](help/FAQ#rss)**
* **[Where I can find help?](help/FAQ#help)**

Admins

* **[Can I configure multiple domains with the same code instance?](help/FAQ#multiple)**
* **[Where can I find the source code of friendica, addons and themes?](help/FAQ#sources)**

User
--------
*****
<a name="ssl"></a>

**Why do I getting warnings about certificates?**

Sometimes you get a browser warning about a missing certificate. These warnings can have three reasons: 

1. the server you are connected to doesn't have SSL; 

2. the server has a self-signed certificate (not recommended)

3. the certificate is expired. 

*(SSL (Secure Socket Layer) is a technology to encrypt data as it passes between two computers).* 

If you dont have a SSL cert yet, there are three ways to get one: buy one, get a free one (eg. via StartSSL) or create your own (not recommended). [You can find more information about setting up SSL and why it's a bad idea to use self-signed SSL here.](help/SSL) 

Be aware that a browser warning about security issues is something that can make new users feel insecure about the whole friendica project. 
Because of this, Friendica Red will only accept SSL certs signed by a recognized CA and doesn't connect to servers without these kind of SSL. Despite of the negative aspects of SSL, this is a necessary solution until there is an established alternative for this technique.

Also you can have problems with the connection to diaspora because some pods require a SSL-certificated connection. 

If you are just using friendica for a specified group of people on a single server without a connection to the rest of the friendica network, there is no need to use SSL. If you exclusively use public posts, there is also no need for it. 

If you havn't set up a server yet, it's wise to compare the different provider and their SSL conditions. Some allow the usage of free certificates or give you the access to their certificate for free. Other ones only allow bought certificates from themselves or other providers.

<a name="avatars"></a>

**Is it possible to have different avatars per profile?**

Yes. On your Edit/Manage Profiles page, you will find a "change profile photo" link. Clicking this will take you to a page where you can upload a photograph and select which profile it will be associated with. To avoid privacy leakage, we only display the photograph associated with your default profile as the avatar in your posts.

<a name="contacts"></a>

**What is the difference between blocked|ignored|archived|hidden contacts?**

We prevent direct communication with blocked contacts. They are not included in delivery, and their own posts to you are not imported; however their conversations with your friends will still be visible in your stream. If you remove a contact completely, they can send you another friend request. Blocked contacts cannot do this. They cannot communicate with you directly, only through friends.

Ignored contacts are included in delivery - they will receive your posts. However we do not import their posts to you. Like blocking, you will still see this person's comments to posts made by your friends.

[A plugin called "blockem" can be installed to collapse/hide all posts from a particular person in your stream if you desire complete blocking of an individual, including his/her conversations with your other friends.]

An archived contact means that communication is not possible and will not be attempted (perhaps the person moved to a new site and removed the old profile); however unlike blocking, existing posts this person made before being archived will be visible in your stream.

A hidden contact will not be displayed in any "friend list" (except to you). However a hidden contact will appear normally in conversations and this may expose his/her hidden status to anybody who can see the conversation.

<a name="removed"></a>

**What happens when an account is removed? Is it truly deleted?**

If you delete your account, we will immediately remove all your content on your server, and then issue requests to all your contacts to remove you. This will also remove you from the global directory. Doing this requires that your account and profile still be "partially" available for up to 24 hours in order to establish contact with all your friends. We can block it in several ways so that it appears empty and all profile information erased, but will then wait for 24 hours (or after all of your contacts have been notified) before we can physically remove it.

<a name="hashtag"></a>

**Can I follow a hashtag?**

No. The act of 'following' a hashtags is an interesting technology, but presents a few issues.

1.) Posts which have to be copied to all sites on the network that are "listening" to that tag, which increases the storage demands to the detriment of small sites, and making the use of shared hosting practically impossible, and

2.) Making spam easy (tag spam is quite a serious issue on identi.ca for instance)

but mostly

3.) It creates a natural bias towards large sites which hold more tagged content - if your network uses tagging instead of other conversation federation mechanisms such as groups/forums.

Instead, we offer other mechanisms for wide-area conversations while retaining a 'level playing ground' for both large and small sites, such as forums and community pages and shared tags.

<a name="rss"></a>

**How to create a RSS feed of the stream?**

If you want to share your public page via rss you can use one of the following links:

RSS feed of your posts

	basic-url.com/**dfrn_poll/profilename  

	Example: Friendica Support 
	
	https://helpers.pyxis.uberspace.de/dfrn_poll/helpers

RSS feed of the conversations at your site

	basic-url.com/dfrn_poll/profilename/converse
	
	Example: Friendica Support 
	
	https://helpers.pyxis.uberspace.de/dfrn_poll/helpers/converse

<a name="help"></a>

**Where I can find help?**

If you have problems with your Friendica page, you can ask the community at the [Friendica Support Group](https://helpers.pyxis.uberspace.de/profile/helpers). If you can't use your default profile you can either use a test account [test server](http://friendica.com/node/31) respectively an account at a public site [list](http://dir.friendica.com/siteinfo) or you can use the Librelist mailing list. If you want to use the mailing list, please just send a mail to friendica AT librelist DOT com.

If you are using Friendica Red, you will also find help at this forum: [Friendica Red Development](https://myfriendica.net/profile/friendicared).

If you are a theme developer, you will find help at this forum: [Friendica Theme Developers](https://friendica.eu/profile/ftdevs).

Admin
--------
*****
<a name="multiple"></a>

**Can I configure multiple domains with the same code instance?**

You can do that. What you can't do is point two different domains at the same database. As long as .htconfig.php exists to keep it from trying to do an install, you can keep the real config in include/$hostname/.htconfig.php All of the cache and lock stuff can be configured per instance.

<a name="sources"></a>

**Where can I find the source code of friendica, addons and themes?**

You can find the main respository [here](https://github.com/friendica/friendica). There you will always find the current stable version of friendica. The source files of Friendica Red are [here](https://github.com/friendica/red).

Addons are listed at [this page](https://github.com/friendica/friendica-addons).

If you are searching for new themes, you can find them at [Friendica-Themes.com](http://friendica-themes.com/) 