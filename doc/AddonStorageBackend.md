Friendica Storage Backend Addon development
===========================================

* [Home](help)

Storage backends can be added via addons.
A storage backend is implemented as a class, and the plugin register the class to make it avaiable to the system.

## The Storage Backend Class

The class must live in `Friendica\Addon\youraddonname` namespace, where `youraddonname` the folder name of your addon.

The class must implement `Friendica\Model\Storage\IStorage` interface. All method in the interface must be implemented:

namespace Friendica\Model\Storage;

```php
interface IStorage
{
	public function get(string $reference);
	public function put(string $data, string $reference = '');
	public function delete(string $reference);
	public function getOptions();
	public function saveOptions(array $data);
	public function __toString();
	public static function getName();
}
```

- `get(string $reference)` returns data pointed by `$reference`
- `put(string $data, string $reference)` saves data in `$data` to position `$reference`, or a new position if `$reference` is empty.
- `delete(string $reference)` delete data pointed by `$reference`

Each storage backend can have options the admin can set in admin page.

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
one of 'checkbox', 'combobox', 'custom', 'datetime', 'input', 'intcheckbox', 'password', 'radio', 'richtext', 'select', 'select_raw', 'textarea', 'yesno'

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
- 'yesno': array `[ 'label no', 'label yes']`

Each label should be translatable

	];


See doxygen documentation of `IStorage` interface for details about each method.

## Register a storage backend class

Each backend must be registered in the system when the plugin is installed, to be aviable.

`Repository::storageManage()->register(string $class)` is used to register the backend class.

When the plugin is uninstalled, registered backends must be unregistered using
`Repository::storageManage()->unregister(string $class)`.

You have to register a new hook in your addon, listening on `storage_instance(App $a, array $data)`.
In case `$data['name']` is your storage class name, you have to instance a new instance of your `Friendica\Model\Storage\IStorage` class.
Set the instance of your class as `$data['storage']` to pass it back to the backend.

This is necessary because it isn't always clear, if you need further construction arguments.

## Adding tests

**Currently testing is limited to core Friendica only, this shows theoretically how tests should work in the future**

Each new Storage class should be added to the test-environment at [Storage Tests](https://github.com/friendica/friendica/tree/develop/tests/src/Model/Storage/).

Add a new test class which's naming convention is `StorageClassTest`, which extend the `StorageTest` in the same directory.

Override the two necessary instances:
```php
use Friendica\Model\Storage\IStorage;

abstract class StorageTest 
{
	// returns an instance of your newly created storage class
	abstract protected function getInstance();

	// Assertion for the option array you return for your new StorageClass
	abstract protected function assertOption(IStorage $storage);
} 
```

## Example

Here an hypotetical addon which register an unusefull storage backend.
Let's call it `samplestorage`.

This backend will discard all data we try to save and will return always the same image when we ask for some data.
The image returned can be set by the administrator in admin page.

First, the backend class.
The file will be `addon/samplestorage/SampleStorageBackend.php`:

```php
<?php
namespace Friendica\Addon\samplestorage;

use Friendica\Model\Storage\IStorage;

use Friendica\Core\Config;
use Friendica\Core\L10n;

class SampleStorageBackend implements IStorage
{
	const NAME = 'Sample Storage';

	/** @var Config\IConfiguration */
	private $config;
	/** @var L10n\L10n */
	private $l10n;

	/**
	  * SampleStorageBackend constructor.
	  * @param Config\IConfiguration $config The configuration of Friendica
	  *									  
	  * You can add here every dynamic class as dependency you like and add them to a private field
	  * Friendica automatically creates these classes and passes them as argument to the constructor									   
	  */
	public function __construct(Config\IConfiguration $config, L10n\L10n $l10n) 
	{
		$this->config = $config;
		$this->l10n   = $l10n;
	}

	public function get(string $reference)
	{
		// we return always the same image data. Which file we load is defined by
		// a config key
		$filename = $this->config->get('storage', 'samplestorage', 'sample.jpg');
		return file_get_contents($filename);
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
use Friendica\Registry\Repository;

function samplestorage_install()
{
	// on addon install, we register our class with name "Sample Storage".
	// note: we use `::class` property, which returns full class name as string
	// this save us the problem of correctly escape backslashes in class name
	Repository::storage()->register(SampleStorageBackend::class);
}

function samplestorage_unistall()
{
	// when the plugin is uninstalled, we unregister the backend.
	Repository::storage()->unregister(SampleStorageBackend::class);
}

function samplestorage_storage_instance(\Friendica\App $a, array $data)
{
    if ($data['name'] === SampleStorageBackend::getName()) {
    // instance a new sample storage instance and pass it back to the core for usage
        $data['storage'] = new SampleStorageBackend(Core::config(), Core::l10n(), Core::cache());
    }
}
```

**Theoretically - until tests for Addons are enabled too - create a test class with the name `addon/tests/SampleStorageTest.php`:

```php
use Friendica\Model\Storage\IStorage;
use Friendica\Test\src\Model\Storage\StorageTest;

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
	protected function assertOption(IStorage $storage)
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
