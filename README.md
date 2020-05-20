# IMPORTANT ❗
**This package has been discontinued so it won't receive any other update. If you're using it please consider migrating to another solution – or fork it and depend on your version own package.**

--- 

# Service Generator

A package to automatically generate services and validators for your Laravel apps. 

[![Latest Version on Packagist](https://img.shields.io/packagist/v/okaybueno/service-generator.svg?style=flat-square)](https://packagist.org/packages/okaybueno/service-generator)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Quality Score](https://img.shields.io/scrutinizer/g/okaybueno/service-generator.svg?style=flat-square)](https://scrutinizer-ci.com/g/okaybueno/service-generator)
[![Total Downloads](https://img.shields.io/packagist/dt/okaybueno/service-generator.svg?style=flat-square)](https://packagist.org/packages/okaybueno/service-generator)

## Goal

There are lots of different ways to architect your web apps. One of those ways is the so called "Hexagonal architecture",
which far from being as new and hype as micro-services just puts on thing clear on the table: separating of concerns in
n-tiers, so each layer provides certain functionality to the layer above, and uses some functionality from the layer below,
 by using contracts or interfaces that provide entry points for the functionality living in each one of those layers.
We know: it's a bit abstract. You can read more about this architecture [here](http://alistair.cockburn.us/Hexagonal+architecture) 
and [here](http://fideloper.com/hexagonal-architecture).
 
At okay bueno we are concerned about moving fast and iterating efficiently on digital products. That means also
optimising the way the work and automating steps that are tedious and monotonous. One of those steps is the process of 
creating the different services that compound our apps. It's always the same: you have to create the interface and then
the implementation, and in most of the cases you also need to create validators and repositories and inject them. After 
that you have to create the service provider and wire things up. Those files contain the same skeleton all the times, so 
this package provides an interactive way of creating these services via the CLI.

## Installation

1. Install this package by adding it to your `composer.json` or by running `composer require okaybueno/service-generator` in your project's folder.
2. For Laravel 5.5 the Service provider is automatically registered, but if you're using Laravel 5.4, then you must add the 
provider to your `config/app.php` file: `OkayBueno\ServiceGenerator\ServiceGeneratorServiceProvider::class`
3. Publish the configuration file by running `php artisan vendor:publish --provider="OkayBueno\ServiceGenerator\ServiceGeneratorServiceProvider"`
4. Open the configuration file (`config/service-generator.php`) and configure the settings according to your needs.
5. Ready to go!


## Usage

This package simple provides a generator command to bootstrap the files that compound a certain service. Therefore the
only functionality provided is a command that will ask you a few questions and will create and wire things up, so you
don't have to do it manually. 

The package is required only during development, so feel free to use it only during development and not on production.


## Examples

*NOTE: This piece of text is heavily biased. This is the way I like to split my code, but you don't necessarily have to do it like this.*

Our app will be divided in 3 groups of services: frontend (used by the API and other methods exposed to the public), 
backend (used by our backend app), and shared (services or methods that are shared across both backend and frontend).

In each of these folders we have another separation of concerns, so the services for our *Users* will go into a folder,
the services for our *Invoices* will go to another folder, and so on... Same way, each of these services will contain 
the validation on its own, [provided by another of our packages](https://github.com/okaybueno/validation) (required by this package).

All in all, the final structure would look like this:

```
+-- app
|   +-- MyApp
|       +-- Services
|            +-- Backend
|               +-- Users
|                       +-- UsersServiceInterface.php
|                       +-- UsersServiceProvider.php
|                       +-- src
|                           +-- UsersService.php
|               +-- Invoices
|                       +-- InvoicesServiceInterface.php
|                       +-- InvoicesServiceProvider.php
|                       +-- src
|                           +-- InvoicesService.php
|                       +-- Validation
|                           +-- InvoicesValidationInterface.php
|                           +-- src
|                               +-- InvoicesLaravelValidator.php
|               ...
|            +-- Frontend
|               +-- Users
|                       +-- UsersServiceInterface.php
|                       +-- UsersServiceProvider.php
|                       +-- src
|                           +-- UsersService.php
|       +-- Repositories
|           +-- UserRepositoryInterface.php
|            ...
|           +-- Eloquent
|               +-- UserRepository.php
|                ...
```


## Generator

Yeah, creating all those files, link them up, inject validators and repositories is a bummer. Also for me. 
So what if we automate that, so we can spend more time doing cool things (like actually writing some code!)?

Just execute `php artisan make:service {service}`, where `{service}` is just the name of the group-service that you want 
to create (for example, `php artisan make:service Users`) and then just follow the steps on the screen :).

##### 1st step: Selecting the group

As you have seen, the `config/service-generator.php` contains just one parameter to configure: namespace and location
of all the different folders that can keep services. The first step on the interactive prompt is to select the group
for which this service is. You can do that by just selecting the option on the given screen:


```
For which group do you want to create the service?:
  [0] MyApp\Services\Frontend
  [1] MyApp\Services\Backend
  [2] MyApp\Services\Shared
 > 
```

##### 2nd step: A) Injecting repository (optional)

Pretty often our services will be using more or more than one repository, so you can inject it at this point.
The console will ask you if you want to inject a repository. If you select yes, then you'll be prompted to
introduce the full class name of the repository (or interface) that you want to inject. If you select not
(selected by default), then you can move to step 3.

```
 Do you want to inject a repository to this service? (yes/no) [no]:
 >
```

##### 2nd step: B) Selecting repository (optional)

If you selected "yes" to the injection of a repository, then you have to introduce the full class name of the repository
that you want to inject. Our advices is to inject a repository and bind the interface to a class, resolved by the IoC 
container that Laravel offers:

```
 Please specify the full interface (with namespace) for the repository that you want to inject 
 (ie: MyApp\Repositories\MyRepositoryInterface):
 >
```

##### 3rd step: Creating and injecting a validator and its interface (optional)

Sometimes you may also need to validate the data inside the repository, so in these cases you might want to create
and inject a validator. If you select "yes" (selected by default) then a validator interface and its implementation in
Laravel will be generated, following the structure from our example above. If you select "no", then no validation
services will be created:

```
 Do you want to create and inject a validator for this service? (yes/no) [yes]:
 >
```

##### 4th step: Wiring the service provider up 

A service provider will be created as well, so the last step is to wire that service provider to our application. To do,
just add a normal service provider the same way that you would add it if you were creating this services manually :). If 
using Laravel 5.5, you can use auto-discovery (add it to your composer.json), and if you're using Laravel 5.4 (or below),
you can add your service provide to the `config/app.php` file.

That's all! Now you can write all the business logic into those wonderful services ;D.


## Limitations

As useful as it might be, the package is pretty dumb: it just creates files based on some inputs and some predefined stubs.
For that same reason, the package is very limited for now:
- *The auto-generated service provider is never modified if new services are created within the same scope.* For example, if
you have a folder called "Users" but you have 2 services (UsersServiceInterface and ResetPasswordsServiceInterface) 
within that folder, you'll have to manually modify the UsersServiceProvider.php file and include the bindings for this
new service manually.
- *Auto-discovery of services.* Even though it's an easy task, I decided to skip this for now, so all service providers
need to be manually wired up on your composer.json ( >= Laravel 5.5) or your app.php (<= Laravel 5.4 ).


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
