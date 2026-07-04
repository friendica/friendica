Upgrade from 2024.12-1 to 2026.01
=================================

All notable changes to **Friendica** will be documented in this file.
As an *Addon or Theme maintainer* you can inform yourself about all breaking changes and deprecations.

This project [promises Backward Compatibility](index.md#backward-compatibility).

Mandatory (Breaking changes)
----------------------------

This section contains backward compatibility breaks, make sure your code is compatible with these entries before upgrading.

- The class `Friendica\App` was completely refactored and marked as internal, work with `Friendica\AppHelper` instead.

   *Before*
   ```php
   public function __construct(
       private \Friendica\App $app,
   ) {}
   ```

   *After*
   ```php
   public function __construct(
       private \Friendica\AppHelper $appHelper,
   ) {}
   ```
- The `contact_block_end` hook provides a HTML string instead of an array.

   *Before*
   ```php
   function my_addon_contact_block_end(array &$data) {
       $data['output'] .= '<div>Extra content</div>';
   }
   ```

   *After*
   ```php
   function my_addon_contact_block_end(string &$html) {
       $html .= '<div>Extra content</div>';
   }
   ```
- `Friendica\DI::app()` was removed, use `Friendica\DI::appHelper()` instead.

   *Before*
   ```php
   \Friendica\DI::app()->…();
   ```

   *After*
   ```php
   \Friendica\DI::appHelper()->…();
   ```

- `Friendica\Core\Logger::enableWorker()` and `Friendica\Core\Logger::disableWorker()` were removed.

Optional (Deprecations)
----------------------

This section contains deprecation notices. This changes will become mandatory in a future release.

- `bin/daemon.php` is deprecated in favor of `bin/console daemon` by @nupplaphil in [#14642](https://github.com/friendica/friendica/pull/14642)
- `bin/jetstream.php` is deprecated in favor of `bin/console jetstream` by @nupplaphil in [#14655](https://github.com/friendica/friendica/pull/14655)
- `bin/worker.php` is deprecated in favor of `bin/console worker` by @nupplaphil in [#14659](https://github.com/friendica/friendica/pull/14659)
- Providing strategies via `strategies.config.php` file in addons is deprecated and will stop working in 5 months, please use PHP hooks instead and remove the `strategies.config.php` file in your addon.
- Class `Friendica\Core\Addon` is deprecated and will be removed after 5 months, use implementation of `Friendica\Core\Addon\AddonHelper` instead.

   *Before*
   ```php
   \Friendica\Addon::isEnabled($addonId);
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
- Class `Friendica\Core\Addon\Model\AddonLoader` is deprecated and will be removed after 5 months, use implementation of `Friendica\Core\Addon\AddonHelper` via constructor injection or `\Friendica\DI::addonHelper()` instead.
- Interface `Friendica\Core\Addon\Capability\ICanLoadAddons` is deprecated and will be removed after 5 months, use implementation of `\Friendica\Core\Addon\AddonHelper` via constructor injection or `\Friendica\DI::addonHelper()` instead.
- Class `Friendica\Core\Logger` is deprecated, use constructor injection or `Friendica\Di::logger()` instead.

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
- Class `Friendica\Core\Logger\Factory\AbstractLoggerTypeFactory` is deprecated and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.
- Class `Friendica\Core\Logger\Factory\Logger` is deprecated and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.
- Class `Friendica\Core\Logger\Factory\StreamLogger` is deprecated and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.
- Class `Friendica\Core\Logger\Factory\SyslogLogger` is deprecated and will be removed after 5 months, implement `\Friendica\Core\Logger\Factory\LoggerFactory` instead.
- The method `\Friendica\BaseRepository::_selectOne()` is deprecated, use `\Friendica\BaseRepository::_selectFirstRowAsArray()` instead.

   *Before*
   ```php
   return $this->_selectOne($condition, $params);
   ```

   *After*
   ```php
   $fields = $this->_selectFirstRowAsArray($condition, $params);

   return $this->factory->createFromTableRow($fields);
   ```
