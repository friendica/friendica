Docker
=====

* [Home](help)


Auto-Install
---

With the autoinstall function of Friendica, you can automatically create Docker environments, where some of the important configs (f.e the db credentials) are saved inside your volume beside your Friendica data.  

### Basics

Docker automatically creates writable [containers](https://docs.docker.com/get-started/part2/) for Friendica, which means that the data doesn't persist when that container no longer exists.
Therefore, you have to manage your data with the help of [mountpoints](https://docs.docker.com/storage/).

The goal of the auto-install feature is to use the configuration file stored inside your mountpoint to install Friendica, so if you delete the container (or lose your Docker environment because of failures) you won't have to start the install from scratch.   

###1. Preparation

You have to create a `autoconfig.php` file inside the `config/` directory of your Friendica installation.

The auto-installer is activated by the following parameter in your `autoconfig.php`:

````
   $autoconfig_enabled = true;
````

The following parameters are currently supported:
- db_host
- db_user
- db_pass
- db_data
- phppath (optional)
- TZ (optional)
- language (optional)
- adminmail (optional)
- rino (optional)

There is an example file at `util/autoconfig.php`.
It uses environment variables for the initial loading. 

###2. Installation

Go to your Friendica website for the first time. (It will take some time to check all requirements)

How the installation-process works:

- read the `config/autoconfig.php` file
- deploy the database-schema of Fiendica on the `db_host`
- create the `.htconfig.php` inside the `config/` directory
- delete the `autoconfig.php` file to avoid duplicated usage 