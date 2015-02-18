<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Drupal Console](#drupal-console)
  - [Supported version](#supported-version)
  - [Documentation](#documentation)
  - [Installation](#installation)
  - [Usage](#usage)
  - [Supporting organizations](#supporting-organizations)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

Drupal Console
=============================================

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/hechoendrupal/DrupalAppConsole)
[![Build Status](https://travis-ci.org/hechoendrupal/DrupalAppConsole.svg?branch=master)](https://travis-ci.org/hechoendrupal/DrupalAppConsole)
[![Latest Stable Version](https://poser.pugx.org/drupal/console/v/stable.svg)](https://packagist.org/packages/drupal/console)
[![Latest Unstable Version](https://poser.pugx.org/drupal/console/v/unstable.svg)](https://packagist.org/packages/drupal/console) 
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90/mini.png)](https://insight.sensiolabs.com/projects/d0f089ff-a6e9-4ba4-b353-cb68173c7d90)

The Drupal Console brings the Symfony Console Component to Drupal 8.

With the Drupal Console, you can quickly generate the Drupal 8 code and files needed for both new modules and upgrading Drupal 7 modules.

Additionally, you can use the Console to interact with your Drupal installation.

## Supported version

[Drupal 8.0.0-beta6](http://ftp.drupal.org/files/projects/drupal-8.0.0-beta6.tar.gz)

## Documentation

You can read or download the Drupal Console Documentation at [gitbooks.io](http://hechoendrupal.gitbooks.io/drupal-console/).

## Installation
```
// Run this in your terminal to get the latest Console version:
$ curl -LSs http://drupalconsole.com/installer | php

// Or if you don't have curl:
$ php -r "readfile('http://drupalconsole.com/installer');" | php

// To access the Console from anywhere your system, move console.phar and rename it to drupal:
$ mv console.phar /usr/local/bin/drupal

// Show all available Drupal Console commands.
$ drupal

// Generate a module.
$ drupal generate:module
```

## Usage

![image](http://drupalconsole.com/assets/img/console-global.gif)

## Supporting organizations
[![Blink Reaction](https://www.drupal.org/files/blink-reaction-logo.png)](http://www.blinkreaction.com/)
[![Indava](https://www.drupal.org/files/indava-logo.png)](http://www.indava.com/)
