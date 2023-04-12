在 GitHub 上的 Friendica
===================

* [主页](help)

以下是如何在我们的代码上进行开发的步骤。如果您有任何问题，请写信给 Friendica 开发人员论坛。

介绍使用我们 GitHub 仓库的工作流程
-------------------------------------------------------

1. 在您将要进行开发的计算机上安装 git。
2. 创建自己的 [GitHub](https://github.com) 帐户。
3. 从 [https://github.com/friendica/friendica.git](https://github.com/friendica/friendica.git) 分叉 Friendica 仓库。
4. 将您的分叉从您的 GitHub 帐户克隆到您的计算机。
请按照这里提供的说明: [http://help.github.com/fork-a-repo/](http://help.github.com/fork-a-repo/) 创建和使用自己的在 GitHub 上跟踪分叉。
5. 在 Friendica 的文件夹中运行 `bin/composer.phar install`。
6. 提交您的更改到您的 fork。
然后，转到您的 GitHub 页面，创建一个“Pull 请求”来通知我们合并您的工作。

我们的 Git 分支
----------------

在 GitHub 上的主要仓库中有两个相关分支：

1. stable：此分支仅包含稳定版本。
2. develop：此分支包含最新的代码。
这是您所要使用的。

快进合并
---------------

在 git 中默认启用快进合并。
当您使用快进合并时，它不会添加新的提交以标记您已执行合并的时间和方式。
这意味着在您的提交历史记录中，您无法确切地知道发生了什么。
**最好关闭快进合并。**
这是通过运行 "git merge --no-ff" 来完成的。
[这里](https://stackoverflow.com/questions/5519007/how-do-i-make-git-merges-default-be-no-ff-no-commit) 是关于如何配置 git 默认关闭快进合并的解释。
您可以在[这里](http://nvie.com/posts/a-successful-git-branching-model/)找到更多的背景阅读。

发布分支
----------------

当 develop 分支包含所有应该有的功能时，会创建一个发布分支。
发布分支用于以下几种情况。

1. 它允许在发布进入稳定分支之前进行最后一分钟的错误修复。
2. 允许元数据更改（README、CHANGELOG等）以进行版本更新和文档更改。
3. 它确保 develop 分支可以接收不属于此版本的新功能。

最后一个点很重要，因为......
**创建一个发布分支的那一刻，develop 现在就是为此版本之后的版本而预留的。**
因此，请不要将 develop 合并到发布分支中！
例如：如果创建了一个名为“release-3.4”的发布分支，则“develop”将变为 3.5 或 4.0。
如果此时将 develop 合并到 release-3.4 中，那么为 3.5 或 4.0 打算的功能和错误修复可能会泄漏到此发布分支中。
这可能会引入新的错误，这违反了发布分支的目的。

一些重要提醒
------------------------

1. 在发起拉取请求之前，请从项目存储库中获取任何更改并将其与您的工作合并**之前**。我们保留拒绝任何导致大量合并冲突的补丁的权利。在语言翻译的情况下尤为如此-因为我们可能无法理解冲突版本之间的微妙差异。

2. **测试您的更改**。不要假设简单的修复不会破坏其他任何东西。如果可能，请让有经验的Friendica开发人员审核代码。如有疑问，请随时向我们提问。

3. 检查您的代码是否有错字。可以使用名为*typo*的控制台命令进行检查。
    
    	$> php bin/console.php typo

了解如何使用[我们的Vagrant](help/Vagrant)来节省大量设置时间！

