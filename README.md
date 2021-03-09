
# Laravel Repositories  

[![PHP Unit](https://github.com/Saritasa/php-laravel-repositories/workflows/PHP%20Unit/badge.svg)](https://github.com/Saritasa/php-laravel-repositories/actions)
[![PHP CodeSniffer](https://github.com/Saritasa/php-laravel-repositories/workflows/PHP%20Codesniffer/badge.svg)](https://github.com/Saritasa/php-laravel-repositories/actions)
[![CodeCov](https://codecov.io/gh/Saritasa/php-laravel-repositories/branch/master/graph/badge.svg)](https://codecov.io/gh/Saritasa/php-laravel-repositories)
[![Release](https://img.shields.io/github/release/saritasa/php-laravel-repositories.svg)](https://github.com/Saritasa/php-laravel-repositories/releases)
[![PHPv](https://img.shields.io/packagist/php-v/saritasa/laravel-repositories.svg)](http://www.php.net)
[![Downloads](https://img.shields.io/packagist/dt/saritasa/laravel-repositories.svg)](https://packagist.org/packages/saritasa/laravel-repositories)
  
Implementation of Repository pattern for Laravel (on top of Eloquent)  
  
## Laravel 5.5+
  
Install the ```saritasa/laravel-repositories``` package:  
  
```bash  
$ composer require saritasa/laravel-repositories  
```  
## Configuration
- Publish configuration file:

```bash
php artisan vendor:publish --tag=laravel_repositories
```

- Register custom repositories implementation:  
```php
return [
	'bindings' => [
	    \App\Models\User::class => \App\Repositories\UserRepository::class,
	],
];
```
*Note: Your custom repository must implement `IRepository` contract.*

## Getting repository inside your code
To get specific repository in your code you can just build with DI container repositories factory and then
build needed for your repository in this factory.  
 **Example:**
```php
    $repositoryFactory = app(\Saritasa\LaravelRepositories\Repositories\IRepositoryFactory::class);
    $userRepository = $repositoryFactory->getRepository(\App\Models\User::class);
```
## Filtering results with repository
Methods findWhere/getWhere/getWith/getPage/getCursorPage/count/ can receive criteria as params 
Here the examples of available syntax:
- Criterion without operator:
```php
    $criteria = [
        'field1' => 'value1',
        'field2' => 1,
    ];
```  
*In this case `=` operator and `and` boolean between them will be used.*  
**Example:** `... 'field1 = 'value1' and 'field2' = 1 ...`
- Criterion with operator:
```php
    $criteria = [
        ['field1', '<>', 'value1'],
        ['field2', '>', 1, 'or'],
        ['field3', 'in', [1, 2]],
        ['field4', 'not in', new \Illuminate\Support\Collection([1, 2])],
    ];
```  
**`Important:` arrays and collection can be used only with `in` and `not in` operators.**  
*Note: As 4th parameter you can pass boolean `or`/`and` (`and` uses by default).
But you should remember that boolean used between current and previous criterion*    
**Example:** `... 'field1 <> 'value1' or 'field2' > 1 and 'field3' in (1, 2) and 'field4' not in (1, 2) ...`  
- Criterion as DTO:
```php
    $criteria = [
            new Criterion([
                Criterion::OPERATOR => '<>',
                Criterion::VALUE => 'value1',
                Criterion::ATTRIBUTE => 'field1',
            ]),
            new Criterion([
                Criterion::OPERATOR => '>',
                Criterion::VALUE => 1,
                Criterion::ATTRIBUTE => 'field2',
                Criterion::BOOLEAN => 'or',
            ]),
            new Criterion([
                Criterion::OPERATOR => 'in',
                Criterion::VALUE => [1, 2],
                Criterion::ATTRIBUTE => 'field3',
            ]),   
            new Criterion([
                Criterion::OPERATOR => 'not in',
                Criterion::VALUE => [1, 2],
                Criterion::ATTRIBUTE => 'field4',
            ]),                     
    ];
```
Result will be the same as in previous example.
- Nested criteria:  
You can group different conditions that gives flexibility in getting data from repository  
```php
    $criteria = [
        [
            ['field1', '<>', 'value1'],
            ['field2', '>', 1, 'or'],
        ],
        [
            ['field3', 'in', [1, 2]],
            ['field4', 'not in', [1, 2],
            'boolean' => 'or',
        ],
    ];
```
*Note: you can add nesting level in any depth what you want. To use `or` condition between one group
and other(group and non-group condition) you can pass 'boolean' parameter in the same level as other conditions.*

**Example:**`... ('field1 <> 'value1' or 'field2' > 1) or ('field3' in (1, 2) and 'field4' not in (1, 2)) ...`  
- Relation existence criterion:  
You can build queries to check on existence any model relations
```php
    $criteria = [
        new RelationCriterion('roles', [['slug', 'in', [1, 2]]], 'or'), 
    ];
```

## Preload model relations
Method **getWith()** method allows to retrieve list of entities with   
eager loaded related models and related models counts. Also allows to filter this list by given criteria   
and sort in requested order.  
  
**Example**:  
```php  
$usersRepository->getWith(
    ['role', 'supervisors'],
    ['phones'],
    [],
    new Saritasa\LaravelRepositories\DTO\SortOptions('name', 'DESC')
);
```  
- Each user will be retrieved with pre-loaded role and supervisors models.
- Each user will be retrieved with pre-loaded phones relation count.
- List of users will be ordered by requested sort options.  
  
## Exceptions
### Repository Exception  
Base exception for repository layer.
### Repository register exception
Throws when can not register custom repository.
### Model not found Exception  
Throws in case when some model not exists in storage.  
### Bad Criteria Exception
Throws when provided criteria has incorrect format at least in one criterion inside.

## Contributing  
  
1. Create fork, checkout it  
2. Develop locally as usual. **Code must follow [PSR-1](http://www.php-fig.org/psr/psr-1/), [PSR-2](http://www.php-fig.org/psr/psr-2/)** -  
    run [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) to ensure, that code follows style guides  
3. **Cover added functionality with unit tests** and run [PHPUnit](https://phpunit.de/) to make sure, that all tests pass  
4. Update [README.md](README.md) to describe new or changed functionality  
5. Add changes description to [CHANGES.md](CHANGES.md) file. Use [Semantic Versioning](https://semver.org/) convention to determine next version number.  
6. When ready, create pull request  
  
### Make shortcuts  
  
If you have [GNU Make](https://www.gnu.org/software/make/) installed, you can use following shortcuts:  
  
* ```make cs``` (instead of ```php vendor/bin/phpcs```) -  
    run static code analysis with [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)  
    to check code style  
* ```make csfix``` (instead of ```php vendor/bin/phpcbf```) -  
    fix code style violations with [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer)  
    automatically, where possible (ex. PSR-2 code formatting violations)  
* ```make test``` (instead of ```php vendor/bin/phpunit```) -  
    run tests with [PHPUnit](https://phpunit.de/)  
* ```make install``` - instead of ```composer install```  
* ```make all``` or just ```make``` without parameters -  
    invokes described above **install**, **cs**, **test** tasks sequentially -  
    project will be assembled, checked with linter and tested with one single command  
  
## Resources  
  
* [Bug Tracker](http://github.com/saritasa/php-laravel-repositories/issues)  
* [Code](http://github.com/saritasa/php-laravel-repositories)  
* [Changes History](CHANGES.md)  
* [Authors](http://github.com/saritasa/php-laravel-repositories/contributors)
