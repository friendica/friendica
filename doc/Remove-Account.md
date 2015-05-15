Remove Account
==============

* [Home](help)

We don't like to see people leave Friendica, but if you need to remove your account, you should visit the URL

http://sitename/removeme

with your web browser.
You will need to be logged in at the time.

You will be asked for your password to confirm the request.
If this matches your stored password, your account will immediately be blocked to all probing.
Unlike some social networks we do **not** hold onto it for a grace period in case you change your mind.
All your content and user data, etc is instantly removed. For all intents and purposes, the account is gone in moments.  

We then send out an "unfriend" signal to all of your contacts.
This signal deletes all content on those networks.
Unfortunately, due to limitations of the other networks, this only works well with Friendica contacts.
We allow four days for this, in case some servers were down and the unfriend signal was queued.
After this, we finish off deleting the account.
