Friendica mods files
====================

## `bookmarklet-share2friendica`

Browser bookmarklet to share any page with your Friendica account.
Please see `bookmarklet-share2friendica/README.md` for detailed instruction. 

## `fpostit`

Node-agnostic Friendica bookmarklet by Devlon Duthie.
Unmaintained and unsupported. 

## `home.css` and `home.html`

Example files to customize the landing page of your Friendica node.
The `home.html` file contains the text of the page, the `home.css` file the style information.
The login box will be added according to the other system settings.
Both files have to be placed in the base directory of your Friendica installation to be used for the landing page.

## `local.config.vagrant.php`

Configuration file used by the Vagrant setup script.

## `sample-Lighttpd.config` and `sample-nginx.config`

Sample configuration files to use Friendica with Lighttpd or Nginx.
Please check software documentation to know how modify these examples to make them work on your server.

## `sample-systemd.timer` and `sample-systemd.service`

Sample systemd unit files to start worker.php periodically.
		
Please place them in the correct location for your system, typically this is `/etc/systemd/system/friendicaworker.timer` and `/etc/systemd/system/friendicaworker.service`.
Please report problems and improvements to `!helpers@forum.friendi.ca` and `@utzer@social.yl.ms` or open an issue in [the Github Friendica page](https://github.com/friendica/friendica/issues).
This is for usage of systemd instead of cron to start the worker periodically, the solution is a work-in-progress and can surely be improved.

## `release-list.txt`

Contains all directories and files, which are necessary for a Friendica release. 