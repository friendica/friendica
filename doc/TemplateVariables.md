# Friendica Template Variables

## Introduction

Friendica utilizes the Smarty templating engine for rendering its user interface. Templates are typically `.tpl` files located in the `view/templates/` directory and within specific theme directories (e.g., `view/theme/frio/tpl/`).

Variables are passed from PHP controller logic to these Smarty templates. This is primarily handled by the `Friendica\Core\Renderer::replaceMacros()` method, which takes a template string and an associative array of variables to substitute.

In Smarty templates (`.tpl` files), variables are accessed using Smarty's syntax, such as `{{$variable}}` for simple variables or `{{$array.key}}` for elements within arrays/objects. The PHP code passes an associative array of data to the template engine, and the keys of this array become the top-level template variables.

This document aims to catalog commonly used variables available in Friendica templates.

**Note on Localization**: Many textual variables are localized in PHP using Friendica's internationalization system (e.g., `DI::l10n()->t()`) before being passed to templates. The 'Localized: Yes' note indicates that the string content is subject to translation.

## Main Categories of Variables

### Global/Site Variables

Variables generally available in most page templates.

*   `$baseurl` (String): The base URL of the Friendica installation. (Context: Most templates, e.g., `head.tpl`)
*   `$l10n` (Array): An array containing localized strings for the current language. Keys are original English strings (or keys), values are translations. (Context: Passed to many page-level templates like `head.tpl`, also available via `DI::l10n()` in PHP)
*   `$platform_name` (String): The name of the platform, typically "Friendica". (Context: `nav.tpl`, `login.tpl`)
*   `$site_name` (String): The configured name of the Friendica site. (Context: `login.tpl`, `register.tpl`)
*   `$site_info` (String): Additional information about the site. (Context: `login.tpl`)
*   `$banner` (String/HTML): Site banner/logo HTML. (Context: `nav.tpl`)
*   `$page_title` (String): The title of the current page. (Context: `head.tpl`)
*   `$max_file_size` (Integer): Maximum allowed file upload size in bytes. (Context: Upload forms, e.g. `photos_upload.tpl` implies this via `$usage`)
*   `$footerScripts` (Array): List of JavaScript files to be included in the footer. (Context: `footer.tpl`)
*   `$stylesheets` (Array): Associative array of stylesheet paths and their media types. (Context: `head.tpl`)
*   `$shortcut_icon` (String): URL for the site's shortcut icon (favicon). (Context: `head.tpl`)
*   `$touch_icon` (String): URL for the site's touch icon (for mobile devices). (Context: `head.tpl`)
*   `$generator` (String): Friendica version string. (Context: `head.tpl`)
*   `$qcommentWords` (Array): List of quick comment words, if qcomment addon is enabled. (Context: `comment_item.tpl` implies this via `$qcomment`)
*   `$request_id` (String): A unique ID for the current request (useful for debugging). (Context: Available in page templates if enabled by admin)

### Item Variables
Variables available when displaying a single item (post, comment, etc.), typically within an `$item` object/array passed to the template. These are largely based on `Item::DISPLAY_FIELDLIST` and processed by `Item::prepareBody()` and `Post::getTemplateData()`. Common context is `wall_thread.tpl`, `search_item.tpl`, `photo_view.tpl`.

*   `$item.id` (Integer): The unique ID of this specific item copy.
*   `$item.guid` (String): Globally Unique Identifier for the item.
*   `$item.type` (String): The verb of the item, often used for CSS classing (e.g., 'post', 'like', based on `$item.type` which is derived from `$item.verb`). (Context: `wall_thread.tpl`)
*   `$item.body_html` (String): The final, processed HTML content of the item for display, converted from BBCode. (Localized: Content itself is user-generated)
*   `$item.title` (String): Item title. (Localized: User-generated)
*   `$item.profile_url` (String): URL to the author's profile.
*   `$item.name` (String): Author's display name.
*   `$item.thumb` (String): URL to the author's avatar (typically 80x80 for wall items).
*   `$item.item_photo_menu_html` (String): HTML for the item's photo menu (if applicable, e.g., for photo items).
*   `$item.plink` (Array): Contains `href` (String) and `title` (String) for the permalink to the item. (Localized: title)
*   `$item.lock` (String|Boolean): String with lock status if private (e.g., "Private Message"), otherwise false. (Localized: String content)
*   `$item.vote` (Array): Contains arrays for like/dislike/share buttons with labels and states (e.g., `$item.vote.like` which is an array `[0 => "Like Text", 1 => "Unlike Text"]`). (Localized: labels)
*   `$item.responses` (Array): Contains activity summaries (e.g., `$item.responses.like.output`, `$item.responses.dislike.total`). (Localized: text parts like `$item.responses.like.title`)
*   `$item.emojis` (Array): List of emoji reactions, each with `icon` (object with `fa` and `icon` keys), `total` (count), `title` (localized string listing actors), and `emoji` (the emoji character). (Localized: titles)
*   `$item.quoteshares` (Array): Information about quote shares of the item, includes `total` and `title` (localized string listing actors).
*   `$item.comment_html` (String): HTML for the comment box, if applicable for the current user to comment on this item.
*   `$item.tags`, `$item.hashtags`, `$item.mentions` (Arrays of Objects): Processed tags for display. Each object typically contains `url` and `tag` (the term).
*   `$item.sparkle` (String): CSS class, often ' sparkle' if the author's profile is remote or requires redirection.
*   `$item.osparkle` (String): CSS class for the owner, similar to `$item.sparkle`, used in wall-to-wall scenarios.
*   `$item.localtime` (String): Item creation time in local human-readable format (e.g., "Mon, 01 Jan 2024 15:30:00 +0000").
*   `$item.utc` (String): Item creation time in UTC ISO 8601 format (e.g., "2024-01-01T15:30:00Z").
*   `$item.ago` (String): Relative time since item creation (e.g., "2 hours ago"). (Localized: Yes)
*   `$item.app` (String): Name of the application used to create the item, if any.
*   `$item.location_html` (String): HTML representation of the item's location, if provided.
*   `$item.indent` (String): CSS class for comment indentation (e.g., 'comment' if it's a reply).
*   `$item.owner_url`, `$item.owner_photo`, `$item.owner_name`: Profile URL, avatar, and name of the item owner (relevant for wall-to-wall posts where the author is different from the wall owner).
*   `$item.edpost` (Array|Boolean): Array with URL and label for editing if editable by the current user, else false. (Localized: label)
*   `$item.isstarred` (String): 'starred' or 'unstarred', indicating if the current user has starred the item.
*   `$item.star` (Array|Boolean): Array with labels and classes for the star/unstar action if applicable for the current user. (Localized: labels)
*   `$item.filer` (String|Boolean): Label for "Save to folder" action if applicable for the current user. (Localized: Yes)
*   `$item.drop` (Array|Boolean): Array with labels and states for item deletion options available to the current user. (Localized: labels)
*   `$item.block`, `$item.ignore_author`, `$item.collapse`, `$item.report`, `$item.ignore_server`: Arrays for moderation actions (block author, ignore author, etc.) with labels and target IDs/URLs. (Localized: labels)
*   `$item.previewing` (String): Contains ' preview ' if the item is a preview (e.g., during composition), else empty.
*   `$item.threaded` (Boolean): True if the item is part of a threaded view display.
*   `$item.toplevel` (String): Contains 'toplevel_item' if it's a top-level post in a thread, else empty.
*   `$item.total_comments_num` (Integer): Number of comments for a top-level post.
*   `$item.return_path` (String): Current page query string, often used in forms within items for redirection.
*   `$item.network_name` (String): Name of the network the post originated from (e.g., "Friendica", "Mastodon").
*   `$item.network_svg` (String): SVG icon HTML for the originating network.
*   `$item.direction` (Array): Contains `direction` (integer code) and `title` (string description) explaining why the post is shown to the user (e.g., "Reshared by X"). (Localized: title)
*   `$item.reshared` (String): Text indicating who reshared the item, if applicable (e.g., "User X reshared this."). (Localized: Yes)
*   `$item.delivery` (Array): Information about post delivery status, including `queue_count`, `queue_done`, and localized status messages like `notifier_pending`, `delivery_done`. (Localized: messages)
*   `$item.wall` (String): Localized string "Wall-to-Wall", displayed if the item is a wall-to-wall post. (Context: `wall_thread.tpl`) (Localized: Yes)
*   `$item.vwall` (String): Localized string "via Wall-To-Wall:", displayed before the owner's name in a wall-to-wall post. (Context: `wall_thread.tpl`) (Localized: Yes)
*   `$item.switchcomment` (String): Localized text for the comment toggle button (e.g., "Comment"). (Context: `wall_thread.tpl`) (Localized: Yes)
*   `$item.reply_label` (String): Localized text for replying, often includes the author's name (e.g., "Reply to John Doe"). (Context: `wall_thread.tpl`) (Localized: Yes)
*   `$item.menu` (String): Localized text for a "More" or context menu button associated with the item. (Context: `wall_thread.tpl`) (Localized: Yes)
*   `$item.isunknown` (String): Localized string like "Unknown parent" if the parent post of a comment is not accessible or visible. (Context: `wall_thread.tpl`) (Localized: Yes)
*   `$item.isunknown_label` (String): Localized explanatory string like "Parent is probably private or not federated." (Context: `wall_thread.tpl`) (Localized: Yes)
*   `$item.top_level_post` (Boolean): Generally true if `$item.toplevel` is 'toplevel_item'. Indicates it's not a comment. (Context: `wall_thread.tpl`)

### Contact/Profile Variables
Variables found on contact profile pages (e.g., from `BaseProfile::getTabsHTML()`, `contact_block.tpl`) or VCards (`widget/vcard.tpl`).

*   `$contact.id` (Integer): Contact's unique ID.
*   `$contact.name` (String): Contact's display name.
*   `$contact.url` (String): Contact's profile URL.
*   `$contact.addr` (String): Contact's address/handle (e.g., `user@example.com`).
*   `$photo` or `$contact.thumb` (String): URL to contact's avatar.
*   `$about` (String): Contact's bio/about section (HTML). (Localized: User-generated)
*   `$linkbuttons` (Array): Array of action buttons for the contact (e.g., Follow, Message). (Localized: labels)
*   `$editbuttons` (Array): Array of edit buttons if the profile is editable by the current user. (Localized: labels)
*   `$profile.nickname` (String): The nickname of the profile being viewed.
*   `$profile.fullname` (String): The full name of the profile owner.
*   `$profile.photo` (String): Profile photo URL.
*   `$profile.gender` (String): Gender. (Localized: Yes)
*   `$profile.marital` (String): Marital status. (Localized: Yes, and user-generated custom status)
*   `$profile.about` (String): About text. (Localized: User-generated)
*   `$profile.homepage` (String): Homepage URL.
*   `$profile.hometown` (String): Hometown. (Localized: User-generated)
*   `$profile.pdesc` (String): Profile description for public directory. (Localized: User-generated)
*   `$profile.location` (String): Current location. (Localized: User-generated)
*   `$relation_text` (String): Description of the relationship with the contact (e.g., "Is following you"). (Context: `contact_edit.tpl`) (Localized: Yes)
*   `$contact_actions` (Array): List of available actions (follow, block, etc.) with labels and URLs. (Context: `contact_edit.tpl`) (Localized: labels)


### Comment Form Variables
Variables used in `comment_item.tpl` or similar comment forms.

*   `$threaded` (Boolean): True if the comment form is in a threaded context.
*   `$id` (Integer): ID of the parent item being commented on.
*   `$parent` (Integer): Also the ID of the parent item.
*   `$profile_uid` (Integer): User ID of the profile owner where the comment is being made.
*   `$mylink` (String): Current commenter's profile link.
*   `$mytitle` (String): Current commenter's name (often used in a title attribute, e.g., "This is you"). (Localized: Yes)
*   `$myphoto` (String): Current commenter's avatar URL.
*   `$default` (String): Default/pre-filled text for the comment area (e.g. mentions).
*   `$comment` (String): Placeholder text or label for the comment area. (Localized: Yes)
*   `$submit` (String): Label for the submit button. (Localized: Yes)
*   `$preview` (String): Label for the preview button. (Localized: Yes)
*   `$loading` (String): Text shown during loading/submission. (Localized: Yes)
*   `$qcomment` (Array): Array of quick comment suggestions, if enabled.
*   `$return_path` (String): Current page URL for redirection after commenting.
*   `$jsreload` (String): URL for JavaScript reload after commenting.
*   `$rand_num` (String): A random number, often used for unique IDs in the form.
*   `$edbold`, `$editalic`, `$eduline`, `$contentwarn`, `$edquote`, `$edemojis`, `$edcode`, `$edimg`, `$edurl`, `$edattach`, `$prompttext`: Localized strings for editor button titles/prompts. (Localized: Yes)

### Widget-Specific Variables

*   **Group List Widget (`widget/group_list.tpl` via `src/Content/GroupManager.php`)**:
    *   `$title` (String): Widget title (e.g., "Groups"). (Localized: Yes)
    *   `$groups` (Array): List of group objects, each with:
        *   `$group.url` (String): URL to the group's conversation page.
        *   `$group.external_url` (String): External link to the group.
        *   `$group.name` (String): Name of the group.
        *   `$group.cid` (Integer): Contact ID of the group.
        *   `$group.micro` (String): URL to the group's micro avatar.
        *   `$group.id` (Integer): A sequential ID for the list item.
    *   `$link_desc` (String): Description for the external link icon. (Localized: Yes)
    *   `$showless`, `$showmore` (Strings): Labels for toggle to show more/less groups. (Localized: Yes)
    *   `$create_new_group` (String): Label for the "Create new group" link. (Localized: Yes)

*   **Photo Albums Widget (`photo_albums.tpl` via `mod/photos.php`)**:
    *   `$nick` (String): Nickname of the photo album owner.
    *   `$title` (String): Widget title (e.g., "Photo Albums"). (Localized: Yes)
    *   `$recent` (String): Label for "Recent Photos" link. (Localized: Yes)
    *   `$albums` (Array): List of album objects, each with `text` (album name), `total` (photo count), `url`, `urlencode`, `bin2hex`.
    *   `$upload` (Array): Contains label and URL for "Upload New Photos". (Localized: label)
    *   `$can_post` (Boolean): Whether the current user can upload photos.

*   **Pagination Widget (`paginate.tpl` via `src/Content/Pager.php` & `src/Content/BoundariesPager.php`)**:
    *   `$pager` (Array): Contains pagination data:
        *   `$pager.class` (String): CSS class for the pager.
        *   `$pager.prev` (Array): Contains `url`, `text`, `class` for the "previous/newer" link. (Localized: text)
        *   `$pager.next` (Array): Contains `url`, `text`, `class` for the "next/older" link. (Localized: text)
        *   `$pager.first` (Array, full pager only): Contains `url`, `text`, `class` for "first" link. (Localized: text)
        *   `$pager.last` (Array, full pager only): Contains `url`, `text`, `class` for "last" link. (Localized: text)
        *   `$pager.pages` (Array, full pager only): List of page number links, each an array with `url`, `text`, `class`.

### Navigation/Page Structure Variables
(From `nav.tpl`, `head.tpl`, `footer.tpl` via `src/App/Page.php`)

*   `$sitelocation` (String): String representing the current site and user (e.g., `username@example.com`). (Context: `nav.tpl`)
*   `$nav` (Array): Data structure for building the main navigation menu. Contains keys like `admin`, `home`, `network`, `messages`, `settings`, etc., where each value is an array with `[url, text, css_classes, title]`. (Context: `nav.tpl`) (Localized: text and title elements)
*   `$emptynotifications` (String): Message displayed when there are no notifications. (Context: `nav.tpl`) (Localized: Yes)
*   `$userinfo` (Array|null): Contains `icon` (avatar URL) and `name` for the logged-in user, or null if not logged in. (Context: `nav.tpl`)
*   `$sel` (Array): Associative array where keys are navigation item names (e.g., `home`, `network`) and values are 'selected' if that item is active. (Context: `nav.tpl`)
*   `$apps` (Array): List of addon application menu items. (Context: `nav.tpl`)
*   `$home` (String): Localized text for "Home". (Context: `nav.tpl`) (Localized: Yes)
*   `$skip` (String): Localized text for "Skip to main content". (Context: `nav.tpl`) (Localized: Yes)
*   `$clear_notifs` (String): Localized text for "Clear notifications". (Context: `nav.tpl`) (Localized: Yes)
*   `$search_hint` (String): Placeholder/hint text for the search bar. (Context: `nav.tpl`) (Localized: Yes)
*   `$toggle_link` (String): URL for toggling mobile view. (Context: `toggle_mobile_footer.tpl`)
*   `$toggle_text` (String): Text for the mobile view toggle link. (Context: `toggle_mobile_footer.tpl`) (Localized: Yes)
*   `$close` (String): Localized text for "Close" button (e.g., in modals). (Context: `footer.tpl`) (Localized: Yes)

### Form Variables (General)
Common across many settings pages and forms.

*   `$form_security_token` (String): Security token to prevent CSRF attacks, needs to be included in POST requests.
*   `$title` or `$ptitle` (String): Often used as the main title or heading for a form page. (Localized: Yes)
*   `$submit` (String): Common variable for the text of the primary submit button. (Localized: Yes)
*   Many forms also pass arrays where the first element is the field name, second is the localized label, third is the current value, fourth is help text (localized), and fifth might be options for selects/radios (localized). Examples:
    *   `$theme` in `settings/display.tpl`: `['theme', $this->t('Display Theme:'), $theme_selected, '', $themes, true]`
    *   `$username` in `register.tpl`: `['username', DI::l10n()->t('Username'), $_POST['username'] ?? '']`

---

*This document is not exhaustive but aims to cover a significant portion of commonly encountered template variables. Specific modules or addons may introduce their own sets of variables.*
