Friendica Storage Backend Addon development
===========================================

* [Home](help)

Storage backends can be added via addons.
A storage backend is implemented as a class, and the plugin register the class to make it avaiable to the system.

## The Storage Backend Class

The class must live in `Friendica\Addon\youraddonname` namespace, where `youraddonname` the folder name of your addon.

There are two different interfaces you need to implement.

### `IWritableStorage`

The class must implement `Friendica\Core\Storage\IWritableStorage` interface. All method in the interface must be implemented:

```php
namespace Friendica\Core\Storage\IWritableStorage;

interface IWritableStorage
{
	public function get(string $reference);
	public function put(string $data, string $reference = '');
	public function delete(string $reference);
	public function __toString();
	public static function getName();
}
```

- `get(string $reference)` returns data pointed by `$reference`
- `put(string $data, string $reference)` saves data in `$data` to position `$reference`, or a new position if `$reference` is empty.
- `delete(string $reference)` delete data pointed by `$reference`

### `IStorageConfiguration`

Each storage backend can have options the admin can set in admin page.
To make the options possible, you need to implement the `Friendica\Core\Storage\IStorageConfiguration` interface.

All methods in the interface must be implemented:

```php
namespace Friendica\Core\Storage\IStorageConfiguration;

interface IStorageConfiguration
{
	public function getOptions();
	public function saveOptions(array $data);
}
```

- `getOptions()` returns an array with details about each option to build the interface.
- `saveOptions(array $data)` get `$data` from admin page, validate it and save it.

The array returned by `getOptions()` is defined as:

	[
		'option1name' => [ ..info.. ],
		'option2name' => [ ..info.. ],
		...
	]

An empty array can be returned if backend doesn't have any options.

The info array for each option is defined as:

	[
		'type',

define the field used in form, and the type of data.
one of 'checkbox', 'combobox', 'custom', 'datetime', 'input', 'intcheckbox', 'password', 'radio', 'richtext', 'select', 'select_raw', 'textarea'

		'label',

Translatable label of the field. This label will be shown in admin page

		value,

Current value of the option

		'help text',

Translatable description for the field. Will be shown in admin page

		extra data

Optional. Depends on which 'type' this option is:

- 'select': array `[ value => label ]` of choices
- 'intcheckbox': value of input element
- 'select_raw': prebuild html string of `<option >` tags

Each label should be translatable

	];


See doxygen documentation of `IWritableStorage` interface for details about each method.

## Register a storage backend class

Each backend must be registered in the system when the plugin is installed, to be aviable.

`DI::facStorage()->register(string $class)` is used to register the backend class.

When the plugin is uninstalled, registered backends must be unregistered using
`DI::facStorage()->unregister(string $class)`.

You have to register a new hook in your addon, listening on `storage_instance(App $a, array $data)`.
In case `$data['name']` is your storage class name, you have to instance a new instance of your `Friendica\Core\Storage\IStorage` class.
Set the instance of your class as `$data['storage']` to pass it back to the backend.

This is necessary because it isn't always clear, if you need further construction arguments.

## Adding tests

**Currently testing is limited to core Friendica only, this shows theoretically how tests should work in the future**

Each new Storage class should be added to the test-environment at [Storage Tests](https://github.com/friendica/friendica/tree/develop/tests/src/Model/Storage/).

Add a new test class which's naming convention is `StorageClassTest`, which extend the `StorageTest` in the same directory.

Override the two necessary instances:

```php
use Friendica\Core\Storage\IWritableStorage;

abstract class StorageTest 
{
	// returns an instance of your newly created storage class
	abstract protected function getInstance();

	// Assertion for the option array you return for your new StorageClass
	abstract protected function assertOption(IWritableStorage $storage);
} 
```

## Exception handling

There are two intended types of exceptions for storages

### `ReferenceStorageExecption`

This storage exception should be used in case the caller tries to use an invalid references.
This could happen in case the caller tries to delete or update an unknown reference.
The implementation of the storage backend must not ignore invalid references.

Avoid throwing the common `StorageExecption` instead of the `ReferenceStorageException` at this particular situation!

### `StorageException`

This is the common exception in case unexpected errors happen using the storage backend.
If there's a predecessor to this exception (e.g. you caught an exception and are throwing this execption), you should add the predecessor for transparency reasons.

Example:

```php
use Friendica\Core\Storage\IWritableStorage;

class ExampleStorage implements IWritableStorage 
{
	public function get(string $reference) : string
	{
		try {
			throw new Exception('a real bad exception');
		} catch (Exception $exception) {
			throw new \Friendica\Core\Storage\Exception\StorageException(sprintf('The Example Storage throws an exception for reference %s', $reference), 500, $exception);
		}
	}
} 
```

## Example

Here is a hypothetical addon which register a useless storage backend.
Let's call it `samplestorage`.

This backend will discard all data we try to save and will return always the same image when we ask for some data.
The image returned can be set by the administrator in admin page.

First, the backend class.
The file will be `addon/samplestorage/SampleStorageBackend.php`:

```php
<?php
namespace Friendica\Addon\samplestorage;

use Friendica\Core\Storage\IWritableStorage;

use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;

class SampleStorageBackend implements IWritableStorage
{
	const NAME = 'Sample Storage';

	/** @var string */
	private $filename;

	/**
	  * SampleStorageBackend constructor.
 	  * 
	  * You can add here every dynamic class as dependency you like and add them to a private field
	  * Friendica automatically creates these classes and passes them as argument to the constructor									   
	  */
	public function __construct(string $filename) 
	{
		$this->filename = $filename;
	}

	public function get(string $reference)
	{
		// we return always the same image data. Which file we load is defined by
		// a config key
		return file_get_contents($this->filename);
	}
	
	public function put(string $data, string $reference = '')
	{
		if ($reference === '') {
			$reference = 'sample';
		}
		// we don't save $data !
		return $reference;
	}
	
	public function delete(string $reference)
	{
		// we pretend to delete the data
		return true;
	}
	
	public function __toString()
	{
		return self::NAME;
	}

	public static function getName()
	{
		return self::NAME;
	}
}
```

```php
<?php
namespace Friendica\Addon\samplestorage;

use Friendica\Core\Storage\IStorageConfiguration;

use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;

class SampleStorageBackendConfig implements IStorageConfiguration
{
	/** @var IConfig */
	private $config;
	/** @var L10n */
	private $l10n;

	/**
	  * SampleStorageBackendConfig constructor.
 	  * 
	  * You can add here every dynamic class as dependency you like and add them to a private field
	  * Friendica automatically creates these classes and passes them as argument to the constructor									   
	  */
	public function __construct(IConfig $config, L10n $l10n) 
	{
		$this->config = $config;
		$this->l10n   = $l10n;
	}

	public function getFileName(): string
	{
		return $this->config->get('storage', 'samplestorage', 'sample.jpg');
	}

	public function getOptions()
	{
		$filename = $this->config->get('storage', 'samplestorage', 'sample.jpg');
		return [
			'filename' => [
				'input',	// will use a simple text input
				$this->l10n->t('The file to return'),	// the label
				$filename,	// the current value
				$this->l10n->t('Enter the path to a file'), // the help text
				// no extra data for 'input' type..
			],
		];
	}
	
	public function saveOptions(array $data)
	{
		// the keys in $data are the same keys we defined in getOptions()
		$newfilename = trim($data['filename']);
		
		// this function should always validate the data.
		// in this example we check if file exists
		if (!file_exists($newfilename)) {
			// in case of error we return an array with
			// ['optionname' => 'error message']
			return ['filename' => 'The file doesn\'t exists'];
		}
		
		$this->config->set('storage', 'samplestorage', $newfilename);
		
		// no errors, return empty array
		return [];
	}

}
```

Now the plugin main file. Here we register and unregister the backend class.

The file is `addon/samplestorage/samplestorage.php`

```php
<?php
/**
 * Name: Sample Storage Addon
 * Description: A sample addon which implements an unusefull storage backend
 * Version: 1.0.0
 * Author: Alice <https://alice.social/~alice>
 */

use Friendica\Addon\samplestorage\SampleStorageBackend;
use Friendica\Addon\samplestorage\SampleStorageBackendConfig;
use Friendica\DI;

function samplestorage_install()
{
	Hook::register('storage_instance' , __FILE__, 'samplestorage_storage_instance');
	Hook::register('storage_config' , __FILE__, 'samplestorage_storage_config');
	DI::storageManager()->register(SampleStorageBackend::class);
}

function samplestorage_storage_uninstall()
{
	DI::storageManager()->unregister(SampleStorageBackend::class);
}

function samplestorage_storage_instance(App $a, array &$data)
{
	$config          = new SampleStorageBackendConfig(DI::l10n(), DI::config());
	$data['storage'] = new SampleStorageBackendConfig($config->getFileName());
}

function samplestorage_storage_config(App $a, array &$data)
{
	$data['storage_config'] = new SampleStorageBackendConfig(DI::l10n(), DI::config());
}

```

**Theoretically - until tests for Addons are enabled too - create a test class with the name `addon/tests/SampleStorageTest.php`:

```php
use Friendica\Core\Storage\IWritableStorage;
use Friendica\Test\src\Core\Storage\StorageTest;

class SampleStorageTest extends StorageTest 
{
	// returns an instance of your newly created storage class
	protected function getInstance()
	{
		// create a new SampleStorageBackend instance with all it's dependencies
		// Have a look at DatabaseStorageTest or FilesystemStorageTest for further insights
		return new SampleStorageBackend();
	}

	// Assertion for the option array you return for your new StorageClass
	protected function assertOption(IWritableStorage $storage)
	{
		$this->assertEquals([
			'filename' => [
				'input',
				'The file to return',
				'sample.jpg',
				'Enter the path to a file'
			],
		], $storage->getOptions());
	}
} 
```
