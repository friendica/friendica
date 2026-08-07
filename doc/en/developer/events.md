# Events

Friendica uses the PSR-14 EventDispatcher for extensibility. Addons and themes can subscribe to events to modify data or react to application state changes.

## Migrating from Hooks

Legacy hooks registered via `\Friendica\Core\Hook::register()` are still supported through the `HookEventBridge`. In the future, addons will be able to use PSR-14 events directly.

| Legacy Hook | Event Class |
|-------------|-------------|
| `init_1` | `\Friendica\Event\InitEvent` |
| `home_init` | `\Friendica\Event\HomeInitEvent` |
| `logging_out` | `\Friendica\Event\LoggingOutEvent` |
| `display_item` | `\Friendica\Event\DisplayItemEvent` |
| `post_local` | `\Friendica\Event\InsertPostLocalEvent` |
| `post_local_end` | `\Friendica\Event\InsertPostLocalEndEvent` |
| `post_remote` | `\Friendica\Event\InsertPostRemoteEvent` |
| `post_remote_end` | `\Friendica\Event\InsertPostRemoteEndEvent` |
| `prepare_body_init` | `\Friendica\Event\PreparePostStartEvent` |
| `prepare_body_content_filter` | `\Friendica\Event\PreparePostFilterContentEvent` |
| `prepare_body` | `\Friendica\Event\PreparePostEvent` |
| `prepare_body_final` | `\Friendica\Event\PreparePostEndEvent` |
| `display_item` | `\Friendica\Event\DisplayItemEvent` |
| `put_item_in_cache` | `\Friendica\Event\CacheItemEvent` |
| `check_item_notification` | `\Friendica\Event\CheckItemNotificationEvent` |
| `conversation_start` | `\Friendica\Event\ConversationStartEvent` |
| `item_by_link` | `\Friendica\Event\FetchItemByLinkEvent` |
| `tagged` | `\Friendica\Event\ItemTaggedEvent` |
| `item_photo_menu` | `\Friendica\Event\ItemPhotoMenuEvent` |
| `directory_item` | `\Friendica\Event\DirectoryItemEvent` |
| `notifier_end` | `\Friendica\Event\NotifierEndEvent` |
| `ocr-detection` | `\Friendica\Event\OcrDetectionEvent` |
| `network_to_name` | `\Friendica\Event\NetworkToNameEvent` |
| `network_content_init` | `\Friendica\Event\NetworkContentStartEvent` |
| `network_tabs` | `\Friendica\Event\NetworkContentTabsEvent` |
| `parse_link` | `\Friendica\Event\ParseLinkEvent` |
| `render_location` | `\Friendica\Event\RenderLocationEvent` |
| `enotify` | `\Friendica\Event\EnotifyEvent` |
| `enotify_mail` | `\Friendica\Event\EnotifyMailEvent` |
| `enotify_store` | `\Friendica\Event\EnotifyStoreEvent` |
| `photo_post_init` | `\Friendica\Event\PhotoUploadStartEvent` |
| `photo_post_file` | `\Friendica\Event\PhotoUploadEvent` |
| `photo_post_end` | `\Friendica\Event\PhotoUploadEndEvent` |
| `head` | `\Friendica\Event\HeadEvent` |
| `footer` | `\Friendica\Event\FooterEvent` |
| `login_hook` | `\Friendica\Event\LoginFormEvent` |
| `logged_in` | `\Friendica\Event\LoggedInEvent` |
| `authenticate` | `\Friendica\Event\AccountAuthenticateEvent` |
| `register_form` | `\Friendica\Event\AccountRegisterFormEvent` |
| `register_post` | `\Friendica\Event\AccountRegisterPostEvent` |
| `register_account` | `\Friendica\Event\AccountRegisterEvent` |
| `remove_user` | `\Friendica\Event\AccountRemoveEvent` |
| `magic_auth_success` | `\Friendica\Event\MagicAuthSuccessEvent` |
| `zrl_init` | `\Friendica\Event\ZrlInitEvent` |

See the individual event documentation below for the full list.

## Current Events

### `\Friendica\Event\InitEvent`

Fired when Friendica is initialized.

**Contained data:** None (notification-only event).

### `\Friendica\Event\HomeInitEvent`

Fired once the home page is visited.

**Contained data:** None (notification-only event).

### `\Friendica\Event\LoggingOutEvent`

Fired when a user is logging out.

**Contained data:** None (notification-only event).

### `\Friendica\Event\AccountAuthenticateEvent`

Fired when a user attempts to login.

**Contained data:**
- `getUsername(): string` — the supplied username
- `getPassword(): string` — the supplied password
- `isAuthenticated(): bool` — set to true to authenticate the user
- `getUserRecordArray(): ?array` — successful authentication must also return a valid user record

**Modifiable:**
- `setAuthenticated(bool $authenticated): void` — set to true to authenticate
- `setUserRecordArray(?array $userRecord): void` — set the user record on success

### `\Friendica\Event\AccountRegisterFormEvent`

Fired when the registration form is displayed.

**Contained data:**
- `getMarkupTemplate(): string` — the template markup (before macro replacement)

**Modifiable:**
- `setMarkupTemplate(string $template): void` — change the template markup

### `\Friendica\Event\AccountRegisterPostEvent`

Fired when the registration form is submitted.

**Contained data:**
- `getPostArray(): array` — the submitted POST data

**Modifiable:**
- `setPostArray(array $post): void` — change the POST data

### `\Friendica\Event\AccountRegisterEvent`

Fired when a new user account has been registered.

**Contained data:**
- `getUserId(): int` — the user ID

**Modifiable:**
- `setUserId(int $uid): void` — change the user ID

### `\Friendica\Event\AccountRemoveEvent`

Fired when a user account is being removed.

**Contained data:**
- `getUserArray(): array` — the user record array

**Modifiable:**
- `setUserArray(array $user): void` — change the user record

### `\Friendica\Event\LoggedInEvent`

Fired when a user has logged in.

**Contained data:**
- `getRecordArray(): array` — the user record array

**Modifiable:**
- `setRecordArray(array $record): void` — change the user record

### `\Friendica\Event\LoginFormEvent`

Fired when the login form is displayed.

**Contained data:**
- `getHtml(): string` — the login form HTML

**Modifiable:**
- `setHtml(string $html): void` — change the login form HTML

### `\Friendica\Event\MagicAuthSuccessEvent`

Fired when a magic-auth (OpenWebAuth) was successful.

**Contained data:**
- `getVisitorArray(): array` — the visitor contact array
- `getUrl(): string` — the query string of the request

**Modifiable:**
- `setVisitorArray(array $visitor): void` — change the visitor array

### `\Friendica\Event\ZrlInitEvent`

Fired when a ZRL init is triggered.

**Contained data:**
- `getZrlUrl(): string` — the ZRL URL
- `getUrl(): string` — the command URL

### `\Friendica\Event\InsertPostLocalEvent`

Fired when a local post is being inserted.

**Contained data:**
- `getItemArray(): array` — the item record array

**Modifiable:**
- `setItemArray(array $item): void` — change the item record

### `\Friendica\Event\InsertPostLocalEndEvent`

Fired after a local post has been inserted.

**Contained data:**
- `getItemArray(): array` — the item record array

**Modifiable:**
- `setItemArray(array $item): void` — change the item record

### `\Friendica\Event\InsertPostRemoteEvent`

Fired when a remote post is being inserted locally.

**Contained data:**
- `getItemArray(): array` — the item record array

**Modifiable:**
- `setItemArray(array $item): void` — change the item record

### `\Friendica\Event\InsertPostRemoteEndEvent`

Fired after a remote post has been inserted locally.

**Contained data:**
- `getItemArray(): array` — the item record array

**Modifiable:**
- `setItemArray(array $item): void` — change the item record

### `\Friendica\Event\PreparePostStartEvent`

Fired before a post is being prepared for display.

**Contained data:**
- `getItemArray(): array` — the item record array

**Modifiable:**
- `setItemArray(array $item): void` — change the item record

### `\Friendica\Event\PreparePostFilterContentEvent`

Fired before content filtering is applied to a post.

**Contained data:**
- `getItemArray(): array` — the item record array (read-only)
- `getUserId(): int` — the user ID (read-only)
- `getFilterReasons(): array` — the filter reasons array

**Modifiable:**
- `setFilterReasons(array $filterReasons): void` — change the filter reasons

### `\Friendica\Event\PreparePostEvent`

Fired when a post is being prepared for display.

**Contained data:**
- `getItemArray(): array` — the item record array (read-only)
- `getHtml(): string` — the rendered HTML
- `isPreview(): bool` — whether this is a preview
- `getFilterReasons(): array` — the filter reasons array (read-only)

**Modifiable:**
- `setHtml(string $html): void` — change the rendered HTML

### `\Friendica\Event\PreparePostEndEvent`

Fired after a post has been prepared for display.

**Contained data:**
- `getItemArray(): array` — the item record array (read-only)
- `getHtml(): string` — the rendered HTML

**Modifiable:**
- `setHtml(string $html): void` — change the rendered HTML

### `\Friendica\Event\DisplayItemEvent`

Fired when formatting a post for display.

**Contained data:**
- `getItemArray(): array` — the item record array (read-only)
- `getTemplateDataArray(): array` — the template data array

**Modifiable:**
- `setTemplateDataArray(array $output): void` — change the template data

### `\Friendica\Event\CacheItemEvent`

Fired when an item's rendered HTML is being stored in the cache.

**Contained data:**
- `getItemArray(): array` — the item record array (read-only)
- `getRenderedHtml(): string` — the rendered HTML of the item
- `getRenderedHash(): string` — the hash of the rendered HTML

**Modifiable:**
- `setRenderedHtml(string $renderedHtml): void` — change the cached HTML
- `setRenderedHash(string $renderedHash): void` — change the cached hash

### `\Friendica\Event\CheckItemNotificationEvent`

Fired when checking item notifications for a user.

**Contained data:**
- `getUserId(): int` — the user ID (read-only)
- `getProfilesArray(): array` — the list of profiles to check

**Modifiable:**
- `setProfilesArray(array $profiles): void` — change the list of profiles

### `\Friendica\Event\ConversationStartEvent`

Fired when rendering a conversation timeline starts.

**Contained data:**
- `getItemsArray(): array` — the items of the conversation timeline
- `getMode(): string` — the rendering mode (read-only)
- `isUpdate(): bool` — whether this is an AJAX update (read-only)
- `isPreview(): bool` — whether to render in preview mode (read-only)

**Modifiable:**
- `setItemsArray(array $items): void` — change the items of the conversation timeline

### `\Friendica\Event\FetchItemByLinkEvent`

Fired when trying to probe an item from a given URI.

**Contained data:**
- `getUri(): string` — the item URI (read-only)
- `getUserId(): int` — the user to return the item data for (read-only)
- `getItemId(): ?int` — the created item ID if the probe was successful, null otherwise

**Modifiable:**
- `setItemId(?int $itemId): void` — set the fetched item ID

### `\Friendica\Event\ItemTaggedEvent`

Fired when an item is tagged (e.g. mentioned) by a community owner.

**Contained data:**
- `getItemArray(): array` — the tagged item record (read-only)
- `getUserArray(): array` — the tagging user record (read-only)

### `\Friendica\Event\ItemPhotoMenuEvent`

Fired when building the photo menu of an item.

**Contained data:**
- `getItemArray(): array` — the item record (read-only)
- `getMenuArray(): array` — the menu entries as `label => link`

**Modifiable:**
- `setMenuArray(array $menu): void` — change the menu entries

### `\Friendica\Event\DirectoryItemEvent`

Fired when formatting an item for display on the directory page.

**Contained data:**
- `getContactArray(): array` — the contact record (read-only)
- `getEntryArray(): array` — the directory entry

**Modifiable:**
- `setEntryArray(array $entry): void` — change the directory entry

### `\Friendica\Event\NotifierEndEvent`

Fired after the notifier has processed an item.

**Contained data:**
- `getItemArray(): array` — the processed item record (read-only)

### `\Friendica\Event\OcrDetectionEvent`

Fired when OCR detection is run on an image.

**Contained data:**
- `getImgStr(): string` — the binary image data (read-only)
- `getDescription(): ?string` — the detected image description, `null` if none has been detected yet

**Modifiable:**
- `setDescription(string $description): void` — set the detected image description

### `\Friendica\Event\NetworkToNameEvent`

Fired when the name of a network is being determined.

**Contained data:**
- `getNetworks(): array` — the network names keyed by network name

**Modifiable:**
- `setNetworks(array $networks): void` — change the network names

### `\Friendica\Event\NetworkContentStartEvent`

Fired when the network timeline starts being rendered.

**Contained data:**
- `getQuery(): string` — the query string of the network timeline URL (read-only)

### `\Friendica\Event\NetworkContentTabsEvent`

Fired when the network timeline tabs are being rendered.

**Contained data:**
- `getTabs(): array` — the network tabs as a list of tab arrays

**Modifiable:**
- `setTabs(array $tabs): void` — change the network tabs

### `\Friendica\Event\ParseLinkEvent`

Fired when a link is being parsed.

**Contained data:**
- `getUrl(): string` — the URL to parse (read-only)
- `getFormat(): string` — the requested output format (`json` or `html`) (read-only)
- `getText(): ?string` — the parsed text, `null` if not provided

**Modifiable:**
- `setText(?string $text): void` — provide the parsed text

### `\Friendica\Event\RenderLocationEvent`

Fired when a location is being rendered.

**Contained data:**
- `getLocation(): string` — the location name (read-only)
- `getCoord(): string` — the location coordinates (read-only)
- `getHtml(): string` — the rendered location HTML, empty if not provided

**Modifiable:**
- `setHtml(string $html): void` — set the rendered location HTML

### `\Friendica\Event\EnotifyEvent`

Fired when a notification is created and an email is sent.

**Contained data:**
- `getDataArray(): array` — the notification data with the keys `params` (the notification parameters), `subject`, `preamble`, `epreamble`, `body`, `sitelink`, `tsitelink`, `hsitelink` and `itemlink`

**Modifiable:**
- `setDataArray(array $data): void` — change the notification data

### `\Friendica\Event\EnotifyStoreEvent`

Fired before a notification entry is stored in the database.

**Contained data:**
- `getDataArray(): array` — the notification database fields (`type`, `name`, `url`, `photo`, `msg`, `uid`, `link`, `iid`, `parent`, `seen`, `verb`, `otype`, `name_cache`, `msg_cache`, `uri-id`, `parent-uri-id`, `date`)

**Modifiable:**
- `setDataArray(array $data): void` — change the notification database fields

### `\Friendica\Event\EnotifyMailEvent`

Fired when a notification email is being sent.

**Contained data:**
- `getDataArray(): array` — the notification email data with the keys `preamble`, `type`, `parent`, `source_name`, `source_link`, `source_photo`, `uid`, `hsitelink`, `tsitelink`, `itemlink`, `title`, `body`, `subject` and `headers`

**Modifiable:**
- `setDataArray(array $data): void` — change the notification email data

### `\Friendica\Event\PhotoUploadStartEvent`

Fired when a photo is about to be uploaded.

**Contained data:**
- `getRequestArray(): array` — the photo upload request data

**Modifiable:**
- `setRequestArray(array $request): void` — change the photo upload request data

### `\Friendica\Event\PhotoUploadEvent`

Fired when a photo upload is being processed.

**Contained data:**
- `getSrc(): string` — the local source path of the uploaded file
- `getFilename(): string` — the filename of the uploaded file
- `getFilesize(): int` — the filesize of the uploaded file in bytes
- `getType(): string` — the MIME type of the uploaded file

**Modifiable:**
- `setSrc(string $src): void` — change the source path
- `setFilename(string $filename): void` — change the filename
- `setFilesize(int $filesize): void` — change the filesize
- `setType(string $type): void` — change the MIME type

### `\Friendica\Event\PhotoUploadEndEvent`

Fired after a photo upload has been processed.

**Contained data:**
- `getId(): int` — the resource ID of the uploaded photo, `0` if the upload failed (read-only)
