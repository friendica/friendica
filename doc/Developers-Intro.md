Where to get started to help improve Friendica?
===============================================

* [Home](help)

Do you want to help us improve Friendica?
Here we have compiled some hints on how to get started and some tasks to help you choose.
A project like Friendica is the sum of many different contributions.
**Very different skills are required to make good software.
Some of them involve coding, others do not.**
We are looking for helpers in all areas, whether you write text or code, whether you spread the word to convince people or design new icons.
Whether you feel like an expert or like a newbie - join us with your ideas!

Contact us
---

The discussion of Friendica development takes place in the following Friendica forums:

* The main [forum for Friendica development](https://helpers.pyxis.uberspace.de/profile/developers)
* The [forum for Friendica theme development](https://friendica.eu/profile/ftdevs)

Help other users
---

Remember the questions you had when you first tried Friendica?
A good place to start can be to help new people find their way around Friendica in the [general support forum](https://helpers.pyxis.uberspace.de/profile/helpers).
Welcome them, answer their questions, point them to documentation or ping other helpers directly if you can't help but think you know who can.

Translation
---

The documentation contains help on how to translate Friendica [at Transifex](/help/translations) where the UI is translated.
If you don't want to translate the UI, or it is already done to your satisfaction, you might want to work on the translation of the /help files?

Design
---

Are you good at designing things?
If you have seen Friendica you probably have ideas to improve it, haven't you?

* If you would like to work with us on enhancing the user interface, please join the [UX Watchdogs forum](https://fc.oscp.info/profile/ux-watchdogs)
* Make plans for a better Friendica interface design and share them with us.
* Tell us if you are able to realize your ideas or what kind of help you need.
We can't promise we have the right skills in the group but we'll try.
* Choose a thing to start with, e.g. work on the icon set of your favorite theme

Programming
---

###Coding standards

For the sake of consistency between contribution and general code readability, Friendica follows the widespread [PSR-2 coding standards](http://www.php-fig.org/psr/psr-2/). Don't worry, you don't have to know by heart the PSR-2 coding standards. Before comitting your work, you can use the PHP Coding Standard Fixer located in the `util` folder this way:

	$> php util/php-cs-fixer.phar fix <file or folder> --level=psr2

Here's a few primers if you are new to the PSR-2 coding standards:
 * Indentation is 4 spaces, period.
 * By default, strings are enclosed in single quotes, but fell free to use double quotes if it makes more sense.
 * Operators are wrapped by spaces, e.g. `$var === true`, `$var = 1 + 2` and `'string' . $concat' . 'enation'`
 * Braces are mandatory in conditions
 * No closing PHP tag
 * No trailing spaces

###Code documentation

If you are interested in having the documentation of the Friendica code outside of the code files, you can use [Doxygen](http://doxygen.org) to generate it.
The configuration file for Doxygen is located in the `util` directory of the project sources.
Run

    $> doxygen util/Doxyfile

to generate the files which will be located in the `doc/html` subdirectory in the Friendica directory.
You can browse these files with any browser.

If you find missing documentation, don't hesitate to contact us and write it down to enhance the code documentation.

###Issues

Have a look at our [issue tracker](https://github.com/friendica/friendica) on github!

 * Try to reproduce a bug that needs more inquiries and write down what you find out.
 * If a bug looks fixed, ask the bug reporters for feedback to find out if the bug can be closed.
 * Fix a bug if you can. Please make the pull request against the *develop* branch of the repository.
 * There is a *Junior Job* label for issues we think might be a good point to start with.
   But you don't have to limit yourself to those issues.

###Web interface

The thing many people want most is a better interface, preferably a responsive Friendica theme.
This is a piece of work!
If you want to get involved here:

* Look at the first steps that were made (e.g. the clean theme).
Ask us to find out whom to talk to about their experiences.
* Talk to design people if you know any.
* Let us know about your plans [in the dev forum](https://helpers.pyxis.uberspace.de/profile/developers) and the [theme developer forum](https://friendica.eu/profile/ftdevs).
Do not worry about cross-posting.

###Client software
As Friendica is using a [Twitter/GNU Social compatible API](help/api) any of the clients for those platforms should work with Friendica as well.
Furthermore there are several client projects, especially for use with Friendica.
If you are interested in improving those clients, please contact the developers of the clients directly.

* Android / CynogenMod: **Friendica for Android** [src](https://github.com/max-weller/friendica-for-android), [homepage](http://friendica.android.max-weller.de/) - abandoned
* iOS: *currently no client*
* SailfishOS: **Friendiy** [src](https://kirgroup.com/projects/fabrixxm/harbour-friendly) - developed by [Fabio](https://kirgroup.com/profile/fabrixxm/?tab=profile)
* Windows: **Friendica Mobile** for Windows versions [before 8.1](http://windowsphone.com/s?appid=e3257730-c9cf-4935-9620-5261e3505c67) and [Windows 10](https://www.microsoft.com/store/apps/9nblggh0fhmn) - developed by [Gerhard Seeber](http://mozartweg.dyndns.org/friendica/profile/gerhard/?tab=profile)
