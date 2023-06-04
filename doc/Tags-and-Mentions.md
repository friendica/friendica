Tags and Mentions
=================


* [Home](help)


Like many other modern social networks, Friendica uses a special notation inside messages to indicate "tags" or contextual links to other entities.

**Mentions**

People are tagged by preceding their name with the @ character.

You can tag **persons who are in your social circle** by adding the "@"-sign in front of the name.

* @mike - indicates a known contact in your social circle whose nickname is "mike"
* @mike_macgirvin - indicates a known contact in your social circle whose  full name is "Mike Macgirvin". Note that spaces cannot be used inside tags.
* @mike+151 - this form is used by the drop-down tag completion tool. It indicates the contact whose nickname is mike and whose contact identifier number is 151. The drop-down tool may be used to resolve people with duplicate nicknames.

You can tag a person on a different network or one that is **not in your social circle** by using the following notation:

* @mike@macgirvin.com - This is called a "remote mention" and can only be an email-style locator, not a web URL.

Unless their system blocks unsolicited "mentions", the person tagged will likely receive a "Mention" post/activity or become a direct participant in the conversation in the case of public posts.
Friendica blocks incoming “mentions” from people with no relationship to you.
The exception is an ongoing conversation started from a contact of both you and the 3rd person or a conversation in a group where you are a member of.
This is a spam prevention measure.

Remote mentions are delivered using the OStatus protocol.
This protocol is used by Friendica and GNU Social and several other systems like Mastodon, but is not currently implemented in Diaspora.
As the OStatus protocol allows this Friendica user can be @-mentioned by users from platforms using this protocol in conversations if the "Enable OStatus support" is activated on the Friendica node.
These @-mentions won't be blocked, even if there is no relationship between the sender and the receiver of the message.

Friendica makes no distinction between people and groups for the purpose of tagging.
You can use @-mentions for groups like for other accounts to tag the group.
If you want to post something exclusively to a group (e.g. the support group) please use the !-mention instead of  the @-mention.
So !helpers will be an exclusive posting to the support group if you are connected with the group.
If you select a group from the ACL a !-mention will be added automatically to your posting.

If you sort your contacts into circles, you cannot @-mention these circles.
But you can select the circle in the access control when creating a new posting, to allow (or disallow) a certain circle of people to see the posting.
See [Circles and Privacy](help/Circles-and-Privacy) for more details about grouping your contacts.

**Topical Tags**

Topical tags are indicated by preceding the tag name with the # character.
This will create a link in the post to a generalised site search for the term provided.
For example, #cars will provide a search link for all posts mentioning 'cars' on your site.
Topical tags are generally a minimum of three characters in length.
Shorter search terms are not likely to yield any search results, although this depends on the database configuration.
The same rules apply as with names that spaces within tags are represented by the underscore character.
It is therefore not possible to create a tag whose target contains an underscore.

Topical tags are also not linked if they are purely numeric, e.g. #1.
If you wish to use a numeric hashtag, please add some descriptive text such as #2012-elections.

