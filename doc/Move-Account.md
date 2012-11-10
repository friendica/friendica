Move Account
============

* [Home](help)


! **this is an experimental feature**

** How to move an account between servers **

Go to "Settings" -> "[Export personal data](uexport)"
Click on "Export account" to save your account data.
This file contains your details, your contacts, groups, and personal settings.
It contains also your secret keys to authenticate yourself to your contacts:
**save this file in a secure place**!

Go to your new server, and open *http://newserver.com/uimport* (there is not a 
direct link to this page at the moment).

Load your saved account file and click "Import".

Friendica will recreate your account on new server, with your contacts and groups.
A message is sent to Friendica contacts, to inform them about your move: if your
contacts are runnning on an updated server, automatically your details on their
side will be updated.
Contacts on Statusnet/Identi.ca or Diaspora will be archived, as we can't inform
them about your move.
You should ask them to remove your contact from their lists and readd you, and you
should do the same with their contact.

After the move, the account on the old server will not work reliably anymore, and
should be not used.

