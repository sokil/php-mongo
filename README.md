PHPMongo
========
[![Build Status](https://travis-ci.org/sokil/php-mongo.png?branch=master&1)](https://travis-ci.org/sokil/php-mongo)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-mongo/v/stable.png)](https://packagist.org/packages/sokil/php-mongo)
[![Coverage Status](https://coveralls.io/repos/sokil/php-mongo/badge.png)](https://coveralls.io/r/sokil/php-mongo)
[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/sokil/php-mongo?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

#### Object Document Mapper for MongoDB.

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
* [Update few documents](#update-few-documents)
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
* [Debugging](#debugging)
* [Capped collections](#capped-collections)
* [Executing commands](#executing-commands)
* [Queue](#queue)
* [Migrations](#migrations)
* [GridFS](#gridfs)

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

Connecting to MongoDB server made through \Sokil\Mongo\Client class:

```php
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

If you have few connections you may prefer connection pool instead of managing different connections. Use \Sokil\Mongo\ClientPool instance to initialize pool object:

```php
$pool = new ClientPool(array(
    'connect1' => array(
        'dsn' => 'mongodb://127.0.0.1',
        'defaultDatabase' => 'db2',
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
        'mappign' => array(
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
To get instance of database class \Sokil\Mongo\Database:
```php
$database = $client->getDatabase('databaseName');
// or simply
$database = $client->databaseName;
```

To get instance of collection class \Sokil\Mongo\Collection:
```php
$collection = $database->getCollection('collectionName');
// or simply
$collection = $database->collectionName;
```

Default database may be specified to get collection directly from $client object:
```php
$client->useDatabase('databaseName');
$collection = $client->getCollection('collectionName');
```

If you need to use your own collection classes, you must create class extended from \Sokil\Mongo\Collection and map it to collection class:
```php
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

If 'class' omitted, then used standart \Sokil\Mongo\Collection class. 
All options lated may be accessed:

```php
// will return 'value1'
$client
    ->getDatabase('databaseName')
    ->getCollection('collectionName')
    ->getOption('collectionOption1');

To override default document class use 'documentClass' option of collection:
```php
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

Document schema
------------------------

Document object is instance of class \Sokil\Mongo\Document. If you want to use your own class, you must configure its name in collection's class:

```php
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

You may flexibly configure document's class in \Sokil\Mongo\Collection::getDocumentClassName() relatively to concrete document's data:
```php
class CustomCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null) {
        return '\Custom' . ucfirst(strtolower($documentData['type'])) . 'Document';
    }
}
```

In example above class \CustomVideoDocument related to {"_id": "45..", "type": "video"}, and \CustomAudioDocument to {"_id": "45..", type: "audio"}

Document's scheme is completely not required. If field is required and has default value, it can be defined in special property of document class:
```php
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
$document = $collection->getDocument('5332d21b253fe54adf8a9327');
```

Create new document
-------------------

Create new empty document object:

```php
$document = $collection->createDocument();
```

Or with pre-defined values:

```php
$document = $collection->createDocument([
    'param1' => 'value1',
    'param2' => 'value2'
]);
```

Get and set data in document
----------------------------

To get value of document's field you may use one of following ways:
```php
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
$document->someField = 'someValue'; // {someField: 'someValue'}
$document->set('someField', 'someValue'); // {someField: 'someValue'}
$document->set('someField.sub.document.field', 'someValue'); // {someField: {sub: {document: {field: {'someValue'}}}}}
$document->setSomeField('someValue');  // {someField: 'someValue'}
```

Storing document
----------------

To store document in database just save it.
```php
$document = $collection->createDocument(['param' => 'value'])->save();

$document = $collection->getDocument('23a4...')->set('param', 'value')->save();
```

Querying documents
------------------

To query documents, which satisfy some conditions you need to use query builder:
```php
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

Result of the query is iterator \Sokil\Mongo\QueryBuilder, which you can then iterate:
```php
foreach($cursor as $documentId => $document) {
    echo $document->get('name');
}
```

Or you can get result array:
```php
$result = $cursor->findAll();
```

To get only one result:
```php
$document = $cursor->findOne();
```

To get only one random result:
```php
$document = $cursor->findRandom();
```

To get values from a single field in the result set of documents:
```php
$columnValues = $cursor->pluck('some.field.name');
```

For extending standart query builder class with custom condition methods you need to override property Collection::$_queryExpressionClass with class, which extends \Sokil\Mongo\Expression:

```php

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


Pagination
----------

Query builder allows you to create pagination.
```php
$paginator = $collection->find()->where('field', 'value')->paginate(3, 20);
$totalDocumentNumber = $paginator->getTotalRowsCount();
$totalPageNumber = $paginator->getTotalPagesCount();

// iterate through documents
foreach($paginate as $document) {
    echo $document->getId();
}
```


Update few documents
--------------------

Making changes in few documents:
```php
$expression = $collection
    ->expression()
    ->where('field', 'value');
    
$collection
    ->multiUpdate($expression, array('field' => 'new value'));
```

Persistence (Unit of Work)
--------------------------

Instead of saving and removing objects right now, we can queue this job and execute all changes at once. This may be done through well-known pattern Unit of Work.

Lets create persistance manager
```php
$persistence = $client->createPersistence();
```

Now we can add some documents to be saved or removed later
```php
$persistence->persist($document1);
$persistence->persist($document2);

$persistence->remove($document3);
$persistence->remove($document4);
```

If later we decice do not save or remove document, we may detach it from persistence manager
```php
$persistence->detach($document1);
$persistence->detach($document3);
```

Or we even may remove them all:
```php
$persistence->clear();
```

Note that after detaching document from persistence manager, it's changes do not removed and document still may be saved directly or by adding to persistence manager.

If we decide to store changes to databasae we may flush this changes:
```php
$persistence->flush();
```

Note that persisted documents do not deleted from persistence manager after flush, but removed will be deleted.

Document validation
-------------------

Document can be validated before save. To set validation rules method \Sokil\Mongo\Document::roles() must be override with validation rules. Supported rules are:
```php
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

Document can have validation state, based on scenario. Scenarion can be specified by method Document::setScenario($scenario).
```php
$document->setScenario('register');
```

If some validation rule applied only for some scenarios, this scenarios must be passed on 'on' key, separated by comma.
```php
public function rules()
    {
        return array(
            array('field' ,'null', 'on' => 'register,update'),
        );
    }
```

If some validation rule applied to all except some scenarios, this scenarios must be passed on 'except' key, separated by comma.

```php
public function rules()
    {
        return array(
            array('field' ,'null', 'except' => 'register,update'),
        );
    }
```

If document invalid, \Sokil\Mongo\Document\Exception\Validate will trigger and errors may be accessed through Document::getErrors() method of document object. This document may be get from exception method:
```php
try {

} catch(\Sokil\Mongo\Document\Exception\Validate $e) {
    $e->getDocument()->getErrors();
}
```

Error may be triggered manually by calling method triggerError($fieldName, $rule, $message)
```php
$document->triggerError('someField', 'email', 'E-mail must be at domain example.com');
```

You may add you custom validation rule just adding method to document class and defining method name as rule:
```php
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
Just extend your class from abstract validator class \Sokil\Mongo\Validator and register your own validator namespace:

```php
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

Deleting collections and documents
-----------------------------------

Deleting of collection:
```php
$collection->delete();
```

Deleting of document:
```php
$document = $collection->getDocument($documentId);
$collection->deleteDocument($document);
// or simply
$document->delete();
```

Deleting of few documents:
```php
$collection->deleteDocuments($collection->expression()->where('param', 'value'));
```

Aggregation framework
--------------------------------

To do aggregation you need first to create pipelines object:
```php
$pipeline = $collection->createPipeline();
````

To get results of aggregation after configuring pipelines:
```php
/**
 * @var array list of aggregation results
 */
$result = $pipeline->aggregate();
```

Match pipeline:
```php
$pipeline-> match([
    'date' => [
        '$lt' => new \MongoDate,
    ]
]);
```


Events
-------
Event support based on Symfony's Event Dispatcher component. Events can be attached in class while initialusing object or any time to the object. To attach events in Document class you need to override Document::beforeConstruct() method:
```php
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
$document->onBeforeSave(function() {
    $this->set('date' => new \MongoDate);
});
```
To cancel operation execution on some condition use event handling cancel:
```php
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
Behavior is a class extended from \Sokil\Mongo\Behavior:
```php
class SomeBehavior extends \Sokil\Mongo\Behavior
{
    public function return42()
    {
        return 42;
    }
}
```

To get instance of object, to which behavior is attached, call Behavior::getOwner() method:
```php
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
$document->attachBehavior(new \SomeBehavior);
```

Then you can call any methods of behaviors. This methods searches in order of atraching behaviors:
```php
echo $document->return42();
```

Relations
-------------

You can define relations between different documents, which helps you to load related doluments. Library supports relations one-to-one and one-to-many 

To define relation to other document you need to override Document::relations() method and returl array of relations in format [relationName => [relationType, targetCollection, reference], ...]

### One-to-one relation

We have to classes User and Profile. User has one profile, and profile belongs to User.

```php
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
$user = $userColletion->getDocument('234...');
echo $user->profileRelation->get('age');

$profile = $profileCollection->getDocument('234...');
echo $pfofile->userRelation->get('email');
```

### One-to-many relation

One-to-many relation helps you to load all related documents. Class User has few posts of class Post:

```php
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
foreach($user->postsRelation as $post) {
    echo $post->getMessage();
}
```

### Many-to-many relation

Many-to-many relation in relational databases uses intermediate table with stored ids of related rows from both tables. In mongo this table equivalent embeds to one of two related documents. Element of relation definition at position 3 must be set to true in this document.


```php

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
foreach($car->drivers as $driver) {
    echo $driver->name;
}
```

### Add relation

There is helper to add related document, if you don't 
want modify relation field directly:

```php
$car->addRelation('drivers', $driver);
```

This helper automatically resolves collection and field
where to store relation data.

### Remove relation

There is helper to remove related document, if you don't 
want modify relation field directly:

```php
$car->removeRelation('drivers', $driver);
```

This helper automatically resolves collection and field
where to remove relation data. If relation type is HAS_MANY or BELONS_TO, 
second parameter wich defined related object may be omitted.


Read preferences
----------------
[Read preference](http://docs.mongodb.org/manual/core/read-preference/) describes how MongoDB clients route read operations to members of a replica set. You can configure read preferences at any level:

```php
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

// by passing to \Sokil\Mongo\Client instance
$client->setMajorityWriteConcern(10000);

// by passing to database
$database = $client->getDatabase('databaseName')->setMajorityWriteConcern(10000);

// by passing to collection
$collection = $database->getCollection('collectionName')->setWriteConcern(4, 1000);
```

Debugging
---------------

Library suports logging of queries. To configure logging, you need to pass logger object to instance of \Sokil\Mongo\Client. Logger must implement \Psr\Log\LoggerInterface due to [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md):

```php
$client = new Client($dsn);
$client->setLogger($logger);
```

Capped collections
------------------

To use capped collection you need previously to create it:
```php
$numOfElements = 10;
$sizeOfCollection = 10*1024;
$collection = $database->createCappedCollection('capped_col_name', $numOfElements, $sizeOfCollection);
```

Now you can add only 10 documents to collection. All old documents will ve rewritted ny new elements.

Executing commands
------------------

Command is universal way to do anything with mongo. Let's get stats of collection:
```php
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
$queue = $database->getQueue('channel_name');
$queue->enqueue('world');
$queue->enqueue(['param' => 'value']);
```

Send message with priority
```php
$queue->enqueue('hello', 10);
```

Reading messages from channel:
```php
$queue = $database->getQueue('channel_name');
echo $queue->dequeue(); // hello
echo $queue->dequeue(); // world
echo $queue->dequeue()->get('param'); // value
```

Number of messages in queue
```php
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
$imagesFS = $database->getGridFS('image');
$cssFS = $database->getGridFS('css');
```

Now you can store file, located on disk:
```php
$id = $imagesFS->storeFile('/home/sokil/images/flower.jpg');
```

You can store file from binary data:
```php
$id1 = $imagesFS->storeBytes('some text content');
$id2 = $imagesFS->storeBytes(file_get_contents('/home/sokil/images/flower.jpg'));
```

You are able to store some metadata with every file:
```php
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
$imagesFS->getFileById('6b5a4f53...42ha54e');
```

Find file by metadata:
```php
foreach($imagesFS->find()->where('category', 'books') as $file) {
    echo $file->getFilename();
}
```

Deleting files by id:
```php
$imagesFS->deleteFileById('6b5a4f53...42ha54e');
```

If you want to use your own GridFSFile classes, you need to define mapping, as it does with collections:
```php
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

<hr/>
<br/>
Pull requests, bug reports and feature requests is welcome.
