PHPMongo
========
[![Build Status](https://travis-ci.org/sokil/php-mongo.png?branch=master&1)](https://travis-ci.org/sokil/php-mongo)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-mongo/v/stable.png)](https://packagist.org/packages/sokil/php-mongo)
[![Coverage Status](https://coveralls.io/repos/sokil/php-mongo/badge.png)](https://coveralls.io/r/sokil/php-mongo)
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/sokil/php-mongo?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Documentation Status](https://readthedocs.org/projects/phpmongo/badge/?version=latest)](https://readthedocs.org/projects/phpmongo/?badge=latest)
[![Total Downloads](http://img.shields.io/packagist/dt/sokil/php-mongo.svg)](https://packagist.org/packages/sokil/php-mongo)

#### PHP ODM for MongoDB.

Why to use this library? You can easily work with document data through comfortable getters and setters instead of array and don't check if key exist in array. Access to subdocument use dot-syntax. You can validate data passed to document before save. We give you  events, which you can handle in different moments of document's life, and more things which make you life easier.

#### Requirements

* PHP 5.3 or above
* PHP MongoDB Extension 0.9 or above (Some features require >= 1.5)
* Symfony Event Dispatcher
* PSR-3 logger interface

#### Table of contents

* [Installation](#installation)
* [Connecting](#connecting)
* [Selecting database and collection](#selecting-database-and-collection)
* [Document schema](#document-schema)
* [Getting documents by id](#getting-documents-by-id)
* [Create new document](#create-new-document)
* [Get and set data in document](#get-and-set-data-in-document)
* [Storing document](#storing-document)
* [Querying documents](#querying-documents)
* [Pagination](#pagination)
* [Batch operations](#batch-operations)
  * [Batch insert](#batch-insert)
  * [Batch update](#batch-update)
  * [Moving data between collections](#moving-data-between-collections)
* [Persistence (Unit of Work)](#persistence-unit-of-work)
* [Document validation](#document-validation)
* [Deleting collections and documents](#deleting-collections-and-documents)
* [Aggregation framework](#aggregation-framework)
* [Events](#events)
* [Behaviors](#behaviors)
* [Relation](#relations)
  * [One-to-one relation](#one-to-one-relation)
  * [One-to-many relation](#one-to-many-relation)
  * [Many-to-many relation](#many-to-many-relation)
  * [Add relation](#add-relation)
  * [Remove relation](#remove-relation)
* [Read preferences](#read-preferences)
* [Write concern](#write-concern)
* [Capped collections](#capped-collections)
* [Executing commands](#executing-commands)
* [Queue](#queue)
* [Migrations](#migrations)
* [GridFS](#gridfs)
* [Versioning](#versioning)
* [Indexes](#indexes)
* [Caching and documents with TTL](#caching-and-documents-with-ttl)
* [Debugging](#debugging)
  * [Logging](#logging)
  * [Profiling](#profiling)

Installation
------------

You can install library through Composer:
```javascript
{
    "require": {
        "sokil/php-mongo": "dev-master"
    }
}
```

If you use Symfony framework, you can use [Symfony MongoDB Bundle](https://github.com/sokil/php-mongo-bundle) which wraps this library

```javascript
{
    "require": {
        "sokil/php-mongo-bundle": "dev-master"
    }
}
```

If you use Yii Framework, you can use [Yii Adapter](https://github.com/sokil/php-mongo-yii) which wraps this library

```javascript
{
    "require": {
        "sokil/php-mongo-yii": "dev-master"
    }
}
```

If you require migrations, you can add dependency to "[sokil/php-mongo-migrator](https://github.com/sokil/php-mongo-migrator)", based on this library:

```javascript
{
    "require": {
        "sokil/php-mongo-migrator": "dev-master"
    }
}
```

Connecting
----------

#### Single connection

Connecting to MongoDB server made through `\Sokil\Mongo\Client` class:

```php
<?php
$client = new Client($dsn);
```

Format of DSN used to connect to server described in [PHP manual](http://www.php.net/manual/en/mongo.connecting.php).
To connect to localhost use next DSN:
```
mongodb://127.0.0.1
```
To connect to replica set use next DSN:
```
mongodb://server1.com,server2.com/?replicaSet=replicaSetName
```

#### Pool of connections

If you have few connections you may prefer connection pool instead of managing different connections. Use `\Sokil\Mongo\ClientPool` instance to initialize pool object:

```php
<?php

$pool = new ClientPool(array(
    'connect1' => array(
        'dsn' => 'mongodb://127.0.0.1',
        'defaultDatabase' => 'db2',
        'connectOptions' => array(
            'connectTimeoutMS' => 1000,
            'readPreference' => \MongoClient::RP_PRIMARY,
        ),
        'mapping' => array(
            'db1' => array(
                'col1' => '\Collection1',
                'col2' => '\Collection2',
            ),
            'db2' => array(
                'col1' => '\Collection3',
                'col2' => '\Collection4',
            )
        ),
    ),
    'connect2' => array(
        'dsn' => 'mongodb://127.0.0.1',
        'defaultDatabase' => 'db2',
        'mapping' => array(
            'db1' => array(
                'col1' => '\Collection5',
                'col2' => '\Collection6',
            ),
            'db2' => array(
                'col1' => '\Collection7',
                'col2' => '\Collection8',
            )
        ),
    ),
));

$connect1Client = $pool->get('connect1');
$connect2Client = $pool->get('connect2');
```

Selecting database and collection
-----------------------
To get instance of database class `\Sokil\Mongo\Database`:
```php
<?php
$database = $client->getDatabase('databaseName');
// or simply
$database = $client->databaseName;
```

To get instance of collection class `\Sokil\Mongo\Collection`:
```php
<?php
$collection = $database->getCollection('collectionName');
// or simply
$collection = $database->collectionName;
```

Default database may be specified to get collection directly from `$client` object:
```php
<?php
$client->useDatabase('databaseName');
$collection = $client->getCollection('collectionName');
```

If you need to use your own collection classes, you must create class extended from `\Sokil\Mongo\Collection` and map it to collection class:
```php
<?php
class CustomCollection extends \Sokil\Mongo\Collection
{

}

$client->map([
    'databaseName'  => [
        'collectionName' => '\CustomCollection'
    ],
]);

/**
 * @var \CustomCollection
 */
$collection = $client->getDatabase('databaseName')->getCollection('collectionName');
```

Mapping may be specified through class prefix.
```php
<?php
$client->map([
    'databaseName'  => '\Class\Prefix',
]);

/**
 * @var \Class\Prefix\CollectionName
 */
$collection = $client->getDatabase('databaseName')->getCollection('collectionName');

/**
 * @var \Class\Prefix\CollectionName\SubName
 */
$collection = $client->getDatabase('databaseName')->getCollection('collectionName.subName');
```

If you want to pass some options to collection's constructor, you also can
configure them in mapping definition:

```php
<?php
$client->map([
    'databaseName'  => [
        'collectionName' => [
            'class' => '\Some\Custom\Collection\Classname',
            'collectionOption1' => 'value1',
            'collectionOption2' => 'value2',
        ]
    ],
]);
```

If 'class' omitted, then used standart `\Sokil\Mongo\Collection class`.
All options lated may be accessed:

```php
<?php
// will return 'value1'
$client
    ->getDatabase('databaseName')
    ->getCollection('collectionName')
    ->getOption('collectionOption1');
```

To override default document class use `documentClass` option of collection:
```php
<?php
$client->map([
    'databaseName'  => [
        'collectionName' => [
            'documentClass' => '\Some\Document\Class',
        ]
    ],
]);

// is instance of \Some\Document\Class
$document = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName')
    ->createDocument();
```

Collection name in mapping may be defined as RegExp pattern. Pattern must start
from symbol `/`:

```php
<?php
$database->map(array(
    '/someCollection\d/' => '\Some\Collection\Class',
));
```

Any collection with name matched to pattern will be instance of `\Some\Collection\Class`:
```php
<?php
$col1 = $database->getCollection('someCollection1');
$col2 = $database->getCollection('someCollection2');
$col4 = $database->getCollection('someCollection4');
```

Document schema
------------------------

Document object is instance of class `\Sokil\Mongo\Document`. If you want to use your own class, you must configure its name in collection's class:

```php
<?php
class CustomCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null) {
        return '\CustomDocument';
    }
}

class CustomDocument extends \Sokil\Mongo\Document
{

}
```

You may flexibly configure document's class in `\Sokil\Mongo\Collection::getDocumentClassName()` relatively to concrete document's data:
```php
<?php
class CustomCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null) {
        return '\Custom' . ucfirst(strtolower($documentData['type'])) . 'Document';
    }
}
```

In example above class `\CustomVideoDocument` related to `{"_id": "45..", "type": "video"}`, and `\CustomAudioDocument` to `{"_id": "45..", type: "audio"}`

Document's scheme is completely not required. If field is required and has default value, it can be defined in special property of document class:
```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    protected $_data = [
        'requiredField' => 'defaultValue',
        'someField'     => [
            'subDocumentField' => 'value',
        ],
    ];
}
```

Getting documents by id
-----------------------

To get document from collection by its id:
```php
<?php
$document = $collection->getDocument('5332d21b253fe54adf8a9327');
```

Create new document
-------------------

Create new empty document object:

```php
<?php
$document = $collection->createDocument();
```

Or with pre-defined values:

```php
<?php
$document = $collection->createDocument([
    'param1' => 'value1',
    'param2' => 'value2'
]);
```

Get and set data in document
----------------------------

To get value of document's field you may use one of following ways:
```php
<?php
$document->requiredField; // defaultValue
$document->get('requiredField'); // defaultValue
$document->getRequiredField(); // defaultValue

$document->someField; // ['subDocumentField' => 'value']
$document->get('someField'); // ['subDocumentField' => 'value']
$document->getSomeField(); // ['subDocumentField' => 'value']
$document->get('someField.subDocumentField'); // 'value'

$document->get('some.unexisted.subDocumentField'); // null
```
If field not exists, null value returned.

To set value you may use following ways:
```php
<?php
$document->someField = 'someValue'; // {someField: 'someValue'}
$document->set('someField', 'someValue'); // {someField: 'someValue'}
$document->set('someField.sub.document.field', 'someValue'); // {someField: {sub: {document: {field: {'someValue'}}}}}
$document->setSomeField('someValue');  // {someField: 'someValue'}
```

Storing document
----------------

To store document in database just save it.
```php
<?php
$document = $collection->createDocument(['param' => 'value'])->save();

$document = $collection->getDocument('23a4...')->set('param', 'value')->save();
```

Querying documents
------------------

To query documents, which satisfy some conditions you need to use query builder:
```php
<?php
$cursor = $collection
    ->find()
    ->fields(['name', 'age'])
    ->where('name', 'Michael')
    ->whereGreater('age', 29)
    ->whereIn('interests', ['php', 'snowboard', 'traveling'])
    ->skip(20)
    ->limit(10)
    ->sort([
        'name'  => 1,
        'age'   => -1,
    ]);
```

All "where" conditions added with logical AND. To add condition with logical OR:
```
$cursor = $collection
    ->find()
    ->whereOr(
        $collection->expression()->where('field1', 50),
        $collection->expression()->where('field2', 50),
    );
```

Result of the query is iterator `\Sokil\Mongo\QueryBuilder`, which you can then iterate:
```php
<?php
foreach($cursor as $documentId => $document) {
    echo $document->get('name');
}
```

Or you can get result array:
```php
<?php
$result = $cursor->findAll();
```

To get only one result:
```php
<?php
$document = $cursor->findOne();
```

To get only one random result:
```php
<?php
$document = $cursor->findRandom();
```

To get values from a single field in the result set of documents:
```php
<?php
$columnValues = $cursor->pluck('some.field.name');
```

For extending standart query builder class with custom condition methods you need to override property `Collection::$_queryExpressionClass` with class, which extends `\Sokil\Mongo\Expression`:

```php
<?php

// define expression in collection
class UserCollection extends \Sokil\Mongo\Collection
{
    protected $_queryExpressionClass = 'UserExpression';
}

// define expression
class UserExpression extends \Sokil\Mongo\Expression
{
    public function whereAgeGreaterThan($age)
    {
        $this->whereGreater('age', (int) $age);
    }
}

// use custom method for searching
$collection = $db->getCollection('user'); // instance of UserCollection
$queryBuilder = $collection->find(); // instance of UserExpression

$queryBuilder->whereAgeGreaterThan(18)->fetchRandom();

// or since v.1.3.2 configure query builder through callable:
$collection
    ->find(function(UserExpression $e) {
        $e->whereAgeGreaterThan(18);
    })
    ->fetchRandom();

```

If you want to cache your results or want to compare to queries, you need some
identifier which unambiguously identify query. You can use `Cursor::getHash()` for
that reason:

```
$queryBuilder = $this->collection
    ->find()
    ->field('_id')
    ->field('ineterests')
    ->sort(array(
        'age' => 1,
        'gender' => -1,
    ))
    ->limit(10, 20)
    ->whereAll('interests', ['php', 'snowboard']);

    $hash = $queryBuilder->getHash(); // will return 508cc93b371c222c53ae90989d95caae

    if($cache->has($hash)) {
        return $cache->get($hash);
    }

    $result = $queryBuilder->findAll();

    $cache->set($hash, $result);
    return $result;
```

Pagination
----------

Query builder allows you to create pagination.
```php
<?php
$paginator = $collection->find()->where('field', 'value')->paginate(3, 20);
$totalDocumentNumber = $paginator->getTotalRowsCount();
$totalPageNumber = $paginator->getTotalPagesCount();

// iterate through documents
foreach($paginator as $document) {
    echo $document->getId();
}
```


Batch operations
----------------

### Batch insert

To insert many documents at once with validation of inserted document:
```php
<?php
$collection->insertMultiple(array(
    array('i' => 1),
    array('i' => 2),
));
```

### Batch update

Making changes in few documents:

```php
<?php

$collection->updateMultiple(function(\Sokil\Mongo\Expression $expression) {
    return $expression->where('field', 'value');
}, array('field' => 'new value'));
```

To update all documents:
```php
<?php
$collection->updateAll(array('field' => 'new value'));
```

### Moving data between collections

To copy documents from one collection to another according to expression:

```php
<?php
// to new collection of same database
$collection
    ->find()
    ->where('condition', 1)
    ->copyToCollection('newCollection');

// to new collection in new database
$collection
    ->find()
    ->where('condition', 1)
    ->copyToCollection('newCollection', 'newDatabase');
```

To move documents from one collection to another according to expression:

```php
<?php
// to new collection of same database
$collection
    ->find()
    ->where('condition', 1)
    ->moveToCollection('newCollection');

// to new collection in new database
$collection
    ->find()
    ->where('condition', 1)
    ->moveToCollection('newCollection', 'newDatabase');
```

Important to note that there is no transactions so if error will occur
during process, no changes will rollback.

Persistence (Unit of Work)
--------------------------

Instead of saving and removing objects right now, we can queue this job and execute all changes at once. This may be done through well-known pattern Unit of Work.

Lets create persistance manager
```php
<?php
$persistence = $client->createPersistence();
```

Now we can add some documents to be saved or removed later
```php
<?php
$persistence->persist($document1);
$persistence->persist($document2);

$persistence->remove($document3);
$persistence->remove($document4);
```

If later we decice do not save or remove document, we may detach it from persistence manager
```php
<?php
$persistence->detach($document1);
$persistence->detach($document3);
```

Or we even may remove them all:
```php
<?php
$persistence->clear();
```

Note that after detaching document from persistence manager, it's changes do not removed and document still may be saved directly or by adding to persistence manager.

If we decide to store changes to databasae we may flush this changes:
```php
<?php
$persistence->flush();
```

Note that persisted documents do not deleted from persistence manager after flush, but removed will be deleted.

Document validation
-------------------

Document can be validated before save. To set validation rules method `\Sokil\Mongo\Document::roles()` must be override with validation rules. Supported rules are:
```php
<?php
class CustomDocument except \Sokil\Mongo\Document
{
    public function rules()
    {
        return array(
            array('email,password', 'required'),
            array('role', 'equals', 'to' => 'admin'),
            array('role', 'not_equals', 'to' => 'guest'),
            array('role', 'in', 'range' => array('admin', 'manager', 'user')),
            array('contract_number', 'numeric', 'message' => 'Custom error message, shown by getErrors() method'),
            array('contract_number' ,'null', 'on' => 'SCENARIO_WHERE_CONTRACT_MUST_BE_NULL'),
            array('code' ,'regexp', '#[A-Z]{2}[0-9]{10}#')
        );
    }
}
```

Document can have validation state, based on scenario. Scenarion can be specified by method `Document::setScenario($scenario)`.
```php
<?php
$document->setScenario('register');
```

If some validation rule applied only for some scenarios, this scenarios must be passed on `on` key, separated by comma.
```php
<?php
public function rules()
    {
        return array(
            array('field' ,'null', 'on' => 'register,update'),
        );
    }
```

If some validation rule applied to all except some scenarios, this scenarios must be passed on `except` key, separated by comma.

```php
<?php
public function rules()
    {
        return array(
            array('field' ,'null', 'except' => 'register,update'),
        );
    }
```

If document invalid, `\Sokil\Mongo\Document\Exception\Validate` will trigger and errors may be accessed through `Document::getErrors()` method of document object. This document may be get from exception method:
```php
<?php
try {

} catch(\Sokil\Mongo\Document\Exception\Validate $e) {
    $e->getDocument()->getErrors();
}
```

Error may be triggered manually by calling method `triggerError($fieldName, $rule, $message)`
```php
<?php
$document->triggerError('someField', 'email', 'E-mail must be at domain example.com');
```

You may add you custom validation rule just adding method to document class and defining method name as rule:
```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    punlic function rules()
    {
        return array(
            array(
                'email',
                'uniqueFieldValidator',
                'message' => 'E-mail must be unique in collection'
            ),
        );
    }

    /**
     * Validator
     */
    public function uniqueFieldValidator($fieldName, $params)
    {
        // Some logic of checking unique mail.
        //
        // Before version 1.7 this method must return true if validator passes,
        // and false otherwise.
        //
        // Since version 1.7 this method return no values and must call
        // Document::addError() method to add error into stack.
    }
}
```

You may create your own validator class, if you want to use validator in few classes.
Just extend your class from abstract validator class `\Sokil\Mongo\Validator` and register your own validator namespace:

```php
<?php
namespace Vendor\Mongo\Validator;

/**
 * Validator class
 */
class MyOwnEqualsValidator extends \Sokil\Mongo\Validator
{
    public function validateField(\Sokil\Mongo\Document $document, $fieldName, array $params)
    {
        if (!$document->get($fieldName)) {
            return;
        }

        if ($document->get($fieldName) === $params['to']) {
            return;
        }

        if (!isset($params['message'])) {
            $params['message'] = 'Field "' . $fieldName . '" must be equals to "' . $params['to'] . '" in model ' . get_called_class();
        }

        $document->addError($fieldName, $this->getName(), $params['message']);
    }
}

/**
 * Registering validator in document
 */

class SomeDocument extends \Sokil\Mongo\Document
{
    public function beforeConstruct()
    {
        $this->addValidatorNamespace('Vendor\Mongo\Validator');
    }

    public function rules()
    {
        return array(
            // 'my_own_equals_validator' converts to 'MyOwnEqualsValidator' class name
            array('field', 'my_own_equals_validator', 'to' => 42, 'message' => 'Not equals'),
        );
    }
}
```

Deleting collections and documents
-----------------------------------

Deleting of collection:
```php
<?php
$collection->delete();
```

Deleting of document:
```php
<?php
$document = $collection->getDocument($documentId);
$collection->deleteDocument($document);
// or simply
$document->delete();
```

Deleting of few documents:
```php
<?php
$collection->deleteDocuments($collection->expression()->where('param', 'value'));
```

Aggregation framework
--------------------------------

To do aggregation you need first to create pipelines object:
```php
<?php
$pipeline = $collection->createPipeline();
````

To get results of aggregation after configuring pipelines:
```php
<?php
/**
 * @var array list of aggregation results
 */
$result = $pipeline->aggregate();
```

Match pipeline:
```php
<?php
$pipeline-> match([
    'date' => [
        '$lt' => new \MongoDate,
    ]
]);
```


Events
-------
Event support based on Symfony's Event Dispatcher component. Events can be attached in class while initialusing object or any time to the object. To attach events in Document class you need to override `Document::beforeConstruct()` method:
```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    public function beforeConstruct()
    {
        $this->onBeforeSave(function() {
            $this->set('date' => new \MongoDate);
        });
    }
}
```

Or you can attach event handler to document object:
```php
<?php
$document->onBeforeSave(function() {
    $this->set('date' => new \MongoDate);
});
```
To cancel operation execution on some condition use event handling cancel:
```php
<?php
$document
    ->onBeforeSave(function(\Sokil\Mongo\Event $event) {
        if($this->get('field') === 42) {
            $event->cancel();
        }
    })
    ->save();
```


Behaviors
----------

Behavior is a posibility to extend functionality of document object and reuse code among documents of different class.
Behavior is a class extended from `\Sokil\Mongo\Behavior`:
```php
<?php
class SomeBehavior extends \Sokil\Mongo\Behavior
{
    public function return42()
    {
        return 42;
    }
}
```

To get instance of object, to which behavior is attached, call `Behavior::getOwner()` method:
```php
<?php
class SomeBehavior extends \Sokil\Mongo\Behavior
{
    public function getOwnerParam($selector)
    {
        return $this->getOwner()->get($selector);
    }
}
```

You can add behavior in document class:
```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    public function behaviors()
    {
        return [
            '42behavior' => '\SomeBehavior',
        ];
    }
}
```

You can attach behavior in runtime too:
```php
<?php
$document->attachBehavior(new \SomeBehavior);
```

Then you can call any methods of behaviors. This methods searches in order of atraching behaviors:
```php
<?php
echo $document->return42();
```

Relations
-------------

You can define relations between different documents, which helps you to load related doluments. Library supports relations one-to-one and one-to-many

To define relation to other document you need to override `Document::relations()` method and returl array of relations in format `[relationName => [relationType, targetCollection, reference], ...]`

### One-to-one relation

We have to classes User and Profile. User has one profile, and profile belongs to User.

```php
<?php
class User extends \Sokil\Mongo\Document
{
    protected $_data = [
        'email'     => null,
        'password'  => null,
    ];

    public function relations()
    {
        return [
            'profileRelation' => [self::RELATION_HAS_ONE, 'profile', 'user_id'],
        ];
    }
}

class Profile extends \Sokil\Mongo\Document
{
    protected $_data = [
        'name' => [
            'last'  => null,
            'first' => null,
        ],
        'age'   => null,
    ];

    public function relations()
    {
        return [
            'userRelation' => [self::RELATION_BELONGS, 'user', 'user_id'],
        ];
    }
}
```

Now we can lazy load related documnts just calling relation name:
```php
<?php
$user = $userColletion->getDocument('234...');
echo $user->profileRelation->get('age');

$profile = $profileCollection->getDocument('234...');
echo $pfofile->userRelation->get('email');
```

### One-to-many relation

One-to-many relation helps you to load all related documents. Class User has few posts of class Post:

```php
<?php
class User extends \Sokil\Mongo\Document
{
    protected $_data = [
        'email'     => null,
        'password'  => null,
    ];

    public function relations()
    {
        return [
            'postsRelation' => [self::RELATION_HAS_MANY, 'posts', 'user_id'],
        ];
    }
}

class Posts extends \Sokil\Mongo\Document
{
    protected $_data = [
        'user_id' => null,
        'message'   => null,
    ];

    public function relations()
    {
        return [
            'userRelation' => [self::RELATION_BELONGS, 'user', 'user_id'],
        ];
    }

    public function getMessage()
    {
        return $this->get('message');
    }
}
```

Now you can load related posts of document:
```php
<?php
foreach($user->postsRelation as $post) {
    echo $post->getMessage();
}
```

### Many-to-many relation

Many-to-many relation in relational databases uses intermediate table with stored ids of related rows from both tables. In mongo this table equivalent embeds to one of two related documents. Element of relation definition at position 3 must be set to true in this document.


```php
<?php

// this document contains field 'driver_id' where array of ids stored
class CarDocument extends \Sokil\Mongo\Document
{
    protected $_data = [
        'brand' => null,
    ];

    public function relations()
    {
        return array(
            'drivers'   => array(self::RELATION_MANY_MANY, 'drivers', 'driver_id', true),
        );
    }
}

class DriverDocument extends \Sokil\Mongo\Document
{
    protected $_data = [
        'name' => null,
    ];

    public function relations()
    {
        return array(
            'cars'    => array(self::RELATION_MANY_MANY, 'cars', 'driver_id'),
        );
    }
}
```

Now you can load related documents:
```php
<?php
foreach($car->drivers as $driver) {
    echo $driver->name;
}
```

### Add relation

There is helper to add related document, if you don't
want modify relation field directly:

```php
<?php
$car->addRelation('drivers', $driver);
```

This helper automatically resolves collection and field
where to store relation data.

### Remove relation

There is helper to remove related document, if you don't
want modify relation field directly:

```php
<?php
$car->removeRelation('drivers', $driver);
```

This helper automatically resolves collection and field
where to remove relation data. If relation type is `HAS_MANY` or `BELONS_TO`,
second parameter wich defined related object may be omitted.


Read preferences
----------------
[Read preference](http://docs.mongodb.org/manual/core/read-preference/) describes how MongoDB clients route read operations to members of a replica set. You can configure read preferences at any level:

```php
<?php
// in constructor
$client = new Client($dsn, array(
    'readPreference' => 'nearest',
));

// by passing to \Sokil\Mongo\Client instance
$client->readNearest();

// by passing to database
$database = $client->getDatabase('databaseName')->readPrimaryOnly();

// by passing to collection
$collection = $database->getCollection('collectionName')->readSecondaryOnly();
```

Write concern
-------------
[Write concern](http://docs.mongodb.org/manual/core/write-concern/) describes the guarantee that MongoDB provides when reporting on the success of a write operation. You can configure write concern at any level:

```php
<?php

// by passing to \Sokil\Mongo\Client instance
$client->setMajorityWriteConcern(10000);

// by passing to database
$database = $client->getDatabase('databaseName')->setMajorityWriteConcern(10000);

// by passing to collection
$collection = $database->getCollection('collectionName')->setWriteConcern(4, 1000);
```

Capped collections
------------------

To use capped collection you need previously to create it:
```php
<?php
$numOfElements = 10;
$sizeOfCollection = 10*1024;
$collection = $database->createCappedCollection('capped_col_name', $numOfElements, $sizeOfCollection);
```

Now you can add only 10 documents to collection. All old documents will ve rewritted ny new elements.

Executing commands
------------------

Command is universal way to do anything with mongo. Let's get stats of collection:
```php
<?php
$collection = $database->createCappedCollection('capped_col_name', $numOfElements, $sizeOfCollection);
$stats = $database->executeCommand(['collstat' => 'capped_col_name']);
```

Result in $stats:
```
array(13) {
  'ns' =>  string(29) "test.capped_col_name"
  'count' =>  int(0)
  'size' =>  int(0)
  'storageSize' =>  int(8192)
  'numExtents' =>  int(1)
  'nindexes' =>  int(1)
  'lastExtentSize' =>  int(8192)
  'paddingFactor' =>  double(1)
  'systemFlags' =>  int(1)
  'userFlags' =>  int(1)
  'totalIndexSize' =>  int(8176)
  'indexSizes' =>  array(1) {
    '_id_' =>    int(8176)
  }
  'ok' =>  double(1)
}
```

Queue
-----

Queue gives functionality to send messages from one process and get them in another process. Messages can be send to different channels.

Sending message to queue with default priority:
```php
<?php
$queue = $database->getQueue('channel_name');
$queue->enqueue('world');
$queue->enqueue(['param' => 'value']);
```

Send message with priority
```php
<?php
$queue->enqueue('hello', 10);
```

Reading messages from channel:
```php
<?php
$queue = $database->getQueue('channel_name');
echo $queue->dequeue(); // hello
echo $queue->dequeue(); // world
echo $queue->dequeue()->get('param'); // value
```

Number of messages in queue
```php
<?php
$queue = $database->getQueue('channel_name');
echo count($queue);
```

Migrations
----------

Migrations allows you easily change schema and data versions. This functionality implemented in packet https://github.com/sokil/php-mongo-migrator and can be installed through composer:
```javascript
{
    "require": {
        "sokil/php-mongo-migrator": "dev-master"
    }
}
```

GridFS
------

GridFS allows you to store binary data in mongo database. Details at http://docs.mongodb.org/manual/core/gridfs/.

First get instance of GridFS. You can specify prefix for partitioning filesystem:

```php
<?php
$imagesFS = $database->getGridFS('image');
$cssFS = $database->getGridFS('css');
```

Now you can store file, located on disk:
```php
<?php
$id = $imagesFS->storeFile('/home/sokil/images/flower.jpg');
```

You can store file from binary data:
```php
<?php
$id1 = $imagesFS->storeBytes('some text content');
$id2 = $imagesFS->storeBytes(file_get_contents('/home/sokil/images/flower.jpg'));
```

You are able to store some metadata with every file:
```php
<?php
$id1 = $imagesFS->storeFile('/home/sokil/images/flower.jpg', [
    'category'  => 'flower',
    'tags'      => ['flower', 'static', 'page'],
]);

$id2 = $imagesFS->storeBytes('some text content', [
    'category' => 'books',
]);
```

Get file by id:
```php
<?php
$imagesFS->getFileById('6b5a4f53...42ha54e');
```

Find file by metadata:
```php
<?php
foreach($imagesFS->find()->where('category', 'books') as $file) {
    echo $file->getFilename();
}
```

Deleting files by id:
```php
<?php
$imagesFS->deleteFileById('6b5a4f53...42ha54e');
```

If you want to use your own `GridFSFile` classes, you need to define mapping, as it does with collections:
```php
<?php
// define mapping of prefix to GridFS class
$database->map([
    'GridFSPrefix' => '\GridFSClass',
]);

// define GridFSFile class
class GridFSClass extends \Sokil\Mongo\GridFS
{
    public function getFileClassName(\MongoGridFSFile $fileData = null)
    {
        return '\GridFSFileClass';
    }
}

// define file class
class GridFSFileClass extends \Sokil\Mongo\GridFSFile
{
    public function getMetaParam()
    {
        return $this->get('meta.param');
    }
}

// get file as instance of class \GridFSFileClass
$database->getGridFS('GridFSPrefix')->getFileById($id)->getMetaParam();
```

Versioning
----------

To enable versioning of documents in collection, you can set protected
property `Collection::$versioning` to `true`, or call `Collection::enableVersioning()`
method.

```php
<?php
// througn protected property
class MyCollection extends \Sokil\Mongo\Collection
{
    protected $versioning = true;
}

// througn method
$collection = $database->getCollection('my');
$collection->enableVersioning();
```

To check if documents in collections is versioned call:

```php
<?php
if($collection->isVersioningEnabled()) {}
```

Revision is an instance of class `\Sokil\Mongo\Revision` and inherits `\Sokil\Mongo\Document`,
so any methods of document may be applied to revision. Revisions may be accessed:
```php
<?php
// get all revisions
$document->getRevisions();

// get slice of revisions
$limit = 10;
$offset = 15;
$document->getRevisions($limit, $offset);
```

To get one revision by id use:
```php
<?php
$revision = $document->getRevision($revisionKey);
```

To get count of revisions:
```php
<?php
$count = $document->getRevisionsCount();
```

To clear all revisions:
```php
<?php
$document->clearRevisions();
```

Revisions stored in separate collection, named `{COLLECTION_NAME}.revisions`
To obtain original document of collection `{COLLECTION_NAME}` from revision,
which is document of collection `{COLLECTION_NAME}.revisions`,
use `Revision::getDocument()` method:

```php
<?php
$document->getRevision($revisionKey)->getDocument();
```

Properties of document from revision may be accessed directly:
```
echo $document->property;
echo $document->getRevision($revisionKey)->property;
```

Also date of creating revison may be obtained from document:
```php
<?php
// return timestamp
echo $document->getRevision($revisionKey)->getDate();
// return formatted date string
echo $document->getRevision($revisionKey)->getDate('d.m.Y H:i:s');
```

Indexes
-------

Create index with custom options (see options in http://php.net/manual/en/mongocollection.ensureindex.php):
```php
<?php
$collection->ensureIndex('field', [ 'unique' => true ]);
```

Create unique index:
```php
<?php
$collection->ensureUniqueIndex('field');
```

Create sparse index (see http://docs.mongodb.org/manual/core/index-sparse/ for details about sparse indexes):
```php
<?php
$collection->ensureSparseIndex('field');
```

Create TTL index (see http://docs.mongodb.org/manual/tutorial/expire-data/ for details about TTL indexes):
```php
<?php
$collection->ensureTTLIndex('field');
```

You may define field as array where key is field name and value is direction:
```php
<?php
$collection->ensureIndex(['field' => 1]);
```

Also you may define compound indexes:
```php
<?php
$collection->ensureIndex(['field1' => 1, 'field2' => -1]);
```

You may define all collection indexes in property `Collection::$_index`
as array, where each item is an index definition.
Every index definition must contain key `keys` with list of fields and orders,
and optional options, as described in http://php.net/manual/en/mongocollection.createindex.php.

```php
<?php
class MyCollection extends \Sokil\Mongo\Collection
{
    protected $_index = array(
        array(
            'keys' => array('field1' => 1, 'field2' => -1),
            'unique' => true
        ),
    );
}
```

Then you must create this indexes by call of `Collection::initIndexes()`:

```php
<?php
$collection = $database->getCollection('myCollection')->initIndexes();
```

You may use [Mongo Migrator](https://github.com/sokil/php-mongo-migrator) package
to ensure indexes in collections from migration scripts.

[Query optimiser](http://docs.mongodb.org/manual/core/query-plans/#read-operations-query-optimization)
 automatically choose which index to use, but you can manuallty define it:

```php
<?php
$collection->find()->where('field', 1)->hind(array('field' => 1));
```

Caching and documents with TTL
------------------------------

If you want to get collection where documents will expire after some specified time, just add special index to this collection.

```php
<?php
$collection->ensureTTLIndex('createDate', 1000);
```

You can do this also in migration script, using [Mongo Migrator](https://github.com/sokil/php-mongo-migrator).
For details see readme on than pakage's page.

Or you can use `\Sokil\Mongo\Cache` class, which already implement this functionality.

```php
<?php
// Get cache instance
$cache = $document->getCache('some_namespace');
```
Before using cache must be inititalised by calling method `Cache:init()`:
```php
<?php
$cahce->init();
```

This operation creates index with `expireAfterSecond` key in collection `some_namespace`.

This operation may be done in some console command or migration script, or
you can create manually in mongo console:

```javascript
db.some_namespace.ensureIndex('e', {expireAfterSeconds: 0});
```

Now you can store new value with:
```php
<?php
// this store value for 10 seconds by defininc concrete timestamp when cached value expired
$cache->setByDate('key', 'value', time() + 10);
// same but expiration defined relatively to current time
$cache->set('key', 'value', 10);
```

You can devine value which never expired and must be deleted manually:
```php
<?php
$cache->setNeverExpired('key', 'value');
```

You can define some tags defined with key:
```php
<?php
$cache->set('key', 'value', 10, ['php', 'c', 'java']);
$cache->setNeverExpired('key', 'value', ['php', 'c', 'java']);
$cache->setDueDate('key', 'value', time() + 10, ['php', 'c', 'java']);
```

To get value
```php
<?php
$value = $cache->get('key');
```

To delete cached value by key:
```php
<?php
$cache->delete('key');
```

Delete few values by tags:
```php
<?php
// delete all values with tag 'php'
$cache->deleteMatchingTag('php');
// delete all values without tag 'php'
$cache->deleteNotMatchingTag('php');
// delete all values with tags 'php' and 'java'
$cache->deleteMatchingAllTags(['php', 'java']);
// delete all values which don't have tags 'php' and 'java'
$cache->deleteMatchingNoneOfTags(['php', 'java']);
// Document deletes if it contains any of passed tags
$cache->deleteMatchingAnyTag(['php', 'elephant']);
// Document deletes if it contains any of passed tags
$cache->deleteNotMatchingAnyTag(['php', 'elephant']);
```

Debugging
---------

### Logging

Library suports logging of queries. To configure logging, you need to pass logger object to instance of `\Sokil\Mongo\Client`. Logger must implement `\Psr\Log\LoggerInterface` due to [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md):

```php
<?php
$client = new Client($dsn);
$client->setLogger($logger);
```

### Profiling

Mode details about profiling at [Analyze Performance of Database Operations](http://docs.mongodb.org/manual/tutorial/manage-the-database-profiler/)
profiler data stores to `system.profile` collection, which you can query through query builder:

```php
<?php

$qb = $database
    ->findProfilerRows()
    ->where('ns', 'social.users')
    ->where('op', 'update');
```

Structure of document described in article [Database Profiler Output](http://docs.mongodb.org/manual/reference/database-profiler/)

There is three levels of profiling, described in article [Profile command](http://docs.mongodb.org/manual/reference/command/profile/).
Switching between then may be done by calling methods:

```php
<?php

// disable profiles
$database->disableProfiler();

// profile slow queries slower than 100 milliseconds
$database->profileSlowQueries(100);

// profile all queries
$database->profileAllQueries();
```

To get current level of profiling, call:
```php
<?php
$params = $database->getProfilerParams();
echo $params['was'];
echo $params['slowms'];

// or directly
$level = $database->getProfilerLevel();
$slowms = $database->getProfilerSlowMs();
```



<hr/>
<br/>
Pull requests, bug reports and feature requests is welcome. Add new to [issues](https://github.com/sokil/php-mongo/issues)
