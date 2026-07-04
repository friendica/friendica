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
