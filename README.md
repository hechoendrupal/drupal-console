Drupal App Console | The symfony console in Drupal
==============

This project is an idea of [Jesus Manuel Olivas](https://twitter.com/jmolivas) & [David Flores](https://twitter.com/dmouse),  Drupal 8 has changed a lot the way we develop websites, the idea of this project is to provide similar functionality as the Symfony console, providing the tools to automate the creation of modules using the terminal to automatically generate the directory structure for controllers, forms, services and required files.

The DrupalAppConsole not is a competition of Drush it’s your new best friend.

### Steps for install:

```bash
$ cd path/to/drupal/8
$ curl -sS https://getcomposer.org/installer | php
$ composer.phar require dmouse/drupal-app-console
Please provide a version constraint for the dmouse/drupal-app-console requirement: dev-master
$ cp core/vendor/dmouse/drupal-app-console/bin/console . # This step is provisional
```

### Usage

#### Generate module structure
```bash
$ ./console generate:module
                                          
  Welcome to the Drupal module generator  
                                          
Module name: wawa
Description [My Awesome Module]: My awesome module 
Package [Other]: My Package
Do you want to generate a routing file [yes]? yes
Do you want to generate the whole directory structure [no]? yes
Do you confirm generation [yes]? 

$ tree modules/wawa/
modules/wawa/
├── config
├── lib
│   └── Drupal
│       └── wawa
│           ├── Controller
│           ├── Form
│           ├── Plugin
│           │   └── Block
│           └── Tests
├── templates
├── tests
├── wawa.info.yml
├── wawa.module
└── wawa.routing.yml

11 directories, 3 files
```
