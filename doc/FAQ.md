Frequently Asked Questions - FAQ
==============

* [Home](help)

* **[Where I can find help?](help/FAQ#help)**
* **[Why do I get warnings about certificates?](help/FAQ#ssl)**
* **[How can I upload images, files, links, videos and sound files to posts?](help/FAQ#upload)**
* **[Is it possible to have different avatars per profile?](help/FAQ#avatars)**
* **[How can I view Friendica in a certain language?](help/FAQ#language)**
* **[How do blocked, ignored, archived and hidden contacts behave?](help/FAQ#contacts)**
* **[What happens when an account is removed? Is it truly deleted?](help/FAQ#removed)**
* **[Can I subscribe to a hashtag?](help/FAQ#hashtag)**
* **[How to create an RSS feed of the stream?](help/FAQ#rss)**
* **[What friendica clients can I use?](help/FAQ#clients)**


<a name="help"></a>

### Where I can find help?

If this FAQ does not answer your question you can always reach out to the community via the following options:

  * Friendica Support Group: [@helpers@forum.friendi.ca](https://forum.friendi.ca/~helpers)
  * Community chat rooms (the IRC, Matrix and XMPP rooms are bridged) these public chats are logged [from IRC](https://gnusociarg.nsupdate.info/2021/%23friendica/) and [Matrix](https://view.matrix.org/alias/%23friendi.ca:matrix.org/)
    * XMPP: support(at)forum.friendi.ca
    * IRC: #friendica at [libera.chat](https://web.libera.chat/?channels=#friendica)
    * Matrix: [#friendica-en:matrix.org](https://matrix.to/#/#friendica-en:matrix.org) or [#friendi.ca:matrix.org](https://matrix.to/#/#friendi.ca:matrix.org)
  * [Mailing List](http://mailman.friendi.ca/mailman/listinfo/support-friendi.ca)
  <!--- * [XMPP](xmpp:support@forum.friendi.ca?join)
	https://github.com/github/markup/issues/202
	https://github.com/gjtorikian/html-pipeline/pull/307
	https://github.com/github/opensource.guide/pull/807
  --->

<a name="ssl"></a>
### Why do I get warnings about SSL certificates?

SSL (Secure Socket Layer) is a technology to encrypt data transfer between computers.
Sometimes your browser warns you about a missing or invalid certificate.
These warnings can have three reasons:

1. The server you are connected to doesn't offer SSL encryption.
2. The server has a self-signed certificate (not recommended).
3. The certificate is expired.

We recommend to talk to the admin(s) of the affected friendica server. (Admins, please see the respective section of the [admin manual](help/SSL).)

<a name="upload"></a>
### How can I upload images, files, links, videos and sound files to posts?

You can upload images from your computer using the [editor](help/Text_editor).
An overview of all uploaded images is listed at *yourpage.com/profile/profilename/photos*.
On that page, you can also upload images directly and choose if your contacts will receive a message about this upload.

Generally, you can attach any kind of file to a post.
This is possible by using the "paper-clip"-symbol in the editor.
These files will be linked to your post and can be downloaded by your contacts.
But it's not possible to get a preview for these items.
Because of this, this upload method is only recommended for office or zipped files.
If you want to share content from Dropbox, Owncloud or any other [filehoster](http://en.wikipedia.org/wiki/Comparison_of_file_hosting_services), use the "link"-button (chain-symbol).

When you're adding URLs of other webpages with the "link"-button, Friendica tries to create a small preview.
If this doesn't work, try to add the link by typing: [url=http://example.com]*self-chosen name*[/url].

You can also add video and audio files to posts.
However, instead of a direct upload you have to use one of the following methods:

1. Add the video or audio link of a hoster (YouTube, Vimeo, Soundcloud and anyone else with oembed/opengraph-support). Videos will be shown with a preview image you can click on to start. SoundCloud directly inserts a player to your post.

2. If you have your own server, you can upload multimedia files via FTP and insert the URL.

Friendica uses HTML5 for embedding content.
Therefore, the supported files are dependent on your browser and operating system.
Some supported file types are WebM, MP4, MP3 and OGG.
See Wikipedia for more of them ([video](http://en.wikipedia.org/wiki/HTML5_video), [audio](http://en.wikipedia.org/wiki/HTML5_audio)).

<a name="avatars"></a>
### Is it possible to have different avatars per profile?

Yes.
On your Edit/Manage Profiles page, you will find a "change profile photo" link.
Clicking this will take you to a page where you can upload a photograph and select which profile it will be associated with.
To avoid privacy leakage, we only display the photograph associated with your default profile as the avatar in your posts.

<a name="language"></a>
### How can I view Friendica in a certain language?

You can do this by adding the `lang` parameter to the url in your url bar.
The data in the parameter is a [ISO 639-1](https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes) code.
A question mark is required for the separation between url and parameters.

Example:

    https://social.example.com/profile/example

in German:

    https://social.example.com/profile/example?lang=de.

When a certain language is forced, the language remains until session is closed.

<a name="contacts"></a>
### How do blocked, ignored, archived and hidden contacts behave?

These are various categories of contacts that are restricted in some way.
Many of these types are related to [Safety](help/Safety).

##### Blocked

Direct communication will be blocked.
Blocked contacts are not included in delivery, and their own posts to you are not imported.
However, their conversations with your friends will still be visible in your stream.
If you remove a contact completely, they can send you another friend request.
Blocked contacts cannot do this. They cannot communicate with you directly, only through friends.

##### Ignored

Ignored contacts are included in delivery and will receive your posts and private messages.
However, we do not import their posts or private messages to you.
Like blocking, you will still see this person's comments to posts made by your friends.

An addon called "blockem" can be installed to collapse/hide all posts from a particular person in your stream if you desire complete blocking of an individual, including their conversations with your other friends.

##### Archived

Communication is not possible and will not be attempted.
However, unlike blocking, existing posts this person made before being archived will be visible in your stream.

##### Hidden

The contact not be displayed in your public friend list.
However, a hidden contact will appear normally in conversations and this may expose their hidden status to anybody who can see the conversation.

<a name="removed"></a>
### What happens when an account is removed?

If you remove your account, it will be scheduled for permanent deletion in *seven days*.
As soon as you activate the deletion process you won't be able to log in anymore.
Only the administrator of your node can halt this process prior to permanent deletion.

After the elapsed time of seven days, all your posts, messages, photos, and personal information stored on your node will be deleted.
Your node will also issue removal requests to all your contacts; this will also remove your profile from the global directory if you are listed.
Your username cannot be reissued for future sign-ups for security reasons.

<a name="hashtag"></a>
### Can I follow a hashtag?

Yes. Simply add the hashtag to your saved searches.
The posts will appear on your network page.
For technical reasons, your answers to such posts won't appear on the "personal" tab in the network page and the whole thread isn't accessible via the API.

<a name="rss"></a>
### How to create an RSS feed of the stream?

If you want to share your public page via rss you can use one of the following links:

#### RSS feed of your posts

	basic-url.com//feed/[nickname]/posts

Example: Friendica Support

	https://forum.friendi.ca/feed/helpers/posts

#### RSS feed of the conversations at your site

	basic-url.com/feed/profilename/comments

Example: Friendica Support

	https://forum.friendi.ca/feed/helpers/comments

<a name="clients"></a>
### What friendica clients can I use?

Friendica supports [Mastodon API](help/API-Mastodon) and [Twitter API | gnusocial](help/api).
This means you can use some of the Mastodon and Twitter clients for Friendica.
The available features are client specific and may differ.
Clients dedicated to Friendica are marked in **bold**.

#### Android

* [AndStatus](http://andstatus.org) ([F-Droid](https://f-droid.org/repository/browse/?fdid=org.andstatus.app), [Google Play](https://play.google.com/store/apps/details?id=org.andstatus.app))
* [Fedilab](https://fedilab.app) ([F-Droid](https://f-droid.org/app/fr.gouv.etalab.mastodon), [Google Play](https://play.google.com/store/apps/details?id=app.fedilab.android))
* **[Friendiqa](https://git.friendi.ca/lubuwest/Friendiqa)** ([F-Droid](https://git.friendi.ca/lubuwest/Friendiqa#install), [Google Play](https://play.google.com/store/apps/details?id=org.qtproject.friendiqa))
* [Husky](https://codeberg.org/husky/husky) ([F-Droid](https://f-droid.org/repository/browse/?fdid=su.xash.husky), [Google Play](https://play.google.com/store/apps/details?id=su.xash.husky))
* [Mastodon](https://github.com/mastodon/mastodon-android) ([F-Droid](https://f-droid.org/en/packages/org.joinmastodon.android/), [Google Play](https://play.google.com/store/apps/details?id=org.joinmastodon.android))
* [Pachli](https://pachli.app/) ([F-Droid](https://f-droid.org/en/packages/app.pachli/), [Google Play](https://play.google.com/store/apps/details?id=app.pachli))
* **[Raccoon for Friendica](https://github.com/LiveFastEatTrashRaccoon/RaccoonForFriendica)** ([F-Droid](https://f-droid.org/packages/com.livefast.eattrash.raccoonforfriendica),  [Google Play](https://play.google.com/apps/testing/com.livefast.eattrash.raccoonforfriendica))
* **[Relatica](https://gitlab.com/mysocialportal/relatica)**
* [Subway Tooter](https://github.com/tateisu/SubwayTooter) ([F-Droid via Izzy](https://android.izzysoft.de/repo/apk/jp.juggler.subwaytooter.noFcm))
* [Tooot](https://tooot.app/) ([Google Play](https://play.google.com/store/apps/details?id=com.xmflsct.app.tooot))
* [Tusky](https://tusky.app) ([F-Droid](https://f-droid.org/repository/browse/?fdid=com.keylesspalace.tusky), [Google Play](https://play.google.com/store/apps/details?id=com.keylesspalace.tusky))
* [TwidereX](https://github.com/TwidereProject/TwidereX-Android) ([F-Droid](https://f-droid.org/en/packages/com.twidere.twiderex/), [Google Play](https://play.google.com/store/apps/details?id=com.twidere.twiderex))
* [Yuito](https://github.com/accelforce/Yuito) ([Google Play](https://play.google.com/store/apps/details?id=net.accelf.yuito))

#### iOS

* [Mastodon](https://joinmastodon.org/apps) ([App Store](https://apps.apple.com/us/app/mastodon-for-iphone/id1571998974))
* **[Relatica](https://gitlab.com/mysocialportal/relatica)**
* [Stella*](https://www.stella-app.net/) ([App Store](https://apps.apple.com/us/app/stella-for-mastodon-twitter/id921372048))
* [Tooot](https://github.com/tooot-app) ([App Store](https://apps.apple.com/app/id1549772269))
* [TwidereX](https://github.com/TwidereProject/TwidereX-iOS) ([App Store](https://apps.apple.com/app/twidere-x/id1530314034))

#### Linux

* [Choqok](https://choqok.kde.org)
* **[Relatica](https://gitlab.com/mysocialportal/relatica)**
* [TheDesk](https://thedesk.top/en/) ([GitHub](https://github.com/cutls/TheDesk))
* [Toot](https://toot.readthedocs.io/en/latest/)
* [Whalebird](https://whalebird.social/en/desktop/contents) ([GitHub](https://github.com/h3poteto/whalebird-desktop))

#### macOS

* **[Relatica](https://gitlab.com/mysocialportal/relatica)**
* [TheDesk](https://thedesk.top/en/) ([GitHub](https://github.com/cutls/TheDesk))
* [Whalebird](https://whalebird.social/en/desktop/contents) ([App Store](https://apps.apple.com/de/app/whalebird/id1378283354), [GitHub](https://github.com/h3poteto/whalebird-desktop))

#### Windows

* **[Relatica](https://gitlab.com/mysocialportal/relatica)**
* [TheDesk](https://thedesk.top/en/) ([GitHub](https://github.com/cutls/TheDesk))
* [Whalebird](https://whalebird.social/en/desktop/contents) ([Microsoft Store](https://apps.microsoft.com/detail/9nbw4csdv5hc), [GitHub](https://github.com/h3poteto/whalebird-desktop))

#### Web Frontend

* [Halcyon](https://www.halcyon.social/)
* [Pinafore](https://github.com/nolanlawson/pinafore)
