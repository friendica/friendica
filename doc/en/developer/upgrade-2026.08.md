Upgrade from 2026.04 to 2026.08
===============================

All notable changes to **Friendica** will be documented in this file.
As an *Addon or Theme maintainer* you can inform yourself about all breaking changes and deprecations.

This project [promises Backward Compatibility](help/developer/index#backward-compatibility).

> ℹ️ **Note:**
> Friendica 2026.08 requires PHP 8.2 or higher! Support for PHP 7.4, 8.0 and 8.1 has been dropped.

Mandatory (Breaking changes)
----------------------------

This section contains backward compatibility breaks, make sure your code is compatible with these entries before upgrading.

- Outbound requests to non-public IP addresses are blocked by default.

   This can affect internal feed servers, mail servers, media proxies and other services using private network addresses.
   Add required hostnames to `system.allowed_internal_hosts` as a comma-separated string.
   The protection can be disabled with `system.block_private_addresses`, but this is not recommended on public nodes.

- The web server has to serve `.mjs` files as JavaScript.

   Friendica loads part of its JavaScript as ES modules, and browsers reject a module that is not served with a JavaScript MIME type.
   nginx and older Apache versions have no `.mjs` entry in their MIME type table, which leaves the navigation bar empty.
   `.htaccess-dist` and `mods/sample-nginx.config` ship the mapping, an existing configuration has to be adjusted by hand.

   *nginx* – the include has to stay, a bare `types` block would replace the whole table
   ```nginx
   include mime.types;

   types {
     text/javascript mjs;
   }
   ```

   *Apache*
   ```apache
   AddType text/javascript .mjs
   ```

- The icon library has been changed from Font Awesome to Remix Icons. Theme developers must replace `fa-*` CSS classes with their `ri-*` equivalents.

   The Font Awesome dependency has been removed. Any theme or addon that relies on Font Awesome must either include it themselves or migrate to Remix Icons.

   *Before*
   ```html
   <i class="fa fa-search"></i>
   ```

   *After*
   ```html
   <i class="ri-search-line"></i>
   ```

- Removed deprecated standalone scripts `bin/daemon.php`, `bin/jetstream.php` and `bin/worker.php`. Use the `bin/console.php` subcommand instead.

   *Before*
   ```bash
   bin/daemon.php start
   bin/jetstream.php
   bin/worker.php
   ```

   *After*
   ```bash
   bin/console.php daemon start
   bin/console.php jetstream
   bin/console.php worker
   ```

- Removed deprecated class `Friendica\Core\Addon`. Use `\Friendica\Core\Addon\AddonHelper` via constructor injection or `\Friendica\DI::addonHelper()` instead.

   *Before*
   ```php
   \Friendica\Core\Addon::isEnabled($addonId);
   ```

   *After* – via constructor injection
   ```php
   public function __construct(
       private \Friendica\Core\Addon\AddonHelper $addonHelper,
   ) {}

   $this->addonHelper->isAddonEnabled($addonId);
   ```

   *After* – via DI
   ```php
   \Friendica\DI::addonHelper()->isAddonEnabled($addonId);
   ```

- Removed deprecated class `Friendica\Core\Addon\Model\AddonLoader`. Use `\Friendica\Core\Addon\AddonHelper::getAddonDependencyConfig()` instead.

- Removed deprecated interface `Friendica\Core\Addon\Capability\ICanLoadAddons`. Use `\Friendica\Core\Addon\AddonHelper` instead.

- Removed deprecated class `Friendica\Core\Logger`. Use constructor injection or `Friendica\DI::logger()` instead.

   *Before*
   ```php
   \Friendica\Core\Logger::info('Message', ['key' => 'value']);
   ```

   *After* – via constructor injection
   ```php
   public function __construct(
       private \Psr\Log\LoggerInterface $logger,
   ) {}

   $this->logger->info('Message', ['key' => 'value']);
   ```

   *After* – via DI
   ```php
   \Friendica\DI::logger()->info('Message', ['key' => 'value']);
   ```

- Removed deprecated classes `Friendica\Core\Logger\Factory\AbstractLoggerTypeFactory`, `Friendica\Core\Logger\Factory\Logger`, `Friendica\Core\Logger\Factory\StreamLogger` and `Friendica\Core\Logger\Factory\SyslogLogger`. Implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.

   *Before*
   ```php
   class MyLogger extends \Friendica\Core\Logger\Factory\AbstractLoggerTypeFactory { … }
   ```

   *After*
   ```php
   class MyLogger implements \Friendica\Core\Logger\Factory\LoggerFactory {
       public function createLogger(string $logLevel, string $logChannel): \Psr\Log\LoggerInterface { … }
   }
   ```

- Removed support for the deprecated `monolog` value for `system.logger_config`. Use `stream` or `syslog` instead.

- Removed deprecated method `Friendica\DI::workerLogger()`. Use `Friendica\DI::logger()` instead.

   *Before*
   ```php
   $logger = \Friendica\DI::workerLogger();
   ```

   *After*
   ```php
   $logger = \Friendica\DI::logger();
   ```

- Removed deprecated method `\Friendica\BaseRepository::_selectOne()`. Use `\Friendica\BaseRepository::_selectFirstRowAsArray()` instead.

   *Before*
   ```php
   protected function findFirst(array $condition, array $params = []): Entity
   {
       return $this->_selectOne($condition, $params);
   }
   ```

   *After*
   ```php
   protected function findFirst(array $condition, array $params = []): Entity
   {
       $fields = $this->_selectFirstRowAsArray($condition, $params);

       return $this->getFactory()->createFromTableRow($fields);
   }
   ```

Optional (Deprecations)
-----------------------

This section contains deprecation notices. This changes will become mandatory in a future release.

- Remove usage of `\Friendica\BaseRepository::$factory`, create a `getFactory()` instead.

   *Before*
   ```php
   use Friendica\BaseRepository;
   use Friendica\Database\Database;
   use Psr\Log\LoggerInterface;

   class CustomRepository extends BaseRepository
   {
       /** @var CustomFactory */
       protected $factory;

       public function __construct(Database $database, LoggerInterface $logger, CustomFactory $factory)
       {
           parent::__construct($database, $logger, $factory);
       }

       private function selectOne(array $condition, array $params = []): CustomEntity
       {
           $fields = $this->_selectFirstRowAsArray($condition, $params);

           return $this->factory->createFromTableRow($fields);
       }

       // …
   }
   ```

   *After*
   ```php
   use Friendica\BaseRepository;
   use Friendica\Database\Database;
   use Psr\Log\LoggerInterface;

   class CustomRepository extends BaseRepository
   {
       public function __construct(Database $database, LoggerInterface $logger, private CustomFactory $entityFactory)
       {
           parent::__construct($database, $logger, $entityFactory);
       }

       protected function getFactory(): CustomFactory {
           return $this->entityFactory;
       }

       private function selectOne(array $condition, array $params = []): CustomEntity
       {
           $fields = $this->_selectFirstRowAsArray($condition, $params);

           return $this->getFactory()->createFromTableRow($fields);
       }

       // …
   }
   ```

- `Worker::add()` will enforce `int|array $run_parameter` and `string $command` in a future release. Passing any other type is deprecated and triggers a deprecation warning.

   *Before*
   ```php
   Worker::add('Notifier', $item_id);          // non-int|array $run_parameter
   Worker::add(Worker::PRIORITY_HIGH, 42);     // non-string $command
   ```

   *After*
   ```php
   Worker::add(Worker::PRIORITY_HIGH, 'Notifier', $item_id);
   Worker::add(Worker::PRIORITY_HIGH, (string) 42);
   ```

- Deprecated `\Friendica\Model\Item::newURI()` will be removed in a future release. Use `\Friendica\Post\UriGenerator::newURI()` instead. Calling `\Friendica\Model\Item::newURI()` triggers a deprecation warning.

   *Before*
   ```php
   use Friendica\Model\Item;

   $uri = Item::newURI($guid);
   ```

   *After* – via constructor injection
   ```php
   use Friendica\Post\UriGenerator;

   public function __construct(
       private UriGenerator $postUriGenerator,
   ) {}

   $uri = $this->postUriGenerator->newURI($guid);
   ```

   *After* – via DI
   ```php
   use Friendica\DI;

   $uri = DI::postUriGenerator()->newURI($guid);
   ```

- `BaseModule::httpExit()` is deprecated. Use `BaseModule::earlyHttpExit()` instead:

   *Before*
   ```php
   $this->httpExit($content);
   ```

   *After*
   ```php
   $this->earlyHttpExit($content);
   ```

- `BaseModule::jsonExit()` is deprecated. Use `BaseModule::earlyJsonExit()` instead:

   *Before*
   ```php
   $this->jsonExit($data);
   ```

   *After*
   ```php
   $this->earlyJsonExit($data);
   ```

- `BaseModule::jsonError()` is deprecated. Use `BaseModule::earlyJsonError()` instead:

   *Before*
   ```php
   $this->jsonError(404, ['error' => 'not found']);
   ```

   *After*
   ```php
   $this->earlyJsonError(404, ['error' => 'not found']);
   ```

- `BaseModule::httpError()` is deprecated. Throw `BaseModule::earlyHttpError()` instead:

   *Before*
   ```php
   $this->httpError(403, 'Forbidden');
   ```

   *After*
   ```php
   $this->earlyHttpError(403, 'Forbidden');
   ```

- `BaseApi::getLinkHeader()` is deprecated. Use `BaseApi::getPaginationLinkHeaderValue()` instead:

   *Before*
   ```php
   $linkHeader = self::getLinkHeader($asDate);
   ```

   *After*
   ```php
   $linkHeader = $this->getPaginationLinkHeaderValue($asDate);
   ```

- `BaseApi::getOffsetAndLimitLinkHeader()` is deprecated. Use `BaseApi::getOffsetAndLimitPaginationLinkHeaderValue()` instead:

   *Before*
   ```php
   $linkHeader = self::getOffsetAndLimitLinkHeader($offset, $limit);
   ```

   *After*
   ```php
   $linkHeader = $this->getOffsetAndLimitPaginationLinkHeaderValue($offset, $limit);
   ```

- `BaseApi::setLinkHeader()` is deprecated. Use `BaseApi::setPaginationLinkHeader()` instead:

   *Before*
   ```php
   self::setLinkHeader($asDate);
   ```

   *After*
   ```php
   $this->setPaginationLinkHeader($asDate);
   ```

- `BaseApi::setLinkHeaderByOffsetLimit()` is deprecated. Use `BaseApi::setPaginationLinkHeaderByOffsetLimit()` instead:

   *Before*
   ```php
   self::setLinkHeaderByOffsetLimit($offset, $limit);
   ```

   *After*
   ```php
   $this->setPaginationLinkHeaderByOffsetLimit($offset, $limit);
   ```
