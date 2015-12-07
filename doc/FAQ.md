Frequently Asked Questions - FAQ
==============

* [Home](help)

User

* **[Why do I getting warnings about certificates?](help/FAQ#ssl)**
* **[How can I upload images, files, links, videos and sound files to posts?](help/FAQ#upload)**
* **[Is it possible to have different avatars per profile?](help/FAQ#avatars)**
* **[What is the difference between blocked|ignored|archived|hidden contacts?](help/FAQ#contacts)**
* **[What happens when an account is removed? Is it truly deleted?](help/FAQ#removed)**
* **[Can I subscribe to a hashtag?](help/FAQ#hashtag)**
* **[How to create a RSS feed of the stream?](help/FAQ#rss)**
* **[Are there any clients for friendica I can use?](help/FAQ#clients)**
* **[Where I can find help?](help/FAQ#help)**

Admins

* **[Can I configure multiple domains with the same code instance?](help/FAQ#multiple)**
* **[Where can I find the source code of friendica, addons and themes?](help/FAQ#sources)**
* **[I've changed the my email address now the admin panel is gone?](help/FAQ#adminaccount1)**
* **[Can there be more then just one admin for a node?](help/FAQ#adminaccount2)**

User
--------
*****
<a name="ssl"></a>

###Why do I get warnings about SSL certificates?

SSL (Secure Socket Layer) is a technology to encrypt data transfer between computers.
Sometimes your browser warns you about a missing or invalid certificate.
These warnings can have three reasons: 

1. The server you are connected to doesn't offer SSL encryption.
2. The server has a self-signed certificate (not recommended).
3. The certificate is expired.

We recommend to talk to the admin(s) of the affected friendica server. (Admins, please see the respective section of the [admin manual](help/SSL).)

<a name="upload"></a>

###How can I upload images, files, links, videos and sound files to posts?

You can upload images from your computer by using the [editor](help/Text_editor).
An overview of all uploaded images is listed at *yourpage.com/photos/profilename*.
On that page, you can also upload images directly and choose, if your contacts shall receive a message about this upload.

Generally, you could attach every kind of file to a post.
This is possible by using the "paper-clip"-symbol in the editor.
These files will be linked to your post and can be downloaded by your contacts.
But it's not possible to get a preview for these ones.
Because of this, this upload method is recommended for office or zipped files. 
If you want share content from Dropbox, Owncloud or any other [filehoster](http://en.wikipedia.org/wiki/Comparison_of_file_hosting_services), use the "link"-button (chain-symbol). 

When you're adding URLs of other webpages with the "link"-button, Friendica tries to create a small preview.
If this doesn't work, try to add the link by typing: [url=http://example.com]*self-chosen name*[/url].

You can also add video and audio files to posts.
But instead of a direct upload you have to use one of the following methods:

1. Add the video or audio link of a hoster (Youtube, Vimeo, Soundcloud and everyone else with oembed/opengraph-support). Videos will be shown with a preview image you can click on to start it. SoundCloud directly inserts a player to your post. 

2. If you have your own server, you can upload multimedia files via FTP and insert the URL. 

Friendica is using HTML5 for embedding content.
Therefore, the supported files are depending on your browser and operating system.
Some supported filetypes are WebM, MP4, MP3 and OGG.
See Wikipedia for more of them ([video](http://en.wikipedia.org/wiki/HTML5_video), [audio](http://en.wikipedia.org/wiki/HTML5_audio)).

<a name="avatars"></a>

###Is it possible to have different avatars per profile?

Yes. On your Edit/Manage Profiles page, you will find a "change profile photo" link.
Clicking this will take you to a page where you can upload a photograph and select which profile it will be associated with.
To avoid privacy leakage, we only display the photograph associated with your default profile as the avatar in your posts.

<a name="contacts"></a>

###What is the difference between blocked|ignored|archived|hidden contacts?

We prevent direct communication with **blocked contacts**.
They are not included in delivery, and their own posts to you are not imported.
However their conversations with your friends will still be visible in your stream.
If you remove a contact completely, they can send you another friend request.
Blocked contacts cannot do this. They cannot communicate with you directly, only through friends.

**Ignored contacts** are included in delivery - they will receive your posts and private messages.
However we do not import their posts or private messages to you.
Like blocking, you will still see this person's comments to posts made by your friends.

A plugin called "blockem" can be installed to collapse/hide all posts from a particular person in your stream if you desire complete blocking of an individual, including his/her conversations with your other friends.

An **archived contact** means that communication is not possible and will not be attempted.
(Perhaps the person moved to a new site and removed the old profile.)
However unlike blocking, existing posts this person made before being archived will be visible in your stream.

A **hidden contact** will not be displayed in any "friend list" (except to you).
However a hidden contact will appear normally in conversations and this may expose his/her hidden status to anybody who can see the conversation.

<a name="removed"></a>

###What happens when an account is removed? Is it truly deleted?

If you delete your account, we will immediately remove all your content on **your** server.

Then Friendica issues requests to all your contacts to remove you.
This will also remove you from the global directory.
Doing this requires your account and profile still to be "partially" available for up to 24 hours in order to establish contact with all your friends.
We can block it in several ways so that it appears empty and all profile information erased, but will then wait for 24 hours (or after all of your contacts have been notified) before we can physically remove it.

After that, your account is deleted.

<a name="hashtag"></a>

###Can I follow a hashtag?

No. The act of 'following' a hashtags is an interesting technology, but presents a few issues.

1. Posts would have to be copied to all sites on the network that are "listening" to that hashtag. This would increase the storage demands to the detriment of small sites. It would make the use of shared hosting practically impossible.

2. Making spam easy (tag spam is a serious issue on Twitter for instance)

3. It creates a natural bias towards large sites which hold more tagged content - if your network uses tagging instead of other conversation federation mechanisms such as groups/forums.

Instead, we offer other mechanisms for wide-area conversations while retaining a 'level playing ground' for both large and small sites, such as forums and community pages and shared tags.

<a name="rss"></a>

###How to create a RSS feed of the stream?

If you want to share your public page via rss you can use one of the following links:

RSS feed of your posts

	basic-url.com/**dfrn_poll/profilename  

	Example: Friendica Support 
	
	https://helpers.pyxis.uberspace.de/dfrn_poll/helpers

RSS feed of the conversations at your site

	basic-url.com/dfrn_poll/profilename/converse
	
	Example: Friendica Support 
	
	https://helpers.pyxis.uberspace.de/dfrn_poll/helpers/converse

<a name="clients"></a>

###Are there any clients for friendica I can use?

Friendica is using a [Twitter/GNU Social compatible API](help/api), which means you can use any Twitter/GNU Social client for your plattform as long as you can change the API path in its settings.
Here is a list of known working clients:

* Android
  * Friendica Client for Android
  * AndStatus
  * Twidere
  * Mustard and Mustard-Mod
* Linux
  * Hotot
  * Choqok
* MacOS X
  * Hotot
* Windows
  * Hotot

Depending on the features of the client you might encounter some glitches in usability, like being limited in the length of your postings to 140 characters and having no access to the [permission settings](help/Groups-and-Privacy).

<a name="help"></a>

###Where I can find help?

If you have problems with your Friendica page, you can ask the community at the [Friendica Support Group](https://helpers.pyxis.uberspace.de/profile/helpers).
If you can't use your default profile you can either use a test account [test server](http://friendica.com/node/31) respectively an account at a public site [list](http://dir.friendica.com/siteinfo) or you can use the Librelist mailing list.
If you want to use the mailing list, please just send a mail to friendica AT librelist DOT com.

If you are a theme developer, you will find help at this forum: [Friendica Theme Developers](https://friendica.eu/profile/ftdevs).

Admin
--------
*****
<a name="multiple"></a>

###Can I configure multiple domains with the same code instance?

No, this function is not supported anymore starting from Friendica 3.3.

<a name="sources"></a>

###Where can I find the source code of friendica, addons and themes?

You can find the main respository [here](https://github.com/friendica/friendica).
There you will always find the current stable version of friendica.

Addons are listed at [this page](https://github.com/friendica/friendica-addons).

If you are searching for new themes, you can find them at [Friendica-Themes.com](http://friendica-themes.com/) 

<a name="adminaccount1"></a>
###I've changed the my email address now the admin panel is gone?

Have a look into your <tt>.htconfig.php</tt> and fix your email address there.

<a name="adminaccount2"></a>
###Can there be more then just one admin for a node?

Yes. You just have to list more then one email address in the
<tt>.htconfig.php</tt> file. The listed emails need to be separated by a comma.
