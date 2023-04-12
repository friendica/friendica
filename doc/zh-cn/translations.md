# Friendica 翻译

* [主页](help)

## 概述

Friendica 的翻译过程基于 `gettext` PO 文件。

基本流程：
1. 使用 `xgettext` 工具在位于 `view/lang/C/messages.po` 的主 PO 文件中收集整个项目的翻译字符串。
2. 这个文件通过 [Transifex Friendica 页面](https://www.transifex.com/Friendica/friendica/dashboard/) 使得翻译字符串可供翻译者翻译。
3. 翻译本身在 Transifex 完成，由志愿者完成。
4. 每种语言的结果 PO 文件手动更新到 `view/lang/<language>/messages.po` 中。
5. PO 文件转换为 PHP 数组，存放在 `view/lang/<language>/strings.php` 中，并最终被 Friendica 使用以显示翻译。

## 在您喜欢的语言中翻译 Friendica

感谢您对改进 Friendica 的翻译表现感兴趣！
请注册一个免费的 Transifex 账户，并在 [Transifex Friendica 页面](https://www.transifex.com/Friendica/friendica/dashboard/) 上申请加入您喜欢的语言的翻译团队。

作为经验法则，我们在 Friendica 中添加对某种语言的支持时，会将至少 50% 的字符串翻译为目标语言以避免不连贯的体验。
对于插件，我们会在 Friendica 中支持的语言中添加对其语言的支持。

## 添加新的翻译字符串

### 支持的 gettext 版本

我们目前支持 gettext 版本 0.19.8.1，并使用此版本主动检查新的翻译字符串。

如果您没有使用此版本，则可能会因为换行符之类的微小差异导致检查失败。
如果您有一个 Docker 环境，您可以轻松地使用以下命令更新翻译：
```shell
docker run --rm -v $PWD:/data -w /data friendicaci/transifex bin/run_xgettext.sh
```

### 核心

一旦您在代码更改中添加了新的翻译字符串，请从 Friendica 的基本目录运行 `bin/run_xgettext.sh`，并将更新的 `view/lang/C/messages.po` 提交到您的分支中。

### 插件

如果您有 `friendica-addons` 仓库在 Friendica 克隆仓库的 `addon` 目录下，则只需从 Friendica 的基本目录运行 `bin/run_xgettext.sh -a <addon_name>`。

否则：

	cd /path/to/friendica-addons/<addon_name>
	/path/to/friendica/bin/run_xgettext.sh -s

无论哪种情况，您都需要将更新的 `<addon_name>/lang/C/messages.po` 提交到您的工作分支中。

## 更新来自 Transifex 的翻译

请下载 `view/lang/<language>/messages.po` 中的 Transifex 文件，然后运行 `bin/console po2php view/lang/<language>/messages.po` 命令来更新相关的 `strings.php` 文件，并将这两个文件提交到你的工作分支。

### 使用 Transifex 客户端

Transifex 有一个客户端程序，允许您在本地 Friendica 存储库和 Transifex 之间同步文件。有关客户端的帮助可以在[Transifex 帮助中心](https://docs.transifex.com/client/introduction)找到。在这里我们只介绍基本用法。

安装客户端后，您的系统上应该有一个 `tx` 命令可用。要使用它，首先使用您的凭据创建配置文件。在 Linux 上，此文件应放置在您的主目录 `~/.transifexrc` 中。文件的内容应如下所示：

    [https://www.transifex.com]
    username = user
    token =
    password = p@ssw0rd
    hostname = https://www.transifex.com

自 Friendica 版本 3.5.1 起，我们在核心存储库和附加组件存储库中提供了 Transifex 客户端的配置文件 `.tx/config`。在您在 Transifex 网站上翻译了 Esperanto 等字符串后，您可以使用 `tx` 将更新的 PO 文件下载到正确的位置。

    $> tx pull -l eo

然后运行 `bin/console po2php view/lang/<language>/messages.po` 命令来更新相关的 `strings.php` 文件，并将这两个文件提交到您的工作分支。

## 翻译函数使用

### 基本用法

- `Friendica\DI::l10n()->t('Label')` => `Label`
- `Friendica\DI::l10n()->t('Label %s', 'test')` => `Label test`

### 复数

- `Friendica\DI::l10n()->tt('Label', 'Labels', 1)` => `Label`
- `Friendica\DI::l10n()->tt('Label', 'Labels', 3)` => `Labels`
- `Friendica\DI::l10n()->tt('%d Label', '%d Labels', 1)` => `1 Label`
- `Friendica\DI::l10n()->tt('%d Label', '%d Labels', 3)` => `3 Labels`
- `Friendica\DI::l10n()->tt('%d Label', 'Labels %2%s %3%s', 1, 'test', 'test2')` => `Label test test2`
- `Friendica\DI::l10n()->tt('%d Label', 'Labels %2%s %3%s', 3, 'test', 'test2')` => `Labels test test2`
