# Frontend Guidelines

* [Developer Intro](index)
* [Your First Change in Friendica](first-change)
* [Smarty Templates](smarty3-templates)

---

<a name="at-a-glance" id="at-a-glance"></a>
## At a glance

Find your area, check the rule, and read the linked section for the background and examples.

- [ ] **Templates** — find the PHP behind the template and what data it passes; keep output escaped → [§1](#templates), [§2](#xss)
- [ ] **External or user-supplied URLs** — validated in PHP before output → [§2.2](#xss)
- [ ] **User-visible strings** — all translated (PHP, templates, JS) → [§3](#translations)
- [ ] **Forms** — CSRF token present and verified; input validated and authorised → [§4](#forms), [First Change Steps 4–6](first-change#request)
- [ ] **CSS / themes** — data in PHP, structure in templates, appearance in CSS → [§5](#css)
- [ ] **JavaScript** — JSON→JS via `json_encode()` + `JSON_HEX_*`; DOM updates via `textContent` → [§6](#javascript)
- [ ] **Accessibility** — native elements, `alt` text, visible focus → [§7](#accessibility)
- [ ] **Backend / `Content/` renderer (PHP)** — presentation logic belongs here, not in the Module → [PHP Architecture §2.4](php-architecture#presentation-layer-content)

Before pushing, run the per-change testing matrix → [§8](#checklist).

---

<a name="templates" id="templates"></a>
## 1. Templates

### 1.1 File locations

| Purpose         | Location                       |
|-----------------|--------------------------------|
| Core templates  | `view/templates/`              |
| Frio overrides  | `view/theme/frio/templates/`   |
| Vier overrides  | `view/theme/vier/templates/`   |
| Addon templates | `addon/{addonname}/templates/` |

Core templates in `view/templates/` are the **fallback** used by any theme that does not provide its own override.
If a theme has its own copy of a template (e.g. `view/theme/frio/templates/example.tpl`), a change to the core `view/templates/example.tpl` will **not** be visible in that theme.
Check whether the active theme overrides a template before editing the core version.

### 1.2 Rendering from PHP

```php
$tpl = Renderer::getMarkupTemplate('mymodule/index.tpl');
return Renderer::replaceMacros($tpl, [
    '$title'               => $this->t('Page Title'),
    '$items'               => $items,
    '$form_security_token' => self::getFormSecurityToken('my-action'),
]);
```

By Friendica convention, template variable keys are prefixed with `$`.

### 1.3 Smarty syntax

Friendica uses double curly braces `{{ }}`:

```smarty
{{* Comment — not shown in output *}}

{{$title}}

{{if $condition}}
    ...
{{elseif $other}}
    ...
{{else}}
    ...
{{/if}}

{{foreach $items as $item}}
    {{$item.name}}        {{* dot notation for array/object access *}}
    {{$item['key']}}      {{* bracket notation also works *}}
{{/foreach}}
```

### 1.4 Finding the PHP file behind a template (and vice versa)

Friendica does not name templates after their module, so the link between a page, its template, and the variables it receives is not obvious.
Two reliable ways to trace it:

**From something visible on the page → the template → the PHP.**
Pick a unique marker in the rendered HTML (an `id`, a class, an icon name) using your browser's dev tools, then `grep` for it to find the template, then `grep` the template's file name to find the PHP that renders it:

```bash
grep -rn "ri-hashtag" view/          # marker → template file
grep -rn "tag_cloud.tpl" src/ mod/   # template name → the PHP that loads it
```

**From a URL → the PHP → the template.**
Look up the URL pattern in `static/routes.config.php` to find the Module class, then read that class: the `Renderer::getMarkupTemplate('…')` call names the template, and the `replaceMacros()` array shows exactly which `$variables` are passed into it.

The template variables a page receives are defined in that PHP file — read it to see what data is available before changing the template.

---

<a name="xss" id="xss"></a>
## 2. Output Escaping and XSS Prevention

> **Read this section before touching any template.**
> One wrong `nofilter` can expose every user of the instance to a cross-site scripting attack.

### 2.1 Auto-escaping is ON — rely on it for all normal variables

Friendica sets `escape_html = true` in `src/Render/FriendicaSmarty.php`.
Every `{{$variable}}` is HTML-escaped automatically.
This is your primary [XSS](#abbreviations) defense — **for normal HTML text and ordinary HTML attribute values.**

```smarty
{{* Safe — HTML text context *}}
<span>{{$username}}</span>

{{* Safe — ordinary attribute value *}}
<input type="text" value="{{$current_value}}">
```

> HTML-escaping is **not** enough for every context, because it only neutralizes HTML syntax (`<`, `>`, `&`, `"`).
> It does nothing inside other languages.
> Do **not** interpolate dynamic values directly into:
> - JavaScript (`<script>var x = "{{$v}}";</script>`) — a `"` or `</script>` in the value breaks out of the string into executable code; use the JSON pattern in [§6.3](#javascript)
> - CSS or `style` attributes — CSS syntax survives HTML-escaping, so a value can inject `url(...)` exfiltration or layout-breaking rules
> - URL attributes without scheme validation — a `javascript:` or `data:` URL stays intact through HTML-escaping and runs on click ([§2.2](#xss))
> - event-handler attributes (`onclick`, etc.) — the attribute value is JavaScript, not HTML, so HTML-escaping leaves the injected code executable
> - raw HTML via `nofilter` — this disables escaping entirely, so any markup in the value is rendered as-is

### 2.2 External URLs need PHP-side scheme validation first

Auto-escaping protects HTML attribute syntax but does NOT stop `javascript:` URLs in `href` or `src`.
Before passing any external or user-supplied URL to a template:

```php
// ✓ Validate in PHP — use the project helper
use Friendica\Util\Network;

if (!Network::isValidHttpUrl($rawUrl)) {
    $rawUrl = '';
}
```

> `Network::isValidHttpUrl()` checks for an `http`/`https` scheme and a host.
> It does **not** check for private IP ranges, loopback, or DNS rebinding — do not use it as a clearance to fetch the URL from the server.

Internal paths generated by the router or `BaseURL` (e.g. `settings/server`) do not need validation.

### 2.3 `nofilter` — only for approved rendering paths

```smarty
{{* Only for HTML that has gone through an approved renderer or sanitizer *}}
<div class="wall-item-body">{{$rendered_html nofilter}}</div>
```

The key question is not where the data *came from* but whether it went through a *trusted rendering path* — for example `Item::prepareBody()`.

**Never** use `nofilter` for raw user input, content from remote servers without sanitization, or strings assembled by concatenation.

---

<a name="translations" id="translations"></a>
## 3. Translations

### 3.1 All user-visible strings — PHP, templates, AND JavaScript — must be translated

**In PHP modules:**

```php
$this->t('Permission denied.')
$this->t('Welcome, %s!', $username)        // substitution
$this->tt('%d item', '%d items', $count)   // plural
```

**Pass translated values to templates; never hardcode English in `.tpl` or `.js` files:**

```php
'$l10n' => [
    'title'  => $this->t('Settings'),
    'submit' => $this->t('Save changes'),
    'saved'  => $this->t('Settings saved.'),  // also used by JS via §6.3
],
```

```smarty
<h1>{{$l10n.title}}</h1>
<button type="submit">{{$l10n.submit}}</button>
```

### 3.2 No string concatenation across word boundaries

```php
// ✗
$this->t('Hello') . ', ' . $username . '!';

// ✓
$this->t('Hello, %s!', $username);
```

---

<a name="forms" id="forms"></a>
## 4. Forms

> ### Form Safety Minimum
>
> **If you only changed the template or CSS**, you own the two markup-level items:
>
> - The form **includes a CSRF token** field ([§4.2](#forms))
> - Dynamic output stays **escaped** — no new `nofilter` ([§2](#xss))
>
> **Full form requirements** — the server-side items below.
> Confirm them yourself if you wrote the PHP:
>
> - The server checks **authentication and authorization** ([First Change Step 5](first-change#auth))
> - The CSRF token is **verified** in `post()` ([First Change Step 6](first-change#csrf))
> - Input **type, required state, and length** are validated in the Module ([First Change Step 4](first-change#request))
> - **Business rules** are handled by a Service, not the Module or template


### 4.1 Standard form field templates

Friendica provides Smarty include templates for consistent form fields (`field_input.tpl`, `field_checkbox.tpl`, `field_select.tpl`, …; the full set lives in `view/templates/` and `view/theme/frio/templates/`).
Each takes a single **positional array**, and the slot order, meaning, and escaping **differ between templates and between core and frio overrides**.

**Always open the actual template file before passing dynamic values.**
Some slots — typically the label, help text, and extra-attributes slots — are rendered with `nofilter` (raw, unescaped), and exactly which ones varies by template and theme.

Security rules that hold regardless of the exact slot layout:

- Put only **trusted, server-generated** values into a raw slot — a `$this->t()` literal or a hardcoded attribute string. Never user- or remote-derived data.
- **A translated string is not automatically safe.** `t()` with substitution does **not** HTML-escape the substituted value:

  ```php
  // ✗ Unsafe in a nofilter slot — $remoteName is not escaped by t()
  $this->t('Account: %s', $remoteName);
  ```

  For dynamic content, use a normally auto-escaped slot, or split the template so the dynamic part is output without `nofilter`.

Example using `field_input.tpl` (open the file to confirm the current slot order):

```php
// ✓ Safe usage — every value is a trusted translated string or an auto-escaped value
'$email' => [
    'email',                       // name
    $this->t('Email address'),     // label
    $currentEmail,                 // current value (auto-escaped)
    $this->t('Your login email.'), // help text
    $this->t('Required'),          // required tooltip
    '',                            // extra attributes — empty is safest
    'email',                       // input type
    $this->t('name@example.com'),  // placeholder
],
```

```smarty
{{include file="field_input.tpl" field=$email}}
```

### 4.2 Every mutating form MUST have a CSRF token

```smarty
<form method="post" action="{{$baseurl}}/my-path">
    <input type="hidden" name="form_security_token" value="{{$form_security_token}}">
    ...
</form>
```

See [First Change — CSRF](first-change#csrf) for the PHP side.

---

<a name="css" id="css"></a>
## 5. CSS and Themes

> **Keep the three layers separate.**
>
> - Data and content manipulation belong in the **PHP** (Module/Service/Presentation)
> - structure and markup in the **template**
> - appearance in the **CSS**
>
> Don't emit formatted HTML, hardcoded styles, or pre-joined strings from PHP when the template or stylesheet could do the presentation — e.g. pass a list as an array the template can render as a `<ul>`, comma-separated text, or anything else, instead of a fixed comma-separated string.
> This keeps a change of look possible in the template or stylesheet alone, which is what makes themes and schemes practical to build.

### 5.1 Frio and Vier — two themes, separate codebases

| Theme    | Location           | Status                                                                                                                                                                                   |
|----------|--------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **frio** | `view/theme/frio/` | Default; actively maintained                                                                                                                                                             |
| **vier** | `view/theme/vier/` | Ships with Friendica, but the user documentation describes it as "no longer officially maintained". Check the current project review policy before requiring vier-specific work in a PR. |

A CSS change in `view/theme/frio/css/style.css` does **not** affect vier, and vice versa.
If your fix should apply to both themes, you need separate changes in each.

### 5.2 How to add styles — three strategies

**Strategy A: Page-specific stylesheet for new theme-overridable components**

Register a stylesheet for one page via injected `App\Page`, resolving the path with `Theme::getPathForFile()`.
Use this when a new component needs a base stylesheet that themes may override.
For narrow fixes in existing theme CSS, use Strategy B or C instead.
This is a new-page pattern, not a broad existing convention in the repository.

If you choose it, create a path under `css/module/`:
- base fallback: `view/css/module/mymodule.css`
- frio override: `view/theme/frio/css/module/mymodule.css`
- vier override: `view/theme/vier/css/module/mymodule.css`

```php
use Friendica\App;
use Friendica\Core\Theme;

// In your module constructor — see first-change.md for the full constructor.
// BaseSettings and BaseModeration already provide $this->page.
// In a plain BaseModule subclass, inject App\Page yourself and store it.

protected function content(array $request = []): string
{
    // getPathForFile() searches view/theme/{current}/ → view/theme/{parent}/ → view/
    // and returns the FIRST match, or '' if nothing is found — always check before registering.
    $stylesheet = Theme::getPathForFile('css/module/mymodule.css');
    if ($stylesheet !== '') {
        $this->page->registerStylesheet($stylesheet);
    }
}
```

> **A theme copy fully replaces the base file — it does not cascade.**
> Because `getPathForFile()` returns only the first match, a `view/theme/frio/css/module/mymodule.css` shadows the base `view/css/module/mymodule.css` entirely.
> If a theme override should keep the base rules, `@import` or duplicate them.

> **Avoid `DI::page()` in new code** — inject `App\Page` through the constructor instead.

**Strategy B: Frio-specific CSS fix**

Edit `view/theme/frio/css/style.css`. This does not affect vier.

**Strategy C: Vier-specific CSS fix**

Edit `view/theme/vier/style.css`. This does not affect frio.

### 5.3 Frio colors and schemes

Frio exposes a few status colors as CSS custom properties in `style.css` (`--primary`, `--info`, `--success`, `--warning`, `--danger`, …), but there is no complete design-token system.
Most colors are still emitted as literal values or PHP template variables per scheme.
So reuse an existing variable where one fits, but copy color patterns from `style.css` or the scheme files rather than inventing new global variables.

A scheme (light / dark / black / gnome) is the user's **explicit choice**, not the operating system's — vier's light/dark variants work the same way.
So **don't** add dark mode with `@media (prefers-color-scheme: dark)` in a base or component stylesheet: the OS preference is a different signal as the chosen scheme.
Assuming they match would hand a light-scheme user a dark component, or the reverse.

Test color changes across all four frio schemes, the custom scheme, and the accent-color variants (`scheme_accent`).

### 5.4 Static presentation styles belong in CSS, not inline

Inline `style=""` attributes are hard to theme and maintain. Use CSS classes instead.

---

<a name="javascript" id="javascript"></a>
## 6. JavaScript

### 6.1 File locations

| Purpose            | Location                      |
|--------------------|-------------------------------|
| Core JS            | `view/js/main.js`, `view/js/` |
| Page-specific JS   | `view/js/module/{page-name}/` |
| Frio JS            | `view/theme/frio/js/`         |
| Vendored libraries | `view/js/` or `view/asset/`   |

### 6.2 Use the existing IIFE pattern — not ES modules

Scripts load as `<script type="text/javascript" src="...">`. Wrap your code in an
[IIFE](#abbreviations) — the `(function () { ... })()` wrapper shown below.
[ES](#abbreviations) module syntax (`export` / `import`) is not supported and causes errors.

```javascript
// SPDX-License-Identifier: AGPL-3.0-or-later
(function () {
    'use strict';

    function init() {
        document.querySelectorAll('.my-element').forEach(function (el) {
            el.addEventListener('click', handleClick);
        });
    }

    function handleClick(event) {
        // ...
    }

    document.addEventListener('DOMContentLoaded', init);
})();
```

Register via injected `App\Page`:

```php
use Friendica\Core\Theme;

$script = Theme::getPathForFile('js/module/my-feature/index.js');
if ($script !== '') {
    $this->page->registerFooterScript($script);
}
```

### 6.3 SPA lifecycle initialization

Friendica can navigate between pages without a full browser reload when the SPA
mode is enabled. Page-specific JavaScript must therefore be initialized through
the lifecycle helpers from `view/js/main.js`:

```javascript
window.onDocumentReady('#my-widget', function (element) {
        // Initialize content that is ready with the document.
});

window.onWindowLoad('#my-widget', function (element) {
        // Initialize content that depends on the window load phase.
});
```

Do not use `$(document).ready(...)`, `$(window).load(...)`,
`DOMContentLoaded`, or direct `load` listeners for page-specific initialization
that must work with SPA navigation. Those handlers are tied to the initial
document load and are not reliably repeated when the SPA replaces page content.

Both helpers take the same two parameters:

- `target` — the initialization target. A CSS selector such as `'#my-widget'`
    is resolved against the current document. A DOM element or jQuery object is
    also accepted. The callback is skipped when the target does not exist on the
    current page.
- `initialize` — the callback that initializes the target. It receives the
    resolved DOM element as its first argument.

Use `onDocumentReady` for normal DOM initialization and `onWindowLoad` when the
initialization depends on resources that finish loading with the window. The
helpers take care of both the normal page lifecycle and the corresponding SPA
lifecycle events.

### 6.4 SPA implementation details

The lifecycle helpers are necessary but not sufficient for SPA-compatible code.
Keep the following details in mind when adding or changing frontend code:

- **Make initialization repeatable.** A lifecycle callback can run again after
    navigation. Avoid registering duplicate handlers or starting duplicate
    processes. Use namespaced events and remove the previous handler before
    registering it again:

    ```javascript
    window.onDocumentReady('#my-widget', function (element) {
            $(element)
                    .off('click.my-feature', '.my-action')
                    .on('click.my-feature', '.my-action', handleAction);
    });
    ```

- **Do not retain stale DOM references.** SPA navigation replaces central
    containers such as `main`. Look up page elements inside the lifecycle
    callback or use the callback's `element` argument instead of keeping a
    reference to an element from the previous page.

- **Control timers and polling.** `setTimeout`, `setInterval`, and polling can
    outlive a navigation. Store their handles and clear them before starting a
    replacement, or guard against starting the same process more than once.

- **Handle dynamically inserted content explicitly.** Content added by live
    updates or infinite scroll does not automatically go through the initial
    page setup. Register a single `postprocess_liveupdate` handler when such
    content needs additional processing:

    ```javascript
    document.addEventListener('postprocess_liveupdate', function () {
            initializeDynamicContent();
    });
    ```

    The handler itself must not be registered repeatedly during navigation.

- **Separate global definitions from page initialization.** External scripts
    are managed by the SPA router and page-specific scripts can be loaded and
    executed again. Keep reusable definitions separate from initialization code,
    and do not rely on page-specific global state surviving navigation.

- **Mark persistent external scripts explicitly.** An external script that
    must remain loaded across SPA navigation needs the `data-spa-persistent`
    attribute:

    ```html
    <script src="/view/js/my-global-module.js" data-spa-persistent></script>
    ```

    Use this only for scripts that are genuinely global. Page-specific scripts
    should be able to load again and reinitialize safely.

- **Avoid competing with the SPA router.** Do not add custom navigation logic
    to links that should be handled by the router. Links with `onclick`,
    `data-spa-ignore`, `modal-open`, or `data-fancybox` are handled specially;
    use semantic links and buttons and keep custom behavior explicit.

- **Expect missing targets and incomplete responses.** Error pages, redirects,
    and pages without a particular module target may not contain the expected
    containers. Check for the required element before accessing it. The
    lifecycle helpers skip the callback automatically when their `target` is not
    present.

- **Reinitialize plugins on the current element.** Widgets such as calendars,
    scrollbars, autocomplete, and Masonry may need initialization after every
    navigation. Check whether a plugin is already active before initializing it
    again, and clean up plugin-specific state when the plugin requires it.

### 6.5 Pass data and translations to JS via a JSON block

In new code, don't hardcode English strings in `.js` files and don't inject PHP values straight into a `<script>`.
Encode any data — translated strings or structured config — as JSON with the `JSON_HEX_*` flags, then read it in JS:

```php
'$data_json' => json_encode([
    'saved'  => $this->t('Settings saved.'),
    'failed' => $this->t('Saving failed.'),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR),
```

```smarty
{{* nofilter is safe here: the value came straight from json_encode() with JSON_HEX_* flags *}}
<script type="application/json" id="my-data">{{$data_json nofilter}}</script>
```

```javascript
var data = JSON.parse(document.getElementById('my-data').textContent);
// data.saved, data.failed
```

The `JSON_HEX_*` flags escape `<`, `>`, `&`, `'`, `"`, so the JSON cannot break out of the `<script>` element.

### 6.6 Pass small scalar values via data attributes

```smarty
<div id="my-widget" data-uid="{{$uid}}" data-url="{{$validated_url}}"></div>
```

```javascript
var uid = document.getElementById('my-widget').dataset.uid;
```

<a name="dom-xss" id="dom-xss"></a>
### 6.7 DOM-XSS — safe JavaScript DOM manipulation

Receiving JSON safely is not enough.
Inserting data into the DOM can also be an XSS vector.
Follow these rules whenever you put a value into the page via JavaScript:

```javascript
// ✓ — textContent creates a text node; no markup parsing, no XSS
element.textContent = config.displayName;

// ✓ For text attributes — safe
anchor.setAttribute('title', config.label);

// ✓ For URL attributes — safe only after PHP-side scheme validation
anchor.setAttribute('href', validatedUrl);

// ✗ Never set event-handler attributes from data
element.setAttribute('onclick', config.code);

// ✗ Never with untrusted data — innerHTML parses markup and can create
// executable DOM content (event handlers, dangerous URLs, SVG scripts)
element.innerHTML = config.displayName;

// ✗ Never — insertAdjacentHTML with untrusted content
element.insertAdjacentHTML('beforeend', data.html);

// ✗ Never — building HTML strings from data
var html = '<span>' + config.name + '</span>';
element.innerHTML = html;
```

**Rule:** Use `textContent` for text. Use `createElement` + `setAttribute` +
`appendChild` when you need to build elements. Never use `innerHTML`,
`insertAdjacentHTML`, or string concatenation into HTML with untrusted data.

For content that is intentionally pre-rendered HTML (e.g. from `Item::prepareBody()` fetched via API), use a specifically approved sanitizer before setting `innerHTML`.

### 6.8 No new inline event handlers

```smarty
{{* ✗ *}}
<button onclick="doSomething()">Click</button>

{{* ✓ *}}
<button type="button" class="my-action">Click</button>
```

```javascript
document.querySelectorAll('.my-action').forEach(function (btn) {
    btn.addEventListener('click', handleAction);
});
```

---

<a name="accessibility" id="accessibility"></a>
## 7. Accessibility

Target [WCAG 2.1 AA](https://www.w3.org/WAI/WCAG21/quickref/). The rules below come up most in Friendica frontend work; the §8 checklist enforces them.

| Rule                                         | Do                                                          | Avoid                                                               |
|----------------------------------------------|-------------------------------------------------------------|---------------------------------------------------------------------|
| Use native elements (keyboard-accessible)    | `<button type="button">`, `<a href="…">`, `<input>`         | `<div class="clickable">` (an `<a>` without `href` isn't focusable) |
| `alt` on every image                         | `alt="{{$display_name}}"` informative · `alt=""` decorative | missing `alt`                                                       |
| Keep a visible focus ring                    | a custom focus style if you override the default            | `outline: none` with no replacement                                 |
| Don't rely on colour alone to convey meaning | pair colour with an icon or text                            | colour as the only signal                                           |

**Dynamic updates** — announce changes with an ARIA live region:

```smarty
<div role="status" aria-live="polite" id="notification-count">{{$notification_count}}</div>
```

**Contrast** — verify ≥ 4.5:1 (normal text) / 3:1 (large) in **every** scheme your change touches: frio light/dark/black/gnome (plus the custom scheme and accent colours for colour changes) and vier. Reusing a theme variable does not guarantee contrast.

---

<a name="checklist" id="checklist"></a>
## 8. Frontend checklist before pushing

The rule checklist lives at the top ([At a glance](#at-a-glance)); this section covers the automated and manual checks to run before pushing.

```bash
# PHP checks (templates are rendered by PHP)
composer run lint
composer run phpstan

# Note: lint and phpstan do NOT check JavaScript syntax, CSS, Smarty,
# accessibility, or browser behaviour. Those require manual testing.
```

**Manual checks:**

| Change type                     | Minimum testing                                                                                      |
|---------------------------------|------------------------------------------------------------------------------------------------------|
| Frio-only CSS file              | Affected frio schemes (light / dark / black / gnome) — no need to test vier                          |
| Vier-only CSS file              | Vier                                                                                                 |
| Core template                   | Check first whether frio/vier override it; test only the themes where the change is actually visible |
| Core JS loaded by all themes    | Every theme in which the script is loaded                                                            |
| New form or interactive element | The theme(s) it appears in + keyboard + focus + contrast                                             |
| Structural / semantic change    | Affected theme(s) + keyboard + screen-reader spot-check                                              |
| Colour change                   | Every scheme/theme actually affected; verify contrast ≥ 4.5:1 (normal text)                          |

---

<a name="abbreviations" id="abbreviations"></a>
## Abbreviations

| Abbr.    | Term                                          | Reference                                                           |
|----------|-----------------------------------------------|---------------------------------------------------------------------|
| **CSRF** | Cross-Site Request Forgery                    | [OWASP](https://owasp.org/www-community/attacks/csrf)               |
| **XSS**  | Cross-Site Scripting                          | [OWASP](https://owasp.org/www-community/attacks/xss/)               |
| **IIFE** | Immediately Invoked Function Expression       | [MDN](https://developer.mozilla.org/en-US/docs/Glossary/IIFE)       |
| **ES**   | ECMAScript (the JavaScript language standard) | [MDN](https://developer.mozilla.org/en-US/docs/Glossary/ECMAScript) |
