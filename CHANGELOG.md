## 1.12.8@DEV
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
