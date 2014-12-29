Friendica on Github
===================

* [Home](help)

**Here is how you can work on the code with us**

1. Install git on the system you will be developing on.
2. Create your own [github](https://github.com) account.
3. Fork the Friendica repository from [https://github.com/friendica/friendica.git](https://github.com/friendica/friendica.git).
4. Clone your fork from your Github account to your machine. Follow the instructions provided here: [http://help.github.com/fork-a-repo/](http://help.github.com/fork-a-repo/) to create and use your own tracking fork on github
5. Commit your changes to your fork. Then go to your github page and create a "Pull request" to notify us to merge your work.

**Branches**

There are two branches in the main repo on Github:

1. master: This branch contains stable releases only.
2. develop: This branch contains the latest code. This is what you want to work with.

**Important**

Please pull in any changes from the project repository and merge them with your work **before** issuing a pull request. We reserve the right to reject any patch which results in a large number of merge conflicts. This is especially true in the case of language translations - where we may not be able to understand the subtle differences between conflicting versions.

Also - **test your changes**. Don't assume that a simple fix won't break something else. If possible get an experienced Friendica developer to review the code. 

**Vagrant**

[Vagrant](https://www.vagrantup.com/) is a virtualization solution for developers. No need to setup up a webserver etc. before actually starting. Vagrant creates a virtual machine (an Ubuntu 12.04) for you that you can just run inside VirtualBox and start to work directly on Friendica. What you need to do:

1. Install VirtualBox and vagrant.
2. git clone Friendica (note the Vagrantfile inside).
3. Run vagrant up, have some patience.
4. Run vagrant ssh to log into the virtual machine.
5. It depends on the network setup of your host and virtual box guest how you reach the friendica web interface of the VM.

