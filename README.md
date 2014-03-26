PHPMongo
========
[![Build Status](https://travis-ci.org/sokil/php-mongo.png?branch=master)](https://travis-ci.org/sokil/php-mongo)
[![Latest Stable Version](https://poser.pugx.org/sokil/php-mongo/v/stable.png)](https://packagist.org/packages/sokil/php-mongo)

Object Document Mapper for MongoDB


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
```

To get instance of collection class \Sokil\Mongo\Collection:
```php
$collection = $database->getCollection('collectionName');
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

Getting documents by id
-----------------------

To get document from collection by its id:
```php
$document = $collection->getDocument('5332d21b253fe54adf8a9327');
```

This document object is instance of class \Sokil\Mongo\Document. If you want to use your own document class, you must configure its name in relative collection class:

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

Document schema and data
------------------------

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

To get value of document's field you may use one of following ways:
```php
$document->requiredField; // defaultValue
$document->get('requiredField'); // defaultValue

$document->someField; // ['subDocumentField' => 'value']
$document->get('someField'); // ['subDocumentField' => 'value']
$document->get('someField.subDocumentField'); // 'value'

$document->get('some.unexisted.subDocumentField'); // null
```
If field not exists, null value returned.

To set value you may use following ways:
```php
$document->someField = 'someValue'; // {someField: 'someValue'}
$document->set('someField', 'someValue'); // {someField: 'someValue'}
$document->set('someField.sub.document.field', 'someValue'); // {someField: {sub: {document: {field: {'someValue'}}}}}
```

Document validation
-------------------

Document can be validated before save. To set validation rules, method roles must be redefined by validation rules.
Supported rules are:

```php
class User except \Sokil\Mongo\Document
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

If some validation rule applied only for some scenarios, this scenarios must be passed on 'on' key, separated by comma.

If some validation rule applied to all except some scenarios, this scenarios must be passed on 'except' key, separated by comma.

If document invalid, errors may be accessed through getErrors method.

Error may be triggered manually by calling method triggerError($fieldName, $rule, $message)
