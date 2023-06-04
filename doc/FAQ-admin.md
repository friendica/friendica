Frequently Asked Questions (Admin) - FAQ
==============

* [Home](help)

* **[Can I configure multiple domains with the same code instance?](help/FAQ-admin#multiple)**
* **[Where can I find the source code of friendica, addons and themes?](help/FAQ-admin#sources)**
* **[I've changed the my email address now the admin panel is gone?](help/FAQ-admin#adminaccount1)**
* **[Can there be more then just one admin for a node?](help/FAQ-admin#adminaccount2)**
* **[The Database structure seems not to be updated. What can I do?](help/FAQ-admin#dbupdate)**


<a name="multiple"></a>
### Can I configure multiple domains with the same code instance?

No, this function is no longer supported as of Friendica 3.3 onwards.

<a name="sources"></a>
### Where can I find the source code of friendica, addons and themes?

You can find the main repository [here](https://github.com/friendica/friendica).
There you will always find the current stable version of friendica.

Addons are listed at [this page](https://github.com/friendica/friendica-addons).

If you are searching for new themes, you can find them at [github.com/bkil/friendica-themes](https://github.com/bkil/friendica-themes)

<a name="adminaccount1"></a>
### I've changed my email address now the admin panel is gone?

Have a look into your <tt>config/local.config.php</tt> and fix your email address there.

<a name="adminaccount2"></a>
### Can there be more then one admin for a node?

Yes.
You just have to list more then one email address in the
<tt>config/local.config.php</tt> file.
The listed emails need to be separated by a comma.

<a name="dbupdate">
### The Database structure seems not to be updated. What can I do?

Please have a look at the Admin panel under [DB updates](/admin/dbsync/) and follow the link to *check database structure*.
This will start a background process to check if the structure is up to the current definition.

You can manually execute the structure update from the CLI in the base directory of your Friendica installation by running the following command:

    bin/console dbstructure update

<<<<<<< HEAD
if there occur any errors, please contact the [support forum](https://forum.friendi.ca/profile/helpers).
=======
if there occur any errors, please contact the [support group](https://forum.friendi.ca/profile/helpers).
>>>>>>> 483cc45712a9a3e299f6c2265e3f1ea7e763cfd2
