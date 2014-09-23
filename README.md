Drupal 8 Console
=============================================
[![Build Status](https://travis-ci.org/hechoendrupal/DrupalAppConsole.svg?branch=master)](https://travis-ci.org/hechoendrupal/DrupalAppConsole)
[![Latest Stable Version](https://poser.pugx.org/drupal/console/v/stable.svg)](https://packagist.org/packages/drupal/console) [![Total Downloads](https://poser.pugx.org/drupal/console/downloads.svg)](https://packagist.org/packages/drupal/console) [![Latest Unstable Version](https://poser.pugx.org/drupal/console/v/unstable.svg)](https://packagist.org/packages/drupal/console) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90/mini.png)](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90)

Every modern framework nowadays provides a scaffolding tool code generator for speeding up the process of starting a new project and avoid early repetitive tasks.

The purpose of this project is to leverage the Symfony Console Component to provide a CLI tool to automate the creation of drupal 8 modules by generating the directory structure for a module, routing, controllers, forms, services, plugins and required configuration files.

It supports adding services using Dependency Injection on class generation.

#### What is out of the box?
* Generators:
 * Generates module and info files.
 * Generates PSR-4 compliant directory structure for a module.
 * Register routes on YML files and map to controller and form PHP Classes.
 * Create classes adding namespaces, uses and also the extend and implements keywords when required.
 * Support adding services using Dependency Injection on class generation.
 * Listing services

* Other commands:
 * List registered services on the service container
 * List registered routes on the routing system
 * Rebuilt routes

#### Who will benefit of using it?
* **Module Maintainers & Developers**  
  Create & Migrate contributed modules to Drupal 8.

* **Drupal Trainers & Consultors**  
  Train developers on Drupal 8.

* **Drupal Shops**  
  Reduce module development time for Drupal 8.

### Steps for install:

You need to download composer first:  

Run this in your terminal to get the latest Composer version:
```bash
curl -sS https://getcomposer.org/installer | php
```
Or if you don't have curl:
```bash
php -r "readfile('https://getcomposer.org/installer');" | php
```

Instructions to install Drupal Console if you are using composer inside Drupal Installation.
```
$ COMPOSER_BIN_DIR=bin php composer.phar require --dev drupal/console:~0.1
```

Instructions to install Drupal Console if you are using composer globally.
```
$ COMPOSER_BIN_DIR=bin composer require --dev drupal/console:~0.1
```

### Usage
```bash
$ ./bin/console list
$ ./bin/console --shell
```

### Commands
| Generators                    | Router                | Container       | Commands
| :-----------------------------|:----------------------|:----------------|:---------
| generator:module              | router:debug          | container:debug | drush
| generator:controller          | router:rebuild        |                 |
| generator:form:config         |                       |                 |
| generator:entity:config       |                       |                 |
| generator:entity:content      |                       |                 |
| generator:command             |                       |                 |
| generator:plugin:block        |                       |                 |
| generator:plugin:imageeffect  |                       |                 |
| generator:entity:config       |                       |                 |
| generator:service             |                       |                 |


#### Videos
* [Introducing the Drupal 8 Console scaffolding module generator with Jesus Manuel Olivas](http://bit.ly/acquia-console)
* [Generate a content entity type using the drupal 8 console](https://www.youtube.com/watch?v=agcqTEr5_48)
* [Generate a configuration entity type using the drupal 8 console](https://www.youtube.com/watch?v=x1zYfMLzFIM)
* [Introducing the Drupal 8 Console scaffolding module generator](https://www.youtube.com/watch?v=lzjcj-_xlAg)
* [How to install & use youtube video no audio](http://www.youtube.com/watch?v=NkHT2KctR-Y)
