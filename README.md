Drupal 8 Console scaffolding module generator [![Build Status](https://travis-ci.org/egulias/DrupalAppConsole.svg?branch=travis-integration)](https://travis-ci.org/egulias/DrupalAppConsole)
==============

Every modern framework nowadays provides a scaffolding tool code generator for speeding up the process of starting a new project and avoid the repetitive tasks.

The purpose of this project is to leverage the Symfony Console Component to provide a CLI tool to automate the creation of drupal 8 modules by generating the directory structure for a module, routing, controllers, forms, services, plugins and required configuration files.

It supports adding services using Dependency Injection on class generation.

### Steps for install:

There are two options to install the console. The first one is using Composer and install the project as a module.

```bash
$ cd path/to/drupal8.dev
$ curl -sS https://getcomposer.org/installer | php
$ COMPOSER_BIN_DIR=bin php composer.phar require --dev drupal/console:dev-master
$ ./bin/console --help
```

The second one is packing this module as a `.phar` file. Clone this repo as a separate project and run the following commands.

```bash
$ curl -s http://box-project.org/installer.php | php
$ box.phar build -v
```

Copy `console.phar` to the root of your Drupal project and instead of `./bin/console` use `php console.phar`.

### Usage

#### Generate module structure
```bash
$ ./bin/console generate:module
                                          
  Welcome to the Drupal module generator  
                                          
Module name: module_name
Description [My Awesome Module]: My awesome module 
Package [Other]: My Package
Do you want to generate a routing file [yes]? yes
Do you want to generate the whole directory structure [no]? yes
Do you confirm generation [yes]? 

$ tree modules/module_name/
modules/module_name/
├── config
├── lib
│   └── Drupal
│       └── module_name
│           ├── Controller
│           ├── Form
│           ├── Plugin
│           │   └── Block
│           └── Tests
├── templates
├── tests
├── module_name.info.yml
├── module_name.module
└── module_name.routing.yml

11 directories, 3 files
```

#### Generate controller structure
```bash
$ ./bin/console generate:controller

  Welcome to the Drupal controller generator  
                                              
Enter your module: : module_name
Enter the controller name [DefaultControler]: FrontController
Enter your service: : twig
Enter your service: : database
Enter your service: : config.factory
Enter your service: : config.context
Enter your service: : 
Update routing file? [yes]? 

$ cat modules/module_name/lib/Drupal/module_name/Controller/FrontController.php
```

#### Generate form structure
```bash

  Welcome to the Drupal form generator  
                                        
Enter your module : module_name
Enter the form name [DefaultForm]: 
Do you like asdd service? [yes]? 
 Enter your service: twig
 Enter your service: config.factory
 Enter your service: 
Do you like generate a form structure? [yes]? 
 Input label: User
  Input machine name [user]: 
  Type: text
 Input label: Password
  Input machine name [password]: 
  Type: password
 Input label: Send
  Input machine name [send]: 
  Type: submit
 Input label: 
 Do you like generate config file? [yes]? 
Update routing file? [yes]? 

```

#### Next Step
* Enable module
* Open Browser and load `http://drupal8.dev/module_name/hello/Drupal`

#### Video
[How to install & use youtube video no audio](http://www.youtube.com/watch?v=NkHT2KctR-Y)
