# Laravel Repositories

[![Build Status](https://travis-ci.org/Saritasa/php-laravel-repositories.svg?branch=master)](https://travis-ci.org/Saritasa/php-laravel-repositories)
[![CodeCov](https://codecov.io/gh/Saritasa/php-laravel-repositories/branch/master/graph/badge.svg)](https://codecov.io/gh/Saritasa/php-laravel-repositories)
[![Release](https://img.shields.io/github/release/saritasa/php-laravel-repositories.svg)](https://github.com/Saritasa/php-laravel-repositories/releases)
[![PHPv](https://img.shields.io/packagist/php-v/saritasa/laravel-repositories.svg)](http://www.php.net)
[![Downloads](https://img.shields.io/packagist/dt/saritasa/laravel-repositories.svg)](https://packagist.org/packages/saritasa/laravel-repositories)

Implementation of Repository pattern for Laravel (on top of Eloquent)

## Laravel 5.x

Install the ```saritasa/laravel-repositories``` package:

```bash
$ composer require saritasa/laravel-repositories
```

*Note:* For Laravel 5.4 use 1.* versions, for Laravel 5.5+ use 2.*

## Available classes

### IRepository
Interface, declaring common methods

### Repository
Repository class implements **IRepository** interface. Contains helper methods to build query for SQL-like storages.
Has protected method to join tables by Eloquent model relation name.
Supported relations are: **BelongsToMany**, **HasOne**, **HasMany**, **BelongsTo**
Nested relations supported.

**Example**

For method

```php
public function getUsersList(): Collection
{
    $query = $this->query();

    $this->joinRelation($query, ['role', 'profile', 'cars', 'profile.phones']);

    return $query;
}
```

result query will be

```SQL
SELECT *
FROM "users"
  LEFT JOIN "roles" ON "users"."role_id" = "roles"."id"
  LEFT JOIN "profiles" ON "profiles"."user_id" = "users"."id"
  LEFT JOIN "cars" ON "cars"."user_id" = "users"."id"
  LEFT JOIN "phones" ON "phones"."profile_id" = "profiles"."id"
```

###IEloquentRepository, EloquentRepository
In addition to **Repository** has **getWith()** method that allows to retrieve list of entities with 
eager loaded related models and related models counts. Also allows to filter this list by given criteria 
and sort in requested order.

**Example**:
```php
$usersRepository->getWith(['role', 'supervisors'], ['phones'], [['age', '>', 21]], $sortOptions)

// Retrieves list of users which age greater than 21. 
// Each user will be retrieved with pre-loaded role and supervisors models.
// List of users will be ordered by requested sort options (**SortOptions** class object)
```

###SortOptions
DTO that allows to pass sort options to repository. Contains sort order field 
and sort order direction that should be one of **OrderDirections** enum value.

## Exceptions
### Repository Exception
Should be thrown by class, implementing IRepository, if there is no more suitable exception defined.

**Example**:
```php
function findWhere(array $fieldValues) {
    if (!count($fieldValues)) {
        new RepositoryException($this, "No search criteria provided");
    }
    // ...
}
```

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
