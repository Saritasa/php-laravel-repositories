# Laravel Repositories

Implementation of Repository pattern for Laravel (on top of Eloquent)

## Laravel 5.x

Install the ```saritasa/laravel-repositories``` package:

```bash
$ composer require saritasa/laravel-repositories
```

## Available classes

### IRepository
Interface, declaring common methods

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
4. Update [README.md](README.md) to describe new or changed functionality. Add changes description to [CHANGES.md](CHANGES.md) file.
5. When ready, create pull request

## Resources

* [Bug Tracker](http://github.com/saritasa/php-laravel-repositories/issues)
* [Code](http://github.com/saritasa/php-laravel-repositories)
* [Changes History](CHANGES.md)
* [Authors](http://github.com/saritasa/php-laravel-repositories/contributors)
