## 1.11.4 (2014-0104)
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