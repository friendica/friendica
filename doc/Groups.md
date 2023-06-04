Groups
=====

* [Home](help)


Friendica also lets you create accounts that can function as discussion groups, celebrity accounts, announcement channels, news reflectors, or organization pages, depending on how you want to interact with others.
Management of these accounts can be delegated to other accounts, or a parent account can be designated to easily toggle multiple identities.

Every account in Friendica has a nickname and these must all be unique.
This applies to all accounts, whether they are individual profiles or group profiles.

Managing Accounts
---

To create a new linked account that can be used as a group, log in to your normal account and go to Settings > Manage Accounts.
Here you can register additional accounts with new nicknames that will be linked to your primary account.

You may appoint a delegate to manage your new account.
The Delegates section of Manage Accounts page will provide you with a list of contacts on this instance under "Potential Delegates".
Selecting one or more persons will give them access to manage your newly created account.
They will be able to edit contacts, profiles, and all content for this account.
Please use this facility wisely.
Delegated managers will not be able to alter basic account settings, such as passwords or account types, or remove the account.

Additionally, this page is also where you can choose to designate an account as a parent user.
If your primary account is designated as the parent user, you will be able to easily toggle identities and manage your groups or other types of accounts.

Types of Accounts
---

On the new account, visit the Settings > Account page.
Towards the end of the page is a section for "Advanced account types".
Typically, you would use "Personal Page - Standard" for a normal personal account with manual approval of “friends” and “followers.”
This is the default selection.
On this page you can change the type of account if desired.

The other subtypes of a Personal Page are “Soapbox” and “Love-all.”
A Soapbox account is an announcement channel that automatically approvals follower requests.
Everything posted by the account will go out to the followers, but there will be no opportunity for interaction.
This setting would typically be used for announcements or corporate communications.
“Love-all” automatically approves contacts as friends.

In addition to Personal Page, there are options for Organization Page, News Page, and Community Group.
Organization and New Pages automatically approve contact requests as followers.

Community Group provide the ability for people to join the group without requiring approval.
This creates a group where all members can freely interact.

Posting to Community groups
---

If you are a member of a community group, you may post to the group by including an @-mention in the post mentioning the group.
For example @bicycle would send my post to all members of the group "bicycle" in addition to the normal recipients.
If you mention a group (you are a member of) in a new posting, the posting will be distributed to all members of the group, regardless of your privacy settings for the posting.
Also, if the group is public, your posting will be public for the all internet users.
If your post is private you must also explicitly include the group in the post permissions (to allow the group "contact" to see the post) **and** mention it in a tag (which redistributes the post to the group members).
Posting privately to a public group, will result in your posting being displayed on the group wall, but not on yours.

Additionally, it is possible to address a group with the exclamation mark.
In the example above this means that you can address the bicycle group via !bicycle.
The difference with the @-mention is that the post will only be sent to the addressed group.
This also means that you shouldn't address multiple groups in a single post in that way since it will only be distributed by one the groups.

You may also post to a community group by posting a "wall-to-wall" post using secure cross-site authentication.

Comments which are relayed to community groups will be relayed back to the original post creator.
Mentioning the group with an @-mention in a comment does not relay the message, as distribution is controlled entirely by the original post creator.
