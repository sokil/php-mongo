PHPMongo ODM
============

[![Total Downloads][badge-totalDownloads-img]][badge-totalDownloads-url]
[![Daily Downloads](https://poser.pugx.org/sokil/php-mongo/d/daily)](https://packagist.org/packages/sokil/php-mongo)
[![Build Status](https://travis-ci.org/sokil/php-mongo.png?branch=master&2)](https://travis-ci.org/sokil/php-mongo)
[![Coverage Status](https://coveralls.io/repos/sokil/php-mongo/badge.png)](https://coveralls.io/r/sokil/php-mongo)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sokil/php-mongo/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sokil/php-mongo/?branch=master)
[![Code Climate](https://codeclimate.com/github/sokil/php-mongo/badges/gpa.svg)](https://codeclimate.com/github/sokil/php-mongo)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/45b7bd7f-9145-49af-8d6a-9380f14e12b6/mini.png)](https://insight.sensiolabs.com/projects/45b7bd7f-9145-49af-8d6a-9380f14e12b6)
[![Gitter](https://badges.gitter.im/Join_Chat.svg)](https://gitter.im/sokil/php-mongo?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

#### PHP ODM for MongoDB.

Why to use this ODM? You can easily work with document data through comfortable getters and setters instead of array and don't check if key exist in array. Access to sub document uses dot-syntax. You can validate data passed to document before save. We give you  events, which you can handle in different moments of document's life. You can create relations, build aggregations, create versioned documents, write migrations and do more things which makes your life easier.

[![ArmySOS - Help for Ukrainian Army](http://armysos.com.ua/wp-content/uploads/2014/09/728_90.jpg)](http://armysos.com.ua/en/help-the-army)

#### Requirements

* PHP 5
   * PHP 5.3 - PHP 5.6
  * [PHP Mongo Extension](https://pecl.php.net/package/mongo) 0.9 or above (Some features require >= 1.5)
* PHP 7 and HHVM
  * [PHP MongoDB Extension](https://pecl.php.net/package/mongodb) 1.0 or above
  * [Compatibility layer](https://github.com/alcaeus/mongo-php-adapter). Please, note some [restriontions](#compatibility-with-php-7)
  * Currently not tested in HHVM
* Tested over MongoDB v.2.4.12, v.2.6.9, v.3.0.2, v.3.2.10, v.3.3.15, v.3.4.0. See [Unit tests](#unit-tests) for details
<br/>
<br/>

#### Compatibility with PHP 7

> PHPMongo currently based on old [ext-mongo](https://pecl.php.net/package/mongo) entension.
> To use this ODM with PHP 7, you need to add [compatibility layer](https://github.com/alcaeus/mongo-php-adapter), which implement API of old extension over new [ext-mongodb](https://pecl.php.net/package/mongodb).
> To start using PHPMongo with PHP7, add requirement [alcaeus/mongo-php-adapter](https://github.com/alcaeus/mongo-php-adapter) to composer.
> Restrictions for using ODM with compatibility layer you can read in [known issues](https://github.com/alcaeus/mongo-php-adapter#known-issues) of original adapter.

To use lib under PHP7, add requirement:
```
composer require "alcaeus/mongo-php-adapter" --ignore-platform-reqs
```

<br/>

#### Table of contents

* [Installation](#installation)
  * [Common installation](#common-installation)
  * [Symfony bundle](#symfony-bundle)
  * [Yii component](#yii-component)
  * [Yii2 component](#yii2-component)
  * [Support of migrations](#support-of-migrations)
* [Connecting](#connecting)
* [Mapping](#mapping)
  * [Selecting database and collection](#selecting-database-and-collection)
  * [Custom collections](#custom-collections)
  * [Document schema](#document-schema)
* [Document validation](#document-validation)
* [Getting documents by id](#getting-documents-by-id)
* [Create new document](#create-new-document)
* [Get and set data in document](#get-and-set-data-in-document)
* [Embedded documents](#embedded-documents)
  * [Get embedded document](#get-embedded-document)
  * [Set embedded document](#set-embedded-document)
  * [Get embedded list of documents](#get-embedded-list-of-documents)
  * [Set embedded list of documents](#set-embedded-list-of-documents)
  * [Validation of embedded documents](#validation-of-embedded-documents)
* [DBRefs](#dbrefs)
* [Storing document](#storing-document)
  * [Storing mapped object](#storing-mapped-object)
  * [Insert and update documents without ODM](#insert-and-update-documents-without-odm)
  * [Batch insert](#batch-insert)
  * [Batch update](#batch-update)
  * [Moving data between collections](#moving-data-between-collections)
* [Querying documents](#querying-documents)
  * [Query Builder](#query-builder)
  * [Extending Query Builder](#extending-query-builder)
  * [Identity Map](#identity-map)
  * [Comparing queries](#comparing-queries)
* [Geospatial queries](#geospatial-queries)
* [Fulltext search](#fulltext-search)
* [Pagination](#pagination)
* [Persistence (Unit of Work)](#persistence-unit-of-work)
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
* [Concurency](#concurency)
  * [Optimistic locking](#optimistic-locking)
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
* [Unit tests](#unit-tests)
<br/>
<br/>

Installation
------------

#### Common installation

You can install library through Composer:
```
composer require sokil/php-mongo
```

Download latest release:
[Latest sources from GitHub](https://github.com/sokil/php-mongo/releases/latest)

#### Symfony bundle
If you use Symfony framework, you can use [Symfony MongoDB Bundle](https://github.com/sokil/php-mongo-bundle) which wraps this library

```
composer require sokil/php-mongo-bundle
```

#### Yii component
If you use Yii Framework, you can use [Yii Adapter](https://github.com/sokil/php-mongo-yii) which wraps this library

```
composer require sokil/php-mongo-yii
```

This package in addition to PHPMongo adapter also has data provider and log router for MongoDb.

#### Yii2 component
If you use Yii2 Framework, you can use [Yii2 Adapter](https://github.com/PHPMongoKit/yii2-mongo-odm) which wraps this library

```
composer phpmongokit/yii2-mongo-odm
```

#### Support of migrations
If you require migrations, you can add dependency to [Migrator](https://github.com/sokil/php-mongo-migrator), based on this library:

```
composer require sokil/php-mongo-migrator
```
<br/>
<br/>

Connecting
----------

#### Single connection

Connecting to MongoDB server made through `\Sokil\Mongo\Client` class:

```php
<?php
$client = new Client($dsn);
```

Format of DSN used to connect to server is described in [PHP manual](http://www.php.net/manual/en/mongo.connecting.php).
To connect to localhost, use next DSN:
```
mongodb://127.0.0.1
```
To connect to replica set, use next DSN:
```
mongodb://server1.com,server2.com/?replicaSet=replicaSetName
```

#### Pool of connections

If you have few connections, you may prefer connection pool instead of managing different connections. Use `\Sokil\Mongo\ClientPool` instance to initialize pool object:

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
<br/>
<br/>

Mapping
-------

### Selecting database and collection

You can get instances of databases and collections by its name.

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

Default database may be specified to get collection directly from `\Sokil\Mongo\Client` object:
```php
<?php
$client->useDatabase('databaseName');
$collection = $client->getCollection('collectionName');
```

### Custom collections

Custom collections are used to add some collection-specific features in related class. 
First you need to create class extended from `\Sokil\Mongo\Collection`:
```php
<?php

// define class of collection
class CustomCollection extends \Sokil\Mongo\Collection
{

}
```

This class must be then mapped to collection name in order to return object of this class when collection requested.
Custom collection referenced in standard way:

```php
<?php
/**
 * @var \CustomCollection
 */
$collection = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName');
```

#### Collection definition

Collection name must be mapped to collection class. 
If you want to pass some additional options to collection, you also can
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

All options later may be accessed by `Collection::getOption()` method:

```php
<?php
// will return 'value1'
$client
    ->getDatabase('databaseName')
    ->getCollection('collectionName')
    ->getOption('collectionOption1');
```

Predefined options are:

| Option              | Default value            | Description                                                |
| ------------------- | ------------------------ | ---------------------------------------------------------- |
| class               | \Sokil\Mongo\Collection  | Fully qualified collectin class                            |
| documentClass       | \Sokil\Mongo\Document    | Fully qualified document class                             |
| versioning          | false                    | Using document versioning                                  |
| index               | null                     | Index definition                                           |
| expressionClass     | \Sokil\Mongo\Expression  | Fully qualified expression class for custom query builder  |
| behaviors           | null                     | List of behaviors, attached to every document              |
| relations           | null                     | Definition of relations to documents in other collection   |
| batchSize           | null                     | Number of documents to return in each batch of response    |
| clientCursorTimeout | null                     | A timeout can be set at any time and will affect subsequent queries on the cursor, including fetching more results from the database    |
| serverCursorTimeout | null                     | A cumulative time limit in milliseconds to be allowed by the server for processing operations on the cursor |
| documentPool        | true                     | Document pool, used to store already fetched documnts in identity map |

If `class` omitted, then used standart `\Sokil\Mongo\Collection` class.

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

#### Mapping of collection name to collection class

If only class name of collection defined, you may simply pass it in mapping. 


```php
<?php

// map class to collection name
$client->map([
    'databaseName'  => [
        'collectionName' => [
            'class' => \Acme\MyCollection',
        ],
    ],
]);

/**
 * @var \Acme\MyCollection
 */
$collection = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName');
```


_There is also deprecated method to specify collection's class name. Please, use array definition and option `class`._

```php
<?php

// map class to collection name
$client->map([
    'databaseName'  => [
        'collectionName' => '\Acme\MyCollection'
    ],
]);

/**
 * @var \Acme\MyCollection
 */
$collection = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName');
```

#### Mapping with class prefix

Collections not configured directly, may be mapped automatically by using `*` in mapping keys. 
Any collection may be mapped to class without enumerating every collection name.

```php
<?php
$client->map([
    'databaseName'  => [
        '*' => [
            'class' => '\Acme\Collection\Class\Prefix',
        ],
    ],
]);

/**
 * @var \Acme\Collection\Class\Prefix\CollectionName
 */
$collection = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName');

/**
 * @var \Acme\Collection\Class\Prefix\CollectionName\SubName
 */
$collection = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName.subName');
```

_There is also deprecated method to specify class prefix. Please, use `*` as collection name and array definition with option `class`._

```php
<?php
$client->map([
    'databaseName'  => '\Acme\Collection\Class\Prefix',
]);

/**
 * @var \Acme\Collection\Class\Prefix\CollectionName
 */
$collection = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName');

/**
 * @var \Acme\Collection\Class\Prefix\CollectionName\SubName
 */
$collection = $client
    ->getDatabase('databaseName')
    ->getCollection('collectionName.subName');
```

#### Regexp mapping

Collection name in mapping may be defined as RegExp pattern. Pattern must start from symbol `/`:

```php
<?php
$database->map(array(
    '/someCollection(\d)/' => '\Some\Collection\Class',
));
```

Any collection with name matched to pattern will be instance of `\Some\Collection\Class`:
```php
<?php
$col1 = $database->getCollection('someCollection1');
$col2 = $database->getCollection('someCollection2');
$col4 = $database->getCollection('someCollection4');
```

Any stored regexp values than may be get through `$collection->getOption('regex');`.

```php
<?php
$database->map(array(
    '/someCollection(\d+)/' => '\Some\Collection\Class',
));
$col42 = $database->getCollection('someCollection42');
echo $col1->getOption('regexp')[0]; // someCollection42
echo $col1->getOption('regexp')[1]; // 42
```

### Document schema and validating

#### Custom document class

Custom document class may be useful when required some processing of date on load, getting or save. Custom document class must extend `\Sokil\Mongo\Document`.

```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{

}
```

Now you must configure its name in collection's class by overriding method `Collection::getDocumentClassName()`:

```php
<?php
class CustomCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null) {
        return '\CustomDocument';
    }
}
```

#### Single Collection Inheritance

Often useful to have different document classes, which store data in single collection.
For example you have products in your shop `Song` and `VideoClip`, which inherit abstract `Product`.
They have same fields like author or duration, but may also have other different fields and
behaviors. This situation described in example [Product Catalog](http://docs.mongodb.org/ecosystem/use-cases/product-catalog/).

You may flexibly configure document's class in `\Sokil\Mongo\Collection::getDocumentClassName()` relatively to concrete value of field (this field called discriminator), or other more complex logic:

```php
<?php
class CustomCollection extends \Sokil\Mongo\Collection
{
    public function getDocumentClassName(array $documentData = null) {
        return '\Custom' . ucfirst(strtolower($documentData['type'])) . 'Document';
    }
}
```

Also document class may be defined in collection mapping:

```php
<?php
$client->map([
    'databaseName'  => [
        'collectionName1' => [
            'documentClass' => '\CustomDocument',
        ],
        'collectionName2' => function(array $documentData = null) {
            return '\Custom' . ucfirst(strtolower($documentData['type'])) . 'Document';
        },
        'collectionName3' => [
            'documentClass' => function(array $documentData = null) {
                return '\Custom' . ucfirst(strtolower($documentData['type'])) . 'Document';
            },
        ],
    ],
]);
```

In example above class `\CustomVideoDocument` related to `{"_id": "45..", "type": "video"}`, and `\CustomAudioDocument` to `{"_id": "45..", type: "audio"}`

#### Document schema

Document's scheme is completely not required. 
If field is required and has default value, it can be defined in special property of document class `Document::schema`:

```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
{
    protected $schema = [
        'requiredField' => 'defaultValue',
        'someField'     => [
            'subDocumentField' => 'value',
        ],
    ];
}
```

Also supported deprecated format `Document::_data`:

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
<br/>
<br/>

Document validation
-------------------

Document may be validated before save. To set validation rules, you may override method `\Sokil\Mongo\Document::rules()` and pass validation rules here. Supported rules are:

```php
<?php
class CustomDocument extends \Sokil\Mongo\Document
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

Document can have validation state, based on scenario. Scenario can be specified by method `Document::setScenario($scenario)`.

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

There are two equal cases for document validation:
```php
try {
    $document->save();
} catch (\Sokil\Mongo\Document\InvalidDocumentException $e) {
    // get validation errors
    var_dump($document->getErrors());
    // get document instance from exception
    var_dump($e->getDocument()->getErrors());
}
```
or
```php
if ($document->isValid())
    $document->save();
} else {
    var_dump($document->getErrors());
}
```

By default, document validates before save and `\Sokil\Mongo\Document\InvalidDocumentException` is thrown if it invalid. Exception `\Sokil\Mongo\Document\Exception\Validate` was thrown before `v.1.11.6`
when document was invalid. Since `v.1.11.6` this exception is deprecated. Use `\Sokil\Mongo\Document\InvalidDocumentException` instead.

Errors may be accessed through `Document::getErrors()` method of document object. 

Also instabce of document may be get from exception method:
```php
<?php
try {
    $document->save();
} catch(\Sokil\Mongo\Document\InvalidDocumentException $e) {
    $e->getDocument()->getErrors();
}
```

If allowed to save invalid documents, disable validation on save:
```php
$document->save(false);
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
            // 'my_own_equals' converts to 'MyOwnEqualsValidator' class name
            array('field', 'my_own_equals', 'to' => 42, 'message' => 'Not equals'),
        );
    }
}
```
<br/>
<br/>

Getting documents by id
-----------------------

To get document from collection by its id:
```php
<?php
$document = $collection->getDocument('5332d21b253fe54adf8a9327');
```

To add additional checks or query modifiers use callable:
```php
<?php
$document = $collection->getDocument(
    '5332d21b253fe54adf8a9327',
    function(\Sokil\Mongo\Cursor $cursor) {
        // get document only if active
        $cursor->where('status', 'active');
        // slice embedded documents
        $cursor->slice('embdocs', 10, 30);
    }
);
```

Note that if callable specified, document always loaded directly omitting document pool.
<br/>
<br/>

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
<br/>
<br/>

Get and set data in document
----------------------------

### Get

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

### Set

To set value you may use following ways:
```php
<?php
$document->someField = 'someValue'; // {someField: 'someValue'}
$document->set('someField', 'someValue'); // {someField: 'someValue'}
$document->set('someField.sub.document.field', 'someValue'); // {someField: {sub: {document: {field: {'someValue'}}}}}
$document->setSomeField('someValue');  // {someField: 'someValue'}
```

### Push
Push will add value to array field:
```php
<?php

$document->push('field', 1);
$document->push('field', 2);

$document->get('field'); // return [1, 2]
```

If field already exists, and not sequential list of values, then in case of scalar, scalar will be converted to array.
If values pushed to field with subdocument, then triggered `Sokil\Mongo\Document\InvalidOperationException`.

<br/>
<br/>

Embedded documents
------------------

### Get embedded document

Imagine that you have document, which represent `User` model:

```javascript
{
    "login": "beebee",
    "email": "beebee@gmail.com",
    "profile": {
        "birthday": "1984-08-11",
        "gender": "female",
        "country": "Ukraine",
        "city": "Kyiv"
    }
}
```

You can define embedded `profile` document as standalone class:
```php
<?php

/**
 * Profile class
 */
class Profile extends \Sokil\Mongo\Structure
{
    public function getBirthday() { return $this->get('birthday'); }
    public function getGender() { return $this->get('gender'); }
    public function getCountry() { return $this->get('country'); }
    public function getCity() { return $this->get('city'); }
}

/**
 * User model
 */
class User extends \Sokil\Mongo\Document
{
    public function getProfile()
    {
        return $this->getObject('profile', '\Profile');
    }
}
```

Now you are able to get profile params:

```php
<?php
$birthday = $user->getProfile()->getBirthday();
```

### Set embedded document

You can also set embedded document. If embedded document has validation rules, they will be checked before embed it to document:

```php
/**
 * Profile class
 */
class Profile extends \Sokil\Mongo\Structure
{
    public function getBirthday() { return $this->get('birthday'); }

    public function rules()
    {
        return array(
            array('birthday', 'required', 'message' => 'REQUIRED_FIELD_EMPTY_MESSAGE'),
        );
    }
}

/**
 * User model
 */
class User extends \Sokil\Mongo\Document
{
    public function setProfile(Profile $profile)
    {
        return $this->set('profile', $profile);
    }
}
```

If embedded document is invalid, it will throw `Sokil\Mongo\Document\InvalidDocumentException`. Embedded 
document may be obtained from exception object:

```php
try {
    $user->set('profile', $profile);
} catch (InvalidDocumentException $e) {
    $e->getDocument()->getErrors();
}
```

### Get embedded list of documents

Imagine that you have stored post data in collection 'posts', and post document has
embedded comment documents:

```javascript
{
    "title": "Storing embedded documents",
    "text": "MongoDb allows to atomically modify embedded documents",
    "comments": [
        {
            "author": "MadMike42",
            "text": "That is really cool",
            "date": ISODate("2015-01-06T06:49:41.622Z"
        },
        {
            "author": "beebee",
            "text": "Awesome!!!11!",
            "date": ISODate("2015-01-06T06:49:48.622Z"
        },
    ]
}
```

So we can create `Comment` model, which extends `\Sokil\Mongo\Structure`:

```php
<?php
class Comment extends \Sokil\Mongo\Structure
{
    public function getAuthor() { return $this->get('author'); }
    public function getText() { return $this->get('text'); }
    public function getDate() { return $this->get('date')->sec; }
}

```

Now we can create `Post` model with access to embedded `Comment` models:

```php
<?php

class Post extends \Sokil\Mongo\Document
{
    public function getComments()
    {
        return $this->getObjectList('comments', '\Comment');
    }
}
```

Method `Post::getComments()` allows you to get all of embedded document. To
paginate embedded documents you can use `\Sokil\Mongo\Cursor::slice()` functionality.

```php
<?php
$collection->find()->slice('comments', $limit, $offset)->findAll();
```

If you get `Document` instance through `Collection::getDocument()` you can define
additional expressions for loading it:

```php
<?php
$document = $collection->getDocument('54ab8585c90b73d6949d4159', function(Cursor $cursor) {
    $cursor->slice('comments', $limit, $offset);
});

```

### Set embedded list of documents

You can store embedded document to array, and validate it before pushing:
```php
<?php
$post->push('comments', new Comment(['author' => 'John Doe']));
$post->push('comments', new Comment(['author' => 'Joan Doe']));
```

### Validation of embedded documents

As embedded document is `Structure`, it has all validation functionality as `Document`. Currently embedded document validates only just before set to `Document` or manually. If embedded document is invalid, it trowns `Sokil\Mongo\Document\InvalidDocumentException`.

```php
class EmbeddedDocument extends Structure()
{
    public function rules() {}
}

$embeddedDocument = new EmbeddedDocument();
// auto validation
try {
    $document->set('some', embeddedDocument);
    $document->addToSet('some', embeddedDocument);
    $document->push('some', embeddedDocument);
} catch (InvalidDocumentException $e) {
    
}

// manual validation
if ($embeddedDocument->isValid()) {
    $document->set('some', embeddedDocument);
    $document->addToSet('some', embeddedDocument);
    $document->push('some', embeddedDocument);
}
```
<br/>
<br/>

DBRefs
------

In most cases you should use the manual reference method for connecting two or more related documents -
including one document’s _id field in another document.
The application can then issue a second query to resolve the referenced fields as needed.
See more info about supporting manual references in section [Relations](#relations)

However, if you need to reference documents from multiple collections, or use
legacy database with DBrefs inside, consider using DBRefs.
See more info about DBRef at https://docs.mongodb.com/manual/reference/database-references/.

If you have DBRef array, you can get document instance:

```php
<?php
$collection->getDocumentByReference(array('$ref' => 'col', '$id' => '23ef12...ff452'));
$database->getDocumentByReference(array('$ref' => 'col', '$id' => '23ef12...ff452'));
```
    
Adding reference to one document in another:

```php
<?php
$relatedDocument = $this->collection->createDocument(array('param' => 'value'))->save();
$document = $this->collection
    ->createDocument()
    ->setReference('related', $relatedDocument)
    ->save();
```

Get document from reference field:

```php
<?php

$relatedDocument = $document->getReferencedDocument('related');

```

Push relation to list of relations:

```php
<?php

$relatedDocument = $this->collection->createDocument(array('param' => 'value'))->save();
$document = $this->collection
    ->createDocument()
    ->pushReference('related', $relatedDocument)
    ->save();
```

Get list of related documents:

```php
<?php

$foundRelatedDocumentList = $document->getReferencedDocumentList('related');

```

List of references may be from different collections of same database.
Specifying of database in reference not supported.
<br/>
<br/>

Storing document
----------------

### Storing mapped object

If you have previously loaded and modified instance of `\Sokil\Mongo\Document`, just save it.
Document will automatically be inserted or updated if it already stored.

```php
<?php

// create new document and save
$document = $collection
    ->createDocument(['param' => 'value'])
    ->save();

// load existed document, modify and save
$document = $collection
    ->getDocument('23a4...')
    ->set('param', 'value')
    ->save();
```

### Insert and update documents without ODM

If required quick insert of document without validating, events just insert it as array:

```php
<?php
$collection->insert(['param' => 'value']);
```

To update existed documents, use:

```php
<?php
$collection->update($expression, $data, $options);
```

Expression may be defined as array, `\Sokil\Mongo\Expressin` object or callable, which configure expression.
Operator may be defined as array, `\Sokil\Mongo\Operator` object or callable which configure operator.
Options is array of all allowed options, described in http://php.net/manual/ru/mongocollection.update.php.

For example:
```php
<?php
$collection->update(
    function(\Sokil\Mongo\Expression $expression) {
        $expression->where('status', 'active');
    },
    function(\Sokil\Mongo\Operator $operator) {
        $operator->increment('counter');
    },
    array(
        'multiple' => true,
    )
);
```

### Batch insert

To insert many documents at once with validation of inserted document:
```php
<?php
$collection->batchInsert(array(
    array('i' => 1),
    array('i' => 2),
));
```

Also supported `\MongoInsertBatch` through interface:
```php
<?php
$collection
    ->createBatchInsert()
    ->insert(array('i' => 1))
    ->insert(array('i' => 2))
    ->execute('majority');
```

### Batch update

Making changes in few documents:

```php
<?php

$collection->batchUpdate(function(\Sokil\Mongo\Expression $expression) {
    return $expression->where('field', 'value');
}, array('field' => 'new value'));

// deprecated since 1.13
$collection->updateMultiple(function(\Sokil\Mongo\Expression $expression) {
    return $expression->where('field', 'value');
}, array('field' => 'new value'));
```

Method `Collection::updateAll` since 1.13 is deprecated. 
To update all documents use:
```php
<?php

$collection->batchUpdate([], array('field' => 'new value'));
// deprecated since 1.13
$collection->updateAll(array('field' => 'new value'));
```
Also supported `\MongoUpdateBatch` through interface:
```php
<?php
$collection
    ->createBatchUpdate()
    ->update(
        array('a' => 1),
        array('$set' => array('b' => 'updated1')),
        $multiple,
        $upsert
    )
    ->update(
        $collection->expression()->where('a', 2),
        $collection->operator()->set('b', 'updated2'),
        $multiple,
        $upsert
    )
    ->update(
        function(Expression $e) { $e->where('a', 3); },
        function(Operator $o) { $o->set('b', 'updated3'); },
        $multiple,
        $upsert
    )
    ->execute('majority');
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
<br/>
<br/>

Querying documents
------------------

### Query Builder

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
```php
<?php
$cursor = $collection
    ->find()
    ->whereOr(
        $collection->expression()->where('field1', 50),
        $collection->expression()->where('field2', 50),
    );
```

Result of the query is iterator `\Sokil\Mongo\Cursor`, which you can then iterate:
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

To map found documents:
```php
<?php
$result = $collection->find()->map(function(Document $document) {
    return $document->param;
});
```

To filter found documents:
```php
<?php
$result = $collection->find()->filter(function(Document $document) {
    return $document->param % 2;
});
```

To apply chain of functions to result, use `ResultSet`:
```php
<?php
$collection->find()
    ->getResultSet()
    ->filter(function($doc) { return $doc->param % 2 })
    ->filter(function($doc) { return $doc->param > 6 })
    ->map(function($item) {
        $item->param = 'update' . $item->param;
        return $item;
    });
```

When iterating through cursor client
[retrieve some amount of documents](http://docs.mongodb.org/manual/reference/method/cursor.batchSize/)
from the server in one round trip.
To define this numner of documents:

```php
<?php

$cursor->setBatchSize(20);
```

#### Query timeouts

Client timeout defined to stop waiting for a response and throw a \MongoCursorTimeoutException after a set time.
A timeout can be set at any time and will affect subsequent queries on the cursor, including fetching more results from the database.

```php 
$collection->find()->where('name', 'Michael')->setClientTimeout(4200);
```

Server-side timeout for a query specifies a cumulative time limit in milliseconds to be allowed by the server for processing operations on the cursor.

```php
$collection->find()->where('name', 'Michael')->setServerTimeout(4200);
```

### Distinct values

To get distinct values of field:
```php
<?php
// return all distinct values
$values = $collection->getDistinct('country');
```

Values may be filtered by expression specified as array, callable or `Expression` object:
```php
<?php
// by array
$collection->getDistinct('country', array('age' => array('$gte' => 25)));
// by object
$collection->getDistinct('country', $collection->expression()->whereGreater('age', 25));
// by callable
$collection->getDistinct('country', function($expression) { return $expression->whereGreater('age', 25); });
```

### Extending Query Builder

For extending standart query builder class with custom condition methods you need to create expression class which extends `\Sokil\Mongo\Expression`:

```php
<?php

// define expression
class UserExpression extends \Sokil\Mongo\Expression
{
    public function whereAgeGreaterThan($age)
    {
        $this->whereGreater('age', (int) $age);
    }
}
```

And then specify it in collection mapping:
```php
<?php

$client->map([
    'myDb' => [
        'user' => [
            'class' => '\UserCollection',
            'expressionClass' => '\UserExpression',
        ],
    ],
]);
```

Also there is _deprecated_ feature to override property `Collection::$_queryExpressionClass`:

```php
<?php

// define expression in collection
class UserCollection extends \Sokil\Mongo\Collection
{
    protected $_queryExpressionClass = 'UserExpression';
}
```

Now new expression methods available in the query buiilder:

```php
<?php
// use custom method for searching
$collection = $db->getCollection('user'); // instance of UserCollection
$queryBuilder = $collection->find(); // instance of UserExpression

// now methods available in query buider
$queryBuilder->whereAgeGreaterThan(18)->fetchRandom();

// since v.1.3.2 also supported query builder configuration through callable:
$collection
    ->find(function(UserExpression $e) {
        $e->whereAgeGreaterThan(18);
    })
    ->fetchRandom();
```

### Identity Map

Imagine that you have two different query builders and they are both
return same document. Identity map helps us to get same instance of object
from different queries, so if we made changes to document from first query,
that changes will be in document from second query:

```php
<?php

$document1 = $collection->find()->whereGreater('age' > 18)->findOne();
$document2 = $collection->find()->where('gender', 'male')->findOne();

$document1->name = 'Mary';
echo $document2->name; // Mary
```

This two documents referenced same object. Collection by default store all requested documents to identity map. 
If we obtain document directly by id using `Collection::getDocument()` and document was previously loaded to identity map, it will be fetched from identity map without requesing database. Even document present in identity map, it can be fetched direcly from db by using `Collection::getDocumentDirectly()` with same syntax as [Collection::getDocument()](#getting-documents-by-id).

If serial requests fetch same document, this document not replaced in identity mav, but content of this document will be renewed. So different requests works with same document stored in identity map. 

If we know that documents never be reused, we can disable storing documents to identity map:

Document pool may be disabled or enabled in mapping. By default it is enabled:

```php
<?php
$collection->map([
    'someDb' => [
        'someCollection', array(
            'documentPool' => false,
        ),
    ],
]);

```


```php
<?php

$collection->disableDocumentPool();
```

To enable identity mapping:
```php
<?php

$collection->enableDocumentPool();
```

To check if identity mapping enabled:
```php
<?php

$collection->isDocumentPoolEnabled();
```

To clear pool identity map from previously stored documents:
```php
<?php

$collection->clearDocumentPool();
```

To check if there are documents in map already:
```php
<?php

$collection->isDocumentPoolEmpty();
```

If document already loaded, but it may be changed from another proces in db,
then your copy is not fresh. You can manually refresh document state
syncing it with db:
```php
<?php

$document->refresh();
```

### Comparing queries

If you want to cache your search results or want to compare two queries, you need some
identifier which unambiguously identify query. You can use `Cursor::getHash()` for
that reason. This hash uniquely identify just query parameners rather
than result set of documents, because it calculated from all query parameters:

```php
<?php

$queryBuilder = $this->collection
    ->find()
    ->field('_id')
    ->field('interests')
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
```
<br/>
<br/>

Geospatial queries
------------------

Before querying geospatial coordinates we need to create geospatial index
and add some data.

Index 2dsphere available since MongoDB version 2.4 and may be created in few ways:
```php

<?php
// creates index on location field
$collection->ensure2dSphereIndex('location');
// cerate compound index
$collection->ensureIndex(array(
    'location' => '2dsphere',
    'name'  => -1,
));
```

Geo data can be added as array in [GeoJson](http://geojson.org/) format or
using GeoJson objects of library [GeoJson](https://github.com/jmikola/geojson):

Add data as GeoJson object
```php
<?php

$document->setGeometry(
    'location',
    new \GeoJson\Geometry\Point(array(30.523400000000038, 50.4501))
);

$document->setGeometry(
    'location',
    new \GeoJson\Geometry\Polygon(array(
        array(24.012228, 49.831485), // Lviv
        array(36.230376, 49.993499), // Harkiv
        array(34.174927, 45.035993), // Simferopol
        array(24.012228, 49.831485), // Lviv
    ))
);

```

Data may be set througn array:
```php
<?php

// Point
$document->setPoint('location', 30.523400000000038, 50.4501);
// LineString
$document->setLineString('location', array(
    array(30.523400000000038, 50.4501),
    array(36.230376, 49.993499),
));
// Polygon
$document->setPolygon('location', array(
    array(
        array(24.012228, 49.831485), // Lviv
        array(36.230376, 49.993499), // Harkiv
        array(34.174927, 45.035993), // Simferopol
        array(24.012228, 49.831485), // Lviv
    ),
));
// MultiPoint
$document->setMultiPoint('location', array(
    array(24.012228, 49.831485), // Lviv
    array(36.230376, 49.993499), // Harkiv
    array(34.174927, 45.035993), // Simferopol
));
// MultiLineString
$document->setMultiLineString('location', array(
    // line string 1
    array(
        array(34.551416, 49.588264), // Poltava
        array(35.139561, 47.838796), // Zaporizhia
    ),
    // line string 2
    array(
        array(24.012228, 49.831485), // Lviv
        array(34.174927, 45.035993), // Simferopol
    )
));
// MultiPolygon
$document->setMultyPolygon('location', array(
    // polygon 1
    array(
        array(
            array(24.012228, 49.831485), // Lviv
            array(36.230376, 49.993499), // Harkiv
            array(34.174927, 45.035993), // Simferopol
            array(24.012228, 49.831485), // Lviv
        ),
    ),
    // polygon 2
    array(
        array(
            array(24.012228, 49.831485), // Lviv
            array(36.230376, 49.993499), // Harkiv
            array(34.174927, 45.035993), // Simferopol
            array(24.012228, 49.831485), // Lviv
        ),
    ),
));
// GeometryCollection
$document->setGeometryCollection('location', array(
    // point
    new \GeoJson\Geometry\Point(array(30.523400000000038, 50.4501)),
    // line string
    new \GeoJson\Geometry\LineString(array(
        array(30.523400000000038, 50.4501),
        array(24.012228, 49.831485),
        array(36.230376, 49.993499),
    )),
    // polygon
    new \GeoJson\Geometry\Polygon(array(
        // line ring 1
        array(
            array(24.012228, 49.831485), // Lviv
            array(36.230376, 49.993499), // Harkiv
            array(34.174927, 45.035993), // Simferopol
            array(24.012228, 49.831485), // Lviv
        ),
        // line ring 2
        array(
            array(34.551416, 49.588264), // Poltava
            array(32.049226, 49.431181), // Cherkasy
            array(35.139561, 47.838796), // Zaporizhia
            array(34.551416, 49.588264), // Poltava
        ),
    )),
));

```

Query documents near point on flat surface, defined by latitude 49.588264 and
longitude 34.551416 and distance 1000 meters from this point:

```php
<?php
$collection->find()->nearPoint('location', 34.551416, 49.588264, 1000);
```

This query require `2dsphere` or `2d` indexes.

Distance may be specified as array `[minDistance, maxDistance]`. This
feature allowed for MongoDB version 2.6 and greater. If some value
empty, only existed value applied.

```php
<?php
// serch distance less 100 meters
$collection->find()->nearPoint('location', 34.551416, 49.588264, array(null, 1000));
// search distabce between 100 and 1000 meters
$collection->find()->nearPoint('location', 34.551416, 49.588264, array(100, 1000));
// search distabce greater than 1000 meters
$collection->find()->nearPoint('location', 34.551416, 49.588264, array(1000, null));
```

To search on spherical surface:
```php
<?php
$collection->find()->nearPointSpherical('location', 34.551416, 49.588264, 1000);
```

To find geometries, which intersect specified:
```php
<?php
$this->collection
    ->find()
    ->intersects('link', new \GeoJson\Geometry\LineString(array(
        array(30.5326905, 50.4020355),
        array(34.1092134, 44.946798),
    )))
    ->findOne();
```

To select documents with geospatial data that exists entirely within a specified shape:
```php
<?php
$point = $this->collection
    ->find()
    ->within('point', new \GeoJson\Geometry\Polygon(array(
        array(
            array(24.0122356, 49.8326891), // Lviv
            array(24.717129, 48.9117731), // Ivano-Frankivsk
            array(34.1092134, 44.946798), // Simferopol
            array(34.5572385, 49.6020445), // Poltava
            array(24.0122356, 49.8326891), // Lviv
        )
    )))
    ->findOne();
```

Search documents within flat circle:
```php
<?php
$this->collection
    ->find()
    ->withinCircle('point', 28.46963, 49.2347, 0.001)
    ->findOne();
```

Search document within spherical circle:
```php
<?php
$point = $this->collection
    ->find()
    ->withinCircleSpherical('point', 28.46963, 49.2347, 0.001)
    ->findOne();
```

Search documents with points (stored as legacy coordinates) within box:
```php
<?php
$point = $this->collection
    ->find()
    ->withinBox('point', array(0, 0), array(10, 10))
    ->findOne();
```

Search documents with points (stored as legacy coordinates) within polygon:
```php
<?php
$point = $this->collection
    ->find()
    ->withinPolygon(
        'point',
        array(
            array(0, 0),
            array(0, 10),
            array(10, 10),
            array(10, 0),
        )
    )
    ->findOne();
```
<br/>
<br/>

Fulltext search
---------------

Before search field must be previously indexed as fulltext search field:

```php
<?php

// one field
$collection->ensureFulltextIndex('somefield');

// couple of fields
$collection->ensureFulltextIndex(['somefield1', 'somefield2']);
```

Searching on fulltext field:

```php
<?php

$collection->find()->whereText('string searched in all fulltext fields')->findAll();
```
<br/>
<br/>

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
<br/>
<br/>

Persistence (Unit of Work)
--------------------------

Instead of saving and removing objects right now, we can queue this job and execute all changes at once. This may be done through well-known pattern Unit of Work. If installed PHP driver above v. 1.5.0 and version of MongoDB above, persistence will use `MongoWriteBatch` classes, which can execute all operations of same type and in same collection at once.

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
<br/>
<br/>

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
$document->delete();
```

Deleting of few documents by expression:
```php
<?php

$collection->batchDelete($collection->expression()->where('param', 'value'));
// deprecated since 1.13
$collection->deleteDocuments($collection->expression()->where('param', 'value'));
```
Also supported `\MongoDeleteBatch` through interface:

```php
<?php
$batch = $collection->createBatchDelete();
$batch
    ->delete(array('a' => 2))
    ->delete($collection->expression()->where('a', 4))
    ->delete(function(Expression $e) { $e->where('a', 6); })
    ->execute();
```
<br/>
<br/>

Aggregation framework
--------------------------------

Create aggregator:
```php
<?php
$aggregator = $collection->createAggregator();
````

Than you need to configure aggregator by pipelines.
```php
<?php
// through array
$aggregator->match(array(
    'field' => 'value'
));
// through callable
$aggregator->match(function($expression) {
    $expression->whereLess('date', new \MongoDate);
});
```

To get results of aggregation after configuring pipelines:
```php
<?php
/**
 * @var array list of aggregation results
 */
$result = $aggregator->aggregate();
// or
$result = $collection->aggregate($aggregator);
```

You can execute aggregation without previously created aggregator:

```php
<?php
// by array
$collection->aggregate(array(
    array(
        '$match' => array(
            'field' => 'value',
        ),
    ),
));
// or callable
$collection->aggregate(function($aggregator) {
    $aggregator->match(function($expression) {
        $expression->whereLess('date', new \MongoDate);
    });
});
```
#### Options

Available aggregation options may be found at 
https://docs.mongodb.org/manual/reference/command/aggregate/#dbcmd.aggregate.

Options may be passed as argument of `aggregate` method:

```php
<?php

// as argument of Pipeline::aggregate
$collection->createAggregator()->match()->group()->aggregate($options);

// as argument of Collection::aggregate
$collection->aggregate($pipelines, $options);

// as calling of Pipeline methods
$collection
    ->createAggregator()
    ->explain()
    ->allowDiskUse()
    ->setBatchSize(100);
```

#### Debug

If client in debug mode and logger configured, pipelines will be logged.
There is ability to get explanation of aggregation:

```php
<?php

// set explain option
$collection->aggregate($pipelines, ['explain' => true]);

// or configure pipeline
$collection->createAggregator()->match(...)->group(...)->explain()->aggregate();
```

#### Aggregation cursor

`Collection::aggregate` return array as result, but also iterator may be optained:
Read more at http://php.net/manual/ru/mongocollection.aggregatecursor.php.

```php
<?php

// set as argument
$asCursor = true;
$collection->aggregate($pipelines, $options, $asCursor);

// or call method
$cursor = $collection->createAggregator()->match()->group()->aggregateCursor();
```
<br/>
<br/>

Events
-------

Event support based on Symfony's
[Event Dispatcher](http://symfony.com/doc/current/components/event_dispatcher/introduction.html)
component. You can attach and trigger
any event you want, but there are some already defined events:

| Event name     | Description                                                |
| -------------- | ---------------------------------------------------------- |
| afterConstruct | Already after construct executed                           |
| beforeValidate | Before document validation                                 |
| afterValidate  | After document validation                                  |
| validateError  | After document validation when document is invalid         |
| beforeInsert   | Before document will insert to collection                  |
| afterInsert    | After successfull insert                                   |
| beforeUpdate   | Before document will be updated                            |
| afterUpdate    | After successfull update of document                       |
| beforeSave     | Before insert or update of document                        |
| afterSave      | After insert or update of document                         |
| beforeDelete   | Before delete of document                                  |
| afterDelete    | After delete of document                                   |

Event listener is a function that calls when event triggered:

```php
<?php
$listener = function(
    \Sokil\Mongo\Event $event, // instance of event
    string $eventName, // event name
    \Symfony\Component\EventDispatcher\EventDispatcher $eventDispatcher // instance of dispatcher
) {}
```

Event listener may be attached by method `Document::attachEvent()`:
```php
<?php
$document->attachEvent('myOwnEvent', $listener, $priority);
```

It also may be attached through helper methods:
```php
<?php
$document->onMyOwnEvent($listener, $priority);
// which is equals to
$this->attachEvent('myOwnEvent', $listener, $priority);
```

Event may be attached in runtime or in `Document` class by override `Document::beforeConstruct()` method:
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

Event may be triggered to call all attached event listeners:
```php
<?php
$this->triggerEvent('myOwnEvent');
```

You can create your own event class, which extends `\Sokil\Mongo\Event' and pass it to listeners.
This allows you to pass some data to listener:

```php
<?php
// create class
class OwnEvent extends \Sokil\Mongo\Event {
    public $status;
}

// define listener
$document->attachEvent('someEvent', function(\OwnEvent $event) {
    echo $event->status;
});

// configure event
$event = new \OwnEvent;
$event->status = 'ok';

// trigger event
$this->triggerEvent('someEvent', $event);
```

To cancel operation execution on some condition use event handling cancel:
```php
<?php
$document->onBeforeSave(function(\Sokil\Mongo\Event $event) {
    if($this->get('field') === 42) {
        $event->cancel();
    }
})
->save();
```
<br/>
<br/>

Behaviors
----------

Behavior is a posibility to extend functionality of document object and reuse
code among documents of different class.

Behavior is a class extended from `\Sokil\Mongo\Behavior`. Any public method may be
accessed through document, where behavior is attached.

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
// single behavior
$document->attachBehavior('42behavior', '\SomeBehavior');
// set of behaviors
$document->attachBehaviors([
    '42behavior' => '\SomeBehavior',
]);
```

Behaviors may be defined as fully qualified class names, arrays, or `Behavior` instances:
```php
<?php
// class name
$document->attachBehavior('42behavior', '\SomeBehavior');

// array with parameters
$document->attachBehavior('42behavior', [
    'class'     => '\SomeBehavior',
    'param1'    => 1,
    'param2'    => 2,
]);

// Behavior instance
$document->attachBehavior('42behavior', new \SomeBehavior([
    'param1'    => 1,
    'param2'    => 2,
]);
```

Then you can call any methods of behaviors. This methods searches in order of atraching behaviors:
```php
<?php
echo $document->return42();
```
<br/>
<br/>

Relations
-------------

You can define relations between different documents, which helps you to load related documents.

To define relation to other document you need to override `Document::relations()` method and return array of relations in format `[relationName => [relationType, targetCollection, reference], ...]`. 

Also you can define relations in mapping:
```php
<?php

$collection->map([
    'someDb' => [
        'someCollection', array(
            'relations'     => array(
                'someRelation'   => array(self::RELATION_HAS_ONE, 'profile', 'user_id'),
            ),
        ),
    ],
]);
```

If relation specified both in mapping and document class, then mapping relation merged into document's relations, so mapping relations has more priority.

### One-to-one relation

We have to classes User and Profile. User has one profile, and profile belongs to User.

```php
<?php
class User extends \Sokil\Mongo\Document
{
    protected $schema = [
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
    protected $schema = [
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
    protected $schema = [
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
    protected $schema = [
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
    protected $schema = [
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
    protected $schema = [
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
<br/>
<br/>

Concurency
----------

### Optimistic locking

To enable optimistic locking, specify lock mode in mapping:
```php
use Sokil\Mongo\Collection\Definition;
use Sokil\Mongo\Document\OptimisticLockFailureException;

$client->map([
    'db' => [
        'col' => [
            'lock' => Definition::LOCK_OPTIMISTIC,
        ],
    ]
]);
```

Now when some process try to update already updated document, exception
`\Sokil\Mongo\Document\OptimisticLockFailureException` will be thrown.
<br/>
<br/>

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
<br/>
<br/>

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
<br/>
<br/>

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
<br/>
<br/>

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
<br/>
<br/>

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
<br/>
<br/>

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
<br/>
<br/>

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

##### Reading of file content

```php
<?php
// dump binary data to file
$file->dump($filename);

// get binary data
$file->getBytes();

// get resource
$file->getResource();
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
<br/>
<br/>

Versioning
----------

Versioninbg allows you to have history of all document changes. To enable versioning of documents in collection, you can set protected
property `Collection::$versioning` to `true`, call `Collection::enableVersioning()`
method or define versioning in mapping.

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

// through mapping
$database->map('someCollectionName', [
    'versioning' => true,
]);
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
<br/>
<br/>

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
<br/>
<br/>

Caching and documents with TTL
------------------------------

If you want to get collection where documents will expire after some specified time, just add special index to this collection.

```php
<?php
$collection->ensureTTLIndex('createDate', 1000);
```

You can do this also in migration script, using [Mongo Migrator](https://github.com/sokil/php-mongo-migrator).
For details see related documentation.

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

This operation may be done in some console command or migration script, e.g. by using migration tool [sokil/php-mongo-migrator](https://github.com/sokil/php-mongo-migrator), or
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
<br/>
<br/>

Debugging
---------

In debug mode client may log some activity to pre-configured logger or show extended errors.
```php
<?php

// start debugging
$client->debug();

// stop debugging
$client->debug(false);

// check debug state
$client->isDebugEnabled();
```

### Logging

Library suports logging of queries. To configure logging, you need to pass logger object to instance of `\Sokil\Mongo\Client` and enable debug of client. 
Logger must implement `\Psr\Log\LoggerInterface` due to [PSR-3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md):

```php
<?php
$client = new Client($dsn);
$client->setLogger($logger);
```

### Profiling

More details about profiling at [Analyze Performance of Database Operations](http://docs.mongodb.org/manual/tutorial/manage-the-database-profiler/)
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
<br/>
<br/>


Unit tests
---------

[![Build Status](https://travis-ci.org/sokil/php-mongo.png?branch=master&1)](https://travis-ci.org/sokil/php-mongo)
[![Coverage Status](https://coveralls.io/repos/sokil/php-mongo/badge.png)](https://coveralls.io/r/sokil/php-mongo)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sokil/php-mongo/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/sokil/php-mongo/?branch=master)
[![Code Climate](https://codeclimate.com/github/sokil/php-mongo/badges/gpa.svg)](https://codeclimate.com/github/sokil/php-mongo)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/45b7bd7f-9145-49af-8d6a-9380f14e12b6/mini.png)](https://insight.sensiolabs.com/projects/45b7bd7f-9145-49af-8d6a-9380f14e12b6)

### Local PHPUnit tests

To start unit tests, run:

```
./vendor/bin/phpunit -c tests/phpunit.xml tests
```

### Docker PHPUnit tests

Also available Docker containers. They starts with xdebug enabled, so you can sign in to any container and debug code there. 

Before running tests start docker container with:

```
docker-compose -f docker/compose.yml up -d
```

To run only few environments, run:

```
docker-compose -f docker/compose.yml up -d php70 mongodb32
```

To start tests on all supported PHP and MongoDB versions, run 

```
./run-docker-tests.sh
```

To run test on concrete platforms, specify them:
```
./run-docker-tests.sh -p 56 -p 70 -m 30 -m 32
```

To run concrete test , pass it:
```
./run-docker-tests.sh -t DocumentTest.php
```

Tests may be found at `./share/phpunit` dir after finishing tests. 

Contributing
------------

Pull requests, bug reports and feature requests is welcome. Please see [CONTRIBUTING](https://github.com/sokil/php-mongo/blob/master/CONTRIBUTING.md) for details.

Change log
----------

Please see [CHANGELOG](https://github.com/sokil/php-mongo/blob/master/CHANGELOG.md) for more information on what has changed recently.

License
-------

The MIT License (MIT). Please see [License File](https://github.com/sokil/php-mongo/blob/master/LICENSE) for more information.

[badge-totalDownloads-img]: http://img.shields.io/packagist/dt/sokil/php-mongo.svg?1
[badge-totalDownloads-url]: https://packagist.org/packages/sokil/php-mongo
