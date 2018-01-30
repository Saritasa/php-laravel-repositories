# Laravel Repositories

[![Build Status](https://travis-ci.org/Saritasa/php-laravel-repositories.svg?branch=master)](https://travis-ci.org/Saritasa/php-laravel-repositories)

Implementation of Repository pattern for Laravel (on top of Eloquent)

## Laravel 5.x

Install the ```saritasa/laravel-repositories``` package:

```bash
$ composer require saritasa/laravel-repositories
```

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

1. Create fork
2. Checkout fork
3. Develop locally as usual. **Code must follow [PSR-1](http://www.php-fig.org/psr/psr-1/), [PSR-2](http://www.php-fig.org/psr/psr-2/)**
4. Run [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) to ensure, that code follows style guides
5. Update [README.md](README.md) to describe new or changed functionality. Add changes description to [CHANGES.md](CHANGES.md) file.
6. When ready, create pull request

## Resources

* [Bug Tracker](http://github.com/saritasa/php-laravel-repositories/issues)
* [Code](http://github.com/saritasa/php-laravel-repositories)
* [Changes History](CHANGES.md)
* [Authors](http://github.com/saritasa/php-laravel-repositories/contributors)
