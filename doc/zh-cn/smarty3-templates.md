Friendica模板文档
=================

Friendica使用[Smarty 3](http://www.smarty.net/)作为PHP模板引擎。
主要模板文件位于

		/view/templates

主题作者可以通过将与默认模板同名的文件放入

		/view/themes/$themename/templates

目录中来覆盖默认模板。

仅由附加组件使用的模板应放置在

		/addon/$addonname/templates

目录中。

要呈现模板，请使用*getMarkupTemplate*函数加载模板，并使用*replaceMacros*替换刚刚加载的模板文件中的宏/变量。

		$tpl = Renderer::getMarkupTemplate('install_settings.tpl');
        $o .= Renderer::replaceMacros($tpl, array( ... ));

数组由标识符和该标识符的值的关联组成，例如：

		'$title' => $install_title,

其中该值也可能是一个数组。

表单模板
--------

为了确保输入表单（例如在设置部分中）具有一致的外观和感觉，有基本表单字段的模板。
它们使用数据数组进行初始化，具体取决于字段类型。

所有这些都需要一个包含值的数组，例如对于必须使用以邮件地址输入一行文本输入字段，请使用以下内容：

		'$adminmail' => array('adminmail', DI::l10n()->t('Site administrator email address'), $adminmail, DI::l10n()->t('Your account email address must match this in order to use the web admin panel.'), 'required', '', 'email'),

要评估输入值，您可以使用$_POST数组，更精确地说是$_POST['adminemail']变量。

下面列出了模板文件名称、模板的一般目的以及字段参数。

### field_checkbox.tpl

复选框。
如果选择复选框，则其值为**1**。
字段参数：

0. 复选框的名称，
1. 复选框的标签，
2. State checked？ 如果为true，则复选框将被标记为已选中，
3. 复选框的帮助文本。

### field_combobox.tpl

一个下拉选择和文本输入字段的组合框。
字段参数:

0. 组合框的名称，
1. 组合框的标签，
2. 变量的当前值，
3. 组合框的帮助文本，
4. 包含文本输入的可能值的数组，
5. 包含下拉选择的可能值的数组。

### field_custom.tpl

一个可定制模板，用于在表单中包含自定义元素，并具有通常的环境，
字段参数:

0. 字段的名称，
1. 字段的标签，
2. 字段，
3. 字段的帮助文本。

### field_input.tpl

一个适用于任何类型输入的单行输入字段。
字段参数:

0. 字段的名称，
1. 输入框的标签，
2. 变量的当前值，
3. 输入框的帮助文本，
4. 应设为将此字段标记为必填字段的“Required”的翻译，
5. 如果设置为“autofocus”，现代浏览器将在页面加载后将光标放入此框中，
6. 如果设置，将用于输入类型，默认为 `text`（可能的类型：https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#%3Cinput%3E_types）。

### field_intcheckbox.tpl

一个复选框（见上文），但您可以定义其值。
字段参数:

0. 复选框的名称，
1. 复选框的标签，
2. 状态是否选中？如果为true，则复选框将被标记为选中，
3. 复选框的值，
4. 复选框的帮助文本。

### field_openid.tpl

一个输入框（见上文），但准备了用于OpenID输入的特殊CSS样式。
字段参数:

0. 字段的名称，
1. 输入框的标签，
2. 变量的当前值，
3. 输入字段的帮助文本。

### field_password.tpl

一个适用于文本输入的单行输入字段（见上文）。
在浏览器中键入的字符不会显示。
字段参数：

0. 字段的名称，
1. 字段的标签，
2. 字段的值，例如旧密码，
3. 输入字段的帮助文本，
4. 应设为将此字段标记为必填字段的“Required”的翻译，
5. 如果设置为“autofocus”，现代浏览器将自动将光标放入此输入字段。

### field_radio.tpl

这是一个单选框。
字段参数：

0. 单选框的名称，
1. 单选框的标签，
2. 变量的当前值，
3. 按钮的帮助文本，
4. 如果设置了，单选框将被选中。

### field_richtext.tpl

这是一个多行输入框，用于*富文本*内容。
字段参数：

0. 输入框的名称，
1. 输入框的标签，
2. 输入框的当前文本内容，
3. 输入框的帮助文本。

### field_select.tpl

这是一个下拉选择框。
字段参数：

0. 字段的名称，
1. 选择框的标签，
2. 当前选中的值，
3. 选择框的帮助文本，
4. 包含可供选择的下拉选项的数组。

### field_select_raw.tpl

这也是一个下拉选择框（参见上文），但是您需要自己准备选项的值。
字段参数：

0. 字段的名称，
1. 选择框的标签，
2. 当前选中的值，
3. 选择框的帮助文本，
4. 下拉选择框的可选值。

### field_textarea.tpl

这是一个多行输入框，用于(纯)文本内容。
字段参数：

0. 输入框的名称，
1. 输入框的标签，
2. 输入框的当前文本内容，
3. 输入框的帮助文本，
4. 应该设置为"必填"的翻译，以将此字段标记为必填字段。