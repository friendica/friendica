Domain-Driven-Design：
==============

Friendica使用类结构，受Domain-Driven-Design编程模式的启发。本页面旨在解释它在Friendica开发中的实际含义。

## 灵感来源

- https://designpatternsphp.readthedocs.io/en/latest/Structural/DependencyInjection/README.html
- https://designpatternsphp.readthedocs.io/en/latest/Creational/SimpleFactory/README.html
- https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html
- https://designpatternsphp.readthedocs.io/en/latest/Creational/FactoryMethod/README.html
- https://designpatternsphp.readthedocs.io/en/latest/Creational/Prototype/README.html

## 核心概念

### 模型和集合

我们使用模型和集合，而不是数据库字段值的匿名数组，以充分利用PHP类型提示。

前:
```php
function doSomething(array $intros)
{
    foreach ($intros as $intro) {
        $introId = $intro['id'];
    }
}

$intros = \Friendica\Database\DBA::selectToArray('intros', [], ['uid' => Session::getLocalUser()]);

doSomething($intros);
```

后:

```php
function doSomething(\Friendica\Contact\Introductions\Collection\Introductions $intros)
{
    foreach ($intros as $intro) {
        /** @var $intro \Friendica\Contact\Introductions\Entity\Introduction */
        $introId = $intro->id;
    }
}

/** @var $intros \Friendica\Contact\Introductions\Collection\Introductions */
$intros = \Friendica\DI::intro()->selecForUser(Session::getLocalUser());

doSomething($intros);
```

### 依赖注入

在这个概念下，我们希望类对象随身携带它们将使用的依赖关系。
对象使用自己的类成员而不是调用全局/静态函数或方法。

前:
```php
class Model
{
    public $id;

    function save()
    {
        return \Friendica\Database\DBA::update('table', get_object_vars($this), ['id' => $this->id]);
    }
}
```

后:
```php
class Model
{
    /**
     * @var \Friendica\Database\Database
     */
    protected $dba;

    public $id;

    function __construct(\Friendica\Database\Database $dba)
    {
        $this->dba = $dba;
    }
    
    function save()
    {
        return $this->dba->update('table', get_object_vars($this), ['id' => $this->id]);
    }
}
```

主要优点是可测试性。
另一个优点是避免依赖循环和隐式初始化。
在第一个例子中，方法 `save()` 必须通过 `DBA::update()` 方法进行测试，该方法可能具有其自身的依赖关系。

在第二个例子中，我们可以模拟 `\Friendica\Database\Database`，例如通过将其方法替换为占位符来重载该类，这使我们只能隐式地测试 `Model::save()`，而无需测试其他任何内容。

主要缺点是对于依赖较重的类，构造函数变得冗长。
为了缓解这个问题，我们使用 [DiCe](https://r.je/dice) 来简化 Friendica 使用的高级对象的实例化。

我们还添加了一个方便的Factory `\Friendica\DI`，用于创建模块中使用的一些最常见的对象。

### Factory

由于我们向类构造函数添加了许多参数，因此实例化对象变得很麻烦。
为了保持简单，我们使用Factory。
Factory是用于生成其他对象的类，可以集中它们构造函数中所需的依赖项。
Factory封装了更多或更少复杂的对象创建，并创建它们无冗余。

前:
```php
$model = new Model(\Friendica\DI::dba());
$model->id = 1;
$model->key = 'value';

$model->save();
```

后:
```php
class Factory
{
    /**
     * @var \Friendica\Database\Database
     */
    protected $dba;

    function __construct(\Friendica\Database\Database $dba)
    {
        $this->dba;
    }

    public function create()
    {
        return new Model($this->dba);    
    }
}

$model = \Friendica\DI::factory()->create();
$model->id = 1;
$model->key = 'value';

$model->save();
```

这里，`DI::factory()` 返回一个 `Factory` 实例，可以用来创建一个 `Model` 对象，而无需关心其依赖项。

### 仓库

仓库是我们代码架构的最后一个组成部分，它们被设计为模型和存储方式之间的接口。
在Friendica中，它们存储在关系数据库中，但仓库使得模型不必关心这个细节。
仓库也扮演着它们所管理的模型的Factory的角色。

前:
```php
class Model
{
    /**
     * @var \Friendica\Database\Database
     */
    protected $dba;

    public $id;

    function __construct(\Friendica\Database\Database $dba)
    {
        $this->dba = $dba;
    }
    
    function save()
    {
        return $this->dba->update('table', get_object_vars($this), ['id' => $this->id]);
    }
}

class Factory
{
    /**
     * @var \Friendica\Database\Database
     */
    protected $dba;

    function __construct(\Friendica\Database\Database $dba)
    {
        $this->dba;
    }

    public function create()
    {
        return new Model($this->dba);    
    }
}


$model = \Friendica\DI::factory()->create();
$model->id = 1;
$model->key = 'value';

$model->save();
```

后:
```php
class Model {
    public $id;
}

class Repository extends Factory
{
    /**
     * @var \Friendica\Database\Database
     */
    protected $dba;

    function __construct(\Friendica\Database\Database $dba)
    {
        $this->dba;
    }

    public function create()
    {
        return new Model($this->dba);    
    }

    public function save(Model $model)
    {
        return $this->dba->update('table', get_object_vars($model), ['id' => $model->id]);
    }
}

$model = \Friendica\DI::repository()->create();
$model->id = 1;
$model->key = 'value';

\Friendica\DI::repository()->save($model);
```
