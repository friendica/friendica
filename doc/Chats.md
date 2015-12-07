Chats
=====

* [Home](help)

There are two possibilities to use a chat on your friendica site

* IRC Chat
* Jappix

IRC-Chat Plugin
---

After activating the plugin, you can find the chat at [yoursite.com/irc](../irc).
Note: you can use this chat without any login at your site so that everyone could use it.

If you follow the link, you will see the login page of the IR chat.
Now choose a nickname and a chatroom.
You can choose every name for the room, even something like #superchatwhosenameisonlyknownbyme.
At last, solve the captchas and click the connect button.

The following window shows some text while connecting.
This text isn't importend for you, just wait for the next window.
The first line shows your name and your current IP address.
The right part of the window shows all users.
The lower part of the window contains an input field.

Jappix Mini
---

The Jappix Mini Plugin creates a chatbox for jabber- and XMPP-contacts.
You should already have a jabber/XMPP-account before setting up the plugin.
You can find more information at [jabber.org](http://www.jabber.org/).

You can use several servers to create an account:

* [https://jappix.com](https://jappix.com)
* [http://xmpp.net](http://xmpp.net)

###1. Basics

At first you have to get the current version. You can either pull it from [Github](https://github.com) like so:

    $> cd /var/www/virtual/YOURSPACE/html/addon; git pull

Or you can download a tar archive here: [jappixmini.tgz](https://github.com/friendica/friendica-addons/blob/master/jappixmini.tgz) (click at „view raw“).

Just unpack the file and rename the directory to „jappixmini“.
Next, upload this directory and the .tgz-file into your addon directory of your friendica installation.

Now you can activate the plugin globally on the admin pages.
In the plugin sidebar, you will find an entry of jappix now (where you can also find twitter, GNU Social and others).
The following page shows the settings of this plugin.

Activate the BOSH proxy.

###2. Settings

Go to your user account settings next and choose the plugin page.
Scroll down until you find the Jappix Mini addon settings.

At first you have to activate the addon.

Now add your Jabber/XMPP name, the domain/server (without "http"; just "jappix.com").
For „Jabber BOSH Host“ you could use "https://bind.jappix.com/".
Note that you need another BOSH server if you do not use jappix.com for your XMPP account.
You can find further information in the „Configuration Help“-section below this fields.
At last you have enter your password (there are some more optional options, you can choose).
Finish these steps with "send" to save the entries.
Now, you should find the chatbox at the lower right corner of your browser window.

If you want to add contacts manually, you can click "add contact".
