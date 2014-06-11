Drupal 8 Console scaffolding module generator
=============================================
[![Build Status](https://travis-ci.org/hechoendrupal/DrupalAppConsole.svg?branch=master)](https://travis-ci.org/hechoendrupal/DrupalAppConsole)

Every modern framework nowadays provides a scaffolding tool code generator for speeding up the process of starting a new project and avoid early repetitive tasks.

The purpose of this project is to leverage the Symfony Console Component to provide a CLI tool to automate the creation of drupal 8 modules by generating the directory structure for a module, routing, controllers, forms, services, plugins and required configuration files.

It supports adding services using Dependency Injection on class generation.

### Steps for install:

There are two options to install the console. The first one is using Composer and install the project as a module.

Optionally you can install componser inside Drupal Installation.
```bash
$ cd path/to/drupal8.dev
$ curl -sS https://getcomposer.org/installer | php
```

Instructions to install Drupal Console if you are using a local version of Composer
```
$ COMPOSER_BIN_DIR=bin php composer.phar require --dev drupal/console:dev-master
$ ./bin/console --help
```

Instructions to install Drupal Console if you are using composer globally.
```
$ COMPOSER_BIN_DIR=bin composer require --dev drupal/console:dev-master
$ ./bin/console --help
```

###  Generate a command for Drupal Console (optional)

To create a package for Drupal Console as a `.phar` file, you have to  clone this repo as a separate project and run the following commands.

```
$ COMPOSER_BIN_DIR=bin composer require --dev drupal/console:dev-master
$ ./bin/console --help
```

In both versions of installer is required to use the the Composer variable *COMPOSER_BIN_DIR* to indicate where will be located the binary files of packages required by Drupal Console.

```bash
$ curl -s http://box-project.org/installer.php | php
$ box.phar build -v
```

Copy `console.phar` to the root of your Drupal project and instead of `./bin/console` use `php console.phar`.

### Usage

#### Module generator
```bash
$ ./bin/console generate:module
```
#### Controller generator
```bash
$ ./bin/console generate:controller
```
#### Form generator
```bash
$ ./bin/console generate:form
```
#### Command generator
```bash
$ ./bin/console generate:command
```
#### Service generator
```bash
$ ./bin/console generate:service
```
#### Plugin-Block generator
```bash
$ ./bin/console generate:plugin:block
```

#### Videos
[Introducing the Drupal 8 Console scaffolding module generator](https://www.youtube.com/watch?v=lzjcj-_xlAg)  
[How to install & use youtube video no audio](http://www.youtube.com/watch?v=NkHT2KctR-Y)

