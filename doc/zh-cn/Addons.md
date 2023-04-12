Friendica 插件开发

==============

* [主页](帮助)

请参考样例插件 'randplace' 来了解如何使用这些特性。

插件通过拦截事件hook来实现功能，这些hook必须先注册。模块通过拦截特定的页面请求（根据 URL 路径）来实现功能。

## 命名

插件名称用于文件路径和函数名称，因此：

-不能包含空格或标点符号。
-不能以数字开头。

## 元数据

您可以在插件文件的第一个多行注释中提供关于插件的人类可读信息。

以下是结构：

```php
/**
 * Name: {Human-readable name}
 * Description: {Short description}
 * Version: 1.0
 * Author: {Author1 Name}
 * Author: {Author2 Name} <{Author profile link}>
 * Maintainer: {Maintainer1 Name}
 * Maintainer: {Maintainer2 Name} <{Maintainer profile link}>
 * Status: {Unsupported|Arbitrary status}
 */
```

您还可以在 README 或 README.md 文件中提供更长的文档。后者将在插件详情页面中从Markdown转换为HTML。

## 安装/卸载

如果您的插件使用hooks，它们必须在 <addon>_install() 函数中注册。
此函数还允许执行您的插件需要正确运行的任意操作。

卸载插件会自动取消注册的任何hooks，但如果您需要提供特定的卸载步骤，则可以在 <addon>_uninstall() 函数中添加它们。

安装和卸载函数将在插件安装后调用（即重新安装）。
因此，您的卸载操作不应破坏数据，而安装则应考虑数据可能已经存在。
将来的扩展可能会提供 “setup” 和 “remove”。

## PHP插件hooks

在安装过程中注册您的插件hooks。

\Friendica\Core\Hook::register($hookname, $file, $function);
$hookname 是一个字符串，对应于已知的 Friendica PHP hook。

$file 是相对于顶级 Friendica 目录的路径名。在大多数情况下应为 ‘addon/插件名称/插件名称.php’，并且可以缩短为 __FILE__。

$function 是一个字符串，是当调用hook时将执行的函数名称。

### 参数

您的hooks回调函数最多使用一个参数调用

function <addon>_<hookname>(&$b)

}

如果您希望更改被调用数据，您必须在函数声明期间将它们声明为引用变量 (使用 &)。

#### $b

$b 可以随意命名。
这是当前正在处理的hook特定信息，并且通常包含正在立即处理或操作的信息，您可以使用、显示或更改它。
如果要更改它，请不要忘记使用 & 进行声明。

## 管理设置

你的插件可以通过 addon_settings PHP hooks提供特定用户设置，但也可以在插件的管理页面上提供节点范围的设置。

只需声明 <addon>_addon_admin() 函数以显示表单和 <addon>_addon_admin_post() 函数以处理表单数据即可。

## 全局样式表

如果您的插件需要在 Friendica 的所有页面上添加样式表，请添加以下hooks：

```php
function <addon>_install()
{
	\Friendica\Core\Hook::register('head', __FILE__, '<addon>_head');
	...
}


function <addon>_head()
{
	\Friendica\DI::page()->registerStylesheet(__DIR__ . '/relative/path/to/addon/stylesheet.css');
}
```

`__DIR__` 是你的addon的文件夹路径。

## JavaScript

###全局脚本

如果你的插件需要在Friendica的所有页面上添加一个脚本，请添加以下hooks：

```php
function <addon>_install()
{
	\Friendica\Core\Hook::register('footer', __FILE__, '<addon>_footer');
	...
}

function <addon>_footer()
{
	\Friendica\DI::page()->registerFooterScript(__DIR__ . '/relative/path/to/addon/script.js');
}
```

`__DIR__` 是你的addon的文件夹路径。

### JavaScript hooks

### JavaScript hooks

主Friendica脚本通过在 `document` 属性上分发事件来提供hook。
在上述描述的Javascript文件中，像这样添加事件监听器：

```js
document.addEventListener(name, callback);
```

- *name* 是hooks的名称，并对应于已知的Friendica JavaScript hooks。
- *callback* 是要执行的JavaScript匿名函数。

有关Javascript事件侦听器的更多信息，请参见：https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener

#### 当前的JavaScript hooks

##### postprocess_liveupdate

在实时更新过程（XmlHttpRequest）末尾以及帖子预览时调用。
不提供额外数据。

## 模块

插件也可以作为“模块”并拦截给定URL路径的所有页面请求。
为了使插件充当模块，它需要声明一个空函数 `<addon>_module()`。

如果存在此函数，则现在您将接收到所有页面请求 `https://my.web.site/<addon>` - 其中包括任意数量的URL组件作为附加参数。
这些解析为 `App\Arguments` 对象。
因此，`https://my.web.site/addon/arg1/arg2` 将给出如下结果：

```php
DI::args()->getArgc(); // = 3
DI::args()->get(0); // = 'addon'
DI::args()->get(1); // = 'arg1'
DI::args()->get(2); // = 'arg2'
```

要显示一个模块页面，您需要声明函数 `<addon>_content()`，用于定义并返回页面正文内容。

它们还可以包含 `<addon>_post()`，该函数在 `<addon>_content` 函数之前调用，通常处理 POST 表单的结果。

您还可以有 `<addon>_init()`，该函数在 `<addon>_content` 之前调用，应包括您的模块的公共逻辑。

## 模板

如果您的插件需要一些模板，可以使用 Friendica 模板系统。

Friendica 使用 [smarty3](http://www.smarty.net/) 作为模板引擎。

将您的 tpl 文件放在您的插件的 *templates/* 子文件夹中。

在您的代码中，例如在函数 addon_name_content() 中，加载模板文件并传递所需的值以执行它。

```php
use Friendica\Core\Renderer;

# load template file. first argument is the template name,
# second is the addon path relative to friendica top folder
$tpl = Renderer::getMarkupTemplate('mytemplate.tpl', __DIR__);

# apply template. first argument is the loaded template,
# second an array of 'name' => 'values' to pass to template
$output = Renderer::replaceMacros($tpl, array(
	'title' => 'My beautiful addon',
));
```
也请参见维基页面[快速模板指南](https://github.com/friendica/friendica/wiki/Quick-Template-Guide)。

## 当前 PHP hooks

### authenticate

当用户尝试登录时调用。
`$b` 包含：

- **username**：所提供的用户名
- **password**：所提供的密码
- **authenticated**：将其设置为非零以验证用户。
- **user_record**：成功验证还必须从数据库返回有效的用户记录。

### logged_in

在用户成功登录后调用。
`$b` 包含 `App->user` 数组。

### display_item

在格式化帖子以显示时调用。

`$b` 是一个数组：

- **item**：从数据库中获取的项目（数组）详细信息。
- **output**：添加到页面之前此项目的（字符串）HTML表示形式。

### post_local

在本地系统中输入状态帖子或评论时调用。
`$b` 是要存储在数据库中的信息的项目数组。
请注意：正文内容是 BBCode 而不是 HTML。

### post_local_end

在本地状态帖子或评论已存储在本地系统上时调用。
`$b` 是刚刚存储在数据库中的信息的项目数组。
请注意：正文内容是 BBCode 而不是 HTML。

### post_remote

在从其他来源接收到帖子时调用。这也可以用于发布本地活动或系统生成的消息。
`$b` 是要存储在数据库中的信息的项目数组，并且项目正文是 BBCode。

### addon_settings

在生成插件设置页面的 HTML 时调用。
`$data` 是一个包含：

翻译：
- **addon**（输出）：必填项。插件文件夹名称。
- **title**（输出）：必填项。插件设置面板的标题。
- **href**（输出）：可选项。如果设置了该项，将会将面板缩小为指向此URL的链接，可以是相对路径。与以下键不兼容。
- **html**（输出）：可选项。插件表单元素的原始 HTML。`<form>` 标签和提交按钮已经在其他地方处理过了。
- **submit**（输出）：可选项。如果未设置，则会生成一个默认的提交按钮，`name="<addon name>-submit"`。具有以下不同的值类型：
  - **字符串**：替换默认标签。
  - **关联数组**：提交按钮列表，键是 `name` 属性的值，值是显示的标签。
    此列表中的第一个提交按钮被认为是主要的，主题可能强调其显示。
	
#### Examples

##### 有链接

```php
$data = [
	'addon' => 'advancedcontentfilter',
	'title' => DI::l10n()->t('Advanced Content Filter'),
	'href'  => 'advancedcontentfilter',
];
```

##### 使用默认的提交按钮

```php
$data = [
	'addon' => 'fromapp',
	'title' => DI::l10n()->t('FromApp Settings'),
	'html'  => $html,
];
```

##### 没有HTML，只有一个提交按钮

```php
$data = [
	'addon'  => 'opmlexport',
	'title'  => DI::l10n()->t('OPML Export'),
	'submit' => DI::l10n()->t('Export RSS/Atom contacts'),
];
```
##### 有多个提交按钮

```php

$data = [
	'addon'  => 'catavar',
	'title'  => DI::l10n()->t('Cat Avatar Settings'),
	'html'   => $html,
	'submit' => [
		'catavatar-usecat'   => DI::l10n()->t('Use Cat as Avatar'),
		'catavatar-morecat'  => DI::l10n()->t('Another random Cat!'),
		'catavatar-emailcat' => DI::pConfig()->get(Session::getLocalUser(), 'catavatar', 'seed', false) ? DI::l10n()->t('Reset to email Cat') : null,
	],
];
```

### addon_settings_post

当插件设置页面被提交时调用此函数。
`$b` 参数是 $_POST 数组。

### connector_settings

在生成连接器插件设置页面的 HTML 时调用此函数。
`$data` 参数是一个关联数组，包含以下字段：

- **connector**（输出）：必填。插件文件夹名称。
- **title**（输出）：必填。插件设置面板标题。
- **image**（输出）：必填。连接到此插件的平台/协议的标识图片的相对路径，最大尺寸为 48x48 像素。
- **enabled**（输出）：可选。如果设置为假值，则连接器图像将变暗。
- **html**（输出）：可选。插件表单元素的原始 HTML。`<form>` 标签和提交按钮已在其他位置处理。
- **submit**（输出）：可选。如果未设置，默认将生成具有 `name="<addon name>-submit"` 的提交按钮。
  可以具有不同的值类型：
    - **字符串**：替换默认标签的标签。
    - **关联数组**：提交按钮列表，键是 `name` 属性的值，值是显示的标签。
      此列表中的第一个提交按钮被视为主要按钮，并且主题可能会强调其显示。

#### Examples

##### 使用默认的提交按钮
```php
$data = [
	'connector' => 'diaspora',
	'title'     => DI::l10n()->t('Diaspora Export'),
	'image'     => 'images/diaspora-logo.png',
	'enabled'   => $enabled,
	'html'      => $html,
];
```

##### 有自定义的提交按钮标签和无logo
```php
$data = [
	'connector' => 'ifttt',
	'title'     => DI::l10n()->t('IFTTT Mirror'),
	'image'     => 'addon/ifttt/ifttt.png',
	'html'      => $html,
	'submit'    => DI::l10n()->t('Generate new key'),
];
```

##### 带条件的提交按钮
```php
$submit = ['pumpio-submit' => DI::l10n()->t('Save Settings')];
if ($oauth_token && $oauth_token_secret) {
	$submit['pumpio-delete'] = DI::l10n()->t('Delete this preset');
}

$data = [
	'connector' => 'pumpio',
	'title'     => DI::l10n()->t('Pump.io Import/Export/Mirror'),
	'image'     => 'images/pumpio.png',
	'enabled'   => $enabled,
	'html'      => $html,
	'submit'    => $submit,
];
```

### profile_post
当发布个人资料页面时调用。
`$b` 是 $_POST 数组。

### profile_edit
在输出个人资料编辑页面之前调用。
`$b` 是一个包含以下内容的数组：

- **profile**: 数据库中的个人资料(array)记录
- **entry**: 生成的(entry)HTML字符串

### profile_advanced
在生成高级个人资料时调用，对应个人资料页面中的"Profile"选项卡。
`$b` 是生成的(profile)HTML字符串的表示形式。
个人资料数组的详细信息在 `App->profile` 中。

### directory_item
在目录页格式化显示项目时调用。
`$b` 是一个数组：

- **contact**: 数据库中该人员的联系记录数组(contact record array)
- **entry**: 生成的(entry)HTML字符串

### profile_sidebar_enter
在为页面生成侧边栏"short"个人资料之前调用。
`$b` 是该人员的个人资料数组。

### profile_sidebar
在生成页面的侧边栏"short"个人资料时调用。
`$b` 是一个数组：

- **profile**: 数据库中该人员的个人资料记录数组
- **entry**: 生成的(entry) HTML字符串

### contact_block_end
在完成对个人资料侧边栏上联系人/好友块的格式化显示时调用。
`$b` 是一个数组：

- **contacts**: 联系人数组
- **output**: 生成的联系人块的HTML字符串

### bbcode
在bbcode转为HTML之后调用。
`$b` 是转换后的HTML字符串。

	### html2bbcode
在将HTML转换为bbcode（例如远程消息发布）之后调用。
`$b` 是一个字符串类型的文本。

### head
在构建 `<head>` 部分时调用。
应该使用此hook注册样式表。
`$b` 是 `<head>` 标签的 HTML 字符串。

### page_header
在构建页面导航栏之后调用。
`$b` 是导航区域的 HTML 字符串。

### personal_xrd
在输出个人 XRD 文件之前调用。
`$b` 是一个数组:

- **user**: 表示个人的用户记录数组
- **xml**: 完整的 XML 字符串，将被输出

### home_content
在输出首页内容之前调用，这些内容将显示给未登录的用户。
`$b` 是区域的 HTML 字符串。

### contact_edit
在“联系人”页面编辑个人联系人详细信息时调用。
$b 是一个数组：

- **contact**: 目标联系人的联系人记录（数组）
- **output**: 联系人编辑页面生成的 HTML 代码（字符串）

### contact_edit_post
在提交联系人编辑页面时调用。
`$b` 是 `$_POST` 数组。

### init_1
在数据库打开之后、会话开始之前立即调用。
没有hook数据。

### page_end
在 HTML 内容函数完成后调用。
`$b` 是内容 div 的 HTML 字符串。

### footer
在 HTML 内容函数完成后调用。
延迟加载的 JavaScript 文件应使用此hook进行注册。
`$b` 是页脚 div/元素的 HTML 字符串。

### avatar_lookup
在查找头像时调用。`$b` 是一个数组：

- **size**：要查找头像的尺寸
- **email**：要查找头像的电子邮件
- **url**：头像的生成 URL（字符串）

### emailer_send_prepare
在构建 MIME 消息之前从 `Emailer::send()` 调用。`$b` 是一个参数数组，用于传递给 `Emailer::send()`。

- **fromName**：发件人的名称
- **fromEmail**：发件人的电子邮件地址
- **replyTo**：回复地址以便接收回复
- **toEmail**：目标电子邮件地址
- **messageSubject**：消息主题
- **htmlVersion**：消息的 HTML 版本
- **textVersion**：消息的纯文本版本
- **additionalMailHeader**：SMTP 邮件头的附加内容
- **sent**：默认为 false，在挂钩中设置为 true 时，将跳过默认邮件程序。

### emailer_send
在调用 PHP 的 `mail()` 之前调用。`$b` 是一个参数数组，用于传递给 `mail()`。

- **to**
- **subject**
- **body**
- **headers**
- **sent**：默认为 false，在挂钩中设置为 true 时，将跳过默认邮件程序。

### load_config
在 `App` 初始化期间调用，允许插件使用 `App::loadConfigFile()` 加载自己的配置文件。

### nav_info
在 `include/nav.php` 构建导航菜单之后调用。`$b` 是一个包含 `$nav`（来自 `include/nav.php`）的数组。

### template_vars
在将变量传递到模板引擎以渲染页面之前调用。
注册的函数可以添加、更改或删除传递到模板的变量。
`$b` 是一个带有以下内容的数组：

- **template**：模板的文件名
- **vars**：要传递到模板的变量数组

### acl_lookup_end
在其他查询已经通过后调用。
注册的函数可以添加、更改或删除 `acl_lookup()` 变量。

- **results**：acl_lookup() 变量的数组

### prepare_body_init
在 prepare_body 开始时调用。
挂钩数据：

- **item**（输入/输出）：项数组

### prepare_body_content_filter
在 prepare_body 的 HTML 转换之前调用。如果项与插件设置的内容过滤规则匹配，
则应仅将原因添加到挂钩数据的 filter_reasons 元素中。
挂钩数据：

- **item**：项数组（输入）
- **filter_reasons**（输入/输出）：原因数组

### prepare_body
在 `prepare_body()` 中 HTML 转换后被调用。hook数据：

- **item**（输入）：项目数组
- **html**（输入/输出）：转换后的项目正文 HTML
- **is_preview**（输入）：帖子预览标志
- **filter_reasons**（输入）：原因数组

### prepare_body_final
在 `prepare_body()` 结束时调用 `prepare_body_final`。hook数据：

- **item**：项目数组（输入）
- **html**：转换后的项目正文 HTML（输入/输出）

### put_item_in_cache
在 `put_item_in_cache()` 中的 `prepare_text()` 后调用。hook数据：

- **item**（输入）：项目数组
- **rendered-html**（输入/输出）：最终项目正文 HTML
- **rendered-hash**（输入/输出）：原始项目正文哈希值

### magic_auth_success
当 magic-auth 成功时调用。hook数据：

    visitor => 访客的联系记录数组
    url => 查询字符串

### jot_networks
显示帖子权限屏幕时调用。hook数据是需要在 ACL 旁显示的表单字段列表。表单字段数组结构为：

- **type**：`checkbox` 或 `select`。
- **field**：由 `field_checkbox.tpl` 和 `field_select.tpl` 使用的标准字段数据结构。

对于 `checkbox`，**field** 如下：
  - [0]（字符串）：表单字段名称；强制执行。
  - [1]（字符串）：表单字段标签；可选，默认为无。
  - [2]（布尔值）：默认情况下是否应勾选复选框；可选，默认为 false。
  - [3]（字符串）：其他帮助文本；可选，默认为无。
  - [4]（字符串）：其他 HTML 属性；可选，默认为无。

对于 `select`，**field** 如下：
  - [0]（字符串）：表单字段名称；强制执行。
  - [1]（字符串）：表单字段标签；可选，默认为无。
  - [2]（布尔值）：默认情况下需要选择的值；可选，默认为无。
  - [3]（字符串）：其他帮助文本；可选，默认为无。
  - [4]（数组）：选项的关联数组。项目键是选项值，项目值是选项标签；强制执行。

### route_collection
在调度路由器之前调用。hook数据是一个 `\FastRoute\RouterCollector` 对象，应使用它添加指向类的插件路由。

**注意**：路由处理程序中提供的类必须通过自动加载器可访问。

### probe_detect
在尝试检测 URL 的目标网络之前调用。如果任何已注册的hook函数设置了hook数据数组的 `result` 键，则立即返回它。如果hook数据包含现有结果，则hook函数也应立即返回。

hook数据：

- **uri**（输入）：个人资料 URI。
- **network**（输入）：目标网络（可以为空以进行自动检测）。
- **uid**（输入）：返回联系人数据的用户（公共联系人可以为空）。
- **result**（输出）：如果地址与连接器无关，请将其设置为 null；如果探测成功，则将其设置为联系人数组，否则将其设置为 false。

### item_by_link
尝试从给定的 URI 探测项目时调用。如果任何已注册的hook函数设置了hook数据数组的 `item_id` 键，则立即返回它。如果hook数据包含现有的 `item_id`，则hook函数也应立即返回。

hook数据：
- **uri**（输入）：项目 URI。
- **uid**（输入）：返回项目数据的用户（公共联系人可以为空）。
- **item_id**（输出）：如果 URI 与连接器无关，请将其设置为 null；如果探测成功，则将其设置为创建的项目数组，否则将其设置为 false。

### support_follow
断言连接器插件是否提供关注功能。

hook数据：
- **protocol**（输入）：协议的简称。值列表在 `src/Core/Protocol.php` 中可用。
- **result**（输出）：如果连接器提供关注功能，则应为 true；否则保持不变。

### support_revoke_follow
断言连接器插件是否提供取消关注功能。

hook数据：
- **protocol**（输入）：协议的简称。值列表在 `src/Core/Protocol.php` 中可用。
- **result**（输出）：如果连接器提供取消关注功能，则应为 true；否则保持不变。

### follow
在处理非本机网络远程联系人之前，调用以处理用户添加新联系人（例如 Twitter）。

hook数据：

- **url**（输入）：远程联系人的 URL。
- **contact**（输出）：如果跟随成功，应填写具有 uid = 创建联系人的用户的联系人数组。

### unfollow

在取消关注非原生网络（例如 Twitter）上的远程联系人时调用。

hook数据：
- **contact**（输入）：目标公共联系人（uid = 0）数组。
- **uid**（输入）：源本地用户的 ID。
- **result**（输出）：取消关注是否成功的布尔值。

### revoke_follow

在使非原生网络（例如 Twitter）上的远程联系人取消关注您时调用。

hook数据：
- **contact**（输入）：目标公共联系人（uid = 0）数组。
- **uid**（输入）：要为其发出阻止请求的用户 ID。
- **result**（输出）：表示操作是否成功的布尔值。

### block

在阻止非原生网络（例如 Twitter）上的远程联系人时调用。

hook数据：
- **contact**（输入）：远程联系人（uid = 0）数组。
- **uid**（输入）：要发出阻止请求的用户 ID。
- **result**（输出）：表示操作是否成功的布尔值。

### unblock

在取消阻止非原生网络（例如 Twitter）上的远程联系人时调用。

hook数据：
- **contact**（输入）：远程联系人（uid = 0）数组。
- **uid**（输入）：要撤销阻止请求的用户 ID。
- **result**（输出）：表示操作是否成功的布尔值。

### storage_instance

在使用自定义存储（例如 webdav_storage）时调用。

hook数据：
- **name**（输入）：所使用的存储后端的名称。
- **data['storage']**（输出）：要使用的存储实例（**必须**实现 `\Friendica\Core\Storage\IWritableStorage`）。

### storage_config

在节点管理员想要配置自定义存储（例如 webdav_storage）时调用。

hook数据：
- **name**（输入）：所使用的存储后端的名称。
- **data['storage_config']**（输出）：要使用的存储配置实例（**必须**实现 `\Friendica\Core\Storage\Capability\IConfigureStorage`）。

## hook回调函数的完整列表

以下是所有hook回调函数的完整列表以及其文件位置（截至 2018 年 9 月 24 日）。对于任何未在上文中记录的hook，请参阅源代码中的详细信息。

### index.php

    Hook::callAll('init_1');
    Hook::callAll('app_menu', $arr);
    Hook::callAll('page_content_top', DI::page()['content']);
    Hook::callAll($a->module.'_mod_init', $placeholder);
    Hook::callAll($a->module.'_mod_init', $placeholder);
    Hook::callAll($a->module.'_mod_post', $_POST);
    Hook::callAll($a->module.'_mod_content', $arr);
    Hook::callAll($a->module.'_mod_aftercontent', $arr);
    Hook::callAll('page_end', DI::page()['content']);

### include/api.php

    Hook::callAll('logged_in', $a->user);
    Hook::callAll('authenticate', $addon_auth);
    Hook::callAll('logged_in', $a->user);

### include/enotify.php

    Hook::callAll('enotify', $h);
    Hook::callAll('enotify_store', $datarray);
    Hook::callAll('enotify_mail', $datarray);
    Hook::callAll('check_item_notification', $notification_data);

### src/Content/Conversation.php

    Hook::callAll('conversation_start', $cb);
    Hook::callAll('render_location', $locate);
    Hook::callAll('display_item', $arr);
    Hook::callAll('display_item', $arr);
    Hook::callAll('item_photo_menu', $args);
    Hook::callAll('jot_tool', $jotplugins);

### mod/directory.php

    Hook::callAll('directory_item', $arr);

### mod/xrd.php

    Hook::callAll('personal_xrd', $arr);

### mod/parse_url.php

    Hook::callAll("parse_link", $arr);

### src/Module/Delegation.php

    Hook::callAll('home_init', $ret);

### mod/acl.php

    Hook::callAll('acl_lookup_end', $results);

### mod/network.php

    Hook::callAll('network_content_init', $arr);
    Hook::callAll('network_tabs', $arr);

### mod/friendica.php

    Hook::callAll('about_hook', $o);

### mod/profiles.php

    Hook::callAll('profile_post', $_POST);
    Hook::callAll('profile_edit', $arr);

### mod/settings.php

    Hook::callAll('addon_settings_post', $_POST);
    Hook::callAll('connector_settings_post', $_POST);
    Hook::callAll('display_settings_post', $_POST);
    Hook::callAll('addon_settings', $settings_addons);
    Hook::callAll('connector_settings', $settings_connectors);
    Hook::callAll('display_settings', $o);

### mod/photos.php

    Hook::callAll('photo_post_init', $_POST);
    Hook::callAll('photo_post_file', $ret);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', $foo);
    Hook::callAll('photo_post_end', intval($item_id));
    Hook::callAll('photo_upload_form', $ret);

### mod/profile.php

    Hook::callAll('profile_advanced', $o);

### mod/home.php

    Hook::callAll('home_init', $ret);
    Hook::callAll("home_content", $content);

### mod/contacts.php

    Hook::callAll('contact_edit_post', $_POST);
    Hook::callAll('contact_edit', $arr);

### mod/tagger.php

    Hook::callAll('post_local_end', $arr);

### mod/uexport.php

    Hook::callAll('uexport_options', $options);

### mod/register.php

    Hook::callAll('register_post', $arr);
    Hook::callAll('register_form', $arr);

### mod/item.php

    Hook::callAll('post_local_start', $_REQUEST);
    Hook::callAll('post_local', $datarray);
    Hook::callAll('post_local_end', $datarray);

### src/Render/FriendicaSmartyEngine.php

    Hook::callAll("template_vars", $arr);

### src/App.php

    Hook::callAll('load_config');
    Hook::callAll('head');
    Hook::callAll('footer');
    Hook::callAll('route_collection');

### src/Model/Item.php

    Hook::callAll('post_local', $item);
    Hook::callAll('post_remote', $item);
    Hook::callAll('post_local_end', $posted_item);
    Hook::callAll('post_remote_end', $posted_item);
    Hook::callAll('tagged', $arr);
    Hook::callAll('post_local_end', $new_item);
    Hook::callAll('put_item_in_cache', $hook_data);
    Hook::callAll('prepare_body_init', $item);
    Hook::callAll('prepare_body_content_filter', $hook_data);
    Hook::callAll('prepare_body', $hook_data);
    Hook::callAll('prepare_body_final', $hook_data);

### src/Model/Contact.php

    Hook::callAll('contact_photo_menu', $args);
    Hook::callAll('follow', $arr);

### src/Model/Profile.php

    Hook::callAll('profile_sidebar_enter', $profile);
    Hook::callAll('profile_sidebar', $arr);
    Hook::callAll('profile_tabs', $arr);
    Hook::callAll('zrl_init', $arr);
    Hook::callAll('magic_auth_success', $arr);

### src/Model/Event.php

    Hook::callAll('event_updated', $event['id']);
    Hook::callAll("event_created", $event['id']);

### src/Model/Register.php

    Hook::callAll('authenticate', $addon_auth);

### src/Model/User.php

    Hook::callAll('authenticate', $addon_auth);
    Hook::callAll('register_account', $uid);
    Hook::callAll('remove_user', $user);

### src/Module/Notifications/Ping.php

    Hook::callAll('network_ping', $arr);

### src/Module/PermissionTooltip.php

    Hook::callAll('lockview_content', $item);

### src/Module/Post/Edit.php

    Hook::callAll('jot_tool', $jotplugins);

### src/Module/Settings/Delegation.php

    Hook::callAll('authenticate', $addon_auth);

### src/Module/Settings/TwoFactor/Index.php

    Hook::callAll('authenticate', $addon_auth);

### src/Security/Authenticate.php

    Hook::callAll('authenticate', $addon_auth);

### src/Security/ExAuth.php

    Hook::callAll('authenticate', $addon_auth);

### src/Content/ContactBlock.php

    Hook::callAll('contact_block_end', $arr);

### src/Content/Text/BBCode.php

    Hook::callAll('bbcode', $text);
    Hook::callAll('bb2diaspora', $text);

### src/Content/Text/HTML.php

    Hook::callAll('html2bbcode', $message);

### src/Content/Smilies.php

    Hook::callAll('smilie', $params);

### src/Content/Feature.php

    Hook::callAll('isEnabled', $arr);
    Hook::callAll('get', $arr);

### src/Content/ContactSelector.php

    Hook::callAll('network_to_name', $nets);

### src/Content/OEmbed.php

    Hook::callAll('oembed_fetch_url', $embedurl, $j);

### src/Content/Nav.php

    Hook::callAll('page_header', DI::page()['nav']);
    Hook::callAll('nav_info', $nav);

### src/Core/Authentication.php

    Hook::callAll('logged_in', $a->user);

### src/Core/Protocol.php

    Hook::callAll('support_follow', $hook_data);
    Hook::callAll('support_revoke_follow', $hook_data);
    Hook::callAll('unfollow', $hook_data);
    Hook::callAll('revoke_follow', $hook_data);
    Hook::callAll('block', $hook_data);
    Hook::callAll('unblock', $hook_data);

### src/Core/Logger/Factory.php

    Hook::callAll('logger_instance', $data);

### src/Core/StorageManager

    Hook::callAll('storage_instance', $data);
    Hook::callAll('storage_config', $data);

### src/Worker/Directory.php

    Hook::callAll('globaldir_update', $arr);

### src/Worker/Notifier.php

    Hook::callAll('notifier_end', $target_item);

### src/Module/Login.php

    Hook::callAll('login_hook', $o);

### src/Module/Logout.php

    Hook::callAll("logging_out");

### src/Object/Post.php

    Hook::callAll('render_location', $locate);
    Hook::callAll('display_item', $arr);

### src/Core/ACL.php

    Hook::callAll('contact_select_options', $x);
    Hook::callAll($a->module.'_pre_'.$selname, $arr);
    Hook::callAll($a->module.'_post_'.$selname, $o);
    Hook::callAll($a->module.'_pre_'.$selname, $arr);
    Hook::callAll($a->module.'_post_'.$selname, $o);
    Hook::callAll('jot_networks', $jotnets);

### src/Core/Authentication.php

    Hook::callAll('logged_in', $a->user);
    Hook::callAll('authenticate', $addon_auth);

### src/Core/Hook.php

    self::callSingle(self::getApp(), 'hook_fork', $fork_hook, $hookdata);

### src/Core/Worker.php

    Hook::callAll("proc_run", $arr);

### src/Util/Emailer.php

    Hook::callAll('emailer_send_prepare', $params);
    Hook::callAll("emailer_send", $hookdata);

### src/Util/Map.php

    Hook::callAll('generate_map', $arr);
    Hook::callAll('generate_named_map', $arr);
    Hook::callAll('Map::getCoordinates', $arr);

### src/Util/Network.php

    Hook::callAll('avatar_lookup', $avatar);

### src/Util/ParseUrl.php

    Hook::callAll("getsiteinfo", $siteinfo);

### src/Protocol/DFRN.php

    Hook::callAll('atom_feed_end', $atom);
    Hook::callAll('atom_feed_end', $atom);

### src/Protocol/Email.php

    Hook::callAll('email_getmessage', $message);
    Hook::callAll('email_getmessage_end', $ret);

### view/js/main.js

    document.dispatchEvent(new Event('postprocess_liveupdate'));