Tags and Mentions
=================


* [Home](help)


Like many other modern social networks, Friendica uses a special notation inside messages to indicate "tags" or contextual links to other entities.

**Mentions**

People are tagged by preceding their name with the @ character.

The following are various ways of indicating a person:

* @mike - indicates a known contact in your social circle whose nickname is "mike"
* @mike_macgirvin - indicates a known contact in your social circle whose  full name is "Mike Macgirvin". Note that spaces cannot be used inside tags.
* @mike+151 - this form is used by the drop-down tag completion tool. It indicates the contact whose nickname is mike and whose contact identifier number is 151. The drop-down tool may be used to resolve people with duplicate nicknames. 
* @mike@macgirvin.com - indicates the Identity Address of a person on a different network, or one that is *not* in your social circle. This is called a "remote mention" and can only be an email-style locator, not a web URL.

Unless their system blocks unsolicited "mentions", the person tagged will likely receive a "Mention" post/activity or become a direct participant in the conversation in the case of public posts. Please note that Friendica blocks incoming "mentions" from people with no relationship to you. This is a spam prevention measure.

Remote mentions are delivered using the OStatus protocol. This protocol is used by Friendica and StatusNet and several other systems, but is not currently implemented in Diaspora. 

Friendica makes no distinction between people and groups for the purpose of tagging. (Some other networks use !group to indicate a group.)

**Topical Tags**

Topical tags are indicated by preceding the tag name with the  # character. This will create a link in the post to a generalised site search for the term provided. For example, #cars will provide a search link for all posts mentioning 'cars' on your site. Topical tags are generally a minimum of three characters in length.  Shorter search terms are not likely to yield any search results, although this depends on the database configuration. The same rules apply as with names that spaces within tags are represented by the underscore character. It is therefore not possible to create a tag whose target contains an underscore.

Topical tags are also not linked if they are purely numeric, e.g. #1. If you wish to use a numerica hashtag, please add some descriptive text such as #2012-elections. 
 


 

