Upgrade from 2026.04 to 2026.08
===============================

All notable changes to **Friendica** will be documented in this file.
As an *Addon or Theme maintainer* you can inform yourself about all breaking changes and deprecations.

This project [promises Backward Compatibility](index.md#backward-compatibility).

> ℹ️ **Note:**
> Friendica 2026.08 requires PHP 8.2 or higher! Support for PHP 7.4, 8.0 and 8.1 has been dropped.

Mandatory (Breaking changes)
----------------------------

This section contains backward compatibility breaks, make sure your code is compatible with these entries before upgrading.

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

- Remove usage of `\Friendica\BaseModule::run()`, override `handleRequest(\Friendica\App\Request)` instead in your module class.

   The legacy `run()` method still works but will trigger a deprecation warning if your module overrides it.

   *Before*
   ```php
   use Friendica\BaseModule;
   use Friendica\Module\Special\HTTPException as ModuleHTTPException;

   class MyModule extends BaseModule
   {
       public function run(ModuleHTTPException $httpException, array $request = []): ResponseInterface
       {
           // custom logic
           return parent::run($httpException, $request);
       }
   }
   ```

   *After*
   ```php
   use Friendica\App\Request;
   use Friendica\BaseModule;

   class MyModule extends BaseModule
   {
       public function handleRequest(Request $request): ResponseInterface
       {
           // custom logic — use $this->getServerRequest() for typed getters
           return parent::handleRequest($request);
       }
   }
   ```

- Remove usage of `\Friendica\Capabilities\ICanHandleRequests` interface. Implement `\Friendica\Capabilities\IRequestHandler` instead.

   The legacy `ICanHandleRequests` interface is deprecated. The `IRequestHandler` interface provides the same capability
   through `handleRequest(\Friendica\App\Request)`.

   *Before*
   ```php
   use Friendica\Capabilities\ICanHandleRequests;
   use Friendica\Module\Special\HTTPException as ModuleHTTPException;

   class MyModule extends BaseModule implements ICanHandleRequests
   {
       public function run(ModuleHTTPException $httpException, array $request = []): ResponseInterface
       {
           // custom logic
           return parent::run($httpException, $request);
       }
   }
   ```

   *After*
   ```php
   use Friendica\App\Request;
   use Friendica\Capabilities\IRequestHandler;

   class MyModule extends BaseModule implements IRequestHandler
   {
       public function handleRequest(Request $request): ResponseInterface
       {
           // custom logic — use $this->getServerRequest() for typed getters
           return parent::handleRequest($request);
       }
   }
   ```

- Remove constructor injection of `\Friendica\App\Request`. Use `$this->getServerRequest()` inside modules or inject `Psr\Http\Message\ServerRequestInterface` instead.

   Constructor injection of `App\Request` via DICE is deprecated. If your addon service depends on the current request, receive the value directly (e.g. `string $remoteAddress`) or use the PSR-7 `ServerRequestInterface`.

   *Before*
   ```php
   use Friendica\App\Request;

   class MyService
   {
       public function __construct(Request $request)
       {
           $this->remoteAddress = $request->getRemoteAddress();
       }
   }
   ```

   *After*
   ```php
   class MyService
   {
       public function __construct(string $remoteAddress)
       {
           $this->remoteAddress = $remoteAddress;
       }
   }
   ```
