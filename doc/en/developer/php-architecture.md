# PHP Architecture Guidelines

* [Developer Intro](index)
* [Your First Change in Friendica](first-change)
* [Frontend Guidelines](frontend)

This document defines what new PHP code in `src/` should look like.
It is also the reference for Friendica's Domain-Driven Design building blocks — Entity, Factory, Repository, Collection — defined in §2 and the [Key Terms appendix](#key-terms).
The [layered model](#target-architecture) section shows how these building blocks group into the four tiers `src/` is converging on.

**Scope:** These rules apply to new classes and new methods.
A small bug fix in legacy code does not require a full migration of the surrounding area.
When you introduce a new standalone class or component, it should follow these patterns.

---

## Quick decision card — where does my code go?

Before reading further, use this table to find the right place for your code:

| I want to…                                                                                                  | Use                                            |
|-------------------------------------------------------------------------------------------------------------|------------------------------------------------|
| Receive a URL, read input, return a page                                                                    | **Module** (`src/Module/`)                     |
| Check syntax and types of user input                                                                        | **Module** — at the top of `post()` / `get()`  |
| Decide what should happen (business logic)                                                                  | **Service** (`src/*/`)                         |
| Load or save one domain object and the data that must stay consistent with it ([what is this?](#key-terms)) | **Repository** (`src/*/Repository/`)           |
| Run a complex query across multiple tables                                                                  | **Read Repository** (`src/*/Repository/`)      |
| Turn a database row into a typed object                                                                     | **Factory** (`src/*/Factory/`)                 |
| Hold a typed list of entities                                                                               | **Collection** (`src/*/Collection/`)           |
| Represent an immutable value                                                                                | **Value Object** — standalone `readonly class` |
| Turn domain objects into template data or HTML ([§2.4](#presentation-layer-content))                        | **Presentation** (`src/Content/<Feature>/`)    |

**Decision tree:**

```
Is it HTTP-specific (request, response, redirect)?   → Module
Does it touch the database?
    One domain object + its consistent data          → Repository
    Complex read across tables                       → Read Repository/Data Provider
Does it contain business decisions?                  → Service
Does it create a typed object from raw data?         → Factory
Does it turn domain data into template data / HTML?  → Presentation
```

> **A note against overengineering:** A small read-only feature does not require creating an Entity, Factory, Collection, and Repository if a focused Read Repository returning a documented array shape is enough.
> Use the full pattern when the entity genuinely has behavior or when multiple callers need the same typed object.

For how these blocks group into the four tiers `src/` is converging on — the same "where does my code go?" one zoom-level out — see [The layered model](#target-architecture) below.

---

## Before / After — a representative Friendica-style example

`ProfileService` and `ProfileRepository` are illustrative names for the layers in this example, not required suffixes or files to copy.

**Before:**

```php
// Everything in the Module: DI::, $_POST, DBA::
protected function post(array $request = []): void
{
    $uid = DI::userSession()->getLocalUserId();

    if (DBA::exists('user', ['uid' => $uid])) {
        DBA::update('user', [
            'nickname' => $_POST['nickname'],
        ], ['uid' => $uid]);
    }
}
```

**After (target pattern):**

```php
// Module: injected dependencies, explicit auth + CSRF + validation, then delegate
protected function post(array $request = []): void
{
    // Read the user ID once and type-check it (getLocalUserId() returns int|false)
    $uid = $this->session->getLocalUserId();
    if (!is_int($uid) || $uid <= 0) {
        throw new HTTPException\UnauthorizedException($this->t('Please login to continue.'));
    }

    self::checkFormSecurityTokenRedirectOnError(
        $this->args->getQueryString(), 'update-nickname'
    );

    // Read from $request directly so we can type-check; a string default in
    // getRequestValue() would coerce an array (nickname[]=x) to the string "Array"
    $rawNickname = $request['nickname'] ?? null;
    if (!is_string($rawNickname)) {
        throw new HTTPException\BadRequestException($this->t('Please enter a valid nickname.'));
    }

    $nickname = trim($rawNickname);
    if ($nickname === '' || mb_strlen($nickname) > 64) {
        throw new HTTPException\BadRequestException($this->t('Please enter a valid nickname.'));
    }

    $this->profileService->updateNickname($uid, $nickname);

    $this->baseUrl->redirect($this->args->getQueryString());
}
```

```php
// Service: business logic only — no HTTP concerns, no DBA::
final class ProfileService
{
    public function __construct(
        private readonly ProfileRepository $profiles,
    ) {}

    public function updateNickname(int $uid, string $nickname): void
    {
        $profile = $this->profiles->selectByUserId($uid);
        $profile->setNickname($nickname);
        $this->profiles->save($profile);
    }
}
```

What moved where:

| Code                                                              | Layer      | Reason                              |
|-------------------------------------------------------------------|------------|-------------------------------------|
| Session check, CSRF ([Cross-Site Request Forgery](https://owasp.org/www-community/attacks/csrf)), input filtering | Module     | HTTP-layer concerns                 |
| Basic input validation (length, emptiness)                        | Module     | Cheap fail-fast before service call |
| Domain rules ("is this nickname allowed?")                        | Service    | Business logic                      |
| The actual DB call                                                | Repository | Persistence layer                   |


---

## Norm labels

| Label            | Meaning                                                                                                                                                                                                                                                            |
|------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| MUST             | Hard rule. CI or review will block violations.                                                                                                                                                                                                                     |
| SHOULD           | Standard; a justified exception with a brief comment is acceptable — explain the reason in the PR description. A `// @todo` is useful only when it points to a concrete, bounded follow-up (ideally with an issue number). A comment alone is not a justification. |
| MAY              | Permitted option, not required.                                                                                                                                                                                                                                    |
| Legacy exception | Existing code prevents the target pattern here. Note the reason with a `// @todo` that includes a concrete follow-up. Do not introduce a new violation merely because surrounding code already has similar issues.                                                 |
| Migration task   | The improvement is welcome but not required in the current PR.                                                                                                                                                                                                     |

---

<a name="migrating-legacy" id="migrating-legacy"></a>
## Migrating legacy code incrementally

You do not have to rewrite a file to improve it.
The goal is to draw one clean boundary each time you touch legacy code — not to convert everything at once.

**Migrating one legacy method:**

1. Keep the current public behavior unchanged.
2. Add or identify a test that protects that behavior first.
3. Move the direct `DBA::` query into a focused Repository method.
4. Inject that Repository instead of adding another `DI::` call.
5. Move business decisions out of the Module into a Service.
6. Leave unrelated legacy code in the same file untouched.
7. Add Entities or Collections only when they earn their place (see the overengineering note above).

**Where to start, by what you see:**

| Legacy code you're touching                | First useful step                                            |
|--------------------------------------------|--------------------------------------------------------------|
| `DBA::select()` inside a Module            | Extract it into a Repository method                          |
| Several `DI::*` calls in one class         | Inject those dependencies one at a time                      |
| A large static `Model` method              | Wrap it behind a Service or adapter first                    |
| SQL and a business decision mixed together | SQL → Repository, decision → Service                         |
| A complex read-only query                  | Read Repository — not a full Entity/Factory/Collection stack |
| A huge "god" class                         | Extract only the responsibility you are changing right now   |

Over time the islands of clean code grow without a big-bang rewrite.

<a name="extracting-legacy" id="extracting-legacy"></a>
### Extracting legacy into a new file

A class you create while refactoring is new code, so it follows every rule in this document — SPDX header, `strict_types`, constructor injection, no superglobals, DB access only in a Repository.
"Behavior-preserving" means the behavior is preserved, not the legacy style: a leak the move exposes (a renderer reading `$_GET`) is fixed in the extraction, not carried into the new class.

Do not move an 800-line legacy method unchanged into a new class and call the result "new architecture".
If the extracted code cannot meet the rules yet, keep it in the legacy file, or extract only the clean boundary you can name and test now.
The new class should have one responsibility; remaining mixed responsibilities stay behind the legacy boundary until they are migrated deliberately.

The one thing you are **not** required to do: clean up the legacy file you extracted *from*, or its other callers. Leave that surrounding legacy as-is.

---

<a name="target-architecture" id="target-architecture"></a>
## The target architecture

The Quick decision card above places one class; this is the shape all of `src/` is moving toward — the same building blocks, grouped into four **tiers** by what each may depend on.
It is the destination the [incremental migration above](#migrating-legacy) aims at, so a boundary you draw today points the right way.

**The direction rule (our main heuristic):** code dependencies point the same way as the foreign keys — never back, no cycles.
It is not imported theory, it is our own schema: the small tables everything points at (`item-uri`, `user`, `contact`) are the core **nouns**, the tables pointing at them are **features**, code that only delivers or displays is **delivery**, and the plumbing is the **base**.
So `Model\Contact` must not know about `Notifications` — just as the `contact` table does not point at `notification`, but the other way round.

```
  Tier 3 · Delivery   Modules · Content renderers · Workers · Protocol adapters · Console   (nothing depends on these)
     ▼   feature ↔ feature: cross-feature effects via the event bus (src/Event/), not direct imports
  Tier 2 · Features   <Feature> = Service + Entity/Factory/Repository (write) + Read Repository (read)
     ▼
  Tier 1 · Domain     Contact · Post (item-uri) · User   (the big Model/ classes today)
     ▼
  Tier 0 · Base       Database · Cache · Lock · Logger · Config · shared enums / value objects
```

The [§2 building blocks](#layers) are what you write *inside* a tier — the tier only decides who may depend on whom:

| Building block                                                                | Tier                                                               |
|-------------------------------------------------------------------------------|--------------------------------------------------------------------|
| `Module`, `Worker`, `Console`, `Protocol` adapters, and the **renderer/presenter** classes in `Content/` | **Delivery**                                                       |
| `Service`, `Repository`, `Read Repository`, `Factory`, `Entity`, `Collection` | **Features** — or **Domain** for a core noun (Contact, Item, User) |
| `Database`, `Cache`, `Logger`, `Config`, shared enums / value objects         | **Base**                                                           |

**A tier is a property of a class, not a folder.**
Names stay flat — there is no physical `src/Domain/` parent (it would rename every namespace and break every import).
Folders like `Core/`, `Model/`, `Protocol/` still mix tiers, and one folder can span tiers: `Content/<Feature>/` keeps that feature's Delivery renderers next to its Feature/Domain classes ([§2.4](#presentation-layer-content)).
Those mixed folders are what we untangle over time.

These tiers are boundaries for **new** code; existing violations are unwound by baseline-backed follow-up work — how much exists today, and the order to get there, is tracked in [issue #15981](https://github.com/friendica/friendica/issues/15981), not here.

---

## 1. Dependency Injection

### 1.1 New classes MUST use constructor injection

`DI::` is a global service locator kept for historical reasons and actively being phased out.
New code that uses it is much harder to unit-test in isolation: dependencies are invisible and require manipulating global container state to replace with test doubles.

```php
// ✗ Do not use in new classes
class MyService
{
    public function run(): void
    {
        DI::logger()->info('running');
        $uid = DI::userSession()->getLocalUserId();
    }
}

// ✓ Inject through the constructor
class MyService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly IHandleUserSessions $userSession,
    ) {}

    public function run(): void
    {
        $this->logger->info('running');
        $uid = $this->userSession->getLocalUserId();
    }
}
```

Dice resolves concrete class dependencies automatically.
Interfaces and scalar values need an explicit rule in `static/dependencies.config.php` (see [Your First Change — Step 10](first-change#dice)).

**Legacy exception:** `DI::` calls in `mod/` and in `src/Model/` classes not yet migrated are expected.
Do not add new `DI::` calls into otherwise injected classes.

### 1.2 New classes SHOULD NOT call `new ServiceClass()` inside methods

Constructing a service inside a method hides it from tests.
Use constructor injection or a Factory.

**Exception:** Value objects and Entities (`new Entity\Foo(...)`) are correctly created inside Factories.

---

<a name="layers" id="layers"></a>
## 2. Layer Separation

The layers below are the building blocks from the [layered model](#target-architecture); each class built from them lives in a single tier.
These rules keep each block in its lane, which is what keeps the tier dependencies clean.

### 2.1 Responsibility by layer

| Layer             | Responsibility                                             | Must not                                                                                                                               |
|-------------------|------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------|
| `Module`          | HTTP, input validation, auth, CSRF, ownership (uid match)  | Business rules, DB calls, HTML/markup building (it may call the renderer with prepared data — see [§2.4](#presentation-layer-content)) |
| `Service`         | Orchestrate a use case + its business rules                | SQL, HTTP, rendering                                                                                                                   |
| `Repository`      | Read/write one aggregate                                   | Business rules, HTTP                                                                                                                   |
| `Read Repository` | Complex/multi-table reads, projections                     | Business rules, HTTP                                                                                                                   |
| `Factory`         | Create typed objects from raw data                         | Persistence, HTTP                                                                                                                      |
| `Entity`          | Domain data + its own invariants                           | External deps, DB access                                                                                                               |
| `Collection`      | Typed list of Entities                                     | Business rules                                                                                                                         |
| `Presentation`    | Template data / HTML ([§2.4](#presentation-layer-content)) | SQL, request access, domain mutation                                                                                                   |

#### The `Service` layer

A `Service` orchestrates a use case and holds its business rules — one layer, named by what it does (no `*Service` suffix).
Reference: `Protocol\ActivityPub\Firehose` — injected dependencies (incl. the `UserDefinedChannel` repository), no SQL of its own, a domain decision (`isSolicitedPost()`) next to the orchestration, `protected` seams for tests.
(`ProfileService` / `WidgetService` elsewhere are illustrative layer labels, not required class names.)

**Where does a piece of logic go?**

1. **Business rule** about one entity's own data → that **Entity** (`setNickname()` rejects an invalid value). Needs a DB lookup or several entities ("is this nickname unique?") → **Service**.
2. **Orchestration** that changes state or enforces a policy → **Service**; that only assembles data for display → **Presentation** ([§2.4](#presentation-layer-content)).
3. **Auth**: "logged in?", CSRF, own-uid ownership → **Module**; "may this user do X?" (domain policy) → **Service** / **Entity**.

<a name="db-access" id="db-access"></a>
### 2.2 DB access MUST stay inside Repositories

`DBA::` and direct use of the injected `Database` object for application queries belong only in Repository classes.

```php
// ✗ Direct DB call in a Module or Service
$rows = DBA::select('user', ['uid'], ['verified' => true]);

// ✓ Delegate to a Repository
$users = $this->userRepository->selectVerified();
```

**Complex queries:** Not every query maps to a single entity Repository.
For multi-table reads, aggregates, timeline queries, or administrative reports, use a **Read Repository** (also called a Query Object):
a class whose explicit job is data access.
This is a **newer pattern** in Friendica — there is not yet an established example class to copy, so treat the rules below as the intended shape rather than a widespread one.
A Read Repository MAY contain `DBA::` / `Database` queries — it is a persistence class, not a Service.

Keep the distinction clear:

| Class                          | DB access allowed? | Role                                                              |
|--------------------------------|--------------------|-------------------------------------------------------------------|
| Entity Repository              | Yes                | Persist and load one aggregate                                    |
| Read Repository / Query Object | Yes                | Complex/read-optimised queries, projections                       |
| Service                        | No                 | Orchestrates repositories; contains no `DBA::`/`Database` queries |

Do not force complex reads into an entity Repository to satisfy this rule, and do not put SQL into a Service.

**Legacy exception:** `DBA::` calls in `mod/` and in `src/Model/` classes are expected.
Do not add new `DBA::` calls into otherwise clean classes.

**Each table should have one owning Repository — the only class that writes it** (read the owner off the schema: the primary key, the foreign key the row hangs off, or the `uid` for per-user copies).
This is the target direction, not a rule you can always satisfy today: for a core table the owner is often still a static `Model` class, so new write code routes through the owning Repository once it exists, and otherwise extracts the smallest owner seam or notes the migration exception in the PR — it just does not add a fresh `DBA::` write on a core table (`user`, `contact`, `post*`) from an unrelated class.
Schema migrations (`update.php`, `PostUpdate`, `DBStructure`) and low-level database-maintenance jobs write directly by design; ordinary application Workers do not — they go through the owning Repository like any other code.
Derived tables (`post-engagement`, `post-searchindex`) are rebuilt by the read side, never written by hand.

<a name="request-not-superglobals" id="request-not-superglobals"></a>
### 2.3 Modules MUST use `$request` — not superglobals

`$_GET`, `$_POST`, `$_REQUEST`, and `$_SERVER` MUST NOT appear in new module code.
Use `$this->getRequestValue()` on the `$request` array, or read `$request[...]` directly when you need an explicit type check
(reading the passed `$request` array is fine — it is the superglobals themselves that are forbidden).

```php
// ✗
$id = (int) $_POST['id'];

// ✓
$id = $this->getRequestValue($request, 'id', 0);
if ($id === false) {
    throw new HTTPException\BadRequestException();
}
```

> `getRequestValue()` is type-dependent filtering, **not** input validation, and its string and bool defaults have sharp edges (an array input is coerced to the literal `"Array"`; `false` is indistinguishable from invalid).
> See [Your First Change — Step 4](first-change#request) for the per-type rules.
> Validate length, allowed values, and domain constraints separately in the Service or before calling the Repository.

**Legacy exception:** Direct superglobal access in existing `mod/` and `src/Model/` code is expected.
Do not add superglobal access to a new class — including one you create by extracting legacy code (see [Extracting legacy into a new file](#extracting-legacy)).

<a name="presentation-layer-content" id="presentation-layer-content"></a>
### 2.4 Presentation layer (`Content/`)

A class that turns domain objects into template data or HTML — a Renderer, Formatter, or Presenter — belongs in the presentation layer, not in a Module or a domain Service.
In Friendica this layer lives mostly under `src/Content/` (and `src/Object/`).
Presentation code may call read repositories and orchestrate formatters and template rendering, but it should not make business decisions; if the answer changes domain state or decides whether an action is allowed, that belongs in a Service or Entity.

**`Content/<Feature>/` is a feature package, not a layer bucket.** It holds the feature's domain (`Entity/`, `Repository/`, `Factory/`, `Collection/`) *and* its presentation classes together — this package-by-feature layout is intended.
In tier terms the renderers are Delivery while the `Entity/`/`Repository/` are the feature's Feature (or Domain) classes — one folder, each class keeping its own tier ([the layered model](#target-architecture)).
Keep new conversation code under `Content/Conversation/`, new notification code under `Navigation/Notifications/`, and so on.
Do not split a feature across a top-level `Presentation/` vs `Domain/` tree by default.
If one domain is reused by several unrelated presentation surfaces, introduce a shared domain package deliberately and keep the presentation adapters near their surfaces.

A new presentation class follows every rule in this document, plus three of its own:

| Rule                                                                                                                                       | Why                                                                                                           |
|--------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------------|
| **No SQL** — DB reads for the feature go in the package's `Repository/` (a Read Repository for complex reads), not in the renderer         | A renderer that queries is doing two jobs; the read is not testable or reusable                               |
| **No request access** — the Module reads `$request` and passes typed values in                                                             | `$_GET`/`$_POST` in a renderer couples it to HTTP and breaks unit testing ([§2.3](#request-not-superglobals)) |
| **Build data, not markup strings** — hand data to Smarty (`Renderer::replaceMacros`); do not concatenate HTML or `<script>` strings in PHP | String building is where request access and XSS leak into the render layer                                    |

#### Pass a typed view model to the template (SHOULD)

Prefer a typed view-model object over an untyped `array` for structured presentation data.

> **Trade-off.** Friendica's established convention is to pass plain `array`s to Smarty.
> Matching that convention is an accepted SHOULD exception — when you do, document the shape with `@return array{…}` so callers and templates know what is inside.
> Reach for a typed view model when the data is reused, nested, built in several steps, or has helper behaviour; stay with a documented array for small one-template payloads that mirror surrounding Smarty code.

---

## 3. Static Methods

### 3.1 New code SHOULD NOT introduce static methods

Static calls hide coupling, cannot be injected into a constructor, and are harder to replace with test doubles than instance methods.
The large static classes in `src/Model/` (Contact, User, Item, Post) are a known problem, not a model to follow.
In the [target architecture](#target-architecture) these nouns belong in Tier-1 **Domain**; the static classes are temporary shims that shrink as it is adopted.

**Acceptable static use:**
- Pure functions with no dependencies and no side effects
- `BaseModule::getFormSecurityToken()` and `checkFormSecurityToken*()` — use as-is

**Existing framework static APIs (temporarily accepted):**

`Renderer::getMarkupTemplate()`, `Renderer::replaceMacros()`, and `Theme::getPathForFile()` are static methods that internally use `DI::`.
They exist throughout the codebase and have no injected alternative yet.
Calling them in new code is accepted for now.
Do **not** create new static framework APIs following this pattern.

---

## 4. Type System

### 4.1 New methods MUST declare a return type

All new `public` and `protected` methods must have a native return type.
Constructors are exempt — PHP does not allow a return type on `__construct`.

```php
// ✗
public function getItems($uid) { return []; }

// ✓
public function getItems(int $uid): array { return []; }
```

### 4.2 Untyped `array` returns SHOULD be avoided

Prefer a typed Collection, a typed Entity, or a documented shape via PHPDoc.

```php
// ✗ Caller has no idea what is inside
public function getContacts(int $uid): array { ... }

// ✓ Typed Collection (preferred for domain objects)
public function getContacts(int $uid): Contacts { ... }

// ✓ Document the shape when a Collection class does not exist yet
/** @return array{id: int, url: string}[] */
public function buildLinkList(): array { ... }
```

### 4.3 Use `never` only for methods that cannot return

```php
// ✓ PHPStan can then eliminate dead code paths after this call.
private function failLogin(): never
{
    throw new HTTPException\UnauthorizedException($this->t('Login required.'));
}
```

Do not put `never` on normal guard methods such as `requireLocalUserId(): int`.
If a method may return on success, its return type is the successful value type, not `never`.

### 4.4 Every new file MUST start with `declare(strict_types=1)` and the SPDX header

```php
<?php

// Copyright (C) 2010-2026, the Friendica project
// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace Friendica\...;
```

> Most existing code — including the reference stacks this guide cites (`src/Navigation/Notifications/`) — predates this rule.
> It applies to new code going forward, including classes you create by extracting legacy code; it is not applied retroactively to the examples.

---

## 5. Modern PHP (≥ 8.2 — the project minimum)

### 5.1 Use `enum` for new closed value sets

Do not add new abstract classes that serve only as constant containers.

```php
// ✗ Old pattern — still present, do not replicate
abstract class Status
{
    const OPEN   = 0;
    const CLOSED = 1;
}

// ✓ New code — a genuinely closed domain value set with no scalar API dependency
enum ReportStatus: int
{
    case Open   = 0;
    case Closed = 1;
}

// Usage — type-safe; passing an arbitrary int causes a runtime TypeError
// (PHPStan detects it statically before runtime)
function closeReport(int $id, ReportStatus $status): void { ... }
closeReport(42, ReportStatus::Closed);
```

**When enums are NOT appropriate — do not convert existing scalar constants to enums when:**
- The constant is passed directly to a scalar API (`int $ttl`, SQL values, library constants)
  — doing so causes a `TypeError`.
  Example: cache TTL constants (`Duration::HOUR`) are used as `int $ttl` in cache interfaces and cannot be replaced by an enum case without changing every caller.
- Bitmasked or combinable flags (e.g. `Report::CATEGORY_SPAM | Report::CATEGORY_ILLEGAL`)
- The set is open or defined externally (HTTP status codes, ActivityPub verbs)
- Migration would be a public API break

Migrating existing constants to enums is a separate Migration task; do not do it as a side effect of an unrelated fix.

### 5.2 Use `readonly` for standalone Value Objects

`readonly class` cannot extend a non-readonly class.
Classes in the existing `BaseEntity` hierarchy MUST NOT be declared `readonly` until `BaseEntity` itself is migrated.
Use `readonly` only for new standalone Value Objects.

```php
// ✓ Standalone Value Object — no parent class
readonly class Money
{
    public function __construct(
        public int $amount,
        public string $currency,
    ) {}
}
```

### 5.3 Mark sensitive parameters with `#[SensitiveParameter]`

```php
// ✓
public function authenticate(
    string $username,
    #[\SensitiveParameter] string $password,
): bool { ... }
```

> This only redacts the value in PHP **stack traces**.
> It does not redact explicit log context, full request dumps, exception messages, or serialized data.
> Keeping secrets out of logs is a separate responsibility.

### 5.4 Prefer `match` for expression-style branching

```php
// ✗ For simple value → result mappings
switch ($type) {
    case self::PT_NOTE:  return 'note';
    default:             return 'unknown';
}

// ✓
return match($type) {
    self::PT_NOTE  => 'note',
    self::PT_IMAGE => 'image',
    default        => 'unknown',
};
```

`switch` remains appropriate when individual cases contain multiple statements, use intentional fallthrough, or need `continue`/`break` in a loop context.

### 5.5 Use first-class callable syntax

```php
// ✗
$lower = array_map(function (string $s): string { return strtolower($s); }, $items);

// ✓
$lower = array_map(strtolower(...), $items);
```

---

## 6. Named Constants — No Magic Numbers or Strings

Values whose meaning is not obvious at the call site SHOULD have a named constant.

```php
// ✗ Reader cannot know what 2 means without reading Contact class
if ($contact['rel'] === 2) { ... }

// ✓ Contact::SHARING = 2 (Contact::FRIEND = 3 — a different value)
if ($contact['rel'] === Contact::SHARING) { ... }

// ✗ Constant does not exist
if ($item['private'] === Item::PRIVATE_POST) { ... }

// ✓ Correct constant name
if ($item['private'] === Item::PRIVATE) { ... }
```

Not every literal needs a constant.
Log messages, one-off limits, and self-explanatory values do not.
Apply judgment: would a reader unfamiliar with this code understand the value's meaning at the call site?

---

## 7. Error Handling

### 7.1 MUST NOT silently swallow exceptions

```php
// ✗ Never
try {
    $this->repo->save($entity);
} catch (\Exception) {
    // nothing
}
```

### 7.2 Catch only where you can meaningfully handle

Log the exception once, at the point where you have enough context to add value.
Do not log and rethrow in every layer — that produces duplicate log entries for the same error.

```php
// ✓ Translate to a domain-specific exception with added context
try {
    $this->db->insert('friend_suggest', $data);
} catch (\Exception $e) {
    throw new FriendSuggestPersistenceException(
        sprintf('Could not store FriendSuggest for uid %d', $uid),
        previous: $e,
    );
}

// ✓ Handle gracefully when appropriate
try {
    $entity = $this->repository->selectOneByUserAndServer($uid, $gsid);
    $this->repository->delete($entity);
} catch (NotFoundException) {
    // Nothing to delete — expected case
}
```

Use domain-specific exception classes from `src/{Domain}/Exception/`, not generic `\Exception`. Use `HTTPException\*` only in Module classes.

---

## 8. Testing

### 8.1 Test type by layer

| Layer                 | Primary test type           |
|-----------------------|-----------------------------|
| Value Object / Entity | Unit test                   |
| Pure Service          | Unit test                   |
| Factory / Mapper      | Unit test                   |
| Repository            | Integration test            |
| HTTP Client Adapter   | Integration / Contract test |
| Module                | Functional test             |
| Template              | Template test ([Tests](tests#template-tests)) |

Not every class needs a test. Marker interfaces, configuration objects,
and simple DTOs with no logic do not need dedicated tests.

### 8.2 Unit-testable classes SHOULD have unit tests

A class is unit-testable when all its external dependencies and configuration values are explicit and controllable by the test — no `DI::`, no `DBA::`, no superglobals inside the methods.
Scalar configuration values (a base path, a limit) passed through the constructor are fine.

The `src/Contact/FriendSuggest/Factory/FriendSuggest.php` class and its test in `tests/Unit/Contact/FriendSuggest/Factory/FriendSuggestTest.php` are a clean reference example.
The test uses `NullLogger`, no database, and no DI container.

### 8.3 Use PHPUnit data providers for multiple input cases

```php
// ✓ PHPUnit 11 attribute syntax (the version the project currently uses)
#[\PHPUnit\Framework\Attributes\DataProvider('dataCreate')]
public function testCreateFromTableRow(array $input, Entity\FriendSuggest $assertion): void
{
    $factory = new FriendSuggest(new NullLogger());
    $result  = $factory->createFromTableRow($input);

    self::assertSame($assertion->uid, $result->uid);
}
```

> This is a shortened illustration, not copy-paste-ready code.
> The real test method name is `dataCreate` and its rows use the key `assertion`.
> The `FriendSuggest` Entity constructor requires all nine parameters — copy the full field list and the complete provider from `tests/Unit/Contact/FriendSuggest/Factory/FriendSuggestTest.php`.

---

## 9. Tooling Reference

Run automatic fixes first, review the diff, then validate.
The full command sequence — `rectify` → `lint` → `cs:check` → `phpstan` → `phpmd` → tests — is in [Your First Change — Step 11](first-change#checks).
For the test suites and the `MYSQL_*` database variables the harness needs, see [Tests](tests).

**PHPStan level:** Level 4 with partial strict rules.
PHPStan does not enforce architectural boundaries such as `DI::` or `DBA::` in the wrong layer — those are reviewed manually.

---

## 10. Database Performance

These four rules matter more for Friendica's production behavior than most style choices.
They apply to all new code that touches the database.

### 10.1 No DB queries inside loops

```php
// ✗ N+1 queries — executes one query per item
foreach ($items as $item) {
    $contact = $this->contactRepo->selectById($item['author_id']); // inside loop!
}

// ✓ Load all necessary data before the loop
$authorIds = array_column($items, 'author_id');
$contacts  = $this->contactRepo->selectByIds($authorIds);
```

### 10.2 Select only the columns you need

```php
// Inside a Repository:
// ✗ — empty field array means "all columns" in Friendica; wasteful
$users = DBA::select('user', [], ['verified' => true]);

// ✓ — inside a Repository only; name the columns the caller needs
$users = DBA::select('user', ['uid', 'nickname', 'email'], ['verified' => true]);
```

### 10.3 Always limit or paginate large result sets

A query with no `LIMIT` on a large table can kill the server under load.

```php
// ✗
$posts = $this->postRepo->selectByUser($uid); // could return thousands

// ✓
$posts = $this->postRepo->selectByUserWithPagination($uid, $pager);
```

### 10.4 Check for race conditions and locking in multistep operations

When a sequence of DB operations must be atomic (read → check → write), consider whether a concurrent request can produce an inconsistent result.

**Application-side existence checks alone are not concurrency-safe.**
A concurrent request can interleave a "does this row already exist?" read followed by an insert, so two requests both pass the check and both write.
Make the database the final integrity boundary with a `UNIQUE` constraint, and let the insert resolve the conflict atomically instead of hand-writing SQL.

Friendica's `insert` command already supports this via its `$duplicate_mode` argument — you do not need a raw `INSERT ... ON DUPLICATE KEY UPDATE`:

```php
// Insert or update the existing row on a UNIQUE-key clash (upsert):
DBA::insert('contact', $fields, Database::INSERT_UPDATE);

// Insert or silently skip if the row already exists:
DBA::insert('contact', $fields, Database::INSERT_IGNORE);
```

For a longer read → modify → write sequence that must be all-or-nothing, wrap it in an explicit transaction (`DBA::transaction()` / `DBA::commit()`).

### 10.5 Schema changes and data migrations — what goes where

| What you are doing                                              | Where it goes                                                                                              |
|----------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------|
| Add or change a table or column                                | `static/dbstructure.config.php`                                                                            |
| Add or change a database view                                  | `static/dbview.config.php`                                                                                 |
| Migrate data, **critical** — must finish during the update     | `update_<version>()` in `update.php` (use `pre_update_<version>()` if it must run *before* the structure change) |
| Migrate data, **heavy but non-critical** — can run afterwards  | `update<version>()` in `src/Database/PostUpdate.php` — the cron worker runs it in the background           |

The choice between the last two is about timing: `update.php` runs **synchronously** as part of the update (use it only when the data must be correct before the new code serves requests); `PostUpdate` runs **in the background** afterwards (use it for large back-fills that would otherwise block the upgrade).

After any of the above:

1. Increment `DB_UPDATE_VERSION` in `static/dbstructure.config.php` — the migration function/method name must match that number.
2. Regenerate the committed `database.sql` with `composer run db:update-structure`.

## 11. Interface Naming

The `I` convention is used in Capability namespaces:

```
ICacheStore           IUserSessionStore
ITableRowMapper       IPersonalConfigStore
ILockable             IRequestHandler
```

This is a convention which is not implemented yet, but anytime soon.
Other interfaces may follow standard PHP naming (`Container`, `LoggerInterface`, `EventDispatcherInterface`).
Match the style of the namespace you are working in.

---

<a name="key-terms" id="key-terms"></a>
## Appendix: Key Terms

Brief definitions for readers coming from procedural or database-centric backgrounds.

| Term                | One-sentence definition                                                                              | Friendica example                                                                                   |
|---------------------|------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------|
| **Entity**          | A domain object that has identity and may change over time                                           | `Navigation\Notifications\Entity\Notification`                                                      |
| **Value Object**    | An immutable data holder defined by its values, not identity                                         | A normalised URI or validated email address — two objects with the same value are considered equal  |
| **Aggregate**       | A cluster of data that must stay consistent together                                                 | A notification with its actor, target URI, and read state                                           |
| **Factory**         | A class whose only job is to create typed objects from raw data                                      | `Navigation\Notifications\Factory\Notification::createFromTableRow()`                               |
| **Repository**      | A class that loads and saves one kind of aggregate to/from the DB                                    | `Navigation\Notifications\Repository\Notification`                                                  |
| **Read Repository** | Like a Repository, but optimised for complex read queries (newer pattern — no core example yet)      | `Content\Conversation\ConversationDataProvider`                                                     |
| **Service**         | Orchestrates a use case and holds its business rules; no SQL (named by what it does, not `*Service`) | `Protocol\ActivityPub\Firehose` — injects a repository, orchestrates and decides, no SQL of its own |
| **Collection**      | A typed list of Entities with helper methods                                                         | `Navigation\Notifications\Collection\Notifications`                                                 |
| **Presentation**    | Turns domain objects into template data or HTML; no SQL, no request access                           | `Content\Conversation\` renderers and formatters                                                    |

---

## Further reading

The patterns above are standard Domain-Driven Design building blocks. For background:

- [Dependency Injection](https://designpatternsphp.readthedocs.io/en/latest/Structural/DependencyInjection/README.html)
- [Simple Factory](https://designpatternsphp.readthedocs.io/en/latest/Creational/SimpleFactory/README.html) / [Factory Method](https://designpatternsphp.readthedocs.io/en/latest/Creational/FactoryMethod/README.html)
- [Repository](https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html)
