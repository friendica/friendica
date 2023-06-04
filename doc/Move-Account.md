How to move your account between servers
============

* [Home](help)


! **This is an experimental feature**

* Go to "Settings" -> "[Export personal data](uexport)"
* Click on "Export account" to save your account data.
* **Save the file in a secure place!** It contains your details, your contacts, circles, and personal settings. It also contains your secret keys to authenticate yourself to your contacts.
* Go to your new server, and open *http://newserver.com/user/import* (there is not a direct link to this page at the moment). Please consider that this is only possible on servers with open registration. On other systems only the administrator can add accounts with an uploaded file.
* Do NOT create a new account prior to importing your old settings - user import should be used *instead* of register.
* Load your saved account file and click "Import".
* After the move, the account on the old server will not work reliably anymore, and should be not used.


Friendica contacts
---
Friendica will recreate your account on the new server, with your contacts and circles.
A message is sent to Friendica contacts, to inform them about your move:
If your contacts are running on an updated server, your details on their side will be automatically updated.

GNU Social contacts
---
Contacts on GNU Social will be archived, as we can't inform them about your move.
You should ask them to remove your contact from their lists and re-add you, and you should do the same with their contact.

Diaspora contacts
---
Newer Diaspora servers are able to process "account migration" messages.
