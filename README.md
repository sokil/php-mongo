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
