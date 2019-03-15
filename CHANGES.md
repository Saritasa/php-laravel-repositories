# Changes History

3.3.0
------
+ Count method now can receive fieldValues array to filter results
+ Repository methods create/save/delete/ now can throw an exception if given model type don't match with served
model by repository
+ Repository findOrFail method now checks compliance types of given value and served model primary key.
+ getPage and getCursorPage methods now use nested queries functionality as well as findWhere/getWhere/getWith
+ Method getWithBuilder marked as deprecated
+ Increased unit tests coverage
+ Fixed small description issues

3.2.1
------
+ Methods getWith/findWhere/getWhere call when as value uses Carbon\Carbon instance and as key field name, now not throws BadCriteriaException.

3.2.0
------
+ Now in repository methods getWith/getWhere/findWhere could be passed
instance of Criterion instead array.
+ Available use multiple in and not in operators in any nested level
+ Carbon instance can be passed to compare as date
+ In case of bad query state BadCriteriaException will be thrown

3.1.0
------
+ Add package configuration file.
+ Add ability to register custom repositories implementation using configuration file.
+ Add method getSearchableFields in IRepository contract and all implementation.
+ Make implementation of IRepositoryFactory in DI container as singleton by default.
+ Improve default provider documentation.

3.0.0
------
+ Add IRepositoryFactory contract and base implementation.
+ Update IRepository contract and base implementation.
+ Changed namespace to Saritasa\\LaravelRepositories.
+ Removed all controllers classes and unused interface.
+ Add ModelNotFound exception.
+ Switch minimum PHP version to 7.1
+ Switch to minimum Laravel version 5.5

2.1.1
------
+ Change catching type in Repository constructor from \Exception to \Throwable
+ Add description to Repository constructor
+ Update accepted exception type in RepositoryException from \Exception to \Throwable.
+ Add Mocker helper yo use db connections mocks instead sqlite:memory.
+ Add mockery/mockery in composer dependencies.
+ Add base repository test.

2.1.0
------
Add EloquentRepository with extended method **getWith()** to retrieve list of items 

2.0.1
-----
Resolve composer dependencies conflicts:
do not require specific versions of transitive dependencies

2.0.0
-----
Switched to Dingo/Api 2.0. Use this version with Laravel 5.5+

1.0.16
------
Add support for repositories to join tables by relation name

1.0.15
------
Explicitly add [saritasa/dingo-api-custom](https://github.com/Saritasa/php-dingo-api-custom) as dependency

1.0.14
-----
Make Repository query() method overridable (protected)

1.0.13
------
Require saritasa/laravel-controllers v2.0+

1.0.12
------
Use CursorQueryBuilder (wrapper around original query) as default implementation for cursors

1.0.11
------
Make cursor pagination more versatile

1.0.10
-----
Fix issue when model doesn't use SoftDeletes trait

1.0.9
-----
Add cursor result response with custom ordered query

1.0.8
-----
Fix cursor pagination with joined queries

1.0.7
-----
Fix RepositoryException namespace

1.0.6
-----
Improved cursor pagination

1.0.5
-----
- Weaker typing requirements for paging
- Remove clones of BaseApiController
- Implement cursor pagination as in Fractal samples

1.0.4
-----
Fix namespace and SecureApiResourceController constructor

1.0.3
-----
Fix namespaces

1.0.1, 1.0.2
-----
Update dependencies versions

1.0.0
-----

Initial version:
* IRepository
* Base Repository implementation
* Repository Exception
