Vagrant for Friendica Developers
===================

* [Home](help)

Getting started
---------------

[Vagrant](https://www.vagrantup.com/) is a virtualization solution for developers.
No need to setup up a webserver, database etc. before actually starting.
Vagrant creates a virtual machine for you that you can just run inside VirtualBox and start to work directly on Friendica.

It brings an Ubuntu Xenial (16.04) with PHP 7.0 and MySQL 5.7.16

What you need to do:

1. Install VirtualBox and vagrant.
Please use an up-to-date vagrant version from https://www.vagrantup.com/downloads.html.
2. Git clone your Friendica repository.
Inside, you'll find a "Vagrantfile" and some scripts in the utils folder.
3. Run "vagrant up" from inside the friendica clone:
        $> vagrant up
Be patient: When it runs for the first time, it downloads an Ubuntu Server image.
4. Run "vagrant ssh" to log into the virtual machine to log in to the VM:
        $> vagrant ssh
5. Open you test installation in a browser.
Go to 192.168.22.10.
The mysql database is called "friendica", the mysql user and password both are "friendica".
6. Work on Friendica's code in your git clone on your machine (not in the VM).
Your local working directory is set up as a shared directory with the VM (/vagrant).
7. Check the changes in your browser in the VM.
Debug via the "vagrant ssh" login.
Find the Friendica log file /vagrant/logfile.out.
8. Commit and push your changes directly back to Github.

If you want to stop vagrant after finishing your work, run the following command

		$> vagrant halt

in the development directory.
This will not delete the virtual machine.
9. To ultimately delete the virtual machine run

        $> vagrant destroy
        $> rm /vagrant/.htconfig.php

to make sure that you can start from scratch with another "vagrant up".

The vagrant Friendica instance contains a test database.
You will then have the following accounts to login:

  * admin, password admin
  * friendica1, password friendica1
  * friendica2, password friendica2 and so on until friendica5
  * friendica1 is connected to all others. friendica1 has two groups: group1 with friendica2 and friendica4, group2 with friendica3 and friendica5.
  * friendica2 and friendica3 are conntected. friendica4 and friendica5 are connected. 

For further documentation of vagrant, please see [the vagrant*docs*](https://docs.vagrantup.com/v2/).
