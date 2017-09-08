# Laravel Repositories

A package that provides a neat implementation for integrating the Repository pattern with Laravel &amp; Eloquent.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/okaybueno/service-generator.svg?style=flat-square)](https://packagist.org/packages/okaybueno/service-generator)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Quality Score](https://img.shields.io/scrutinizer/g/okaybueno/service-generator.svg?style=flat-square)](https://scrutinizer-ci.com/g/okaybueno/service-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/okaybueno/service-generator.svg?style=flat-square)](https://packagist.org/packages/okaybueno/service-generator)

## Goal

Working with repositories can provide a great way to not only decouple your code but also separate concerns and
isolate stuff, as well as separate and group responsibilities. Most of the time we will perform really generic actions
on our database tables, like create, update, filter or delete.  

However, using repositories is not always a good idea, specially with Laravel and its ORM, Eloquent, as it -sometimes-
forces you to give up some great features in favor of *better architecture* (it depends). For some projects this may be
an overkill and therefore is up to the developer to see if this level of complexity is needed or not.

This package aims to provide a boilerplate when implementing the Repository pattern on Laravel's Eloquent ORM. The way
it provides it it's using a `RepositoryInterface` and a basic `Repository` implementation that is able to work with Eloquent models,
 providing basic methods that will cover 85% if the Database operations that you will probably do in your application.

## Installation

1. Install this package by adding it to your `composer.json` or by running `composer require okaybueno/service-generator` in your project's folder.
2. For Laravel 5.5 the Service provider is automatically registered, but if you're using Laravel 5.4, then you must add the 
provider to your `config/app.php` file: `OkayBueno\Repositories\RepositoryServiceProvider::class`
3. Publish the configuration file by running `php artisan vendor:publish --provider="OkayBueno\Repositories\RepositoryServiceProvider"`
4. Open the configuration file (`config/repositories.php`) and configure paths according to your needs.
5. Ready to go!


## Usage

To start using the package, you just need to create a folder where you will place all your repository interfaces and the
repository implementations and extend every single repository from the `EloquentRepository` class offered by the package.  

The Eloquent model to be handled by the repository that you have created must also be injected via the Repo constructor.  

The package then will try to load all the repository interfaces and bind them to a repository implementation according to
the parameters specified in the `config/repositories.php` file.


## Examples

*NOTE: Although the package includes a generator, please read all the docs carefully as some things may look just "magic".*

Let's consider we will have all our repositories in a folder called "Repositories", under a folder called "MyWebbApp" inside
the "app" folder: app/MyWebApp/Repositories.

At the root of this folder we'll have all our interfaces following the next name convention: `[RepositoryName]Interface.php`

**NOTE**: It does not really matter the name that we use as long as we use "Interface" as suffix. This is important because the 
auto binder will try to find all files matching this pattern.

Inside this Repositories folder, we must have another folder called Eloquent, that will contain all our implementations for
the repositories, following the next name convention: `[RepositoryName].php`.  



## Changelog

-- No public version released yet --


## Credits

- [okay bueno - A fully transparent digital products studio](http://okaybueno.com)
- [Jesús Espejo](https://github.com/jespejoh) ([Twitter](https://twitter.com/jespejo89))

## Bugs & contributing

* Found a bug? That's good (and bad). Let me know using the Issues on Github.
* Need a feature or have something interesting to contribute with? Great! Open a pull request.

## To-dos

- Automated tests: Although this package has been heavily tested (even on production), there are no automated tests in place.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.