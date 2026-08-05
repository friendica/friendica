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
