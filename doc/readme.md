# About the docs of the Friendica Project

**Note**: It is expected that some of the links in these files won't work in the Friendica repository as they are supposed to work on an installed Friendica node.

## User and Admin documentation

Every Friendica node has the _current_ version of the user and admin documentation available in the `/help` location.

The documentation is mainly done in English, but the pages can be translated and some are already to German.

We greatly appreciate help in keeping Friendica well documented, with information that's up to date, so contributions are very welcome.
The documentation lives in the core Friendica repository which, for now, [still lives on GitHub](https://github.com/friendica/friendica/).

To contribute currently, you will need to make an account on GitHub, and if you're a developer or otherwise technically savvy you can create a `pull request` with your suggested changes
You can make your changes using GitHub's builtin text editors, or locally.
Alternatively you can create an `issue` with your suggested changes, and someone else can make a `pull request` based on your suggestions.

Images that you use in the documentation should be located in the `img` sub-directory of this directory.
Translations are located in sub-directories named after the language codes, e.g. `de`.
Depending on the selected interface language the different translations will be applied, or the `en` original will be used as a fall-back.

## Developer documentation

We provide a configuration file for [Doxygen](https://www.doxygen.nl/index.html) in the root of the Friendica repository.
With that you should be able to extract some documentation from the source code.

In addition there are some documentation files about the database structure under the `database` subdirectory.
