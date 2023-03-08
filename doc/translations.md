Friendica translations
======================

* [Home](help)

## Overview

The Friendica translation process is based on `gettext` PO files.

Basic worflow:
1. `xgettext` is used to collect translation strings across the project in the authoritative PO file located in `view/lang/C/messages.po`.
2. This file makes translations strings available at [the Transifex Friendica page](https://www.transifex.com/Friendica/friendica/dashboard/).
3. The translation itself is done at Transifex by volunteers.
4. The resulting PO files by languages are manually updated in `view/lang/<language>/messages.po`.
5. PO files are converted to PHP arrays in `view/lang/<language>/strings.php` that are ultimately used by Friendica to display the translations.

## Translate Friendica in your favorite language

Thank you for your interest in improving Friendica's translation!
Please register a free Transifex account and ask over at [the Transifex Friendica page](https://www.transifex.com/Friendica/friendica/dashboard/) to join the translation team for your favorite language.

As a rule of thumb, we add support for a language in Friendica when at least 50% of the strings have been translated to avoid a scattered experience.
For addons, we add support for a language when if we already support the language in Friendica.

## Add new translation strings

### Supported gettext version

We currently support the gettext version 0.19.8.1 and actively check new translation strings with this version.

If you don't use this version, it's possible that our checks fail (f.e. because of tiny differences at linebreaks).
In case you do have a Docker environment, you can easily update the translations with the following command:
```shell
docker run --rm -v $PWD:/data -w /data friendicaci/transifex bin/run_xgettext.sh
```

### Core

Once you have added new translation strings in your code changes, please run `bin/run_xgettext.sh` from the base Friendica directory and commit the updated `view/lang/C/messages.po` to your branch.

### Addon

If you have the `friendica-addons` repository in the `addon` directory of your Friendica cloned repository, just run `bin/run_xgettext.sh -a <addon_name>` from the base Friendica directory.

Otherwise:

	cd /path/to/friendica-addons/<addon_name>
	/path/to/friendica/bin/run_xgettext.sh -s

In either case, you need to commit the updated `<addon_name>/lang/C/messages.po` to your working branch.

## Update translations from Transifex

Please download the Transifex file "for use" in `view/lang/<language>/messages.po`.

Then run `bin/console po2php view/lang/<language>/messages.po` to update the related `strings.php` file and commit both files to your working branch.

### Using the Transifex client

Transifex has a client program which allows you to sync files between your cloned Friendica repository and Transifex.
Help for the client can be found at the [Transifex Help Center](https://docs.transifex.com/client/introduction).
Here we will only cover basic usage.

After installation of the client, you should have a `tx` command available on your system.
To use it, first create a configuration file with your credentials.
On Linux this file should be placed into your home directory `~/.transifexrc`.
The content of the file should be something like the following:

    [https://www.transifex.com]
    username = user
    token =
    password = p@ssw0rd
    hostname = https://www.transifex.com

Since Friendica version 3.5.1 we ship configuration files for the Transifex client in the core repository and the addon repository in `.tx/config`.
To update the PO files after you have translated strings of e.g. Esperanto on the Transifex website you can use `tx` to download the updated PO file in the right location.

    $> tx pull -l eo

Then run `bin/console po2php view/lang/<language>/messages.po` to update the related `strings.php` file and commit both files to your working branch.

## Translation functions usage

### Basic usage

- `Friendica\DI::l10n()->t('Label')` => `Label`
- `Friendica\DI::l10n()->t('Label %s', 'test')` => `Label test`

### Plural

- `Friendica\DI::l10n()->tt('Label', 'Labels', 1)` => `Label`
- `Friendica\DI::l10n()->tt('Label', 'Labels', 3)` => `Labels`
- `Friendica\DI::l10n()->tt('%d Label', '%d Labels', 1)` => `1 Label`
- `Friendica\DI::l10n()->tt('%d Label', '%d Labels', 3)` => `3 Labels`
- `Friendica\DI::l10n()->tt('%d Label', 'Labels %2%s %3%s', 1, 'test', 'test2')` => `Label test test2`
- `Friendica\DI::l10n()->tt('%d Label', 'Labels %2%s %3%s', 3, 'test', 'test2')` => `Labels test test2`
