如何开始帮助改进 Friendica

你想帮助我们改进 Friendica 吗？
这里我们整理了一些提示，告诉你如何入手以及可以选择哪些任务去帮助我们。像 Friendica 这样的项目，需要许多不同类型的贡献。制作好的软件需要很多不同的技能，其中并不是所有都需要编程！
我们在各个领域都需要帮手，无论你是写文档或是开发代码、宣传推广或是设计新图标。
无论自己是高手还是初学者，欢迎你加入我们，带来你的想法和创意！

## 联系我们

Friendica 发展的讨论发生在以下 Friendica 论坛：

* [主要 Friendica 开发论坛](https://forum.friendi.ca/profile/developers)

## 帮助其他用户

还记得你第一次使用 Friendica 时遇到的问题吗？帮助新用户适应 Friendica 是一个不错的起点，在[通用支持论坛](https://forum.friendi.ca/profile/helpers)中提供帮助并引导他们，回答他们的问题，指向文档，或者如果你认为你知道谁可以帮助他们，也可以直接联系其他帮手。

## 翻译

文档中提供了有关如何在 [Transifex](/help/translations) 上翻译 Friendica 的帮助，这里可以翻译用户界面。
如果你不想翻译用户界面，或者已经达到了你的满意度，你可能会想要着手翻译 /help 文件？

## 设计

你擅长设计吗？
如果你看过 Friendica，你可能有改进它的想法，是吧？

* 如果您想在增强用户接口方面与我们合作，请加入[Friendica开发论坛](https://forum.friendi.ca/profile/developers)。
* 制定更好的 Friendica 接口设计计划并与我们分享。
* 告诉我们是否能够实现你的想法，或者需要什么样的帮助，虽然我们不能保证这个群组拥有正确的技能，但我们将尝试。
* 选择一个事项先开始，例如改进你最喜欢主题的图标集。

## 编程

Friendica 使用 [Domain-Driven-Design](help/Developer-Domain-Driven-Design) 的实现，请务必查看所提供的链接以获取期望的代码架构提示。

### Composer

Friendica 使用 [Composer](https://getcomposer.org) 管理依赖库和类自动加载器，它用于库和命名空间 Friendica 类。

它是一个命令行工具，可以将必需的库下载到 `vendor` 文件夹，并使 `src` 中的任何命名空间类在整个应用程序中可用。

如果你想让 git 使用 composer 自动更新依赖关系，你可以使用 `post-merge` [git-hook](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks)，使用类似下面的脚本：

    #!/usr/bin/env bash
    # MIT © Sindre Sorhus - sindresorhus.com
    # forked by Gianluca Guarini
    # phponly by Ivo Bathke ;)
    # modified for Friendica by Tobias Diekershoff
    changed_files="$(git diff-tree -r --name-only --no-commit-id ORIG_HEAD HEAD)"
    check_run() {
		    echo "$changed_files" | grep --quiet "$1" && eval "$2"
    }
    # `composer install` if the `composer.lock` file gets changed
    # to update all the php dependencies
    check_run composer.lock "bin/composer.phar install --no-dev"

将它放置到 `.git/hooks/post-merge` 中并使其可执行即可。

* [类自动加载器](help/autoloader)
* [使用 Composer](help/Composer)
* [如何将类移至`src`](help/Developer-How-To-Move-Classes-to-src)

### 代码规范

为了保持在贡献和代码可读性之间的一致性，Friendica 遵循广泛采用的 [PSR-2 编码标准](http://www.php-fig.org/psr/psr-2/)，但有少数例外规则。

以下是一些 Friendica 和 PSR-2 编码标准的基础知识：

* 缩进使用制表符，而不是 PSR-2 中的空格。
* 默认情况下，字符串使用单引号括起来，但如果更合理的话，可以使用双引号（如 SQL 查询，添加制表符和换行符）。
* 运算符用空格包裹，例如 `$var === true`、`$var = 1 + 2` 和 `'string' . $concat . 'enation'`。
* 在条件语句中，大括号是必须的。
* PHP 条件中的布尔运算符为 `&&` 和 `||`，SQL 查询中的布尔运算符为 `AND` 和 `OR`。
* 没有 PHP 结尾标签。
* 没有尾随空格。
* 数组声明使用新的方括号语法。
* 引用样式默认为单引号，但必要的字符串插值、SQL 查询字符串按照约定和以自然语言保留的注释除外。

不用担心，你不需要完全记住 PSR-2 编码标准就能开始为 Friendica 做贡献。
在提交之前，有一些工具可用于检查或修复您的文件。

对于 `/doc` 和 `/doc/$lng` 子目录中的 `md` 文件，我们使用 *每行一句* 的标准进行文档编写。

#### 使用 [PHP Code Sniffer](https://github.com/squizlabs/PHP_CodeSniffer) 进行检查

此工具检查您的文件是否遵循各种编码标准，包括 PSR-2，并输出所有标准违规情况的报告。
您只需要通过 PEAR 安装它：`pear install PHP_CodeSniffer`。
一旦安装并在您的 PATH 中可用，下面是在提交工作之前运行的命令：

	$> phpcs --standard=ruleset.xml <file or directory>

输出是一个列表，其中包含您应该在提交工作之前修复的所有编码标准违规情况。
此外，`phpcs` 与一些 IDE（Eclipse、Netbeans、PHPStorm 等）集成，这样您就无需使用命令行。

#### 使用 PHP Code Sniffer 中包含的 PHP Code Beautifier and Fixer (phpbcf) 进行修复

如果运行 `phpcs` 时出现了大量标准违规情况，手动修复所有违规情况可能会很麻烦。
幸运的是，PHP Code Sniffer 包含了一个自动代码修复程序，可以为您处理这个单调乏味的任务。
下面是自动修复您创建/修改的文件的命令：

	$> phpcbf --standard=ruleset.xml <file or directory>

如果您无法使用命令行工具 `diff` 和 `patch`，则 `phpcbf` 可以使用略慢的 PHP 替代品，方法是使用 `--no-patch` 参数。

### 代码文档

如果您想在代码文件之外拥有 Friendica 代码的文档，可以使用 [Doxygen](http://doxygen.org) 来生成它。
Doxygen 的配置文件位于项目源代码的基本目录中。
运行以下命令：

	$> doxygen Doxyfile

即可在 Friendica 目录下的 `doc/html` 子目录中生成文件。
您可以使用任何浏览器浏览这些文件。

如果发现缺少文档，请联系我们并编写它以增强代码文档。

### 问题

请查看我们在 github 上的 [issue tracker](https://github.com/friendica/friendica)！

* 尝试复现需要更多调查的错误，并记录您发现的内容。
* 如果一个错误看起来已经被解决，请要求错误报告者提供反馈，以确定是否可以关闭该错误。
* 如果您能够修复错误，请提交拉取请求至存储库的 *develop* 分支。
* 我们有一个 *[初学者任务](https://github.com/friendica/friendica/issues?q=is%3Aopen+is%3Aissue+label%3A"Junior+Jobs")* 标签，表示这些问题可能是一个很好的起点。
    但您不必将自己限制在那些问题上。

### 网页界面

很多人最想要的是一个更好的界面，最好是一个响应式的 Friendica 主题。
这是一项艰巨的工作！
如果您想要参与：

* 查看已经完成的第一步 (例如，清除主题)。
    询问我们，以了解与他们的经验交流的人物。
* 如果您认识任何设计人员，请与他们交谈。
* 让我们知道您的计划，在 [dev 论坛](https://forum.friendi.ca/profile/developers) 上发布。
    不用担心重复发布。

### 客户端软件

由于 Friendica 使用一个 [Twitter/GNU Social 兼容的 API](help/api)，所以任何适用于这些平台的客户端都应该能够与 Friendica 兼容。
此外，还有几个客户端项目，专门用于 Friendica 的使用。
如果您有兴趣改进这些客户端，请直接联系客户端的开发人员。

* Android / LinageOS：**Friendiqa** [src](https://git.friendi.ca/lubuwest/Friendiqa)/[Google Play](https://play.google.com/store/apps/details?id=org.qtproject.friendiqa)，由 [Marco R](https://freunde.ma-nic.de/profile/marco) 开发。
* iOS：*目前没有客户端*
* SailfishOS：**Friendiy** [src](https://kirgroup.com/projects/fabrixxm/harbour-friendly)，由 [Fabio](https://kirgroup.com/profile/fabrixxm/profile) 开发。
* Windows：**Friendica Mobile** 适用于 [8.1 之前的 Windows 版本](http://windowsphone.com/s?appid=e3257730-c9cf-4935-9620-5261e3505c67) 和 [Windows 10](https://www.microsoft.com/store/apps/9nblggh0fhmn)，由 [Gerhard Seeber](http://mozartweg.dyndns.org/friendica/profile/gerhard/profile) 开发。
