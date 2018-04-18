Docker
=====

* [Home](help)


Auto-Install
---

With the autoinstall function of friendice, you can automatically create docker environments, where some of the important configs (f.e the db credentials) are saved inside your volume beside your friendica data.  

### Basics

Docker automatically creates writable [containers](https://docs.docker.com/get-started/part2/) for friendica, which means that the data doesn't persist when that container no longer exists.
Therefor, you have to manage your data with the help of [mountpoints](https://docs.docker.com/storage/).

The goal of the auto-install feature is that the configuration of your data is stored inside your mountpoint, so if you delete the container (or lost your docker environment because of failures) you don't have to worry about your configuration. It is saved with your data.   

###1. Preparation

You have to create a `autoconfig.php` file inside the `config/` directory of your friendica installation.

The auto-installer is activated by the following parameter in your `autconfig.php`:

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

There is an example file at `util/autoconfig.php`. It uses environment variables for the initial loading. 

###2. Installation

Go to your friendica website at the first time. (It will take some time to check all requirements)

How the installation-process works:

- read the `config/autoconfig.php` file
- deploy the database-schema of friendica on the `db_host`
- create the `.htconfig.php` inside the `config/` directory
- delete the `autoconfig.php` file to avoid duplicated usage 