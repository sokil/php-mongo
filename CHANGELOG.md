## master (2015-08-12)
  * Persistance now fire's document's events (beforeSave/beforeInsert/beforeUpdate)

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
