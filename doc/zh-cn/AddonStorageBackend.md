Friendica 存储后端插件开发
=========================

* [主页](help)

存储后端可以通过插件添加。
存储后端是实现为一个类，并且插件要注册该类以使其可用于系统中。

## 存储后端类

该类必须位于 `Friendica\Addon\youraddonname` 命名空间中，其中 `youraddonname` 是您的插件文件夹名称。

您需要实现两个不同的接口。

### `ICanWriteToStorage`

该类必须实现 `Friendica\Core\Storage\Capability\ICanWriteToStorage` 接口。必须实现接口中的所有方法:

```php
namespace Friendica\Core\Storage\Capability\ICanWriteToStorage;

interface ICanWriteToStorage
{
	public function get(string $reference);
	public function put(string $data, string $reference = '');
	public function delete(string $reference);
	public function __toString();
	public static function getName();
}
```

- `get(string $reference)` 返回 `$reference` 指向的数据。
- `put(string $data, string $reference)` 将 `$data` 中的数据保存到位置 `$reference` 中，如果 `$reference` 为空，则保存到新位置。
- `delete(string $reference)` 删除 `$reference` 指向的数据。

### `ICanConfigureStorage`

每个存储后端都可以具有管理员可以在管理员页面中设置的选项。
为使选项可行，您需要实现 `Friendica\Core\Storage\Capability\ICanConfigureStorage` 接口。

必须实现接口中的所有方法:

```php
namespace Friendica\Core\Storage\Capability\ICanConfigureStorage;

interface ICanConfigureStorage
{
	public function getOptions();
	public function saveOptions(array $data);
}
```

- `getOptions()` 返回一个数组，其中包含有关每个选项的详细信息以构建界面。
- `saveOptions(array $data)` 从管理员页面获取 `$data`，验证它并保存它。

如果后端没有任何选项，则可以返回空数组。

`getOptions()` 返回的数组定义如下:

	[
		'option1name' => [ ..info.. ],
		'option2name' => [ ..info.. ],
		...
	]

每个选项的信息数组定义如下:

	[
		'type',

定义表单中使用的字段和数据类型。
可以是 'checkbox'、'combobox'、'custom'、'datetime'、'input'、'intcheckbox'、'password'、'radio'、'richtext'、'select'、'select_raw'、'textarea' 中的一个

		'label',

字段的可翻译标签。该标签将显示在管理员页面中

		value,

选项的当前值

		'help text',

字段的可翻译描述。将显示在管理员页面中

		extra data

可选的。取决于此选项的 'type'：

- 'select'：选择项数组 `[ value => label ]`
- 'intcheckbox'：输入元素的值
- 'select_raw'：预构建的 `<option>` 标记的 HTML 字符串

每个标签都应该是可翻译的

	];

有关每个方法的详细信息，请参见 `IWritableStorage` 接口的 Doxygen 文档。

## 注册一个存储后端类

每个后端在插件安装时必须在系统中注册，以便可用。

使用 `DI::facStorage()->register(string $class)` 注册后端类。

当卸载插件时，必须使用 `DI::facStorage()->unregister(string $class)` 注销已注册的后端。

您必须在您的插件中注册一个新的钩子，监听 `storage_instance(App $a, array $data)`。如果 `$data['name']` 是您的存储类名称，则必须实例化一个新的 `Friendica\Core\Storage\Capability\ICanReadFromStorage` 类的实例。将您的类的实例设置为 `$data['storage']` ，以传递回端口。

这是必要的，因为有时不清楚是否需要进一步的构造参数。

## 添加测试

**目前测试仅限于 Friendica 核心，这理论上展示了测试在未来应该如何工作**

每个新的 Storage 类都应该添加到 [Storage Tests](https://github.com/friendica/friendica/tree/develop/tests/src/Model/Storage/) 的测试环境中。

添加一个新的测试类，其命名约定为 `StorageClassTest`，并扩展同一目录中的 `StorageTest`。

覆盖两个必要的实例：

```php
use Friendica\Core\Storage\Capability\ICanWriteToStorage;

abstract class StorageTest 
{
	// 返回您新创建的存储类的实例
    abstract protected function getInstance();

    // 用于您新的 StorageClass 返回选项数组的断言
    abstract protected function assertOption(ICanWriteToStorage $storage);
}
```

## 异常处理

存储有两种预期的异常类型

### `ReferenceStorageExecption`

在调用方尝试使用无效引用时，应使用此存储异常。这可能是在调用方尝试删除或更新未知引用的情况下发生的。实现存储后端时，必须不忽略无效引用。

请避免在此特定情况下抛出常见的 `StorageExecption` 而不是 `ReferenceStorageException`！

### `StorageException`

这是在使用存储后端时发生意外错误的常见异常。如果有前任（例如，您捕获了异常并正在抛出此异常），则应添加前任以提高透明度。

示例：

```php
use Friendica\Core\Storage\Capability\ICanWriteToStorage;

class ExampleStorage implements ICanWriteToStorage 
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

## 例子

下面是一个假设的插件，它注册了一个无用的存储后端。我们称之为 `samplestorage`。

这个后端将会丢弃我们尝试保存的所有数据，并且当我们请求一些数据时，它会始终返回相同的图片。管理员可以在管理员页面中设置返回的图片。

首先，是后端类。文件路径为 `addon/samplestorage/SampleStorageBackend.php`:

```php
<?php
namespace Friendica\Addon\samplestorage;

use Friendica\Core\Storage\Capability\ICanWriteToStorage;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;

class SampleStorageBackend implements ICanWriteToStorage
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

use Friendica\Core\Storage\Capability\ICanConfigureStorage;

use Friendica\Core\Config\Capability\IManageConfigValues;
use Friendica\Core\L10n;

class SampleStorageBackendConfig implements ICanConfigureStorage
{
	/** @var \Friendica\Core\Config\Capability\IManageConfigValues */
	private $config;
	/** @var L10n */
	private $l10n;

	/**
	  * SampleStorageBackendConfig constructor.
 	  * 
	  * You can add here every dynamic class as dependency you like and add them to a private field
	  * Friendica automatically creates these classes and passes them as argument to the constructor									   
	  */
	public function __construct(IManageConfigValues $config, L10n $l10n) 
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

现在让我们来看插件的主文件。在这里，我们会注册和取消注册后端类。

该文件位于`addon/samplestorage/samplestorage.php`。

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

**理论上，在为插件启用测试之前，可以创建名为 `addon/tests/SampleStorageTest.php` 的测试类：

```php
use Friendica\Core\Storage\Capability\ICanWriteToStorage;
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
	protected function assertOption(ICanWriteToStorage $storage)
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
