Friendica Addon development
==============

* [Home](help)

Please see the sample addon 'randplace' for a working example of using some of these features.
Addons work by intercepting event hooks - which must be registered.
Modules work by intercepting specific page requests (by URL path).

## Naming

Addon names are used in file paths and functions names, and as such:
- Can't contain spaces or punctuation.
- Can't start with a number.

## Metadata

You can provide human-readable information about your addon in the first multi-line comment of your addon file.

Here's the structure:

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

You can also provide a longer documentation in a `README` or `README.md` file.
The latter will be converted from Markdown to HTML in the addon detail page.

## Install/Uninstall

If your addon uses hooks, they have to be registered in a `<addon>_install()` function.
This function also allows to perform arbitrary actions your addon needs to function properly.

Uninstalling an addon automatically unregisters any hook it registered, but if you need to provide specific uninstallation steps, you can add them in a `<addon>_uninstall()` function.

The install and uninstall functions will be called (i.e. re-installed) if the addon changes after installation.
Therefore your uninstall should not destroy data and install should consider that data may already exist.
Future extensions may provide for "setup" amd "remove".

## PHP addon hooks

Register your addon hooks during installation.

    \Friendica\Core\Hook::register($hookname, $file, $function);

`$hookname` is a string and corresponds to a known Friendica PHP hook.

`$file` is a pathname relative to the top-level Friendica directory.
This *should* be 'addon/*addon_name*/*addon_name*.php' in most cases and can be shortened to `__FILE__`.

`$function` is a string and is the name of the function which will be executed when the hook is called.

### Arguments
Your hook callback functions will be called with at least one and possibly two arguments

    function <addon>_<hookname>(App $a, &$b) {

    }

If you wish to make changes to the calling data, you must declare them as reference variables (with `&`) during function declaration.

#### $a
$a is the Friendica `App` class.
It contains a wealth of information about the current state of Friendica:

* which module has been called,
* configuration information,
* the page contents at the point the hook was invoked,
* profile and user information, etc.

It is recommeded you call this `$a` to match its usage elsewhere.

#### $b
$b can be called anything you like.
This is information specific to the hook currently being processed, and generally contains information that is being immediately processed or acted on that you can use, display, or alter.
Remember to declare it with `&` if you wish to alter it.

## Admin settings

Your addon can provide user-specific settings via the `addon_settings` PHP hook, but it can also provide node-wide settings in the administration page of your addon.

Simply declare a `<addon>_addon_admin(App $a)` function to display the form and a `<addon>_addon_admin_post(App $a)` function to process the data from the form.

## Global stylesheets

If your addon requires adding a stylesheet on all pages of Friendica, add the following hook:

```php
function <addon>_install()
{
	\Friendica\Core\Hook::register('head', __FILE__, '<addon>_head');
	...
}


function <addon>_head(App $a)
{
	\Friendica\DI::page()->registerStylesheet(__DIR__ . '/relative/path/to/addon/stylesheet.css');
}
```

`__DIR__` is the folder path of your addon.

## JavaScript

### Global scripts

If your addon requires adding a script on all pages of Friendica, add the following hook:


```php
function <addon>_install()
{
	\Friendica\Core\Hook::register('footer', __FILE__, '<addon>_footer');
	...
}

function <addon>_footer(App $a)
{
	\Friendica\DI::page()->registerFooterScript(__DIR__ . '/relative/path/to/addon/script.js');
}
```

`__DIR__` is the folder path of your addon.

### JavaScript hooks

The main Friendica script provides hooks via events dispatched on the `document` property.
In your Javascript file included as described above, add your event listener like this:

```js
document.addEventListener(name, callback);
```

- *name* is the name of the hook and corresponds to a known Friendica JavaScript hook.
- *callback* is a JavaScript anonymous function to execute.

More info about Javascript event listeners: https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener

#### Current JavaScript hooks

##### postprocess_liveupdate
Called at the end of the live update process (XmlHttpRequest) and on a post preview.
No additional data is provided.

## Modules

Addons may also act as "modules" and intercept all page requests for a given URL path.
In order for a addon to act as a module it needs to declare an empty function `<addon>_module()`.

If this function exists, you will now receive all page requests for `https://my.web.site/<addon>` - with any number of URL components as additional arguments.
These are parsed into the `App\Arguments` object.
So `https://my.web.site/addon/arg1/arg2` would give this:
```php
DI::args()->getArgc(); // = 3
DI::args()->get(0); // = 'addon'
DI::args()->get(1); // = 'arg1'
DI::args()->get(2); // = 'arg2'
```

To display a module page, you need to declare the function `<addon>_content(App $a)`, which defines and returns the page body content.
They may also contain `<addon>_post(App $a)` which is called before the `<addon>_content` function and typically handles the results of POST forms.
You may also have `<addon>_init(App $a)` which is called before `<addon>_content` and should include common logic to your module.

## Templates

If your addon needs some template, you can use the Friendica template system.
Friendica uses [smarty3](http://www.smarty.net/) as a template engine.

Put your tpl files in the *templates/* subfolder of your addon.

In your code, like in the function addon_name_content(), load the template file and execute it passing needed values:

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

See also the wiki page [Quick Template Guide](https://github.com/friendica/friendica/wiki/Quick-Template-Guide).

## Current PHP hooks

### authenticate
Called when a user attempts to login.
`$b` is an array containing:

- **username**: the supplied username
- **password**: the supplied password
- **authenticated**: set this to non-zero to authenticate the user.
- **user_record**: successful authentication must also return a valid user record from the database

### logged_in
Called after a user has successfully logged in.
`$b` contains the `$a->user` array.

### display_item
Called when formatting a post for display.
$b is an array:

- **item**: The item (array) details pulled from the database
- **output**: the (string) HTML representation of this item prior to adding it to the page

### post_local
Called when a status post or comment is entered on the local system.
`$b` is the item array of the information to be stored in the database.
Please note: body contents are bbcode - not HTML.

### post_local_end
Called when a local status post or comment has been stored on the local system.
`$b` is the item array of the information which has just been stored in the database.
Please note: body contents are bbcode - not HTML

### post_remote
Called when receiving a post from another source. This may also be used to post local activity or system generated messages.
`$b` is the item array of information to be stored in the database and the item body is bbcode.

### addon_settings
Called when generating the HTML for the addon settings page.
`$data` is an array containing:

- **addon** (output): Required. The addon folder name.
- **title** (output): Required. The addon settings panel title.
- **href** (output): Optional. If set, will reduce the panel to a link pointing to this URL, can be relative. Incompatible with the following keys.
- **html** (output): Optional. Raw HTML of the addon form elements. Both the `<form>` tags and the submit buttons are taken care of elsewhere.
- **submit** (output): Optional. If unset, a default submit button with `name="<addon name>-submit"` will be generated.
  Can take different value types:
  - **string**: The label to replace the default one.
  - **associative array**: A list of submit button, the key is the value of the `name` attribute, the value is the displayed label.
    The first submit button in this list is considered the main one and themes might emphasize its display.

#### Examples

##### With link
```php
$data = [
	'addon' => 'advancedcontentfilter',
	'title' => DI::l10n()->t('Advanced Content Filter'),
	'href'  => 'advancedcontentfilter',
];
```
##### With default submit button
```php
$data = [
	'addon' => 'fromapp',
	'title' => DI::l10n()->t('FromApp Settings'),
	'html'  => $html,
];
```
##### With no HTML, just a submit button
```php
$data = [
	'addon'  => 'opmlexport',
	'title'  => DI::l10n()->t('OPML Export'),
	'submit' => DI::l10n()->t('Export RSS/Atom contacts'),
];
```
##### With multiple submit buttons
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
Called when the Addon Settings pages are submitted.
`$b` is the $_POST array.

### connector_settings
Called when generating the HTML for a connector addon settings page.
`$data` is an array containing:

- **connector** (output): Required. The addon folder name.
- **title** (output): Required. The addon settings panel title.
- **image** (output): Required. The relative path of the logo image of the platform/protocol this addon is connecting to, max size 48x48px.
- **enabled** (output): Optional. If set to a falsy value, the connector image will be dimmed.
- **html** (output): Optional. Raw HTML of the addon form elements. Both the `<form>` tags and the submit buttons are taken care of elsewhere.
- **submit** (output): Optional. If unset, a default submit button with `name="<addon name>-submit"` will be generated.
  Can take different value types:
    - **string**: The label to replace the default one.
      - **associative array**: A list of submit button, the key is the value of the `name` attribute, the value is the displayed label.
        The first submit button in this list is considered the main one and themes might emphasize its display.

#### Examples

##### With default submit button
```php
$data = [
	'connector' => 'diaspora',
	'title'     => DI::l10n()->t('Diaspora Export'),
	'image'     => 'images/diaspora-logo.png',
	'enabled'   => $enabled,
	'html'      => $html,
];
```

##### With custom submit button label and no logo dim
```php
$data = [
	'connector' => 'ifttt',
	'title'     => DI::l10n()->t('IFTTT Mirror'),
	'image'     => 'addon/ifttt/ifttt.png',
	'html'      => $html,
	'submit'    => DI::l10n()->t('Generate new key'),
];
```

##### With conditional submit buttons
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
Called when posting a profile page.
`$b` is the $_POST array.

### profile_edit
Called prior to output of profile edit page.
`$b` is an array containing:

- **profile**: profile (array) record from the database
- **entry**: the (string) HTML of the generated entry

### profile_advanced
Called when the HTML is generated for the Advanced profile, corresponding to the Profile tab within a person's profile page.
`$b` is the HTML string representation of the generated profile.
The profile array details are in `$a->profile`.

### directory_item
Called from the Directory page when formatting an item for display.
`$b` is an array:

- **contact**: contact record array for the person from the database
- **entry**: the HTML string of the generated entry

### profile_sidebar_enter
Called prior to generating the sidebar "short" profile for a page.
`$b` is the person's profile array

### profile_sidebar
Called when generating the sidebar "short" profile for a page.
`$b` is an array:

- **profile**: profile record array for the person from the database
- **entry**: the HTML string of the generated entry

### contact_block_end
Called when formatting the block of contacts/friends on a profile sidebar has completed.
`$b` is an array:

- **contacts**: array of contacts
- **output**: the generated HTML string of the contact block

### bbcode
Called after conversion of bbcode to HTML.
`$b` is an HTML string converted text.

### html2bbcode
Called after tag conversion of HTML to bbcode (e.g. remote message posting)
`$b` is a string converted text

### head
Called when building the `<head>` sections.
Stylesheets should be registered using this hook.
`$b` is an HTML string of the `<head>` tag.

### page_header
Called after building the page navigation section.
`$b` is a string HTML of nav region.

### personal_xrd
Called prior to output of personal XRD file.
`$b` is an array:

- **user**: the user record array for the person
- **xml**: the complete XML string to be output

### home_content
Called prior to output home page content, shown to unlogged users.
`$b` is the HTML sring of section region.

### contact_edit
Called when editing contact details on an individual from the Contacts page.
$b is an array:

- **contact**: contact record (array) of target contact
- **output**: the (string) generated HTML of the contact edit page

### contact_edit_post
Called when posting the contact edit page.
`$b` is the `$_POST` array

### init_1
Called just after DB has been opened and before session start.
No hook data.

### page_end
Called after HTML content functions have completed.
`$b` is (string) HTML of content div.

### footer
Called after HTML content functions have completed.
Deferred Javascript files should be registered using this hook.
`$b` is (string) HTML of footer div/element.

### avatar_lookup
Called when looking up the avatar. `$b` is an array:

- **size**: the size of the avatar that will be looked up
- **email**: email to look up the avatar for
- **url**: the (string) generated URL of the avatar

### emailer_send_prepare
Called from `Emailer::send()` before building the mime message.
`$b` is an array of params to `Emailer::send()`.

- **fromName**: name of the sender
- **fromEmail**: email fo the sender
- **replyTo**: replyTo address to direct responses
- **toEmail**: destination email address
- **messageSubject**: subject of the message
- **htmlVersion**: html version of the message
- **textVersion**: text only version of the message
- **additionalMailHeader**: additions to the smtp mail header
- **sent**: default false, if set to true in the hook, the default mailer will be skipped.

### emailer_send
Called before calling PHP's `mail()`.
`$b` is an array of params to `mail()`.

- **to**
- **subject**
- **body**
- **headers**
- **sent**: default false, if set to true in the hook, the default mailer will be skipped.

### load_config
Called during `App` initialization to allow addons to load their own configuration file(s) with `App::loadConfigFile()`.

### nav_info
Called after the navigational menu is build in `include/nav.php`.
`$b` is an array containing `$nav` from `include/nav.php`.

### template_vars
Called before vars are passed to the template engine to render the page.
The registered function can add,change or remove variables passed to template.
`$b` is an array with:

- **template**: filename of template
- **vars**: array of vars passed to the template

### acl_lookup_end
Called after the other queries have passed.
The registered function can add, change or remove the `acl_lookup()` variables.

- **results**: array of the acl_lookup() vars

### prepare_body_init
Called at the start of prepare_body
Hook data:

- **item** (input/output): item array

### prepare_body_content_filter
Called before the HTML conversion in prepare_body. If the item matches a content filter rule set by an addon, it should
just add the reason to the filter_reasons element of the hook data.
Hook data:

- **item**: item array (input)
- **filter_reasons** (input/output): reasons array

### prepare_body
Called after the HTML conversion in `prepare_body()`.
Hook data:

- **item** (input): item array
- **html** (input/output): converted item body
- **is_preview** (input): post preview flag
- **filter_reasons** (input): reasons array

### prepare_body_final
Called at the end of `prepare_body()`.
Hook data:

- **item**: item array (input)
- **html**: converted item body (input/output)

### put_item_in_cache
Called after `prepare_text()` in `put_item_in_cache()`.
Hook data:

- **item** (input): item array
- **rendered-html** (input/output): final item body HTML
- **rendered-hash** (input/output): original item body hash

### magic_auth_success
Called when a magic-auth was successful.
Hook data:

    visitor => array with the contact record of the visitor
    url => the query string

### jot_networks
Called when displaying the post permission screen.
Hook data is a list of form fields that need to be displayed along the ACL.
Form field array structure is:

- **type**: `checkbox` or `select`.
- **field**: Standard field data structure to be used by `field_checkbox.tpl` and `field_select.tpl`.

For `checkbox`, **field** is:
  - [0] (String): Form field name; Mandatory.
  - [1]: (String): Form field label; Optional, default is none.
  - [2]: (Boolean): Whether the checkbox should be checked by default; Optional, default is false.
  - [3]: (String): Additional help text; Optional, default is none.
  - [4]: (String): Additional HTML attributes; Optional, default is none.

For `select`, **field** is:
  - [0] (String): Form field name; Mandatory.
  - [1] (String): Form field label; Optional, default is none.
  - [2] (Boolean): Default value to be selected by default; Optional, default is none.
  - [3] (String): Additional help text; Optional, default is none.
  - [4] (Array): Associative array of options. Item key is option value, item value is option label; Mandatory.

### route_collection
Called just before dispatching the router.
Hook data is a `\FastRoute\RouterCollector` object that should be used to add addon routes pointing to classes.

**Notice**: The class whose name is provided in the route handler must be reachable via auto-loader.

### probe_detect

Called before trying to detect the target network of a URL.
If any registered hook function sets the `result` key of the hook data array, it will be returned immediately.
Hook functions should also return immediately if the hook data contains an existing result.

Hook data:

- **uri** (input): the profile URI.
- **network** (input): the target network (can be empty for auto-detection).
- **uid** (input): the user to return the contact data for (can be empty for public contacts).
- **result** (output): Leave null if address isn't relevant to the connector, set to contact array if probe is successful, false otherwise.

### item_by_link

Called when trying to probe an item from a given URI.
If any registered hook function sets the `item_id` key of the hook data array, it will be returned immediately.
Hook functions should also return immediately if the hook data contains an existing `item_id`.

Hook data:
- **uri** (input): the item URI.
- **uid** (input): the user to return the item data for (can be empty for public contacts).
- **item_id** (output): Leave null if URI isn't relevant to the connector, set to created item array if probe is successful, false otherwise.

### support_follow

Called to assert whether a connector addon provides follow capabilities.

Hook data:
- **protocol** (input): shorthand for the protocol. List of values is available in `src/Core/Protocol.php`.
- **result** (output): should be true if the connector provides follow capabilities, left alone otherwise.

### support_revoke_follow

Called to assert whether a connector addon provides follow revocation capabilities.

Hook data:
- **protocol** (input): shorthand for the protocol. List of values is available in `src/Core/Protocol.php`.
- **result** (output): should be true if the connector provides follow revocation capabilities, left alone otherwise.

### follow

Called before adding a new contact for a user to handle non-native network remote contact (like Twitter).

Hook data:

- **url** (input): URL of the remote contact.
- **contact** (output): should be filled with the contact (with uid = user creating the contact) array if follow was successful.

### unfollow

Called when unfollowing a remote contact on a non-native network (like Twitter)

Hook data:
- **contact** (input): the target public contact (uid = 0) array.
- **uid** (input): the id of the source local user.
- **result** (output): wether the unfollowing is successful or not.

### revoke_follow

Called when making a remote contact on a non-native network (like Twitter) unfollow you.

Hook data:
- **contact** (input): the target public contact (uid = 0) array.
- **uid** (input): the id of the source local user.
- **result** (output): a boolean value indicating wether the operation was successful or not.

### block

Called when blocking a remote contact on a non-native network (like Twitter).

Hook data:
- **contact** (input): the remote contact (uid = 0) array.
- **uid** (input): the user id to issue the block for.
- **result** (output): a boolean value indicating wether the operation was successful or not.

### unblock

Called when unblocking a remote contact on a non-native network (like Twitter).

Hook data:
- **contact** (input): the remote contact (uid = 0) array.
- **uid** (input): the user id to revoke the block for.
- **result** (output): a boolean value indicating wether the operation was successful or not.

### storage_instance

Called when a custom storage is used (e.g. webdav_storage)

Hook data:
- **name** (input): the name of the used storage backend
- **data['storage']** (output): the storage instance to use (**must** implement `\Friendica\Core\Storage\IWritableStorage`) 

### storage_config

Called when the admin of the node wants to configure a custom storage (e.g. webdav_storage)

Hook data:
- **name** (input): the name of the used storage backend
- **data['storage_config']** (output): the storage configuration instance to use (**must** implement `\Friendica\Core\Storage\Capability\IConfigureStorage`)

## Complete list of hook callbacks

Here is a complete list of all hook callbacks with file locations (as of 24-Sep-2018). Please see the source for details of any hooks not documented above.

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
