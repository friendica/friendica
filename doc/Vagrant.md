Vagrant for Friendica Developers
===================

* [Home](help)

Getting started
---------------

[Vagrant](https://www.vagrantup.com/) is a virtualization solution for developers.
No need to setup up a webserver, database etc. before actually starting.
Vagrant creates a virtual machine for you that you can just run inside VirtualBox and start to work directly on Friendica.

It brings an Debian Bullseye with PHP 8.0 and MariaDB 10.5.11.

What you need to do:

1. Install VirtualBox and vagrant.
Please use an up-to-date vagrant version from https://www.vagrantup.com/downloads.html.
2. Git clone your Friendica repository.
Inside, you'll find a `Vagrantfile` and some scripts in the `bin/dev` folder.
Pull the PHP requirements with `bin/composer.phar install`.
3. Run `vagrant up` from inside the friendica clone.
This will start the virtual machine.
Be patient: When it runs for the first time, it downloads a Debian Server image and installs Friendica.
4. Run `vagrant ssh` to log into the virtual machine to log in to the VM in case you need to debug something on the server.
5. Open you test installation in a browser.
Go to friendica.local (or 192.168.56.10).
friendica.local is using a self-signed TLS certificate, so you will need to add an exception to trust the certificate the first time you are visiting the page.
The mysql database is called "friendica", the mysql user and password both are "friendica".
6. Work on Friendica's code in your git clone on your machine (not in the VM).
Your local working directory is set up as a shared directory with the VM (/vagrant).
7. Check the changes in your browser in the VM.
Find the Friendica log file `/vagrant/logfile.out` on the VM or in the `logfile.out` in you local Friendica directory.
8. Commit and push your changes directly back to GitHub.

If you want to stop vagrant after finishing your work, run the following command

		$> vagrant halt

in the development directory.
This will not delete the virtual machine.
9. To ultimately delete the virtual machine run

        $> vagrant destroy
        $> rm /vagrant/config/local.config.php

to make sure that you can start from scratch with another "vagrant up".

Default User Accounts
---------------------

By default the provision script will setup two user accounts.

  * admin, password admin
  * friendica, password friendica

Trouble Shooting
----------------

If you see a version mis-match for the _VirtualBox Guest Additions_ between host and guest during the initial setup of the Vagrant VM, you will need to install an addon to Vagrant (ref. [Stack Overflow](https://stackoverflow.com/a/38010683)).
Stop the Vagrant VM and run the following command:

	$> vagrant plugin install vagrant-vbguest

On the next Vagrant up, the version problem should be fixed.

If `friendica.local` is not resolved, you may need to add an entry to the `/etc/hosts` file (or similar configuration depending on the OS you are using).

For further documentation of vagrant, please see [the vagrant*docs*](https://docs.vagrantup.com/v2/).
