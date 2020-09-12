Domain-Driven-Design
==============

* [Home](help)
  * [Developer Intro](help/Developers-Intro)

Friendica uses class structures inspired by Domain-Driven-Design programming patterns.
This page is meant to explain what it means in practical terms for Friendica development.

## Inspiration

- https://designpatternsphp.readthedocs.io/en/latest/Structural/DependencyInjection/README.html
- https://designpatternsphp.readthedocs.io/en/latest/Creational/SimpleFactory/README.html
- https://designpatternsphp.readthedocs.io/en/latest/More/Repository/README.html
- https://designpatternsphp.readthedocs.io/en/latest/Creational/FactoryMethod/README.html
- https://designpatternsphp.readthedocs.io/en/latest/Creational/Prototype/README.html

## Core concepts

### Models and Collections

Instead of anonymous arrays of arrays of database field values, we have Models and collections to take full advantage of PHP type hints.

Before:
```php
function doSomething(array $intros)
{
    foreach ($intros as $intro) {
        $introId = $intro['id'];
    }
}

$intros = \Friendica\Database\DBA::selectToArray('intros', [], ['uid' => local_user()]);

doSomething($intros);
```

After:
```php
function doSomething(\Friendica\Domain\Collection\Introductions $intros)
{
    foreach ($intros as $intro) {
        /** @var $intro \Friendica\Domain\Model\Introduction */
        $introId = $intro->id;
    }
}

/** @var $intros \Friendica\Domain\Collection\Introductions */
$intros = \Friendica\DI::intro()->select(['uid' => local_user()]);

doSomething($intros);
```

### Dependency Injection

Under this concept, we want class objects to carry with them the dependencies they will use.
Instead of calling global/static function/methods, objects use their own class members.

Before:
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

After:
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

The main advantage is testability.
Another one is avoiding dependency circles and avoid implicit initializing.
In the first example the method `save()` has to be tested with the `DBA::update()` method, which may or may not have dependencies itself.

In the second example we can mock `\Friendica\Database\Database`, e.g. overload the class by replacing its methods by placeholders, which allows us to test only `Model::save()` and nothing else implicitly.

The main drawback is lengthy constructors for dependency-heavy classes.
To alleviate this issue we are using [DiCe](https://r.je/dice) to simplify the instantiation of the higher level objects Friendica uses.

We also added a convenience factory named `\Friendica\DI` that creates some of the most common objects used in modules.

### Factories

Since we added a bunch of parameters to class constructors, instantiating objects has become cumbersome.
To keep it simple, we are using Factories.
Factories are classes used to generate other objects, centralizing the dependencies required in their constructor.
Factories encapsulate more or less complex creation of objects and create them redundancy free.

Before:
```php
$model = new Model(\Friendica\DI::dba());
$model->id = 1;
$model->key = 'value';

$model->save();
```

After:
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

Here, `DI::factory()` returns an instance of `Factory` that can then be used to create a `Model` object without having to care about its dependencies.

### Repositories

Last building block of our code architecture, repositories are meant as the interface between models and how they are stored.
In Friendica they are stored in a relational database but repositories allow models not to have to care about it.
Repositories also act as factories for the Model they are managing.

Before:
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

After:
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
