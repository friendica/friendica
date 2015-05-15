Friendica on Github
===================

* [Home](help)

Here is how you can work on the code with us. If you have any questions please write to the Friendica developers' forum.

Introduction to the workflow with our Github repository
-------------------------------------------------------

1. Install git on the system you will be developing on.
2. Create your own [github](https://github.com) account.
3. Fork the Friendica repository from [https://github.com/friendica/friendica.git](https://github.com/friendica/friendica.git).
4. Clone your fork from your Github account to your machine.
Follow the instructions provided here: [http://help.github.com/fork-a-repo/](http://help.github.com/fork-a-repo/) to create and use your own tracking fork on github
5. Commit your changes to your fork.
Then go to your github page and create a "Pull request" to notify us to merge your work.

Our Git Branches
----------------

There are two relevant branches in the main repo on Github:

1. master: This branch contains stable releases only.
2. develop: This branch contains the latest code.
This is what you want to work with.

Fast-forwarding
---------------

Fast forwarding is enabled by default in git.
When you merge with fast-forwarding it does not add a new commit to mark when you've performed the merge and how.
This means in your commit history you can't know exactly what happened in terms of merges.
**It's best to turn off fast-forwarding.**
This is done by running "git merge --no-ff".
[Here](https://stackoverflow.com/questions/5519007/how-do-i-make-git-merges-default-be-no-ff-no-commit) is an explanation on how to configure git to turn off fast-forwarding by default.
You can find some more background reading [here](http://nvie.com/posts/a-successful-git-branching-model/).

Release branches
----------------

A release branch is created when the develop branch contains all features it should have.
A release branch is used for a few things.

1. It allows last-minute bug fixing before the release goes to master branch.
2. It allows meta-data changes (README, CHANGELOG, etc.) for version bumps and documentation changes.
3. It makes sure the develop branch can receive new features that are **not** part of this release.

That last point is important because...
**The moment a release branch is created, develop is now intended for the version after this release**.
So please don't ever merge develop into a release!
An example: If a release branch "release-3.4" is created, "develop" becomes either 3.5 or 4.0.
If you were to merge develop into release-3.4 at this point, features and bug-fixes intended for 3.5 or 4.0 might leak into this release branch.
This might introduce new bugs, too.
Which defeats the purpose of the release branch.

Some important reminders
------------------------

1. Please pull in any changes from the project repository and merge them with your work **before** issuing a pull request.
We reserve the right to reject any patch which results in a large number of merge conflicts.
This is especially true in the case of language translations - where we may not be able to understand the subtle differences between conflicting versions.

2. **Test your changes**.
Don't assume that a simple fix won't break anything else.
If possible get an experienced Friendica developer to review the code.
Don't hesitate to ask us in case of doubt.

Check out how to work with [our Vagrant](help/Vagrant) to save a lot of setup time!

