PHPMongo
========
[![Build Status](https://travis-ci.org/sokil/php-mongo.png?branch=master)](https://travis-ci.org/sokil/php-mongo)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-mongo/v/stable.png)](https://packagist.org/packages/sokil/php-mongo)

Object Document Mapper for MongoDB

* [Installation](#installation)
* [Connecting](#connecting)
* [Selecting database and collection](#selecting-database-and-collection)
* [Document schema](#document-schema)
* [Getting documents by id](#getting-documents-by-id)
* [Create new document](#create-new-document)
* [Get and set data in document](#get-and-set-data-in-document)
* [Storing document](#storing-document)
* [Querying documents](#querying-documents)
* [Update few documents](Update few documents)
* [Document validation](#document-validation)
* [Deleting collections and documents](#deleting-collections-and-documents)
* [Aggregation framework](#aggregation-framework)
* [Events](#events)
* [Behaviors](#behaviors)
* [Relation](#relations)
* [Read preferences](#read-preferences)
* [Write concern](#write-concern)
* [Debugging](#debugging)

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


Connecting
----------

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
            array('email', 'uniqueFieldValidator', 'message' => 'E-mail must be unique in collection'),
        );
    }
    
    /**
     * @return bool if true, validator passes, if false - failed
     */
    public function uniqueFieldValidator($fieldName, $params)
    {
        // some logic of checking unique mail. Return true if validator passes, and false otherwise
    }
}
```
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

Behaviors
----------

Behavior is a posibility to extend functionality of document object and reuse code among documents of different class. Behavior is a class extended from \Sokil\Mongo\Behavior:
```php
class SomeBehavior extends \Sokil\Mongo\Behavior
{
    public function return42()
    {
        return 42;
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

Not you can load related posts of document:
```php
foreach($user->postsRelation as $post) {
    echo $post->getMessage();
}
```

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

Library suports logging of queries. To configure logging, you need to pass logger object to instance of \Sokil\Mongo\Client. Logger must impelent \Psr\Log\LoggerInterface due to [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md):

```php
$client = new Client($dsn);
$client->setLogger($logger);
```

