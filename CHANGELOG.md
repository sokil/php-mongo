## 1.23.1 (2018-11-24)
  * UrlValidator ping use checkdnsrr
  * Fixed in building docker environment
  * Changed return values of rename methods of Collection 

## 1.23.0 (2018-11-04)
 * Added method to rename collection

## 1.22.2 (2017-11-10)
 * Add $elemMatch to projection argument of `Cursor`
 * `Cursor` methods `findOne`, `findAll`, `findRandom` deprecated, use `one`, `all` and `random` respectively;
 * Allow pass filter to docker tests

## 1.22.1 (2017-11-02)
 * Document's `afterConstruct` event triggered with document instance

## 1.22 (2017-08-11)
 * Cache now compatible with PSR-16;
 * Cache setters now return bool instead of exceptions to be compatible with PSR-16;
 * Cache::setNeverExpired and Cache::setDueDate now deprecated. Use Cache::set instead;

## 1.21.5 (2017-06-20)
 * Fix searching documents with regex [#148](https://github.com/sokil/php-mongo/issues/148)

## 1.21.4 (2017-06-15)
 * Fix collection creation with empty options

## 1.21.2 (2017-06-14)
 * Allow specify validator while create collection

## 1.21.1 (2017-03-24)
 * Fixed mispell in method name BatchInsert::isValidationEbabled
 * Fixed usage of validation flag in BatchInsert

## 1.21 (2017-02-09)
 * `Document::beforeConstruct` moved to `Structure::beforeConstruct` so embedded documents may configure some logic there
 * `Collection::batchDelete()` now has required argument
 * Now may be configured batch limit in `Cursor::copyToCollection` and `Cursor::moveToCollection`
 * Methods of `\Iterator` interface currently not recommended to use directly in `Cursor` and `Paginator`. But if used, now `rewind` MUST be calls before `current`.
 * Remove debug logger calls

## 1.20 (2017-01-09)
 * Implemented support of new ext-mongodb and, as a result, PHP7 and HHVM through compatibility layer "alcaeus/mongo-php-adapter", which implement API from old ext-mongo extension;
 * Cursor::findOne() throws internal `CursorException` exception instead of related to mongo extension. Exception from extension may be obtained from internal exception;
 * Document::save() throws internal `WriteException` exception instead of related to mongo extension. Exception from extension may be obtained from internal exception;
 * Docker tests now check PHP 7 code
 * Structure::apply() now protected

## 1.19.2 (2016-12-18)
 * Fix bug in Document::addToSet
 * Docker config improvements
 
## 1.19.1 (2016-10-08)
 * Fix bug [#132](https://github.com/sokil/php-mongo/issues/132) in Document::push

## 1.19 (2016-09-14)
 * Configure document pool status in collection's mapping;
 * `Collection::_mongoCollection` is deprecated. Use `Collection::getMongoCollection()` instead;
 * `Collection::ensureIndex()` is deprecated, use `Collection::createIndex()`;
 * `Cursor::toArray()` removed, use `Cursor::getMongoQuery()`;
 * `Document::belongsToCollection()` removed, use `Collection::hasDocument()`;
 * `Document::FIELD_TYPE_*` constants removed, use `FieldType` enum
 * `Collection::_database` removed, use Collection::getDatabase() instead;

## 1.18.2 (2016-09-12)
 * Docker tests
 
## 1.18.1 (2016-08-18)
 * Support of DBRefs

## 1.17 (2016-08-16)
  * `Client::$_mapping` set private. `Use Client::map()`
  * Configure document class if collection class also configured by class prefix [#128](https://github.com/sokil/php-mongo/issues/128)

## 1.16.1 (2016-06-27)
  * Fixed aggregation pipeline setter

## 1.16 (2016-06-23)
  * Allow set embedded document and validate it;
  * `\Sokil\Mongo\Structure\Arrayable` moves to `\Sokil\Mongo\ArrayableInterface`;
  * `Structure::$_modifiedFields` and `Structure::$_originalData` set private;
  * Document::_data is now deprecated, and replaced with protected property Document::schema;
  * Documents not allowed to be cloned;
  * Removed 'validator' suffix  from names of validation errors in array of validation errors obtained from Document::getErrors();

## 1.15.2 (2016-04-27)
  * Update version of Event Dispatcher

## 1.15.1 (2016-03-10)
  * Allow delete indexes

## 1.15 (2016-03-01)
  * Removed `Collection::createPipeline()`. Use `Collection::createAggregator`;
  * Aggregator options may be passed as arguments of `Collection::aggregate($pipelines, $options)` or configured 
  through methods of `Pipeline`;
  * Experimental feature: aggregation returns Cursor, if third parameter passed `Collection::aggregate($pipelines, $options, $asCursor)`;
  * `Collection::explainAggregate()` is deprecated. Use `Pipeline::explain()`;
  * Added debug mode to `Client`;

## 1.14 (2016-01-30)
  * Fulltext search

## 1.13.9 (2016-01-13)
  * Fixed bug #121 - Getting relation when document pool disabled

## 1.13.8 (2016-01-01)
  * Added $addToSet operator

## 1.13.7 (2015-09-26)
  * Add support of batch operations

## 1.13.6 (2015-08-25)
  * Support of $unwind pipeline in aggregation

## 1.13.5 (2015-08-07)
  * Fix getting HAS_ONE and BELONGS relation when related object not found;

## 1.13.4 (2015-07-27)
  * Cache detected db version
  * Cursor autocomplete improves

## 1.13.3 (2015-07-06)
  * Use `MongoWriteBatch` classes when using unit of work

## 1.13.2 (2015-05-22)
  * Added support of cursor timeouts

## 1.13.1 (2015-05-14)
  * Added `Database::getLastError()`

## 1.13 (2015-05-03)
  * Optimistic locking
  * Refactoring of mapping configs.
  * Default argument removed from `Collection::getOption()`.
  * Removed deprecated method `Collection::saveDocument()`.
  * Removed deprecated method `Client::setConnection()`. Use Client::setMongoClient().
  * Removed deprecated method `Client::getConnection()`. Use Client::getMongoClient().
  * Removed `Document::pushFromArray()`. Use `Document::pushEach()`.
  * Removed `Paginator::setQueryBuilder()’. Use `Paginator::setCursor()`.
  * Removed `Document::fromArray()`. Use `Document::merge()` instead.
  * Removed `Structure::load()`. Use `merge` or `mergeUnmidified` instead.
  * Revision methods moved to `RevisionManager`. Call them from `Document` instance directly is deprecated. Use `Document::getRevisionManager()` instead.
  * Mark Document::_scenario as private.
  * Define relations in mapping.
  * `Operator::getAll()` is deprecated. Use `Operator::toArray()`.
  * Protected access of property Structure::_modifiedFields is deprecated. Use self::getModifiedFields().
  * Protected access of property Structure::_originalData is deprecated. Use self::getOriginalData().
  * Removed classes QueryBuilder and GridFSQueryBuilder. Hydration logic improved
  * Refactor document saving
  * Collection::deleteDocument() is deprecated. Use Document::delete()
  * Collection::isVersioningEnabled() and Collection::enableVersioning() are deprecated. Use 'vrsioning' in mapping.
  * Collection properties 'documentClass', 'versioning', '_index' and '_queryExpressionClass' are deprecated. Use mapping declarations instead.

## 1.12.8 (2015-03-03)
  * Method 'Validator::validate()' marked as final
  * Refactoring or document relations
  * `Document::belongsToCollection()` now deprecated. Use Collection::hasDocument()
  * Refactor document events

## 1.12.7 (2015-02-20)
  * Define cursor's batch size

## 1.12.6 (2015-02-06)
  * Accept expression array and callable when call `Collection::getDistinct()`

## 1.12.5 (2015-02-01)
  * Fix update when expression defined as callable
  * Fix deleting documents when expression specified as callable

## 1.12.4 (2015-01-27)
  * If callable in `Dollection::getDocument()` specified, document always loaded directly omitting document pool.
  * Fixed 'in' validator when custom error message specified
  * Dependency from major version number of Symfony Event Dispatcher

## 1.12.3 (2015-01-18)
  * Fixed bug with naming of validator in errors array. Removed suffix 'validator'.
  * Behavior refactoring.
  * Old validator name is kept for back compatibility and will be removed in next versions

## 1.12.2 (2015-01-13)
  * `Operator` refactoring and bugfixes
  * Fix Collection::update when update data set as array

## 1.12.1 (2015-01-10)
  * Set `Structure` to document as embedded document

## 1.12.0 (2015-01-09)
  * Apply chain of functions over result set

## 1.11.7 (2015-01-06)
  * Bug with passing behaviors, configured in mapping, to documents

## 1.11.6 (2015-01-06)
  * Refactor document validation exceptions. Exception `\Sokil\Mongo\Document\Exception\Validate` deprecated. Use `\Sokil\Mongo\Document\InvalidDocumentException`.
  * Allow to get matched params on regex mapping.

## 1.11.5 (2015-01-06)
  * Configure cursor when get document by id to slice embedded documents or other reason
  * Add `Collection::update()` method that allow to define different update options
  * Refactoring and bugfixes

## 1.11.4 (2015-01-04)
  * Allow unset field end check for empty value by php functions `unset` and `isset`
  * Attach behaviors in mapping
  * Bugfixes

## 1.11.3 (2014-12-31)
  * Fixed bug with default values of collection options
  * Happy New Year

## 1.11.2 (2014-12-29)
  * `AggragetePipelines` class renamed to `Pipeline`. If you in some reason use it directly, use factory method `Collection::greateAggregator()` instead
  * Added some aggregation group accumulators and expressions
  * Query builder's expression may be configured in mapping

## 1.11.1 (2014-12-21)

  * ```Client::setConnection()``` deprecated sice 1.8.0. Use ```Client::setMongoClient()```
  * ```Client::getConnection()``` deprecated sice 1.8.0. Use ```Client::getMongoClient()```
  * Overloading of query builder through ```Collection::$_queryBuilderClass``` is deprecated and will be marked as private. Overload expression ```Collection::$_queryExpressionClass``` instead
  * ```Collection::saveDocument()``` deprecated since v.1.8.0. Use ```Document::save()``` method
  * Collection::createPipeline() deprecated since 1.10.10, use ```Collection::createAggregator()``` or callable in ```Collection::aggregate()```
  * ```Document::fromArray()``` deprecated use ```Document::merge()``` instead
  * ```Document::pushFromArray()``` deprecated since 1.6.0 use ```Document::pushEach()``` instead
  * ```Paginator::setQueryBuilder()``` deprecated since 1.2.0 use ```Paginator:::setCursor()```
  * ```Structure::load()``` deprecated since 1.8.1 and will be removed in next versions. Use concrete merge methods

## 1.0 (2013-11-01)
 * Initial functionality
