# 主题

* [主页](help)

要改变 Friendica 的外观，您需要操作主题。
当前默认的主题是[Vier](https://github.com/friendica/friendica/tree/stable/view/theme/vier)，但有许多其他主题可供选择。
请查看[github.com/bkil/friendica-themes](https://github.com/bkil/friendica-themes)了解现有主题的概述。
如果没有任何一个主题符合您的需求，则有几种方法可以更改主题。

那么，如何处理 Friendica 的用户界面呢？

您可以直接编辑现有的主题。
但是，当 Friendica 团队更新主题时，您可能会丢失您的更改。

如果您几乎满意于现有主题，最简单的满足您需求的方法是创建一个新主题，继承大部分父主题的属性并仅更改一些次要内容。
详见下文有关主题遗产的更详细描述。

有些主题还允许用户选择主题的“变体”（variants）。
这些主题变体通常包含一个额外的[Cascading Style Sheets (CSS)](https://en.wikipedia.org/wiki/CSS) 文件，以覆盖默认主题值的某些样式。
仓库中的主题中，*vier* 和 *vier* 使用此方法来制作变体。
Quattro则采用稍微不同的方法。

第三种方法是从头开始创建自己的主题。
这是改变 Friendica 外观最复杂的方法。
但它让您有最大的自由。
因此下文将详细描述一些特殊文件的含义。

### 样式

如果您想更改主题的样式，请查看主题的 CSS 文件。
在大多数情况下，这些文件都可以在以下目录中找到：

    /view/theme/**your-theme-name**/style.css

有时，主题目录中还会有一个名为 style.php 的文件。
仅当主题允许用户动态更改某些东西时才需要此文件。
例如设置字体大小或设置背景图像。

### 模板

如果您要更改主题的结构，则需要更改主题使用的模板。
Friendica 主题使用 [SMARTY3](http://www.smarty.net/) 进行模板化。
默认模板位于

    /view/templates

如果您要覆盖任何模板，请在以下目录中创建您的模板版本：

    /view/theme/**your-theme-name**/templates

那里存在的任何模板都将被用于替代默认模板。

### Javascript

相同的规则适用于在`/js`目录下找到的JavaScript文件，它们将被位于`/view/theme/**your-theme-name**/js`目录下的文件覆盖。

## 从零开始创建一个主题

请耐心一点。
基本上你需要做的就是确定你必须更改哪个模板，使它看起来更像你想要的样子。
相应地采用主题的 CSS 样式。
反复迭代这个过程，直到你得到想要的主题。

*使用源代码，Luke。* 不要犹豫，向 @[developers](https://forum.friendi.ca/profile/developers) 或 @[helpers](https://forum.friendi.ca/profile/helpers) 提问。

## 特殊文件

### unsupported

如果主题目录中存在一个名为 `unsupported`（可能为空）的文件，则该主题被标记为*不受支持的*。
不支持的主题可能无法在设置中被用户选择。
已经在使用不支持的主题的用户不会注意到任何变化。

### README(.md)

这个文件的内容，带有或不带有指示 [Markdown](https://daringfireball.net/projects/markdown/) 语法的 `.md` 后缀，将被显示在大多数仓库托管服务和 friendica 的管理面板中的主题页面上。

这个文件应该包含你想让其他人了解的主题信息。

### screenshot.[png|jpg]

如果你想在设置中显示你的主题的预览图片，你应该拍一张截图，并以这个名字保存。
支持的格式为 PNG 和 JPEG。

### theme.php

这是主题的主要定义文件。
在该文件的头部存储了一些元信息。
例如，看一下 *vier* 主题的 `theme.php` 文件：

    <?php
    /**
     * [Licence]
     *
     * Name: Vier
     * Version: 1.2
     * Author: Fabio <http://kirgroup.com/profile/fabrixxm>
     * Author: Ike <http://pirati.ca/profile/heluecht>
     * Author: Beanow <https://fc.oscp.info/profile/beanow>
     * Maintainer: Ike <http://pirati.ca/profile/heluecht>
     * Description: "Vier" is a very compact and modern theme. It uses the font awesome font library: http://fortawesome.github.com/Font-Awesome/
     */


你可以看到主题名称、版本号以及主题的初始作者的定义。这三条信息应该被列出来。如果原始作者不再开发主题，但有维护人员接手了，那么维护人员也应该被列出来。主题标头中的信息将在管理面板中显示。

文件中的第一件事是从 `\Friendica\` 命名空间导入 `App` 类。

    use Friendica\App;

这将让我们的工作更加轻松，因为我们不必每次使用 `App` 类时都指定完整的名称。

theme.php 文件的下一个关键部分是一个 init 函数的定义。函数的名称是 <theme-name>_init。所以在 vier 主题的情况下，它是这样的：

    function vier_init(App $a) {
		$a->theme_info = array();
		$a->set_template_engine('smarty3');
    }

在这里，我们设置了基本的主题信息，在这种情况下它们是空的。但是数组需要被设置。我们还设置了 Friendica 应该为该主题使用的模板引擎。目前，您应该使用 *smarty3* 引擎。曾经也有过一个 Friendica 特定的模板引擎，但现在已不再使用。如果您想使用其他的模板引擎，请自行实现。

如果您想要向主题的 HTML 头添加一些内容，一种方法是通过将其添加到 theme.php 文件中来实现。为此，请添加类似以下内容：

    DI::page()['htmlhead'] .= <<< EOT
    /* stuff you want to add to the header */
    EOT;

这样，您也可以从 theme.php 文件中访问此 friendica 会话的属性。

### default.php

该文件涵盖了底层 HTML 布局的结构。默认文件在

    /view/default.php

如果您想更改它，例如添加用于您最喜欢的 FLOSS 项目的横幅的第四列，请将一个新的 default.php 文件放在您的主题目录中。与 theme.php 文件一样，您可以使用 $a 变量持有 friendica 应用程序的属性来决定显示什么内容。
