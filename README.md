PHPMongo
========

Wrapper to PECL Mongo driver

Basic example
-------

```php
    /**
     * Connect to collection
     */

    // connect to mongo
    $client = new Client('mongodb://127.0.0.1');

    // select database
    $database = $client->getDatabase('test');

    // select collection
    $collection = $database->getCollection('phpmongo_test_collection');

    /**
     * Create document
     */

    $document = $collection->create(array(
        'l1'   => array(
            'l11'   => 'l11value',
            'l12'   => 'l12value',
        ),
        'l2'   => array(
            'l21'   => 'l21value',
            'l22'   => 'l22value',
        ),
    ));

    $collection->save($document);

    /**
     * Update document
     */

    $document->set('l1.l12', 'updated');
    $collection->save($document);

    /**
     * Read document
     */

    $document = $collection->findById($documentId);

    /**
     * Delete document
     */

    $collection->delete($document);
```

Document validation
-------------------

Document can be validated before save. To set validation rules, method roles must be redefined by validation rules.
Supported rules are:

```
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
